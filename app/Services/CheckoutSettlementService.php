<?php

namespace App\Services;

use App\Jobs\SettleCheckoutJob;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Jembatan antara pembayaran satu charge gabungan dan settlement per item.
 *
 * Satu charge Xendit menutup banyak order sekaligus, tapi setiap order anak
 * tetap butuh satu panggilan top-up Digiflazz. Pemanggilan itu dikirim ke
 * queue supaya webhook Xendit tidak perlu menunggu beberapa HTTP call
 * berurutan (bisa melewati batas waktu webhook).
 */
class CheckoutSettlementService
{
    protected TopupSettlementService $settlement;

    public function __construct(TopupSettlementService $settlement)
    {
        $this->settlement = $settlement;
    }

    /**
     * Checkout sudah dibayar gateway. Antrikan pekerjaan settlement per item.
     *
     * Idempoten: checkout yang sudah `processing` atau `success` diabaikan,
     * jadi webhook Xendit yang terkirim berulang aman.
     */
    public function markPaid(Checkout $checkout): void
    {
        if (in_array($checkout->status, ['processing', 'success'], true)) {
            return;
        }

        $pending = $checkout->orders()->where('status', 'pending')->count();

        if ($pending < 1) {
            $checkout->syncStatus();

            return;
        }

        $checkout->update(['status' => 'processing']);

        $this->dispatch($checkout);
    }

    /**
     * Checkout gagal atau kedaluwarsa di sisi gateway.
     *
     * Order anak yang masih pending dibatalkan dan jatah flash deal / voucher
     * dikembalikan, supaya kuota tidak tertahan untuk pesanan yang dibayar
     * tidak pernah terjadi.
     */
    public function markFailed(Checkout $checkout, ?string $reason = null): void
    {
        // Order `processing` sengaja tidak disentuh: top-upnya sudah dikirim ke
        // Digiflazz, membatalkannya di sini justru berisiko membiarkan game
        // terisi tanpa ada transaksi tercatat.
        foreach ($checkout->orders()->where('status', 'pending')->get() as $order) {
            $order->update(['status' => 'failed', 'note' => $reason ?: 'Checkout gagal']);
            $order->releaseDiscounts();
            $order->transaction?->update(['status' => 'failed']);
        }

        if ($checkout->status !== 'success') {
            $checkout->update(['status' => 'failed']);
        }
    }

    /**
     * @return array<int,Order> order anak yang diteruskan ke queue
     */
    public function dispatch(Checkout $checkout): array
    {
        $orders = $checkout->orders()
            ->where('status', 'pending')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            $checkout->syncStatus();

            return [];
        }

        $ids = $orders->modelKeys();

        // afterCommit(): jangan mulai kerja sebelum seluruh baris order & charge
        // benar-benar tersimpan, kalau tidak worker bisa membaca data lama.
        SettleCheckoutJob::dispatch($checkout->id, $ids)->afterCommit();

        Log::info('Checkout settlement diantrikan', [
            'checkout_ref' => $checkout->checkout_ref,
            'orders' => count($ids),
        ]);

        return $orders->all();
    }

    /**
     * Dipakai command rekonsiliasi untuk memancing ulang checkout yang masih
     * punya order anak pending (mis. worker sempat mati).
     */
    public function retryStuck(Checkout $checkout): void
    {
        if ($checkout->status !== 'processing') {
            return;
        }

        $this->dispatch($checkout);
    }
}
<?php

namespace App\Jobs;

use App\Models\Checkout;
use App\Services\TopupSettlementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Kirim satu permintaan top-up Digiflazz untuk tiap order anak sebuah checkout.
 *
 * Dipisah dari webhook Xendit supaya webhook selesai cepat. Keamanannya tetap
 * sama: TopupSettlementService::markPaid() hanya bekerja pada order berstatus
 * `pending`, jadi job ini boleh dijalankan berkali-kali tanpa menghasilkan
 * top-up ganda.
 */
class SettleCheckoutJob implements ShouldQueue
{
    use Queueable;

    /** Berapa kali percobaan sebelum menyerah. */
    public int $tries = 3;

    /** Jeda antar percobaan dalam detik. */
    public array $backoff = [10, 30, 60];

    /** Job menggantung tidak perlu menunggu lama sebelum di-retry. */
    public int $timeout = 120;

    public function __construct(
        public int $checkoutId,
        public array $orderIds = []
    ) {
    }

    public function handle(TopupSettlementService $settlement): void
    {
        $checkout = Checkout::find($this->checkoutId);

        if (! $checkout) {
            return;
        }

        $query = $checkout->orders()->where('status', 'pending')->orderBy('id');

        if ($this->orderIds !== []) {
            $query->whereIn('id', $this->orderIds);
        }

        foreach ($query->get() as $order) {
            try {
                $settlement->markPaid($order);
            } catch (\Throwable $e) {
                // Satu item yang bermasalah tidak boleh menghentikan sisa item,
                // dan tidak boleh menggagalkan seluruh job.
                Log::error('SettleCheckoutJob gagal memproses order', [
                    'checkout_ref' => $checkout->checkout_ref,
                    'order_id' => $order->order_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $checkout->syncStatus();
    }
}
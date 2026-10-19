<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderProviderAttempt;
use Illuminate\Support\Facades\Log;

/**
 * Peng Settlement topup: satu-satunya tempat yang memutuskan Nasib order
 * sekaligus sisi saldonya.
 *
 * Dipakai oleh webhook Xendit, webhook Digiflazz, polling dari browser, dan
 * command rekonsiliasi — semuanya lewat method yang sama supaya tidak ada
 * jalur yang bisa lupa refunded saldonya.
 */
class TopupSettlementService
{
    public function __construct(
        protected DigiflazzService $digiflazz,
        protected BalanceService $balance,
    ) {}

    /**
     * Langkah 1: Xendit mengonfirmasi pembayaran.
     * Order masuk "processing", saldo user naik dan terkunci, lalu topUp dikirim.
     */
    public function markPaid(Order $order): void
    {
        if ($order->status !== 'pending') {
            return;
        }

        $order->update([
            'status' => 'processing',
            'digiflazz_started_at' => $order->digiflazz_started_at ?: now(),
        ]);
        $order->transaction?->update(['status' => 'processing']);

        $this->balance->markPaid($order);

        $result = $this->digiflazz->topUp(
            $order->buyer_sku_code,
            $order->customer_number,
            $order->order_id,
            $order->effective_zone_id
        );

        Log::info('Digiflazz topUp response', ['order_id' => $order->order_id, 'response' => $result]);
        $this->recordAttempt($order, 'initial_submit', $result);

        $this->applyResult($order->fresh(), $result);
    }

    /**
     * Terapkan hasil respons Digiflazz (sukses / pending / gagal).
     */
    public function applyResult(Order $order, array $result): void
    {
        if ($order->status === 'success') {
            return;
        }

        $data = $result['data'] ?? [];
        $status = strtolower(trim((string) ($data['status'] ?? '')));

        if ($status === 'sukses') {
            $this->settleSuccess($order, $data, $result);
        } elseif ($order->saldo_status !== 'held') {
            return;
        } elseif ($status === 'pending') {
            $order->update([
                'status' => 'processing',
                'note' => null,
                'last_status_check_at' => now(),
                'provider_error_at' => null,
                'provider_error_reason' => null,
                'reconcile_status' => 'none',
            ]);
            $order->transaction?->update(['status' => 'processing', 'raw_response' => $result]);
        } else {
            $this->holdProviderFailure(
                $order,
                $data['message'] ?? ($result['message'] ?? ($data['status'] ?: 'Gagal diproses Digiflazz')),
                $result,
            );
        }
    }

    protected function settleSuccess(Order $order, array $data, ?array $result = null): void
    {
        $order->update([
            'status' => 'success',
            'note' => $data['sn'] ?? null,
            'reconcile_status' => 'none',
            'provider_error_at' => null,
            'provider_error_reason' => null,
        ]);
        $order->transaction?->update(['status' => 'success', 'raw_response' => $result]);

        // Kalau saldonya sempat dikembalikan (sukses terlambat), tarik ulang.
        if ($order->saldo_status === 'refunded') {
            $this->balance->markLateSuccess($order);
            $this->balance->debitRestoredPoints($order);
        } else {
            $this->balance->markDelivered($order);
        }
        $this->balance->consumePoints($order);

        $product = Product::where('buyer_sku_code', $order->buyer_sku_code)->first();
        $product?->consumeStock((int) ($order->quantity ?? 1));
    }

    /** Simpan error provider tanpa langsung mengembalikan dana. */
    public function holdProviderFailure(Order $order, string $reason, ?array $result = null): void
    {
        $reason = trim($reason) ?: 'Provider gagal memproses topup.';

        $order->forceFill([
            'status' => 'processing',
            'note' => $reason,
            'provider_error_at' => $order->provider_error_at ?: now(),
            'provider_error_reason' => $reason,
            'reconcile_status' => 'provider_error',
        ])->save();
        $order->transaction?->update(['status' => 'provider_error', 'raw_response' => $result]);
    }

    /** Gagal final: kembalikan dana ke Johen Point dan lepaskan diskon. */
    public function settleFailure(Order $order, string $reason, ?array $result = null): void
    {
        if ($order->saldo_status !== 'held') {
            return;
        }

        $order->update([
            'status' => 'failed',
            'note' => $reason,
            'reconcile_status' => 'settled',
        ]);
        $transactionUpdate = ['status' => 'failed'];
        if ($result !== null) {
            $transactionUpdate['raw_response'] = $result;
        }
        $order->transaction?->update($transactionUpdate);

        $this->balance->markRefunded($order, $reason);
        $this->balance->restorePoints($order, 'Refund Johen Point: '.$reason);
        $order->releaseDiscounts();
    }

    /**
     * Tanya status satu order ke Digiflazz. Mengembalikan status final
     * ('sukses'/'provider_error'), atau null kalau masih pending / gagal ditanya.
     */
    public function poll(Order $order, ?int $adminId = null): ?string
    {
        try {
            $result = $this->digiflazz->checkStatus(
                $order->buyer_sku_code,
                $order->customer_number,
                $order->order_id,
                $order->effective_zone_id
            );
        } catch (\Exception $e) {
            Log::error('Digiflazz status poll failed: '.$e->getMessage());

            return null;
        }

        $order->update([
            'last_status_check_at' => now(),
            'digiflazz_poll_count' => (int) $order->digiflazz_poll_count + 1,
        ]);

        $status = strtolower(trim((string) ($result['data']['status'] ?? '')));
        $this->recordAttempt($order, 'status_check', $result, $adminId);

        // Respons terbaru selalu disimpan agar halaman detail benar-benar
        // menampilkan hasil refresh terakhir, termasuk saat masih pending.
        $order->transaction?->update(['raw_response' => $result]);

        if (in_array($status, ['sukses', 'gagal', 'pending'], true)) {
            $this->applyResult($order->fresh(), $result);

            if ($status === 'gagal') {
                return 'provider_error';
            }

            return $status === 'sukses' ? 'sukses' : null;
        }

        return null;
    }

    /**
     * Apakah order sudah melewati batas waktu=topup.
     * Order tanpa timestamp Digiflazz tidak dinilai di sini; command
     * rekonsiliasi tetap akan mem-poll order tersebut.
     */
    public function hasExceededGracePeriod(Order $order): bool
    {
        $startedAt = $order->digiflazz_started_at;
        $graceMinutes = (int) config('digiflazz.reconcile.grace_minutes', 30);

        if (! $graceMinutes || ! $startedAt) {
            return false;
        }

        return now()->greaterThanOrEqualTo($startedAt->copy()->addMinutes($graceMinutes));
    }

    public function hasExceededPollLimit(Order $order): bool
    {
        $maxPolls = (int) config('digiflazz.reconcile.max_polls', 12);

        return $maxPolls > 0 && (int) $order->digiflazz_poll_count >= $maxPolls;
    }

    public function isWaitingForProviderErrorRetry(Order $order): bool
    {
        return $order->reconcile_status === 'provider_error'
            && $order->saldo_status === 'held'
            && $order->provider_error_at !== null;
    }

    public function hasExceededProviderErrorGracePeriod(Order $order): bool
    {
        if (! $this->isWaitingForProviderErrorRetry($order)) {
            return false;
        }

        $minutes = max(1, (int) config('digiflazz.reconcile.provider_error_grace_minutes', 10));

        return now()->greaterThanOrEqualTo($order->provider_error_at->copy()->addMinutes($minutes));
    }

    public function providerErrorRefundReason(Order $order): string
    {
        $minutes = max(1, (int) config('digiflazz.reconcile.provider_error_grace_minutes', 10));
        $providerReason = trim((string) ($order->provider_error_reason ?: $order->note));

        return 'Refund otomatis setelah error provider tidak terselesaikan selama '.$minutes.' menit'
            .($providerReason !== '' ? ': '.$providerReason : '.');
    }

    public function balance(): BalanceService
    {
        return $this->balance;
    }

    public function retry(Order $order, ?int $adminId = null): array
    {
        $order->forceFill([
            'provider_error_at' => null,
            'provider_error_reason' => null,
            'reconcile_status' => 'none',
            'note' => null,
        ])->save();

        $result = $this->digiflazz->topUp($order->buyer_sku_code, $order->customer_number, $order->order_id, $order->effective_zone_id);
        $this->recordAttempt($order, 'admin_retry', $result, $adminId);
        $this->applyResult($order->fresh(), $result);
        return $result;
    }

    private function recordAttempt(Order $order, string $action, array $result, ?int $adminId = null): void
    {
        $data = (array) ($result['data'] ?? []);
        OrderProviderAttempt::create([
            'order_id' => $order->id,
            'admin_id' => $adminId,
            'action' => $action,
            'provider_ref' => $data['ref_id'] ?? $data['trx_id'] ?? null,
            'status' => $data['status'] ?? null,
            'message' => $data['message'] ?? null,
            'response' => $result,
        ]);
    }
}

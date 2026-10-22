<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\Order;
use Carbon\CarbonImmutable;

class PaymentExpirationService
{
    public function __construct(protected BalanceService $balance) {}

    public function expiresAt(Order|Checkout $payment): CarbonImmutable
    {
        $expiry = data_get($payment->gateway_extra, 'expired_date')
            ?: data_get($payment->gateway_extra, 'expires_at');

        if ($expiry) {
            try {
                return CarbonImmutable::parse($expiry);
            } catch (\Throwable) {
                // Gunakan batas waktu lokal jika gateway mengirim format rusak.
            }
        }

        return CarbonImmutable::instance($payment->created_at)->addHour();
    }

    public function expireIfDue(Order|Checkout $payment): bool
    {
        if ($payment->status !== 'pending' || $this->expiresAt($payment)->isFuture()) {
            return false;
        }

        return $this->expire($payment);
    }

    public function expire(Order|Checkout $payment, string $reason = 'Batas waktu pembayaran telah habis.'): bool
    {
        $extra = array_merge((array) $payment->gateway_extra, [
            'payment_status' => 'EXPIRED',
            'expired_at' => now()->toIso8601String(),
        ]);

        $updated = $payment->newQuery()
            ->whereKey($payment->getKey())
            ->where('status', 'pending')
            ->update([
                'status' => 'expired',
                'gateway_extra' => json_encode($extra),
                'updated_at' => now(),
            ]);

        if (! $updated) {
            return false;
        }

        $payment->refresh();

        if ($payment instanceof Checkout) {
            foreach ($payment->orders()->where('status', 'pending')->get() as $order) {
                $order->update(['status' => 'expired', 'note' => $reason]);
                $order->transaction?->update(['status' => 'expired']);
                $order->releaseDiscounts();
            }

            $this->balance->restorePoints($payment, 'Pembayaran kedaluwarsa; Johen Point dikembalikan');
            $payment->orders()->where('points_status', 'reserved')->update(['points_status' => 'restored']);

            return true;
        }

        $payment->update(['note' => $reason]);
        $payment->transaction?->update(['status' => 'expired']);
        $payment->releaseDiscounts();
        $this->balance->restorePoints($payment, 'Pembayaran kedaluwarsa; Johen Point dikembalikan');

        return true;
    }
}

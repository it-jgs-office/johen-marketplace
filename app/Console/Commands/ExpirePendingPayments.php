<?php

namespace App\Console\Commands;

use App\Models\Checkout;
use App\Models\Order;
use App\Services\PaymentExpirationService;
use Illuminate\Console\Command;

class ExpirePendingPayments extends Command
{
    protected $signature = 'payments:expire';

    protected $description = 'Ubah pembayaran topup yang melewati batas waktu menjadi expired';

    public function handle(PaymentExpirationService $expiration): int
    {
        Checkout::query()
            ->where('status', 'pending')
            ->chunkById(100, function ($payments) use ($expiration) {
                foreach ($payments as $payment) {
                    $expiration->expireIfDue($payment);
                }
            });

        Order::query()
            ->whereNull('checkout_id')
            ->where('status', 'pending')
            ->chunkById(100, function ($payments) use ($expiration) {
                foreach ($payments as $payment) {
                    $expiration->expireIfDue($payment);
                }
            });

        return self::SUCCESS;
    }
}

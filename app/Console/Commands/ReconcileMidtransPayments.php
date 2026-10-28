<?php

namespace App\Console\Commands;

use App\Models\AccountOrder;
use App\Models\Checkout;
use App\Models\Order;
use App\Services\MidtransPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileMidtransPayments extends Command
{
    protected $signature = 'midtrans:reconcile';

    protected $description = 'Sinkronkan pembayaran Midtrans yang belum final';

    public function handle(MidtransPaymentService $payments): int
    {
        foreach ([Checkout::class, AccountOrder::class, Order::class] as $model) {
            $model::where('status', 'pending')
                ->where('gateway_extra->provider', 'midtrans')
                ->chunkById(50, function ($records) use ($payments) {
                    foreach ($records as $record) {
                        try {
                            $payments->sync($record);
                        } catch (\Throwable $e) {
                            Log::warning('Midtrans reconciliation failed', [
                                'model' => $record::class,
                                'id' => $record->getKey(),
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });
        }

        return self::SUCCESS;
    }
}

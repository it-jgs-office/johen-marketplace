<?php

namespace App\Console\Commands;

use App\Models\AccountOrder;
use App\Models\Checkout;
use App\Models\Order;
use App\Services\DokuPaymentSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileDokuPayments extends Command
{
    protected $signature = 'doku:reconcile';

    protected $description = 'Sinkronkan pembayaran langsung DOKU yang belum final';

    public function handle(DokuPaymentSyncService $sync): int
    {
        foreach ([Checkout::class, AccountOrder::class, Order::class] as $model) {
            $model::where('status', 'pending')
                ->whereNotNull('gateway_invoice_id')
                ->where('gateway_extra->provider', 'doku')
                ->where('gateway_extra->integration', 'direct')
                ->chunkById(50, function ($orders) use ($sync) {
                    foreach ($orders as $order) {
                        try {
                            $sync->sync($order);
                        } catch (\Throwable $e) {
                            Log::warning('DOKU reconciliation failed', [
                                'model' => $order::class, 'id' => $order->getKey(), 'exception' => $e::class,
                            ]);
                        }
                    }
                });
        }

        return self::SUCCESS;
    }
}

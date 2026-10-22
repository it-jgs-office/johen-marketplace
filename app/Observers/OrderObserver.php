<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderStatusMailService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        if ($order->status === 'success') {
            app(OrderStatusMailService::class)->send($order, 'success');
        } elseif (in_array($order->status, ['failed', 'expired', 'cancelled'], true)) {
            app(OrderStatusMailService::class)->send($order, 'failed');
        }
    }
}

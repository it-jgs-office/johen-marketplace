<?php

namespace App\Services;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderStatusMailService
{
    public function send(Order $order, string $status): void
    {
        $status = $status === 'success' ? 'success' : 'failed';
        $recipient = trim((string) ($order->email ?: $order->user?->email));

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $column = $status === 'success' ? 'success_email_sent_at' : 'failed_email_sent_at';
        $claimed = false;

        try {
            $claimed = Order::query()
                ->whereKey($order->getKey())
                ->whereNull($column)
                ->update([$column => now()]);

            if ($claimed !== 1) {
                return;
            }

            Mail::to($recipient)->send(new OrderStatusMail(
                $order->fresh(['user', 'transaction']),
                $status,
            ));
        } catch (\Throwable $exception) {
            if ($claimed === 1) {
                try {
                    Order::query()->whereKey($order->getKey())->update([$column => null]);
                } catch (\Throwable) {
                    // Migrasi belum tersedia atau koneksi database sedang gagal.
                }
            }

            Log::error('Email status pesanan gagal dikirim', [
                'order_id' => $order->order_id,
                'status' => $status,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

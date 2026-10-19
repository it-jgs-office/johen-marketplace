<?php

namespace App\Services;

use App\Models\AccountOrder;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DokuPaymentSyncService
{
    public function sync(Model $order): void
    {
        if ($order->status !== 'pending' || ! $order->gateway_invoice_id) {
            return;
        }
        $data = app(DokuDirectService::class)->status($order);
        if ($data) {
            $this->apply($order, $data);
            $order->refresh();
        }
    }

    public function find(string $reference): ?Model
    {
        if ($reference === '') {
            return null;
        }

        return Checkout::where('checkout_ref', $reference)->first()
            ?? AccountOrder::where('order_ref', $reference)->first()
            ?? Order::where('order_id', $reference)->first();
    }

    public function apply(Model $order, array $data): bool
    {
        return DB::transaction(function () use ($order, $data) {
            $locked = $order->newQuery()->lockForUpdate()->findOrFail($order->getKey());
            $extra = (array) $locked->gateway_extra;
            if (($extra['provider'] ?? null) !== 'doku' || ($extra['integration'] ?? null) !== 'direct'
                || ($extra['environment'] ?? '') !== (app(DokuService::class)->isSandbox() ? 'sandbox' : 'production')
                || ($extra['invoice_number'] ?? '') !== data_get($data, 'order.invoice_number')) {
                return false;
            }

            $status = strtoupper((string) data_get($data, 'transaction.status', ''));
            $amount = data_get($data, 'order.amount');
            $expected = $locked->cash_amount;

            // A successful HTTP request is not proof of payment. Check the payment
            // status, reference and exact amount before triggering fulfillment.
            if ($status === 'SUCCESS' && (! is_numeric($amount) || (float) $expected <= 0
                || (float) $amount !== (float) $expected)) {
                return false;
            }
            if ($locked->status !== 'pending') {
                return true;
            }
            if (! in_array($status, ['SUCCESS', 'EXPIRED', 'FAILED', 'CANCELLED'], true)) {
                return true;
            }

            $locked->update(['gateway_extra' => array_merge($extra, ['payment_status' => $status])]);
            if ($status === 'SUCCESS') {
                if ($locked instanceof Checkout) {
                    app(CheckoutSettlementService::class)->markPaid($locked);
                } elseif ($locked instanceof AccountOrder) {
                    $balance = app(BalanceService::class);
                    $balance->markAccountPaid($locked);
                    $locked->update(['status' => 'success']);
                    $locked->listing?->update(['is_sold' => true]);
                    $balance->markAccountDelivered($locked->fresh());
                } else {
                    app(TopupSettlementService::class)->markPaid($locked);
                }
            } elseif ($locked instanceof Checkout) {
                app(CheckoutSettlementService::class)->markFailed($locked, 'Pembayaran DOKU '.$status);
            } else {
                $locked->update(['status' => 'failed']);
                if ($locked instanceof Order) {
                    $locked->transaction?->update(['status' => 'failed']);
                    $locked->releaseDiscounts();
                }
                app(BalanceService::class)->restorePoints($locked, 'Pembayaran DOKU '.$status.'; Johen Point dikembalikan');
            }

            return true;
        });
    }
}

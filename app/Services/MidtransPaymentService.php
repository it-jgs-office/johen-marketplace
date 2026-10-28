<?php

namespace App\Services;

use App\Models\AccountOrder;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MidtransPaymentService
{
    public function __construct(
        protected MidtransService $midtrans,
        protected TopupSettlementService $topupSettlement,
        protected CheckoutSettlementService $checkoutSettlement,
        protected BalanceService $balance,
    ) {}

    public function findByReference(string $reference): Order|Checkout|AccountOrder|null
    {
        return Checkout::where('checkout_ref', $reference)->first()
            ?? AccountOrder::where('order_ref', $reference)->first()
            ?? Order::where('order_id', $reference)->first();
    }

    public function sync(Order|Checkout|AccountOrder $payment): void
    {
        if ($payment->status !== 'pending'
            || data_get($payment->gateway_extra, 'provider') !== 'midtrans'
            || ! $this->midtrans->isConfigured()) {
            return;
        }

        $payload = $this->midtrans->getStatus($this->reference($payment));

        if ($payload) {
            $this->apply($payment, $payload);
        }
    }

    public function apply(Order|Checkout|AccountOrder $payment, array $payload): bool
    {
        $received = (int) round((float) ($payload['gross_amount'] ?? 0));
        $expected = (int) round((float) $payment->cash_amount);

        if ($expected < 1 || $received !== $expected) {
            Log::warning('Midtrans amount mismatch', [
                'order_id' => $this->reference($payment),
                'expected' => $expected,
                'received' => $received,
            ]);

            return false;
        }

        $this->storeGatewayStatus($payment, $payload);

        if ($payment instanceof Order) {
            $payment->transaction?->update([
                'transaction_id' => $payload['transaction_id'] ?? null,
                'payment_type' => 'Midtrans - '.($payload['payment_type'] ?? $payment->payment_method),
                'status' => strtolower((string) ($payload['transaction_status'] ?? 'pending')),
                'raw_response' => $payload,
            ]);
        }

        if ($this->midtrans->isPaid($payload)) {
            if ($this->isSandboxPayment($payment)) {
                Log::info('Pembayaran Midtrans Sandbox diterima tanpa fulfillment', [
                    'reference' => $this->reference($payment),
                    'payment_type' => $payment::class,
                ]);

                return true;
            }

            $this->markPaid($payment);
        } elseif ($this->midtrans->isExpired($payload)) {
            if ($payment instanceof AccountOrder) {
                $this->markFailed($payment, 'EXPIRED');
            } else {
                app(PaymentExpirationService::class)->expire($payment, 'Pembayaran Midtrans kedaluwarsa.');
            }
        } elseif ($this->midtrans->isFailed($payload)) {
            $this->markFailed($payment, strtoupper((string) ($payload['transaction_status'] ?? 'FAILED')));
        }

        return true;
    }

    private function markPaid(Order|Checkout|AccountOrder $payment): void
    {
        if ($payment instanceof Checkout) {
            $this->checkoutSettlement->markPaid($payment);
        } elseif ($payment instanceof AccountOrder) {
            app(AccountDeliveryService::class)->deliver($payment);
        } else {
            $this->topupSettlement->markPaid($payment);
        }
    }

    private function markFailed(Order|Checkout|AccountOrder $payment, string $status): void
    {
        if ($payment instanceof Checkout) {
            $this->checkoutSettlement->markFailed($payment, 'Pembayaran '.$status.' di Midtrans.');

            return;
        }

        if ($payment->status !== 'pending') {
            return;
        }

        $payment->update(['status' => 'failed']);

        if ($payment instanceof Order) {
            $payment->transaction?->update(['status' => 'failed']);
            $payment->releaseDiscounts();
        }

        $this->balance->restorePoints($payment, 'Pembayaran gagal; Johen Point dikembalikan');
    }

    private function storeGatewayStatus(Model $payment, array $payload): void
    {
        $extra = (array) $payment->gateway_extra;
        $extra['provider'] = 'midtrans';
        $extra['integration'] = $extra['integration'] ?? 'core_api';
        $extra['payment_status'] = strtoupper((string) ($payload['transaction_status'] ?? 'PENDING'));
        $extra['payment_type'] = $payload['payment_type'] ?? null;
        $extra['transaction_id'] = $payload['transaction_id'] ?? null;

        $payment->update(['gateway_extra' => $extra]);
    }

    private function reference(Order|Checkout|AccountOrder $payment): string
    {
        return $payment instanceof Checkout
            ? (string) $payment->checkout_ref
            : ($payment instanceof AccountOrder ? (string) $payment->order_ref : (string) $payment->order_id);
    }

    private function isSandboxPayment(Order|Checkout|AccountOrder $payment): bool
    {
        return ! $this->midtrans->isProduction()
            || data_get($payment->gateway_extra, 'environment') === 'sandbox';
    }
}

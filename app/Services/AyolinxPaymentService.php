<?php

namespace App\Services;

use App\Models\AccountOrder;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AyolinxPaymentService
{
    public function __construct(
        protected AyolinxService $ayolinx,
        protected TopupSettlementService $topupSettlement,
        protected CheckoutSettlementService $checkoutSettlement,
        protected BalanceService $balance,
    ) {}

    public function findByReference(string $reference): Order|Checkout|AccountOrder|null
    {
        return Checkout::where('gateway_extra->partner_reference_no', $reference)->first()
            ?? AccountOrder::where('gateway_extra->partner_reference_no', $reference)->first()
            ?? Order::where('gateway_extra->partner_reference_no', $reference)->first()
            ?? Checkout::where('checkout_ref', $reference)->first()
            ?? AccountOrder::where('order_ref', $reference)->first()
            ?? Order::where('order_id', $reference)->first();
    }

    public function findByVirtualAccount(string $virtualAccountNo): Order|Checkout|AccountOrder|null
    {
        return Checkout::where('va_number', $virtualAccountNo)->first()
            ?? AccountOrder::where('va_number', $virtualAccountNo)->first()
            ?? Order::where('va_number', $virtualAccountNo)->first()
            ?? Checkout::where('gateway_extra->virtual_account_no', $virtualAccountNo)->first()
            ?? AccountOrder::where('gateway_extra->virtual_account_no', $virtualAccountNo)->first()
            ?? Order::where('gateway_extra->virtual_account_no', $virtualAccountNo)->first();
    }

    public function matchesInboundBill(
        Order|Checkout|AccountOrder $payment,
        string $trxId,
        string $partnerServiceId,
        string $customerNo,
    ): bool {
        if (data_get($payment->gateway_extra, 'provider') !== 'ayolinx') {
            return false;
        }

        $references = array_filter([
            data_get($payment->gateway_extra, 'partner_reference_no'),
            data_get($payment->gateway_extra, 'trx_id'),
            $payment instanceof Checkout
                ? $payment->checkout_ref
                : ($payment instanceof AccountOrder ? $payment->order_ref : $payment->order_id),
        ], fn ($value) => filled($value));

        if (! in_array($trxId, array_map('strval', $references), true)) {
            return false;
        }

        $expectedPartnerServiceId = trim((string) data_get($payment->gateway_extra, 'partner_service_id'));
        $expectedCustomerNo = trim((string) data_get($payment->gateway_extra, 'customer_no'));

        return ($expectedPartnerServiceId === '' || hash_equals($expectedPartnerServiceId, $partnerServiceId))
            && ($expectedCustomerNo === '' || hash_equals($expectedCustomerNo, $customerNo));
    }

    public function sync(Order|Checkout|AccountOrder $payment): void
    {
        // Payment Link tidak menyediakan endpoint inquiry tersendiri.
        // Status diperbarui melalui callback Ayolinx.
    }

    public function apply(Order|Checkout|AccountOrder $payment, array $payload): bool
    {
        if (data_get($payment->gateway_extra, 'provider') !== 'ayolinx') {
            return false;
        }

        $received = (int) round((float) data_get($payload, 'amount.value', 0));
        $expected = (int) round((float) $payment->cash_amount);

        if ($expected < 1 || $received !== $expected) {
            Log::warning('Ayolinx amount mismatch', [
                'reference' => $this->reference($payment),
                'expected' => $expected,
                'received' => $received,
            ]);

            return false;
        }

        $status = (string) ($payload['latestTransactionStatus'] ?? '');

        if ($payment->status !== 'pending') {
            if ($payment instanceof AccountOrder && $payment->status === 'sold_out' && $status === '00') {
                $this->storeGatewayStatus($payment, $payload, $status);
                $extra = (array) $payment->fresh()->gateway_extra;
                $extra['requires_manual_refund'] = true;
                $extra['paid_after_sold_out_at'] = now()->toIso8601String();
                $payment->update(['gateway_extra' => $extra]);

                Log::critical('Pembayaran diterima setelah stok akun dimenangkan pesanan lain.', [
                    'reference' => $this->reference($payment),
                ]);
            }

            return true;
        }

        $this->storeGatewayStatus($payment, $payload, $status);

        if ($payment instanceof Order) {
            $payment->transaction?->update([
                'transaction_id' => $payload['originalReferenceNo'] ?? null,
                'payment_type' => 'Ayolinx - '.data_get($payload, 'additionalInfo.channel', $payment->payment_method),
                'status' => $status === '00' ? 'paid' : 'pending',
                'raw_response' => $payload,
            ]);
        }

        if ($status === '00') {
            if ($this->isSandboxPayment($payment)) {
                Log::info('Pembayaran Ayolinx Sandbox diterima tanpa fulfillment', [
                    'reference' => $this->reference($payment),
                    'payment_type' => $payment::class,
                ]);

                return true;
            }

            $this->markPaid($payment);
        } elseif (in_array($status, ['04', '05', '06', '07'], true)) {
            $this->markFailed($payment, $status);
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
            $this->checkoutSettlement->markFailed($payment, 'Pembayaran Ayolinx gagal ('.$status.').');

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

    private function storeGatewayStatus(Model $payment, array $payload, string $status): void
    {
        $labels = [
            '00' => 'SUCCESS',
            '01' => 'INITIATED',
            '02' => 'PAYING',
            '03' => 'PENDING',
            '04' => 'REFUNDED',
            '05' => 'CANCELED',
            '06' => 'FAILED',
            '07' => 'NOT_FOUND',
        ];

        $extra = (array) $payment->gateway_extra;
        $extra['provider'] = 'ayolinx';
        $extra['integration'] = 'payment_link';
        $extra['payment_status'] = $labels[$status] ?? 'UNKNOWN';
        $extra['transaction_id'] = $payload['originalReferenceNo'] ?? null;
        $extra['payment_request_id'] = $payload['paymentRequestId'] ?? ($extra['payment_request_id'] ?? null);
        $extra['channel'] = data_get($payload, 'additionalInfo.channel', $extra['channel'] ?? null);
        $extra['payment_link_id'] = data_get($payload, 'additionalInfo.plh', $extra['payment_link_id'] ?? null);

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
        return ! $this->ayolinx->isProduction()
            || data_get($payment->gateway_extra, 'environment') === 'sandbox';
    }
}

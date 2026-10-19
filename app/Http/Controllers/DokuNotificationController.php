<?php

namespace App\Http\Controllers;

use App\Models\AccountOrder;
use App\Models\Checkout;
use App\Models\Order;
use App\Services\CheckoutSettlementService;
use App\Services\DokuService;
use App\Services\TopupSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DokuNotificationController extends Controller
{
    public function __construct(
        protected DokuService $doku,
        protected TopupSettlementService $topupSettlement,
        protected CheckoutSettlementService $checkoutSettlement,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if ($request->hasHeader('X-SIGNATURE')) {
            return $this->snapVirtualAccountNotification($request);
        }

        $rawBody = $request->getContent();
        $requestTarget = '/'.ltrim($request->path(), '/');

        if (! $this->doku->verifyNotification(
            $rawBody,
            $requestTarget,
            $request->header('Client-Id'),
            $request->header('Request-Id'),
            $request->header('Request-Timestamp'),
            $request->header('Signature'),
        )) {
            Log::warning('DOKU notification ditolak: signature tidak valid', [
                'request_id' => $request->header('Request-Id'),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid JSON'], 400);
        }

        $invoiceNumber = trim((string) data_get($payload, 'order.invoice_number', ''));
        $status = strtoupper(trim((string) data_get($payload, 'transaction.status', '')));
        $amount = (int) round((float) data_get($payload, 'order.amount', 0));

        if ($invoiceNumber === '' || $status === '') {
            return response()->json(['status' => 'error', 'message' => 'Invalid notification payload'], 422);
        }

        try {
            $sync = app(\App\Services\DokuPaymentSyncService::class);
            $directOrder = $sync->find($invoiceNumber);
            if ($directOrder && data_get($directOrder->gateway_extra, 'integration') === 'direct') {
                $accepted = $sync->apply($directOrder, $payload);

                return response()->json(['status' => $accepted ? 'ok' : 'error'], $accepted ? 200 : 422);
            }

            if ($checkout = Checkout::where('checkout_ref', $invoiceNumber)->first()) {
                if (! $this->amountMatches((int) $checkout->cash_amount, $amount, $invoiceNumber)) {
                    return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 422);
                }

                if ($status === 'SUCCESS') {
                    $this->checkoutSettlement->markPaid($checkout);
                } elseif ($status === 'EXPIRED') {
                    $this->checkoutSettlement->markFailed($checkout, 'Pembayaran kedaluwarsa di DOKU');
                }

                return response()->json(['status' => 'ok']);
            }

            if ($accountOrder = AccountOrder::where('order_ref', $invoiceNumber)->first()) {
                if (! $this->amountMatches((int) round((float) $accountOrder->cash_amount), $amount, $invoiceNumber)) {
                    return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 422);
                }

                if ($status === 'SUCCESS' && $accountOrder->status === 'pending') {
                    \App\Http\Controllers\HomeController::settleAccountOrder($accountOrder);
                } elseif ($status === 'EXPIRED' && $accountOrder->status === 'pending') {
                    $accountOrder->update(['status' => 'failed']);
                    app(\App\Services\BalanceService::class)->restorePoints($accountOrder, 'Pembayaran kedaluwarsa; Johen Point dikembalikan');
                }

                return response()->json(['status' => 'ok']);
            }

            if ($order = Order::where('order_id', $invoiceNumber)->first()) {
                if (! $this->amountMatches((int) round((float) $order->cash_amount), $amount, $invoiceNumber)) {
                    return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 422);
                }

                $order->transaction?->update([
                    'transaction_id' => data_get($payload, 'transaction.original_request_id'),
                    'payment_type' => 'DOKU - '.(data_get($payload, 'channel.name') ?: data_get($payload, 'channel.id', 'Checkout')),
                    'status' => $status === 'SUCCESS' ? 'paid' : 'pending',
                    'raw_response' => $payload,
                ]);

                if ($status === 'SUCCESS') {
                    $this->topupSettlement->markPaid($order);
                } elseif ($status === 'EXPIRED' && $order->status === 'pending') {
                    $order->update(['status' => 'failed']);
                    $order->transaction?->update(['status' => 'failed']);
                    $order->releaseDiscounts();
                    app(\App\Services\BalanceService::class)->restorePoints($order, 'Pembayaran kedaluwarsa; Johen Point dikembalikan');
                }

                return response()->json(['status' => 'ok']);
            }

            Log::warning('DOKU notification: invoice tidak ditemukan', [
                'invoice_number' => $invoiceNumber,
                'request_id' => $request->header('Request-Id'),
            ]);

            // Tetap 2xx agar DOKU tidak mengirim ulang notifikasi yang memang
            // tidak lagi dikenali oleh aplikasi.
            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('DOKU notification gagal diproses', [
                'invoice_number' => $invoiceNumber,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    private function amountMatches(int $expected, int $received, string $invoiceNumber): bool
    {
        $matches = $expected > 0 && $expected === $received;

        if (! $matches) {
            Log::warning('DOKU notification: nominal tidak cocok', [
                'invoice_number' => $invoiceNumber,
                'expected' => $expected,
                'received' => $received,
            ]);
        }

        return $matches;
    }

    private function snapVirtualAccountNotification(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $timestamp = (string) $request->header('X-TIMESTAMP', '');
        $timestampValue = strtotime($timestamp);
        if (! $this->doku->isConfigured() || ! is_array($payload) || ! $timestampValue
            || abs(time() - $timestampValue) > 300 || ! $request->header('X-EXTERNAL-ID')) {
            return response()->json(['responseCode' => '4012500', 'responseMessage' => 'Invalid notification'], 401);
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expected = app(\App\Services\DokuDirectService::class)->snapSignature(
            '/'.ltrim($request->path(), '/'), $body, (string) $request->bearerToken(), $timestamp
        );
        if (! hash_equals($expected, (string) $request->header('X-SIGNATURE', ''))) {
            return response()->json(['responseCode' => '4012500', 'responseMessage' => 'Invalid signature'], 401);
        }

        $sync = app(\App\Services\DokuPaymentSyncService::class);
        $reference = (string) ($payload['trxId'] ?? '');
        $order = $sync->find($reference);
        if (! $order || $order->gateway_type !== 'va'
            || trim((string) ($payload['virtualAccountNo'] ?? '')) !== trim((string) $order->va_number)
            || data_get($payload, 'paidAmount.currency') !== 'IDR') {
            return response()->json(['responseCode' => '4042500', 'responseMessage' => 'Payment not found'], 404);
        }

        try {
            $accepted = $sync->apply($order, [
                'order' => ['invoice_number' => $reference, 'amount' => data_get($payload, 'paidAmount.value')],
                'transaction' => ['status' => 'SUCCESS'],
            ]);
            if (! $accepted) {
                return response()->json(['responseCode' => '4042513', 'responseMessage' => 'Invalid amount'], 422);
            }

            return response()->json([
                'responseCode' => '2002500', 'responseMessage' => 'Success',
                'virtualAccountData' => \Illuminate\Support\Arr::only($payload, [
                    'partnerServiceId', 'customerNo', 'virtualAccountNo', 'virtualAccountName',
                    'paymentRequestId', 'paidAmount', 'additionalInfo',
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('DOKU SNAP notification failed', ['invoice_number' => $reference, 'exception' => $e::class]);

            return response()->json(['responseCode' => '5002500', 'responseMessage' => 'Processing failed'], 500);
        }
    }
}

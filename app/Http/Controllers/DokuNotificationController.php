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
            if ($checkout = Checkout::where('checkout_ref', $invoiceNumber)->first()) {
                if (! $this->amountMatches((int) $checkout->total, $amount, $invoiceNumber)) {
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
                if (! $this->amountMatches((int) round((float) $accountOrder->total_price), $amount, $invoiceNumber)) {
                    return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 422);
                }

                if ($status === 'SUCCESS' && $accountOrder->status === 'pending') {
                    $accountOrder->update(['status' => 'success']);
                    $accountOrder->listing?->update(['is_sold' => true]);
                } elseif ($status === 'EXPIRED' && $accountOrder->status === 'pending') {
                    $accountOrder->update(['status' => 'failed']);
                }

                return response()->json(['status' => 'ok']);
            }

            if ($order = Order::where('order_id', $invoiceNumber)->first()) {
                if (! $this->amountMatches((int) round((float) $order->price), $amount, $invoiceNumber)) {
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
}

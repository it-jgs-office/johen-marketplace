<?php

namespace App\Http\Controllers;

use App\Services\MidtransPaymentService;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransNotificationController extends Controller
{
    public function __construct(
        protected MidtransService $midtrans,
        protected MidtransPaymentService $payments,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->midtrans->verifySignature($payload)) {
            Log::warning('Midtrans notification rejected: invalid signature');

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        $reference = trim((string) ($payload['order_id'] ?? ''));
        $payment = $this->payments->findByReference($reference);

        if (! $payment) {
            Log::warning('Midtrans notification: order not found', ['order_id' => $reference]);

            return response()->json(['status' => 'ok']);
        }

        try {
            if (! $this->payments->apply($payment, $payload)) {
                return response()->json(['status' => 'error', 'message' => 'Amount mismatch'], 422);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('Midtrans notification failed', [
                'order_id' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}

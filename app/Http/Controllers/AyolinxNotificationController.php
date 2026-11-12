<?php

namespace App\Http\Controllers;

use App\Services\AyolinxPaymentService;
use App\Services\AyolinxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AyolinxNotificationController extends Controller
{
    public function __construct(
        protected AyolinxService $ayolinx,
        protected AyolinxPaymentService $payments,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $timestamp = (string) $request->header('X-TIMESTAMP', '');
        $signature = (string) $request->header('X-SIGNATURE', '');

        if (! $this->ayolinx->verifyCallback(
            $request->method(),
            '/'.$request->path(),
            $request->getContent(),
            $timestamp,
            $signature,
        )) {
            Log::warning('Ayolinx notification rejected: invalid signature');

            return response()->json([
                'responseCode' => '4015600',
                'responseMessage' => 'Unauthorized',
            ], 401);
        }

        if (($payload['callbackType'] ?? null) !== 'payment') {
            return response()->json([
                'responseCode' => '2005600',
                'responseMessage' => 'Successful',
            ]);
        }

        $reference = trim((string) ($payload['originalPartnerReferenceNo'] ?? ''));
        $payment = $this->payments->findByReference($reference);

        if (! $payment) {
            Log::warning('Ayolinx notification: order not found', ['reference' => $reference]);

            return response()->json([
                'responseCode' => '2005600',
                'responseMessage' => 'Successful',
            ]);
        }

        try {
            if (! $this->payments->apply($payment, $payload)) {
                return response()->json([
                    'responseCode' => '4005601',
                    'responseMessage' => 'Invalid transaction data',
                ], 422);
            }

            return response()->json([
                'responseCode' => '2005600',
                'responseMessage' => 'Successful',
            ]);
        } catch (\Throwable $e) {
            Log::error('Ayolinx notification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'responseCode' => '5005600',
                'responseMessage' => 'Internal server error',
            ], 500);
        }
    }
}

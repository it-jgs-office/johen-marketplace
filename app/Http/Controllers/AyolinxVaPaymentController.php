<?php

namespace App\Http\Controllers;

use App\Services\AyolinxPaymentService;
use App\Services\AyolinxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AyolinxVaPaymentController extends Controller
{
    public function __construct(
        protected AyolinxService $ayolinx,
        protected AyolinxPaymentService $payments,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $authorization = (string) $request->header('Authorization', '');
        $authentication = $this->ayolinx->validateInboundServiceRequest(
            $request->method(),
            '/'.$request->path(),
            $request->getContent(),
            (string) $request->header('X-TIMESTAMP', ''),
            (string) $request->header('X-SIGNATURE', ''),
            $authorization,
        );

        if ($authentication !== 'valid') {
            return response()->json([
                'responseCode' => $authentication === 'invalid_token' ? '4012501' : '4012500',
                'responseMessage' => $authentication === 'invalid_token'
                    ? 'Access Token Invalid'
                    : 'Unauthorized Signature',
            ], 401);
        }

        $payload = $request->all();
        $required = ['partnerServiceId', 'customerNo', 'virtualAccountNo', 'trxId', 'paymentRequestId'];

        foreach ($required as $field) {
            if (blank($payload[$field] ?? null)) {
                return $this->error('4002502', 'Invalid Mandatory Field '.$field, 400);
            }
        }

        if (blank(data_get($payload, 'paidAmount.value')) || blank(data_get($payload, 'paidAmount.currency'))) {
            return $this->error('4002502', 'Invalid Mandatory Field paidAmount', 400);
        }

        if (! is_numeric(data_get($payload, 'paidAmount.value'))) {
            return $this->error('4002501', 'Invalid Field Format paidAmount.value', 400);
        }

        $payment = $this->payments->findByReference(trim((string) $payload['trxId']));

        if (! $payment || data_get($payment->gateway_extra, 'provider') !== 'ayolinx') {
            return $this->error('4042512', 'Bill not found', 404);
        }

        $paidAmount = (int) round((float) data_get($payload, 'paidAmount.value'));
        $expectedAmount = (int) round((float) $payment->cash_amount);

        if ($expectedAmount < 1 || $paidAmount !== $expectedAmount) {
            return $this->error('4042513', 'Invalid Amount', 404);
        }

        $notification = [
            'callbackType' => 'payment',
            'originalPartnerReferenceNo' => (string) $payload['trxId'],
            'originalReferenceNo' => $payload['referenceNo'] ?? $payload['paymentRequestId'],
            'paymentRequestId' => (string) $payload['paymentRequestId'],
            'latestTransactionStatus' => '00',
            'amount' => $payload['paidAmount'],
            'finishedTime' => $payload['trxDateTime'] ?? now('Asia/Jakarta')->toIso8601String(),
            'additionalInfo' => $payload['additionalInfo'] ?? [],
        ];

        try {
            if (! $this->payments->apply($payment, $notification)) {
                return $this->error('4042512', 'Bill not found', 404);
            }
        } catch (\Throwable $e) {
            Log::error('Ayolinx VA payment callback failed', [
                'trx_id' => $payload['trxId'],
                'payment_request_id' => $payload['paymentRequestId'],
                'error' => $e->getMessage(),
            ]);

            return $this->error('5002500', 'Internal server error', 500);
        }

        return response()->json([
            'responseCode' => '2002500',
            'responseMessage' => 'Success',
            'virtualAccountData' => [
                'partnerServiceId' => (string) $payload['partnerServiceId'],
                'customerNo' => (string) $payload['customerNo'],
                'virtualAccountNo' => (string) $payload['virtualAccountNo'],
                'virtualAccountName' => (string) ($payload['virtualAccountName'] ?? ''),
                'virtualAccountTrxType' => (string) ($payload['virtualAccountTrxType'] ?? 'C'),
                'trxId' => (string) $payload['trxId'],
                'totalAmount' => $payload['totalAmount'] ?? $payload['paidAmount'],
                'paymentRequestId' => (string) $payload['paymentRequestId'],
                'paidAmount' => $payload['paidAmount'],
                'billDetails' => $payload['billDetails'] ?? [],
                'additionalInfo' => $payload['additionalInfo'] ?? [],
                'paymentFlagReason' => [
                    'english' => 'Success',
                    'indonesia' => 'Sukses',
                ],
            ],
        ]);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'responseCode' => $code,
            'responseMessage' => $message,
        ], $status);
    }
}

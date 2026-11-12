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
            (string) $request->header('X-PARTNER-ID', ''),
        );

        if ($authentication !== 'valid') {
            return response()->json([
                'responseCode' => $authentication === 'invalid_token' ? '4012501' : '4012500',
                'responseMessage' => $authentication === 'invalid_token'
                    ? 'Access Token Invalid'
                    : 'Unauthorized Signature',
            ], 401);
        }

        foreach (['X-EXTERNAL-ID', 'CHANNEL-ID'] as $header) {
            if (blank($request->header($header))) {
                return $this->error('4002502', 'Invalid Mandatory Field '.$header, 400);
            }
        }

        if (mb_strlen((string) $request->header('X-EXTERNAL-ID')) > 36) {
            return $this->error('4002501', 'Invalid Field Format X-EXTERNAL-ID', 400);
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

        $paidValue = (string) data_get($payload, 'paidAmount.value');
        $paidCurrency = strtoupper(trim((string) data_get($payload, 'paidAmount.currency')));

        if (preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]{2})$/', $paidValue) !== 1) {
            return $this->error('4002501', 'Invalid Field Format paidAmount.value', 400);
        }

        if ($paidCurrency !== 'IDR') {
            return $this->error('4002501', 'Invalid Field Format paidAmount.currency', 400);
        }

        $virtualAccountNo = trim((string) $payload['virtualAccountNo']);
        $trxId = trim((string) $payload['trxId']);
        $partnerServiceId = trim((string) $payload['partnerServiceId']);
        $customerNo = trim((string) $payload['customerNo']);
        $payment = $this->payments->findByVirtualAccount($virtualAccountNo);

        if (! $payment || ! $this->payments->matchesInboundBill(
            $payment,
            $trxId,
            $partnerServiceId,
            $customerNo,
        )) {
            return $this->error('4042512', 'Bill not found', 404);
        }

        $paidAmount = (int) round((float) $paidValue * 100);
        $expectedAmount = (int) round((float) $payment->cash_amount * 100);

        if ($expectedAmount < 1 || $paidAmount !== $expectedAmount) {
            return $this->error('4042513', 'Invalid Amount', 404);
        }

        $notification = [
            'callbackType' => 'payment',
            'originalPartnerReferenceNo' => $trxId,
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
                'trx_id' => $trxId,
                'payment_request_id' => $payload['paymentRequestId'],
                'error' => $e->getMessage(),
            ]);

            return $this->error('5002500', 'Internal server error', 500);
        }

        return response()->json([
            'responseCode' => '2002500',
            'responseMessage' => 'Successful',
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

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DokuDirectService
{
    private ?string $lastError = null;

    public function __construct(private DokuService $doku) {}

    public function available(string $method): bool
    {
        if (! $this->doku->isConfigured() || ! in_array($method, config('doku.direct_methods', []), true)) {
            return false;
        }

        if (in_array($method, ['alfamart', 'indomaret'], true)) {
            return true;
        }

        $keyPath = (string) config('doku.private_key_path');
        if ($keyPath === '' || ! is_file($keyPath) || ! is_readable($keyPath)) {
            return false;
        }
        if (! openssl_pkey_get_private(file_get_contents($keyPath))) {
            return false;
        }

        if ($method === 'qris') {
            return filled(config('doku.qris.merchant_id'))
                && filled(config('doku.qris.terminal_id'))
                && preg_match('/^\d{5}$/', (string) config('doku.qris.postal_code')) === 1;
        }

        return array_key_exists($method, config('doku.va', []))
            && preg_match('/^\d{1,8}$/', trim((string) config("doku.va.$method.partner_service_id"))) === 1
            && preg_match('/^\d{1,19}$/', (string) config("doku.va.$method.merchant_bin")) === 1;
    }

    public function create(string $method, array $ctx): array
    {
        $this->lastError = null;

        if (! $this->available($method)) {
            return $this->failed('Konfigurasi DOKU untuk metode pembayaran ini belum lengkap.');
        }

        if ((int) $ctx['amount'] <= 0) {
            return $this->failed('Nominal pembayaran tidak valid.');
        }

        $expires = now('Asia/Jakarta')->addMinutes(max(1, (int) config('doku.payment_due_minutes', 60)))->toIso8601String();
        $amount = ['value' => number_format($ctx['amount'], 2, '.', ''), 'currency' => 'IDR'];
        $extra = [
            'provider' => 'doku', 'integration' => 'direct',
            'invoice_number' => $ctx['reference_id'], 'expired_date' => $expires,
            'expected_amount' => $ctx['amount'],
            'environment' => $this->doku->isSandbox() ? 'sandbox' : 'production',
        ];

        if ($method === 'qris') {
            $data = $this->snap('/snap-adapter/b2b/v1.0/qr/qr-mpm-generate', [
                'partnerReferenceNo' => $ctx['reference_id'], 'amount' => $amount,
                'merchantId' => (string) config('doku.qris.merchant_id'),
                'terminalId' => (string) config('doku.qris.terminal_id'),
                'validityPeriod' => $expires,
                'additionalInfo' => ['postalCode' => (string) config('doku.qris.postal_code'), 'feeType' => '1'],
            ]);
            if (empty($data['qrContent']) || empty($data['referenceNo'])
                || ($data['partnerReferenceNo'] ?? '') !== $ctx['reference_id']) {
                return $this->failed($this->lastError ?: 'DOKU tidak mengembalikan data QRIS yang valid.');
            }

            return ['success' => true, 'attributes' => [
                'gateway_invoice_id' => $data['referenceNo'], 'qr_string' => $data['qrContent'],
                'gateway_extra' => array_merge($extra, [
                    'merchant_id' => (string) config('doku.qris.merchant_id'),
                    'expired_date' => data_get($data, 'additionalInfo.validityPeriod') ?: $expires,
                ]),
            ]];
        }

        if (str_ends_with($method, '_va')) {
            $partnerId = trim((string) config("doku.va.$method.partner_service_id"));
            $partner = str_pad($partnerId, 8, ' ', STR_PAD_LEFT);
            $merchantBin = trim((string) config("doku.va.$method.merchant_bin"));
            $prefix = str_starts_with($merchantBin, $partnerId)
                ? substr($merchantBin, strlen($partnerId))
                : $merchantBin;
            $prefix = $prefix !== '' ? $prefix : '0';
            $availableDigits = 20 - strlen($prefix);
            $referenceDigits = preg_replace('/\D/', '', (string) $ctx['reference_id']);
            $referenceDigits .= sprintf('%u', crc32((string) $ctx['reference_id']));
            $customerNo = $prefix.substr(str_pad($referenceDigits, $availableDigits, '0', STR_PAD_LEFT), -$availableDigits);
            $data = $this->snap('/virtual-accounts/bi-snap-va/v1.1/transfer-va/create-va', [
                'partnerServiceId' => $partner, 'customerNo' => $customerNo,
                'virtualAccountNo' => $partner.$customerNo,
                'virtualAccountName' => mb_substr($ctx['customer_name'] ?: 'Pelanggan Johen', 0, 100),
                'virtualAccountEmail' => $ctx['email'],
                'trxId' => $ctx['reference_id'], 'totalAmount' => $amount,
                'virtualAccountTrxType' => 'C', 'expiredDate' => $expires,
                'additionalInfo' => [
                    'channel' => $this->doku->paymentMethodType($method),
                    'virtualAccountConfig' => ['reusableStatus' => false],
                ],
            ]);
            $va = data_get($data, 'virtualAccountData', []);
            if (empty($va['virtualAccountNo']) || empty($va['customerNo'])
                || ($va['trxId'] ?? '') !== $ctx['reference_id']) {
                return $this->failed($this->lastError ?: 'DOKU tidak mengembalikan data Virtual Account yang valid.');
            }

            return ['success' => true, 'attributes' => [
                'gateway_invoice_id' => $ctx['reference_id'], 'va_number' => trim($va['virtualAccountNo']),
                'gateway_extra' => array_merge($extra, [
                    'partner_service_id' => $va['partnerServiceId'] ?? $partner,
                    'customer_no' => $va['customerNo'], 'virtual_account_no' => $va['virtualAccountNo'],
                    'bank_code' => strtoupper(str_replace('_va', '', $method)),
                    'label' => strtoupper(str_replace('_va', '', $method)).' Virtual Account',
                    'expired_date' => $va['expiredDate'] ?? $expires,
                ]),
            ]];
        }

        $payload = [
            'order' => ['invoice_number' => $ctx['reference_id'], 'amount' => $ctx['amount']],
            'online_to_offline_info' => [
                'expired_time' => max(1, (int) config('doku.payment_due_minutes', 60)),
                'reusable_status' => false, 'info' => 'Johen Gaming',
            ],
            'customer' => ['name' => mb_substr($ctx['customer_name'] ?: 'Pelanggan Johen', 0, 64), 'email' => $ctx['email']],
            'additional_info' => ['override_notification_url' => route('payment.doku.notification')],
        ];
        if ($method === 'indomaret') {
            $payload['indomaret_info']['receipt']['description'] = mb_substr($ctx['item_name'], 0, 128);
        }
        $path = '/'.($method === 'alfamart' ? 'alfa' : 'indomaret').'-online-to-offline/v2/payment-code';
        $data = $this->nonSnap($path, $payload);
        $info = data_get($data, 'online_to_offline_info', []);
        if (empty($info['payment_code']) || data_get($data, 'order.invoice_number') !== $ctx['reference_id']) {
            return $this->failed($this->lastError ?: 'DOKU tidak mengembalikan kode pembayaran yang valid.');
        }

        return ['success' => true, 'attributes' => [
            'gateway_invoice_id' => $ctx['reference_id'], 'payment_code' => $info['payment_code'],
            'gateway_extra' => array_merge($extra, [
                'label' => ucfirst($method), 'retail_outlet_name' => strtoupper($method),
                'expired_date' => $info['expired_date_utc'] ?? $expires,
            ]),
        ]];
    }

    public function status(object $order): ?array
    {
        $extra = (array) $order->gateway_extra;
        $invoice = (string) ($extra['invoice_number'] ?? '');
        if ($invoice === '' || ($extra['environment'] ?? '') !== ($this->doku->isSandbox() ? 'sandbox' : 'production')) {
            return null;
        }
        $key = 'doku-direct-status:'.sha1($this->doku->baseUrl().config('doku.client_id').$invoice);

        return Cache::remember($key, 10, function () use ($order, $extra, $invoice) {
            if ($order->gateway_type === 'retail') {
                return $this->doku->checkStatus($invoice);
            }

            if ($order->gateway_type === 'qris') {
                $data = $this->snap('/snap-adapter/b2b/v1.0/qr/qr-mpm-query', [
                    'originalReferenceNo' => $order->gateway_invoice_id,
                    'originalPartnerReferenceNo' => $invoice, 'serviceCode' => '47',
                    'merchantId' => $extra['merchant_id'],
                ]);
                if (! $data || ($data['originalPartnerReferenceNo'] ?? '') !== $invoice
                    || ($data['originalReferenceNo'] ?? '') !== $order->gateway_invoice_id
                    || data_get($data, 'amount.currency') !== 'IDR') {
                    return null;
                }
                $status = self::snapStatus((string) ($data['latestTransactionStatus'] ?? ''));
                $amount = data_get($data, 'amount.value');
            } elseif ($order->gateway_type === 'va') {
                $data = $this->snap('/orders/v1.0/transfer-va/status', [
                    'partnerServiceId' => $extra['partner_service_id'],
                    'customerNo' => $extra['customer_no'],
                    'virtualAccountNo' => $extra['virtual_account_no'],
                ]);
                if (! $data || trim((string) data_get($data, 'virtualAccountData.virtualAccountNo')) !== trim($extra['virtual_account_no'])
                    || data_get($data, 'additionalInfo.trxId') !== $invoice) {
                    return null;
                }
                $reason = strtoupper((string) data_get($data, 'virtualAccountData.paymentFlagReason.english'));
                $status = match ($reason) {
                    'SUCCESS', 'SUCCESSFUL', 'PAID' => 'SUCCESS',
                    'EXPIRED' => 'EXPIRED', 'FAILED' => 'FAILED', default => 'PENDING',
                };
                $amount = data_get($data, 'virtualAccountData.paidAmount.value');
                if ($status === 'SUCCESS' && data_get($data, 'virtualAccountData.paidAmount.currency') !== 'IDR') {
                    return null;
                }
            } else {
                return null;
            }

            return [
                'order' => ['invoice_number' => $invoice, 'amount' => $amount],
                'transaction' => ['status' => $status],
            ];
        });
    }

    public static function snapStatus(string $status): string
    {
        return match ($status) {
            '00' => 'SUCCESS', '05' => 'CANCELLED', '06' => 'FAILED', '07' => 'EXPIRED',
            default => 'PENDING',
        };
    }

    public function snapSignature(string $path, string $body, string $token, string $timestamp): string
    {
        $raw = 'POST:'.$path.':'.$token.':'.hash('sha256', $body).':'.$timestamp;

        return base64_encode(hash_hmac('sha512', $raw, (string) config('doku.secret_key'), true));
    }

    private function snap(string $path, array $payload): ?array
    {
        try {
            $token = $this->token();
            if (! $token) {
                $this->lastError ??= 'Otorisasi DOKU gagal. Pastikan Client ID dan pasangan key SNAP sudah terdaftar.';

                return null;
            }
            $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $response = Http::acceptJson()->timeout((int) config('doku.timeout', 20))
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token, 'X-PARTNER-ID' => config('doku.client_id'),
                    'X-TIMESTAMP' => $timestamp, 'X-EXTERNAL-ID' => now()->format('YmdHis').random_int(100000000, 999999999),
                    'CHANNEL-ID' => 'H2H', 'X-SIGNATURE' => $this->snapSignature($path, $body, $token, $timestamp),
                ])->withBody($body, 'application/json')->post($this->doku->baseUrl().$path);
            $data = $response->json();
            if ($response->successful() && is_array($data) && preg_match('/^200\d{2}00$/', (string) ($data['responseCode'] ?? ''))) {
                return $data;
            }
            $message = is_array($data) ? (string) ($data['responseMessage'] ?? $data['message'] ?? '') : '';
            $this->lastError = $message !== ''
                ? 'DOKU menolak pembuatan pembayaran: '.$message
                : 'DOKU menolak pembuatan pembayaran.';
            Log::warning('DOKU Direct API rejected', [
                'path' => $path,
                'http_status' => $response->status(),
                'code' => $data['responseCode'] ?? null,
                'message' => $message ?: null,
            ]);
        } catch (\Throwable $e) {
            $this->lastError = 'DOKU tidak dapat dihubungi. Silakan coba lagi.';
            Log::warning('DOKU Direct API unavailable', ['path' => $path, 'exception' => $e::class]);
        }

        return null;
    }

    private function token(): ?string
    {
        $keyPath = (string) config('doku.private_key_path');
        if (! is_file($keyPath) || ! is_readable($keyPath)) {
            $this->lastError = 'Private key DOKU tidak dapat dibaca.';

            return null;
        }
        $key = 'doku-snap-token:'.hash('sha256', $this->doku->baseUrl().config('doku.client_id').config('doku.secret_key').filemtime($keyPath));
        if ($token = Cache::get($key)) {
            return $token;
        }
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $privateKey = openssl_pkey_get_private(file_get_contents($keyPath));
        if (! $privateKey || ! openssl_sign(config('doku.client_id').'|'.$timestamp, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            $this->lastError = 'Private key DOKU tidak valid.';

            return null;
        }
        $response = Http::acceptJson()->timeout((int) config('doku.timeout', 20))->withHeaders([
            'X-CLIENT-KEY' => config('doku.client_id'), 'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => base64_encode($signature),
        ])->post($this->doku->baseUrl().'/authorization/v1/access-token/b2b', ['grantType' => 'client_credentials']);
        if (! $response->successful() || $response->json('responseCode') !== '2007300' || ! $response->json('accessToken')) {
            $message = (string) ($response->json('responseMessage') ?: $response->json('message') ?: '');
            $this->lastError = $message !== ''
                ? 'Otorisasi DOKU ditolak: '.$message
                : 'Otorisasi DOKU ditolak.';
            Log::warning('DOKU SNAP token rejected', [
                'http_status' => $response->status(),
                'code' => $response->json('responseCode'),
                'message' => $message ?: null,
            ]);

            return null;
        }
        $token = (string) $response->json('accessToken');
        Cache::put($key, $token, max(1, min(840, (int) $response->json('expiresIn', 900) - 60)));

        return $token;
    }

    private function nonSnap(string $path, array $payload): ?array
    {
        try {
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $requestId = (string) Str::uuid();
            $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
            $response = Http::acceptJson()->timeout((int) config('doku.timeout', 20))->withHeaders([
                'Client-Id' => config('doku.client_id'), 'Request-Id' => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature' => $this->doku->signature('POST', $path, $requestId, $timestamp, $body),
            ])->withBody($body, 'application/json')->post($this->doku->baseUrl().$path);

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::warning('DOKU retail unavailable', ['path' => $path, 'exception' => $e::class]);

            return null;
        }
    }

    private function failed(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}

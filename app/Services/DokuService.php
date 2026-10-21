<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DokuService
{
    public const CHECKOUT_PATH = '/checkout/v1/payment';

    public function isConfigured(): bool
    {
        return trim((string) config('doku.client_id')) !== ''
            && trim((string) config('doku.secret_key')) !== '';
    }

    public function isSandbox(): bool
    {
        return strtolower((string) config('doku.environment', 'sandbox')) !== 'production';
    }

    public function baseUrl(): string
    {
        return rtrim((string) config(
            $this->isSandbox() ? 'doku.sandbox_url' : 'doku.production_url'
        ), '/');
    }

    /**
     * Membuat halaman DOKU Checkout dan mengembalikan hasil yang sudah
     * dinormalisasi agar bisa dipakai semua jenis order di aplikasi.
     */
    public function createCheckout(array $payload): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'Kredensial DOKU belum dikonfigurasi.'];
        }

        $requestId = (string) Str::uuid();
        $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            return ['success' => false, 'message' => 'Payload DOKU tidak valid.'];
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('doku.timeout', 20))
                ->withHeaders($this->headers('POST', self::CHECKOUT_PATH, $requestId, $timestamp, $body))
                ->withBody($body, 'application/json')
                ->post($this->baseUrl().self::CHECKOUT_PATH);

            return $this->normalizeCheckoutResponse($response, $requestId);
        } catch (\Throwable $e) {
            Log::error('DOKU Checkout request gagal', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'DOKU tidak dapat dihubungi.', 'request_id' => $requestId];
        }
    }

    /**
     * Status API DOKU dibatasi satu kali per 30 detik per invoice agar polling
     * browser tidak membanjiri gateway. Webhook tetap menjadi sumber utama.
     */
    public function checkStatus(string $invoiceNumber): ?array
    {
        if (! $this->isConfigured() || trim($invoiceNumber) === '') {
            return null;
        }

        $invoiceNumber = trim($invoiceNumber);
        $cacheKey = 'doku-status:'.sha1($this->baseUrl().config('doku.client_id').$invoiceNumber);

        return Cache::remember($cacheKey, 30, function () use ($invoiceNumber) {
            $path = '/orders/v1/status/'.rawurlencode($invoiceNumber);
            $requestId = (string) Str::uuid();
            $timestamp = now('UTC')->format('Y-m-d\TH:i:s\Z');

            try {
                $response = Http::acceptJson()
                    ->timeout((int) config('doku.timeout', 20))
                    ->withHeaders($this->headers('GET', $path, $requestId, $timestamp))
                    ->get($this->baseUrl().$path);

                if (! $response->successful()) {
                    Log::warning('DOKU check status gagal', [
                        'invoice_number' => $invoiceNumber,
                        'http_status' => $response->status(),
                        'response' => $response->json(),
                    ]);

                    return null;
                }

                return $response->json();
            } catch (\Throwable $e) {
                Log::warning('DOKU check status tidak dapat dihubungi', [
                    'invoice_number' => $invoiceNumber,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    public function paymentMethodType(string $method): ?string
    {
        return match (strtolower(trim($method))) {
            'doku_wallet' => 'EMONEY_DOKU',
            'akulaku' => 'PEER_TO_PEER_AKULAKU',
            'bjb', 'bjb_va' => 'VIRTUAL_ACCOUNT_BJB',
            'bnc', 'bnc_va' => 'VIRTUAL_ACCOUNT_BNC',
            'bri', 'bri_va' => 'VIRTUAL_ACCOUNT_BRI',
            'bni', 'bni_va' => 'VIRTUAL_ACCOUNT_BNI',
            'bsi', 'bsi_va' => 'VIRTUAL_ACCOUNT_BSI',
            'bss', 'bss_va' => 'VIRTUAL_ACCOUNT_BSS',
            'btn', 'btn_va' => 'VIRTUAL_ACCOUNT_BTN',
            'cimb', 'cimb_va' => 'VIRTUAL_ACCOUNT_BANK_CIMB',
            'doku', 'doku_va' => 'VIRTUAL_ACCOUNT_DOKU',
            'danamon', 'danamon_va' => 'VIRTUAL_ACCOUNT_BANK_DANAMON',
            'maybank', 'maybank_va' => 'VIRTUAL_ACCOUNT_MAYBANK',
            'permata', 'permata_va' => 'VIRTUAL_ACCOUNT_BANK_PERMATA',
            'sinarmas', 'sinarmas_va' => 'VIRTUAL_ACCOUNT_SINARMAS',
            'alfamart' => 'ONLINE_TO_OFFLINE_ALFA',
            'indomaret' => 'ONLINE_TO_OFFLINE_INDOMARET',
            default => null,
        };
    }

    public function supportsMethod(string $method): bool
    {
        return $this->paymentMethodType($method) !== null;
    }

    public function verifyNotification(
        string $rawBody,
        string $requestTarget,
        ?string $clientId,
        ?string $requestId,
        ?string $timestamp,
        ?string $signature,
    ): bool {
        if (! $this->isConfigured()
            || ! is_string($clientId)
            || ! hash_equals((string) config('doku.client_id'), $clientId)
            || ! is_string($requestId) || $requestId === ''
            || ! is_string($timestamp) || $timestamp === ''
            || ! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = $this->signature('POST', $requestTarget, $requestId, $timestamp, $rawBody);

        return hash_equals($expected, $signature);
    }

    public function signature(
        string $method,
        string $requestTarget,
        string $requestId,
        string $timestamp,
        string $body = '',
    ): string {
        $components = [
            'Client-Id:'.config('doku.client_id'),
            'Request-Id:'.$requestId,
            'Request-Timestamp:'.$timestamp,
            'Request-Target:'.$requestTarget,
        ];

        if (strtoupper($method) !== 'GET') {
            $components[] = 'Digest:'.base64_encode(hash('sha256', $body, true));
        }

        $raw = implode("\n", $components);
        $hash = hash_hmac('sha256', $raw, (string) config('doku.secret_key'), true);

        return 'HMACSHA256='.base64_encode($hash);
    }

    private function headers(
        string $method,
        string $path,
        string $requestId,
        string $timestamp,
        string $body = '',
    ): array {
        return [
            'Client-Id' => (string) config('doku.client_id'),
            'Request-Id' => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature' => $this->signature($method, $path, $requestId, $timestamp, $body),
        ];
    }

    private function normalizeCheckoutResponse(Response $response, string $requestId): array
    {
        $data = $response->json() ?: [];
        $payment = data_get($data, 'response.payment', []);
        $url = (string) ($payment['url'] ?? '');
        $tokenId = (string) ($payment['token_id'] ?? '');

        if (! $response->successful() || $url === '' || $tokenId === '') {
            Log::warning('DOKU Checkout ditolak', [
                'request_id' => $requestId,
                'http_status' => $response->status(),
                'response' => $data,
            ]);

            return [
                'success' => false,
                'message' => $this->errorMessage($data)
                    ?: 'DOKU menolak pembuatan pembayaran.',
                'request_id' => $requestId,
                'raw' => $data,
            ];
        }

        return [
            'success' => true,
            'token_id' => $tokenId,
            'payment_url' => $url,
            'expired_date' => $payment['expired_date'] ?? null,
            'request_id' => $requestId,
            'raw' => $data,
        ];
    }

    private function errorMessage(array $data): ?string
    {
        foreach ([
            'error_messages',
            'message',
            'error.message',
            'errors',
            'response.message',
            'response.responseMessage',
            'response.status_message',
        ] as $path) {
            $message = $this->firstText(data_get($data, $path));

            if ($message !== null) {
                return $message;
            }
        }

        return null;
    }

    private function firstText(mixed $value): ?string
    {
        if (is_string($value) || is_numeric($value)) {
            $text = trim((string) $value);

            return $text !== '' ? $text : null;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($text = $this->firstText($item)) {
                    return $text;
                }
            }
        }

        return null;
    }
}

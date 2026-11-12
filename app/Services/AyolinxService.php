<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AyolinxService
{
    public function isConfigured(): bool
    {
        $privateKeyPath = (string) config('ayolinx.private_key_path');
        $callbackPublicKeyPath = (string) config('ayolinx.callback_public_key_path');

        return trim((string) config('ayolinx.client_key')) !== ''
            && trim((string) config('ayolinx.client_id')) !== ''
            && trim((string) config('ayolinx.secret_key')) !== ''
            && $privateKeyPath !== ''
            && is_readable($privateKeyPath)
            && $callbackPublicKeyPath !== ''
            && is_readable($callbackPublicKeyPath);
    }

    public function isProduction(): bool
    {
        return (bool) config('ayolinx.is_production', false);
    }

    public function createPaymentLink(array $payload): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'Credential Ayolinx belum dikonfigurasi.'];
        }

        $path = '/v1.0/payment-link/create';
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $token = $this->accessToken();

        if ($token === null || $body === false) {
            return ['success' => false, 'message' => 'Otorisasi Ayolinx gagal.'];
        }

        $timestamp = $this->timestamp();

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('ayolinx.timeout', 20))
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'X-TIMESTAMP' => $timestamp,
                    'X-SIGNATURE' => $this->serviceSignature('POST', $path, $token, $body, $timestamp),
                    'X-PARTNER-ID' => (string) config('ayolinx.client_key'),
                    'X-EXTERNAL-ID' => Str::lower(Str::random(32)),
                    'CHANNEL-ID' => 'H2H',
                ])
                ->withBody($body, 'application/json')
                ->post($this->baseUrl().$path);

            $data = $response->json();

            if ($response->successful()
                && is_array($data)
                && ($data['responseCode'] ?? null) === '2000100'
                && filled($data['paymentLink'] ?? null)) {
                return ['success' => true, 'data' => $data];
            }

            $message = is_array($data)
                ? (string) ($data['responseMessage'] ?? 'Ayolinx menolak pembuatan pembayaran.')
                : 'Ayolinx menolak pembuatan pembayaran.';

            Log::warning('Ayolinx payment link rejected', [
                'status' => $response->status(),
                'response_code' => is_array($data) ? ($data['responseCode'] ?? null) : null,
                'message' => $message,
            ]);

            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::error('Ayolinx payment link error', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Tidak dapat terhubung ke Ayolinx.'];
        }
    }

    public function verifyCallback(string $method, string $path, string $rawBody, string $timestamp, string $signature): bool
    {
        $publicKeyPath = (string) config('ayolinx.callback_public_key_path');

        if ($timestamp === '' || $signature === '' || $publicKeyPath === '' || ! is_readable($publicKeyPath)) {
            return false;
        }

        $publicKey = openssl_pkey_get_public((string) file_get_contents($publicKeyPath));
        $decodedSignature = base64_decode($signature, true);

        if ($publicKey === false || $decodedSignature === false) {
            return false;
        }

        $stringToSign = $this->callbackStringToSign($method, $path, $rawBody, $timestamp);

        return openssl_verify($stringToSign, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    public function issueInboundAccessToken(string $clientKey, string $timestamp, string $signature): ?array
    {
        $expectedClientKey = trim((string) config('ayolinx.inbound_client_key'));
        $publicKeyPath = (string) config('ayolinx.callback_public_key_path');

        if ($expectedClientKey === '' || ! hash_equals($expectedClientKey, $clientKey)
            || $timestamp === '' || $signature === ''
            || $publicKeyPath === '' || ! is_readable($publicKeyPath)) {
            return null;
        }

        $publicKey = openssl_pkey_get_public((string) file_get_contents($publicKeyPath));
        $decodedSignature = base64_decode($signature, true);

        if ($publicKey === false || $decodedSignature === false
            || openssl_verify($clientKey.'|'.$timestamp, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            return null;
        }

        $ttl = max(60, (int) config('ayolinx.inbound_token_ttl', 3600));
        $token = Str::random(64);

        Cache::put($this->inboundTokenCacheKey($token), true, now()->addSeconds($ttl));

        return [
            'accessToken' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => (string) $ttl,
        ];
    }

    public function validateInboundServiceRequest(
        string $method,
        string $path,
        string $rawBody,
        string $timestamp,
        string $signature,
        string $authorization,
    ): string {
        if (! preg_match('/^Bearer\s+(.+)$/i', trim($authorization), $matches)
            || ! Cache::has($this->inboundTokenCacheKey($matches[1]))) {
            return 'invalid_token';
        }

        if ($timestamp === '' || $signature === '') {
            return 'invalid_signature';
        }

        $expected = $this->serviceSignature($method, $path, $matches[1], $rawBody, $timestamp);

        return hash_equals($expected, $signature) ? 'valid' : 'invalid_signature';
    }

    private function accessToken(): ?string
    {
        $cacheKey = 'ayolinx:access-token:'.hash('sha256', $this->baseUrl().config('ayolinx.client_key'));

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $timestamp = $this->timestamp();
            $signature = $this->asymmetricSignature((string) config('ayolinx.client_key').'|'.$timestamp);

            if ($signature === null) {
                return null;
            }

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->timeout((int) config('ayolinx.timeout', 20))
                    ->withHeaders([
                        'X-TIMESTAMP' => $timestamp,
                        'X-CLIENT-KEY' => (string) config('ayolinx.client_key'),
                        'X-SIGNATURE' => $signature,
                    ])
                    ->post($this->baseUrl().'/v1.0/access-token/b2b', [
                        'grant_type' => 'client_credentials',
                        'additionalInfo' => [
                            'channel' => (int) config('ayolinx.channel_id', 13),
                            'client_id' => (string) config('ayolinx.client_id'),
                        ],
                    ]);

                $data = $response->json();

                if ($response->successful() && is_array($data) && filled($data['accessToken'] ?? null)) {
                    return (string) $data['accessToken'];
                }

                Log::warning('Ayolinx access token rejected', [
                    'status' => $response->status(),
                    'response_code' => is_array($data) ? ($data['responseCode'] ?? null) : null,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Ayolinx access token error', ['error' => $e->getMessage()]);
            }

            return null;
        });
    }

    private function asymmetricSignature(string $value): ?string
    {
        $keyPath = (string) config('ayolinx.private_key_path');
        $privateKey = is_readable($keyPath)
            ? openssl_pkey_get_private((string) file_get_contents($keyPath))
            : false;

        if ($privateKey === false || ! openssl_sign($value, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        return base64_encode($signature);
    }

    private function serviceSignature(string $method, string $path, string $token, string $body, string $timestamp): string
    {
        return base64_encode(hash_hmac(
            'sha512',
            $this->requestStringToSign($method, $path, $token, $body, $timestamp),
            (string) config('ayolinx.secret_key'),
            true,
        ));
    }

    private function requestStringToSign(string $method, string $path, string $token, string $body, string $timestamp): string
    {
        return strtoupper($method).':'.$path.':'.$token.':'.strtolower(hash('sha256', $body)).':'.$timestamp;
    }

    private function callbackStringToSign(string $method, string $path, string $body, string $timestamp): string
    {
        return strtoupper($method).':'.$path.':'.strtolower(hash('sha256', $body)).':'.$timestamp;
    }

    private function timestamp(): string
    {
        return now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('ayolinx.base_url'), '/');
    }

    private function inboundTokenCacheKey(string $token): string
    {
        return 'ayolinx:inbound-token:'.hash('sha256', $token);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    public function isConfigured(): bool
    {
        return trim((string) config('midtrans.merchant_id')) !== ''
            && trim((string) config('midtrans.client_key')) !== ''
            && trim((string) config('midtrans.server_key')) !== '';
    }

    public function isProduction(): bool
    {
        return (bool) config('midtrans.is_production', false);
    }

    public function chargeCore(array $payload): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'Credential Midtrans belum dikonfigurasi.'];
        }

        try {
            $response = Http::withBasicAuth((string) config('midtrans.server_key'), '')
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('midtrans.timeout', 20))
                ->post(rtrim((string) config('midtrans.api_url'), '/').'/v2/charge', $payload);

            $data = $response->json();

            if ($response->successful() && is_array($data) && ! empty($data['transaction_id'])) {
                return ['success' => true, 'data' => $data];
            }

            $message = is_array($data)
                ? implode(' ', (array) ($data['error_messages'] ?? [$data['status_message'] ?? 'Midtrans menolak transaksi.']))
                : 'Midtrans menolak transaksi.';

            Log::warning('Midtrans Core API charge failed', [
                'status' => $response->status(),
                'message' => $message,
            ]);

            return ['success' => false, 'message' => $message, 'raw' => $data];
        } catch (\Throwable $e) {
            Log::error('Midtrans Core API charge error', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Tidak dapat terhubung ke Midtrans.'];
        }
    }

    public function getStatus(string $orderId): ?array
    {
        if (! $this->isConfigured() || trim($orderId) === '') {
            return null;
        }

        try {
            $response = Http::withBasicAuth((string) config('midtrans.server_key'), '')
                ->acceptJson()
                ->timeout((int) config('midtrans.timeout', 20))
                ->get(rtrim((string) config('midtrans.api_url'), '/').'/v2/'.rawurlencode($orderId).'/status');

            return $response->successful() && is_array($response->json())
                ? $response->json()
                : null;
        } catch (\Throwable $e) {
            Log::warning('Midtrans status check error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function verifySignature(array $payload): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $serverKey = (string) config('midtrans.server_key');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '' || $serverKey === '') {
            return false;
        }

        return hash_equals(hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey), $signature);
    }

    public function isPaid(array $payload): bool
    {
        $status = strtolower((string) ($payload['transaction_status'] ?? ''));
        $fraudStatus = strtolower((string) ($payload['fraud_status'] ?? 'accept'));

        return in_array($status, ['settlement', 'capture'], true) && $fraudStatus === 'accept';
    }

    public function isExpired(array $payload): bool
    {
        return strtolower((string) ($payload['transaction_status'] ?? '')) === 'expire';
    }

    public function isFailed(array $payload): bool
    {
        return in_array(strtolower((string) ($payload['transaction_status'] ?? '')), [
            'deny', 'cancel', 'failure',
        ], true);
    }
}

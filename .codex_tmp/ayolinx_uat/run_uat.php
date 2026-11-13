<?php

declare(strict_types=1);

use App\Services\AyolinxService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$service = app(AyolinxService::class);
$reflection = new ReflectionClass($service);

$invokePrivate = static function (string $method, array $arguments = []) use ($reflection, $service): mixed {
    $refMethod = $reflection->getMethod($method);
    $refMethod->setAccessible(true);

    return $refMethod->invokeArgs($service, $arguments);
};

$token = $invokePrivate('accessToken');
if (! is_string($token) || $token === '') {
    fwrite(STDERR, "Unable to obtain Ayolinx sandbox access token.\n");
    exit(1);
}

$baseUrl = (string) $invokePrivate('baseUrl');
$clientKey = (string) config('ayolinx.client_key');
$channelId = (string) config('ayolinx.channel_id', 13);
$timeout = (int) config('ayolinx.timeout', 20);

$mask = static function (string $value): string {
    if (strlen($value) <= 8) {
        return '[REDACTED]';
    }

    return substr($value, 0, 4).'…'.substr($value, -4);
};

$newId = static fn (string $prefix): string => substr($prefix.'-'.bin2hex(random_bytes(12)), 0, 36);
$newExternalId = static fn (): string => now('Asia/Jakarta')->format('ymdHis').(string) random_int(100000000000, 999999999999);

$call = static function (
    string $path,
    array $body,
    array $options = []
) use ($invokePrivate, $token, $baseUrl, $clientKey, $channelId, $timeout, $mask, $newExternalId): array {
    $requestToken = (string) ($options['token'] ?? $token);
    $timestamp = (string) $invokePrivate('timestamp');
    $externalId = (string) ($options['external_id'] ?? $newExternalId());
    $partnerId = (string) ($options['partner_id'] ?? $clientKey);
    $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES);
    if ($bodyJson === false) {
        throw new RuntimeException('Unable to encode request body.');
    }

    $signature = (string) $invokePrivate('serviceSignature', ['POST', $path, $requestToken, $bodyJson, $timestamp]);
    if (($options['signature'] ?? null) === 'invalid') {
        $signature = 'invalid-signature';
    }

    $headers = [
        'Authorization' => 'Bearer '.$requestToken,
        'X-TIMESTAMP' => $timestamp,
        'X-SIGNATURE' => $signature,
        'X-PARTNER-ID' => $partnerId,
        'X-EXTERNAL-ID' => $externalId,
        'CHANNEL-ID' => 'H2H',
    ];

    try {
        $response = Http::acceptJson()
            ->timeout($timeout)
            ->withHeaders($headers)
            ->withBody($bodyJson, 'application/json')
            ->post($baseUrl.$path);

        $decoded = $response->json();
        $responseBody = is_array($decoded) ? $decoded : ['raw' => $response->body()];

        return [
            'executed' => true,
            'request' => [
                'method' => 'POST',
                'url' => $baseUrl.$path,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer [REDACTED]',
                    'X-TIMESTAMP' => $timestamp,
                    'X-SIGNATURE' => '[REDACTED]',
                    'X-PARTNER-ID' => $mask($partnerId),
                    'X-EXTERNAL-ID' => $externalId,
                    'CHANNEL-ID' => 'H2H',
                ],
                'body' => $body,
            ],
            'response' => [
                'http_status' => $response->status(),
                'body' => $responseBody,
            ],
        ];
    } catch (Throwable $error) {
        return [
            'executed' => true,
            'request' => [
                'method' => 'POST',
                'url' => $baseUrl.$path,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer [REDACTED]',
                    'X-TIMESTAMP' => $timestamp,
                    'X-SIGNATURE' => '[REDACTED]',
                    'X-PARTNER-ID' => $mask($partnerId),
                    'X-EXTERNAL-ID' => $externalId,
                    'CHANNEL-ID' => 'H2H',
                ],
                'body' => $body,
            ],
            'response' => [
                'error' => $error->getMessage(),
            ],
        ];
    }
};

$blocked = static fn (string $reason): array => [
    'executed' => false,
    'reason' => $reason,
];

$results = [
    'meta' => [
        'environment' => $service->isProduction() ? 'production' : 'sandbox',
        'base_host' => parse_url($baseUrl, PHP_URL_HOST),
        'authenticated' => true,
        'executed_at' => now('Asia/Jakarta')->format('Y-m-d H:i:sP'),
    ],
    'transfer_va' => [],
    'qr_mpm' => [],
    'direct_debit' => [],
];

$vaPath = '/v1.0/transfer-va/create-va';
$vaSuffix = (string) random_int(100000000000, 999999999999);
$vaTrxId = (string) random_int(100000000000, 999999999999);
$vaBody = [
    'partnerServiceId' => '98829172',
    'customerNo' => '30000000'.$vaSuffix,
    'virtualAccountNo' => (string) random_int(1000000000000000, 9999999999999999),
    'virtualAccountName' => 'Johen UAT Customer',
    'trxId' => $vaTrxId,
    'virtualAccountTrxType' => 'C',
    'totalAmount' => ['value' => '11500.00', 'currency' => 'IDR'],
    'additionalInfo' => ['channel' => 'VIRTUAL_ACCOUNT_BNI'],
];

$results['transfer_va']['11.1'] = $call($vaPath, $vaBody, ['token' => 'invalid-uat-token']);
$results['transfer_va']['11.2'] = $call($vaPath, $vaBody, ['signature' => 'invalid']);
$vaMissing = $vaBody;
unset($vaMissing['customerNo']);
$results['transfer_va']['11.3'] = $call($vaPath, $vaMissing);
$vaInvalid = $vaBody;
$vaInvalid['totalAmount']['value'] = 'invalid';
$results['transfer_va']['11.4'] = $call($vaPath, $vaInvalid);
$vaExternalId = $newExternalId();
$results['transfer_va']['11.14'] = $call($vaPath, $vaBody, ['external_id' => $vaExternalId]);
$results['transfer_va']['11.5'] = $call($vaPath, $vaBody, ['external_id' => $vaExternalId]);
$results['transfer_va']['11.10'] = $blocked('Inbound VA payment endpoint must be provided by the merchant; the project does not expose /v1.0/transfer-va/payment.');
$results['transfer_va']['11.11'] = $blocked('Inbound VA payment endpoint is not implemented, so an unregistered VA payment cannot be exercised.');
$results['transfer_va']['11.12'] = $blocked('Inbound VA payment endpoint is not implemented, so invalid paid amount validation cannot be exercised.');
$results['transfer_va']['11.17'] = $call('/v1.0/transfer-va/inquiry-va', [
    'originalPartnerReferenceNo' => $vaTrxId,
    'additionalInfo' => ['channel' => 'VIRTUAL_ACCOUNT_BNI'],
]);

$qrPath = '/v1.0/qr/qr-mpm-generate';
$qrReference = $newId('qr');
$qrBody = [
    'partnerReferenceNo' => $qrReference,
    'amount' => ['currency' => 'IDR', 'value' => '10001.00'],
    'additionalInfo' => ['channel' => (string) config('ayolinx.qris_channel', 'BNC_QRIS')],
];

$results['qr_mpm']['18.1'] = $call($qrPath, $qrBody, ['token' => 'invalid-uat-token']);
$results['qr_mpm']['18.2'] = $call($qrPath, $qrBody, ['signature' => 'invalid']);
$qrMissing = $qrBody;
unset($qrMissing['partnerReferenceNo']);
$results['qr_mpm']['18.3'] = $call($qrPath, $qrMissing);
$qrInvalid = $qrBody;
$qrInvalid['amount']['value'] = 'invalid';
$results['qr_mpm']['18.4'] = $call($qrPath, $qrInvalid);
$qrExternalId = $newExternalId();
$results['qr_mpm']['18.6'] = $call($qrPath, $qrBody, ['external_id' => $qrExternalId]);
$results['qr_mpm']['18.5'] = $call($qrPath, $qrBody, ['external_id' => $qrExternalId]);
$qrInvalidMerchant = $qrBody;
$qrInvalidMerchant['partnerReferenceNo'] = $newId('qr-invalid-merchant');
$results['qr_mpm']['18.7'] = $call($qrPath, $qrInvalidMerchant, ['partner_id' => 'CKSandbox-invalid-merchant']);

$queryBody = [
    'originalPartnerReferenceNo' => $qrReference,
    'serviceCode' => '51',
    'additionalInfo' => ['channel' => (string) config('ayolinx.qris_channel', 'BNC_QRIS')],
];
$results['qr_mpm']['18.16'] = $call('/v1.0/qr/qr-mpm-query', $queryBody);
$results['qr_mpm']['18.17'] = $call('/v1.0/qr/qr-mpm-query', [
    'originalPartnerReferenceNo' => $newId('qr-not-found'),
    'serviceCode' => '51',
    'additionalInfo' => ['channel' => (string) config('ayolinx.qris_channel', 'BNC_QRIS')],
]);
$results['qr_mpm']['18.15'] = $blocked('A successful QR transaction requires marking the sandbox transaction as paid in the Ayolinx merchant dashboard.');
$results['qr_mpm']['18.18'] = $blocked('Inbound QR notification endpoint /v1.0/qr/qr-mpm-notify is not implemented by the project.');
$results['qr_mpm']['18.19'] = $blocked('Inbound QR notification endpoint /v1.0/qr/qr-mpm-notify is not implemented by the project.');
$cancelBody = [
    'originalPartnerReferenceNo' => $qrReference,
    'additionalInfo' => ['channel' => (string) config('ayolinx.qris_channel', 'BNC_QRIS')],
    'reason' => 'UAT cancellation test',
];
$results['qr_mpm']['18.20'] = $call('/v1.0/qr/qr-mpm-cancel', $cancelBody);
$results['qr_mpm']['18.21'] = $call('/v1.0/qr/qr-mpm-cancel', $cancelBody);

$directDebitReason = 'Direct Debit is not present in the supplied Ayolinx documentation index and the project has no Direct Debit API implementation or sandbox test accounts.';
foreach (['19.1', '19.2', '19.3', '19.4', '19.5', '19.6', '19.7', '19.8', '19.9', '19.10', '19.11', '19.12', '19.13', '19.14', '19.15', '19.16', '19.17', '19.18', '19.19', '19.20'] as $case) {
    $results['direct_debit'][$case] = $blocked($directDebitReason);
}

$outputPath = __DIR__.'/uat-results.json';
file_put_contents($outputPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "UAT results saved to {$outputPath}\n";

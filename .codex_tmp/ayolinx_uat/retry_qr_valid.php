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
$invoke = static function (string $method, array $arguments = []) use ($reflection, $service): mixed {
    $refMethod = $reflection->getMethod($method);
    $refMethod->setAccessible(true);

    return $refMethod->invokeArgs($service, $arguments);
};

$token = $invoke('accessToken');
if (! is_string($token) || $token === '') {
    fwrite(STDERR, "Unable to obtain Ayolinx sandbox access token.\n");
    exit(1);
}

$path = '/v1.0/qr/qr-mpm-generate';
$timestamp = (string) $invoke('timestamp');
$externalId = now('Asia/Jakarta')->format('ymdHis').(string) random_int(100000000000, 999999999999);
$body = [
    'partnerReferenceNo' => 'qr-'.bin2hex(random_bytes(12)),
    'amount' => ['currency' => 'IDR', 'value' => '10001.00'],
    'additionalInfo' => ['channel' => 'ALTO_QRIS'],
];
$bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES);
$signature = (string) $invoke('serviceSignature', ['POST', $path, $token, $bodyJson, $timestamp]);
$partnerId = (string) config('ayolinx.client_key');

$response = Http::acceptJson()
    ->timeout((int) config('ayolinx.timeout', 20))
    ->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-TIMESTAMP' => $timestamp,
        'X-SIGNATURE' => $signature,
        'X-PARTNER-ID' => $partnerId,
        'X-EXTERNAL-ID' => $externalId,
        'CHANNEL-ID' => 'H2H',
    ])
    ->withBody($bodyJson, 'application/json')
    ->post(rtrim((string) config('ayolinx.base_url'), '/').$path);

$mask = static fn (string $value): string => strlen($value) > 8
    ? substr($value, 0, 4).'…'.substr($value, -4)
    : '[REDACTED]';

$result = [
    'executed_at' => now('Asia/Jakarta')->format('Y-m-d H:i:sP'),
    'request' => [
        'method' => 'POST',
        'url' => rtrim((string) config('ayolinx.base_url'), '/').$path,
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
        'body' => $response->json() ?? ['raw' => $response->body()],
    ],
];

$outputPath = __DIR__.'/retry-qr-valid-result.json';
file_put_contents($outputPath, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

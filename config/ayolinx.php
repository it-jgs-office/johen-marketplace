<?php

$privateKeyPath = trim((string) env('AYOLINX_PRIVATE_KEY_PATH'));
$callbackPublicKeyPath = trim((string) env('AYOLINX_CALLBACK_PUBLIC_KEY_PATH'));
if ($privateKeyPath !== '' && preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $privateKeyPath) !== 1) {
    $privateKeyPath = base_path($privateKeyPath);
}
if ($callbackPublicKeyPath !== '' && preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $callbackPublicKeyPath) !== 1) {
    $callbackPublicKeyPath = base_path($callbackPublicKeyPath);
}

return [
    'client_key' => env('AYOLINX_CLIENT_KEY'),
    'client_id' => env('AYOLINX_CLIENT_ID', env('AYOLINX_CLIENT_KEY')),
    'secret_key' => env('AYOLINX_SECRET_KEY'),
    'private_key_path' => $privateKeyPath === '' ? null : $privateKeyPath,
    'callback_public_key_path' => $callbackPublicKeyPath === '' ? null : $callbackPublicKeyPath,
    'inbound_client_key' => env('AYOLINX_INBOUND_CLIENT_KEY'),
    'inbound_token_ttl' => (int) env('AYOLINX_INBOUND_TOKEN_TTL', 3600),
    'inbound_timestamp_tolerance' => (int) env('AYOLINX_INBOUND_TIMESTAMP_TOLERANCE', 300),
    'base_url' => rtrim((string) env('AYOLINX_BASE_URL', 'https://sandbox.ayolinx.id'), '/'),
    'is_production' => (bool) env('AYOLINX_IS_PRODUCTION', false),
    'timeout' => (int) env('AYOLINX_TIMEOUT', 20),
    'expiry_minutes' => (int) env('AYOLINX_EXPIRY_MINUTES', 60),
    'qris_channel' => env('AYOLINX_QRIS_CHANNEL', 'ALTO_QRIS'),
    'channel_id' => (int) env('AYOLINX_CHANNEL_ID', 13),
];

<?php

$privateKeyPath = trim((string) env('DOKU_PRIVATE_KEY_PATH'));
if ($privateKeyPath !== '' && preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $privateKeyPath) !== 1) {
    $privateKeyPath = base_path($privateKeyPath);
}

return [
    'client_id' => env('DOKU_CLIENT_ID'),
    'secret_key' => env('DOKU_SECRET_KEY'),
    'environment' => env('DOKU_ENVIRONMENT', 'sandbox'),
    'sandbox_url' => 'https://api-sandbox.doku.com',
    'production_url' => 'https://api.doku.com',
    'payment_due_minutes' => (int) env('DOKU_PAYMENT_DUE_MINUTES', 60),
    'restrict_payment_method' => (bool) env('DOKU_RESTRICT_PAYMENT_METHOD', false),
    'timeout' => (int) env('DOKU_TIMEOUT', 20),
    // Only list channels approved for Direct API in the DOKU dashboard.
    'direct_methods' => array_values(array_filter(array_map('trim', explode(',', (string) env('DOKU_DIRECT_METHODS', ''))))),
    'private_key_path' => $privateKeyPath !== '' ? $privateKeyPath : null,
    'qris' => [
        'merchant_id' => env('DOKU_QRIS_MERCHANT_ID'),
        'terminal_id' => env('DOKU_QRIS_TERMINAL_ID'),
        'postal_code' => env('DOKU_QRIS_POSTAL_CODE'),
    ],
    'va' => collect(['bca', 'bri', 'bni', 'bsi', 'cimb', 'mandiri', 'permata'])->mapWithKeys(fn ($bank) => [
        $bank.'_va' => [
            'partner_service_id' => env('DOKU_VA_'.strtoupper($bank).'_PARTNER_SERVICE_ID'),
            'merchant_bin' => env(
                'DOKU_VA_'.strtoupper($bank).'_MERCHANT_BIN',
                env('DOKU_VA_'.strtoupper($bank).'_CUSTOMER_PREFIX')
            ),
        ],
    ])->all(),
];

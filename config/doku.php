<?php

return [
    'client_id' => env('DOKU_CLIENT_ID'),
    'secret_key' => env('DOKU_SECRET_KEY'),
    'environment' => env('DOKU_ENVIRONMENT', 'sandbox'),
    'sandbox_url' => 'https://api-sandbox.doku.com',
    'production_url' => 'https://api.doku.com',
    'payment_due_minutes' => (int) env('DOKU_PAYMENT_DUE_MINUTES', 60),
    'restrict_payment_method' => (bool) env('DOKU_RESTRICT_PAYMENT_METHOD', false),
    'timeout' => (int) env('DOKU_TIMEOUT', 20),
];

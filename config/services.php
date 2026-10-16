<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Simulasi Pembayaran
    |--------------------------------------------------------------------------
    |
    | Saat PAYMENT_SIMULATION=true: invoice Xendit tidak dibuat dan
    | transaksi Digiflazz (topUp/checkStatus) dijawab secara lokal.
    |
    */

    'payment' => [
        'simulation' => env('PAYMENT_SIMULATION', false),
        'gateway' => env('PAYMENT_GATEWAY', 'xendit'),
        /*
        |--------------------------------------------------------------------------
        | Saluran Pembayaran
        |--------------------------------------------------------------------------
        |
        | 'invoice' → pelanggan diarahkan ke halaman invoice yang di-hosting Xendit.
        | 'qris'    → QRIS dibuat dan dibayar langsung di halaman sendiri (embed QR).
        |
        */
        'channel' => env('PAYMENT_CHANNEL', 'qris'),
    ],

    /**
     * Web Push (VAPID) untuk notifikasi admin saat user mengirim live chat.
     * Generate key: php artisan -r "echo json_encode(Minishlink\WebPush\VAPID::createVapidKeys());"
     */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@johen.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'notif_brand' => env('VAPID_NOTIF_BRAND', 'Johen Gaming'),
    ],

    'jba_api' => [
        'base_url' => rtrim((string) env('JBA_API_BASE_URL', 'http://localhost:8000/api'), '/'),
        'clients' => [
            'johen' => [
                'key' => env('JBA_API_KEY_JOHEN'),
                'name' => env('JBA_API_NAME_JOHEN', 'Johen API'),
            ],
            'marketplace' => [
                'key' => env('JBA_API_KEY_MARKETPLACE'),
                'name' => env('JBA_API_NAME_MARKETPLACE', 'Marketplace API'),
            ],
        ],
    ],

];

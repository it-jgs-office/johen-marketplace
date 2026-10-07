<?php

return [
    'username' => env('DIGIFLAZZ_USERNAME'),
    'base_url' => env('DIGIFLAZZ_BASE_URL', 'https://api.digiflazz.com/v1'),
    'production' => env('DIGIFLAZZ_PRODUCTION', false),
    'development_key' => env('DIGIFLAZZ_DEVELOPMENT_KEY'),
    'production_key' => env('DIGIFLAZZ_PRODUCTION_KEY'),
    // Tetap mendukung DIGIFLAZZ_KEY agar deployment lama tidak putus.
    'key' => env('DIGIFLAZZ_PRODUCTION', false)
        ? env('DIGIFLAZZ_PRODUCTION_KEY', env('DIGIFLAZZ_KEY'))
        : env('DIGIFLAZZ_DEVELOPMENT_KEY', env('DIGIFLAZZ_KEY')),

    'sync_all_products' => true,

    /*
     * Rekonsiliasi topup yang menggantung.
     *
     * Webhook Digiflazz kadang tidak sampai (server restart, IP diblokir, dsb).
     * Tanpa penjadwal, order akan menggantung selamanya di status "processing"
     * padahal uang user sudah masuk ke saldonya. Command `digiflazz:reconcile`
     * yang menutup celah ini.
     */
    'reconcile' => [
        // Berapa menit topup dianggap masih wajar sebelum saldonya dikembalikan.
        // Topup Digiflazz yang sehat biasanya selesai di bawah 15 menit.
        'grace_minutes' => (int) env('DIGIFLAZZ_RECONCILE_GRACE_MINUTES', 30),

        // Batas poll per order, sebagai pengaman kedua selain batas waktu.
        'max_polls' => (int) env('DIGIFLAZZ_RECONCILE_MAX_POLLS', 12),

        // Berapa order diproses sekali jalan, supaya tidak membanjiri API.
        'chunk' => (int) env('DIGIFLAZZ_RECONCILE_CHUNK', 50),
    ],
];

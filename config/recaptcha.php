<?php

return [
    // reCAPTCHA keys tied to production domains reject localhost.
    'enabled' => env('RECAPTCHA_ENABLED', env('APP_ENV', 'production') !== 'local'),
    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
];

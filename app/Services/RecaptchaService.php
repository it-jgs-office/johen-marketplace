<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public function passes(?string $token, ?string $ipAddress = null): bool
    {
        $secret = (string) config('recaptcha.secret_key');

        if ($token === null || trim($token) === '' || $secret === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->connectTimeout(5)
                ->post((string) config('recaptcha.verify_url'), [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);
        } catch (\Throwable) {
            return false;
        }

        return $response->successful() && $response->boolean('success');
    }
}

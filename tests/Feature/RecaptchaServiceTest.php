<?php

namespace Tests\Feature;

use App\Services\RecaptchaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaServiceTest extends TestCase
{
    public function test_successful_verification_allows_login_to_continue(): void
    {
        config()->set('recaptcha.secret_key', 'test-secret');
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);

        $this->assertTrue(app(RecaptchaService::class)->passes('valid-token', '127.0.0.1'));
    }

    public function test_failed_verification_is_rejected(): void
    {
        config()->set('recaptcha.secret_key', 'test-secret');
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false])]);

        $this->assertFalse(app(RecaptchaService::class)->passes('invalid-token', '127.0.0.1'));
    }
}

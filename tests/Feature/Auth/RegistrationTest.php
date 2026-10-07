<?php

namespace Tests\Feature\Auth;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_requires_a_username(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Pendaftaran di aplikasi ini 2 tahap: form -> OTP -> user dibuat.
     * Jadi setelah submit form, user BELUM ada dan belum login.
     */
    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('verify-otp'));
        $response->assertSessionHas('register_data');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);

        $this->assertDatabaseHas('otp_codes', [
            'email' => 'test@example.com',
            'type' => 'register',
        ]);
    }

    public function test_user_is_created_after_verifying_the_otp(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $otp = OtpCode::where('email', 'test@example.com')
            ->where('type', 'register')
            ->firstOrFail();

        $response = $this->post(route('verify-otp'), ['otp' => $otp->otp]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success', 'Registrasi berhasil. Selamat datang!');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'username' => 'testuser',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertFalse(session()->has('register_data'));
        $this->assertFalse(session()->has('register_email'));
    }

    public function test_wrong_otp_does_not_create_the_user(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response = $this->post(route('verify-otp'), ['otp' => '000000']);

        $response->assertSessionHasErrors('otp');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_otp_cannot_be_reused(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $otp = OtpCode::where('email', 'test@example.com')
            ->where('type', 'register')
            ->firstOrFail();

        $this->post(route('verify-otp'), ['otp' => $otp->otp]);

        $this->post('/logout');
        $this->flushSession();

        // Sesi register_data sudah dihapus, jadi halaman OTP tidak lagi aktif.
        $response = $this->post(route('verify-otp'), ['otp' => $otp->otp]);
        $response->assertRedirect(route('register'));
    }
}

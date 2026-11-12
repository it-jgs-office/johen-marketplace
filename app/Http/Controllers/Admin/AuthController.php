<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecaptchaService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    private const MAX_TWO_FACTOR_ATTEMPTS = 5;

    public function showLoginForm(Request $request): View
    {
        $request->session()->forget(['admin_two_factor_user_id', 'admin_two_factor_remember']);

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $rules = [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];

        if (config('recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = ['required', 'string'];
        }

        $request->validate($rules);

        if (config('recaptcha.enabled')
            && ! app(RecaptchaService::class)->passes($request->string('g-recaptcha-response')->toString(), $request->ip())) {
            return back()->withErrors([
                'g-recaptcha-response' => 'Silakan centang verifikasi reCAPTCHA terlebih dahulu.',
            ])->onlyInput('username');
        }

        $rateLimitKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()
                ->with('error', "Login sementara dikunci. Coba lagi dalam {$seconds} detik.")
                ->onlyInput('username');
        }

        $credentials = $request->only('username', 'password');

        // Izinkan login menggunakan username atau email akun.
        if (filter_var($credentials['username'], FILTER_VALIDATE_EMAIL)) {
            $credentials['email'] = $credentials['username'];
            unset($credentials['username']);
        }

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('admin')->user();

            if ($user->isAdmin()) {
                RateLimiter::clear($rateLimitKey);

                if ($user->hasTwoFactorAuthentication()) {
                    $remember = $request->boolean('remember');
                    Auth::guard('admin')->logout();
                    $request->session()->regenerate();
                    $request->session()->put([
                        'admin_two_factor_user_id' => $user->id,
                        'admin_two_factor_remember' => $remember,
                    ]);

                    return redirect()->route('admin.two-factor.challenge');
                }

                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard'));
            }

            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->with('error', $this->recordFailedLogin($rateLimitKey, 'Akses hanya untuk admin.'))
                ->onlyInput('username');
        }

        return back()
            ->with('error', $this->recordFailedLogin($rateLimitKey, 'Username/email atau password salah.'))
            ->onlyInput('username');
    }

    public function showTwoFactorChallenge(Request $request): View|RedirectResponse
    {
        $user = $this->pendingTwoFactorUser($request);

        if (! $user) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.login', [
            'twoFactorChallenge' => true,
            'twoFactorUser' => $user,
        ]);
    }

    public function twoFactorChallenge(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $this->pendingTwoFactorUser($request);

        if (! $user) {
            return redirect()->route('admin.login')
                ->with('error', 'Sesi verifikasi telah berakhir. Silakan login kembali.');
        }

        $rateLimitKey = 'admin-two-factor|'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_TWO_FACTOR_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->with('error', "Verifikasi sementara dikunci. Coba lagi dalam {$seconds} detik.");
        }

        if (! $twoFactor->verify((string) $user->two_factor_secret, $request->string('code')->toString())) {
            RateLimiter::hit($rateLimitKey, self::LOGIN_DECAY_SECONDS);

            return back()->withErrors(['code' => 'Kode Google Authenticator tidak valid.']);
        }

        $remember = (bool) $request->session()->get('admin_two_factor_remember', false);
        $request->session()->forget(['admin_two_factor_user_id', 'admin_two_factor_remember']);
        RateLimiter::clear($rateLimitKey);
        Auth::guard('admin')->login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.index');
    }

    private function recordFailedLogin(string $rateLimitKey, string $message): string
    {
        RateLimiter::hit($rateLimitKey, self::LOGIN_DECAY_SECONDS);

        $remainingAttempts = RateLimiter::remaining($rateLimitKey, self::MAX_LOGIN_ATTEMPTS);

        if ($remainingAttempts === 0) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return "{$message} Batas ".self::MAX_LOGIN_ATTEMPTS." percobaan tercapai. Coba lagi dalam {$seconds} detik.";
        }

        return "{$message} Sisa percobaan login: {$remainingAttempts} dari ".self::MAX_LOGIN_ATTEMPTS.'.';
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('username')->toString())).'|'.$request->ip();
    }

    private function pendingTwoFactorUser(Request $request): ?User
    {
        $userId = $request->session()->get('admin_two_factor_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user?->hasTwoFactorAuthentication()) {
            $request->session()->forget(['admin_two_factor_user_id', 'admin_two_factor_remember']);

            return null;
        }

        return $user;
    }
}

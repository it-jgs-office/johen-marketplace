<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RecaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
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

        $credentials = $request->only('username', 'password');

        // Izinkan login menggunakan username atau email akun.
        if (filter_var($credentials['username'], FILTER_VALIDATE_EMAIL)) {
            $credentials['email'] = $credentials['username'];
            unset($credentials['username']);
        }

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard('admin')->user();

            if ($user->isAdmin()) {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard'));
            }

            if ($user->isLiveChatAdmin()) {
                $request->session()->regenerate();
                return redirect()->intended(route('lcadmin.conversations'));
            }

            if ($user->isLiveChatCs()) {
                $request->session()->regenerate();
                return redirect()->intended(route('csadmin.conversations'));
            }

            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'username' => 'Akses hanya untuk admin.',
            ])->onlyInput('username');
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.index');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\RecaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if (config('recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = ['required', 'string'];
        }

        $request->validate($rules);

        if (config('recaptcha.enabled')
            && ! app(RecaptchaService::class)->passes($request->string('g-recaptcha-response')->toString(), $request->ip())) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Silakan centang verifikasi reCAPTCHA terlebih dahulu.',
            ]);
        }

        $data = $request->only('name', 'username', 'email', 'password');

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'email' => $data['email'],
            'otp' => $otp,
            'type' => 'register',
            'expires_at' => now()->addMinutes(5),
        ]);

        session(['register_data' => $data, 'register_email' => $data['email']]);

        Mail::to($data['email'])->send(new SendOtpMail($otp, $data['name']));

        return redirect()->route('verify-otp');
    }
}

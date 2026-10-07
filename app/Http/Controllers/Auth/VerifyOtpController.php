<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\Order;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VerifyOtpController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (!session()->has('register_data')) {
            return redirect()->route('register');
        }

        return view('auth.verify-otp');
    }

    public function verify(Request $request): RedirectResponse
    {
        $registerData = session('register_data');

        if (!$registerData) {
            return redirect()->route('register')->with('error', 'Sesi registrasi tidak ditemukan. Silakan daftar ulang.');
        }

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $otpRecord = OtpCode::where('email', $registerData['email'])
            ->where('otp', $request->otp)
            ->where('type', 'register')
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$otpRecord || !$otpRecord->isValid()) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'])->withInput();
        }

        $otpRecord->update(['used_at' => now()]);

        $user = User::create([
            'name' => $registerData['name'],
            'username' => $registerData['username'],
            'email' => $registerData['email'],
            'password' => Hash::make($registerData['password']),
        ]);

        event(new Registered($user));

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->forget(['register_data', 'register_email']);

        Order::whereNull('user_id')
            ->where('email', $user->email)
            ->update(['user_id' => $user->id]);

        return redirect()->route('home')->with('success', 'Registrasi berhasil. Selamat datang!');
    }

    public function resend(Request $request): JsonResponse
    {
        $registerData = session('register_data');

        if (!$registerData) {
            return response()->json(['success' => false, 'message' => 'Sesi tidak ditemukan.'], 400);
        }

        $email = $registerData['email'];

        OtpCode::where('email', $email)
            ->where('type', 'register')
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'email' => $email,
            'otp' => $otp,
            'type' => 'register',
            'expires_at' => now()->addMinutes(5),
        ]);

        try {
            Mail::to($email)->send(new SendOtpMail($otp, $registerData['name'], 'register'));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengirim email. Silakan coba lagi.']);
        }

        return response()->json(['success' => true]);
    }
}

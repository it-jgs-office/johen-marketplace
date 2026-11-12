<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CompanyAuthController extends Controller
{
    public function show(Request $request)
    {
        $origin = (string) ($request->query('origin') ?: $request->session()->get('company_auth_origin'));
        abort_unless(in_array($origin, config('services.company_auth.allowed_origins', []), true), 403);

        $request->session()->put('company_auth_origin', $origin);

        if (! Auth::check()) {
            $request->session()->put('url.intended', route('company-auth'));

            return redirect()->route('login');
        }

        abort_if($request->user()->isAdmin(), 403, 'Akun admin tidak dapat dipakai di live chat pelanggan.');

        $code = Str::random(64);
        Cache::put('company-auth-code:'.hash('sha256', $code), [
            'user_id' => $request->user()->id,
            'origin' => $origin,
        ], now()->addMinutes(2));

        $request->attributes->set('company_auth_origin', $origin);
        $request->session()->forget('company_auth_origin');

        return response()->view('auth.company-auth-complete', compact('origin', 'code'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:64']]);
        $payload = Cache::pull('company-auth-code:'.hash('sha256', $data['code']));
        abort_unless(is_array($payload), 422, 'Kode login tidak valid atau sudah kedaluwarsa.');

        $user = User::find($payload['user_id'] ?? null);
        abort_unless($user && ! $user->isAdmin(), 403);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => (bool) $user->email_verified_at,
        ]);
    }
}

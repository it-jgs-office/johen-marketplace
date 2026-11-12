<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function index(Request $request): View
    {
        $this->ensureMainAdmin();

        $admins = User::query()->where('is_admin', true)->orderBy('name')->get();
        $setupUser = null;
        $setupSecret = null;
        $qrCode = null;
        $setupId = $request->integer('setup');

        if ($setupId > 0) {
            $setupUser = $admins->firstWhere('id', $setupId);
            $setupSecret = $request->session()->get("admin_two_factor_setup.{$setupId}");

            if ($setupUser && is_string($setupSecret) && $setupSecret !== '') {
                $uri = $this->twoFactor->provisioningUri($setupSecret, $setupUser->email);
                $qrCode = $this->twoFactor->qrDataUri($uri);
            }
        }

        return view('admin.two-factor.index', compact('admins', 'setupUser', 'setupSecret', 'qrCode'));
    }

    public function setup(Request $request, User $user): RedirectResponse
    {
        $this->ensureMainAdmin();
        abort_unless($user->isAdmin(), 404);

        $request->session()->put("admin_two_factor_setup.{$user->id}", $this->twoFactor->generateSecret());

        return redirect()->route('admin.two-factor.index', ['setup' => $user->id]);
    }

    public function confirm(Request $request, User $user): RedirectResponse
    {
        $this->ensureMainAdmin();
        abort_unless($user->isAdmin(), 404);

        $request->validate(['code' => ['required', 'digits:6']]);
        $secret = $request->session()->get("admin_two_factor_setup.{$user->id}");

        if (! is_string($secret) || $secret === '') {
            return redirect()->route('admin.two-factor.index')
                ->with('error', 'Sesi penautan sudah berakhir. Buat QR baru.');
        }

        if (! $this->twoFactor->verify($secret, $request->string('code')->toString())) {
            return redirect()->route('admin.two-factor.index', ['setup' => $user->id])
                ->withErrors(['code' => 'Kode Google Authenticator tidak valid.']);
        }

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget("admin_two_factor_setup.{$user->id}");

        return redirect()->route('admin.two-factor.index')
            ->with('success', "Google Authenticator untuk {$user->name} berhasil ditautkan.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureMainAdmin();
        abort_unless($user->isAdmin(), 404);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        $request->session()->forget("admin_two_factor_setup.{$user->id}");

        return redirect()->route('admin.two-factor.index')
            ->with('success', "2FA untuk {$user->name} dinonaktifkan.");
    }

    private function ensureMainAdmin(): void
    {
        abort_unless(Auth::guard('admin')->user()?->isMainAdmin(), 403);
    }
}

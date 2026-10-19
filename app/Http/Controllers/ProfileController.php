<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ImageOptimizer;
use App\Services\MediaStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'pointBalance' => app(\App\Services\BalanceService::class)->balanceFor($request->user()->id),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $oldAvatar = $user->avatar;

        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->hasFile('avatar')) {
            $user->avatar = ImageOptimizer::storeOptimized(
                $request->file('avatar'),
                'avatars',
                512,
                512,
                256 * 1024,
            );
        } elseif ($request->boolean('remove_avatar')) {
            $user->avatar = null;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($oldAvatar !== $user->avatar) {
            $this->deleteLocalAvatar($oldAvatar);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $this->deleteLocalAvatar($user->avatar);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function deleteLocalAvatar(?string $avatar): void
    {
        $path = MediaStore::normalizePath($avatar);

        if ($path && str_starts_with($path, 'avatars/')) {
            MediaStore::delete($path);
        }
    }
}

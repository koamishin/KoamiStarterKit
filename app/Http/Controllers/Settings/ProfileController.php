<?php

namespace App\Http\Controllers\Settings;

use App\Features\FeatureRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        FeatureRegistry::initialize();

        $user = $request->user();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $profileUpdateRequest): RedirectResponse
    {
        $user = $profileUpdateRequest->user();

        if (! FeatureRegistry::isFeatureAvailableForUser($user, 'settings_profile')) {
            return to_route('profile.edit')->with('error', 'Profile editing is not available for your role');
        }

        $profileUpdateRequest->user()->fill($profileUpdateRequest->validated());

        if ($profileUpdateRequest->user()->isDirty('email')) {
            $profileUpdateRequest->user()->email_verified_at = null;
        }

        $profileUpdateRequest->user()->save();

        return to_route('profile.edit');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:1024'],
        ]);

        $user = $request->user();

        if (! FeatureRegistry::isFeatureAvailableForUser($user, 'settings_profile')) {
            return to_route('profile.edit')->with('error', 'Profile editing is not available for your role');
        }

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store('profile-photos', 'public');

        $user->update(['profile_photo_path' => $path]);

        return to_route('profile.edit');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! FeatureRegistry::isFeatureAvailableForUser($user, 'settings_profile')) {
            return to_route('profile.edit')->with('error', 'Profile editing is not available for your role');
        }

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        return to_route('profile.edit');
    }

    public function destroy(ProfileDeleteRequest $profileDeleteRequest): RedirectResponse
    {
        $user = $profileDeleteRequest->user();

        if (! FeatureRegistry::isFeatureAvailableForUser($user, 'settings_account_deletion')) {
            return to_route('profile.edit')->with('error', 'Account deletion is not available for your role');
        }

        Auth::logout();

        $user->delete();

        $profileDeleteRequest->session()->invalidate();
        $profileDeleteRequest->session()->regenerateToken();

        return redirect('/');
    }
}

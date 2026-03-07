<?php

namespace App\Http\Controllers\Settings;

use App\Features\FeatureRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(Request $request): Response
    {
        FeatureRegistry::initialize();
        $user = $request->user();

        return Inertia::render('settings/Password', [
            'availableFeatures' => [
                'password' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_password'),
            ],
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $passwordUpdateRequest): RedirectResponse
    {
        $user = $passwordUpdateRequest->user();

        if (! FeatureRegistry::isFeatureAvailableForUser($user, 'settings_password')) {
            return back()->with('error', 'Password changes are not available for your role');
        }

        $passwordUpdateRequest->user()->update([
            'password' => $passwordUpdateRequest->password,
        ]);

        return back();
    }
}

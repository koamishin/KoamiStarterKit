<?php

namespace App\Http\Controllers\Settings;

use App\Features\FeatureRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\PanelRegistry;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
    public function edit(TwoFactorAuthenticationRequest $twoFactorAuthenticationRequest): Response
    {
        FeatureRegistry::initialize();

        filament()->setCurrentPanel('admin');

        $panel = app(PanelRegistry::class)->get('admin');
        $providers = $panel?->getMultiFactorAuthenticationProviders() ?? [];

        $appProvider = $providers['app'] ?? null;
        $emailProvider = $providers['email_code'] ?? null;

        $user = $twoFactorAuthenticationRequest->user();
        $mfaAppAvailable = FeatureRegistry::isFeatureAvailableForUser($user, 'settings_mfa_app');
        $mfaEmailAvailable = FeatureRegistry::isFeatureAvailableForUser($user, 'settings_mfa_email');

        $props = [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'emailVerified' => $user instanceof MustVerifyEmail ? $user->hasVerifiedEmail() : true,
            'availableFeatures' => [
                'mfaApp' => $mfaAppAvailable,
                'mfaEmail' => $mfaEmailAvailable,
            ],
            'filamentMfa' => [
                'providers' => [
                    'app' => $mfaAppAvailable && ($appProvider instanceof AppAuthentication),
                    'email' => $mfaEmailAvailable && ($emailProvider instanceof EmailAuthentication),
                ],
                'state' => [
                    'app' => method_exists($user, 'getAppAuthenticationSecret') && filled($user->getAppAuthenticationSecret()),
                    'email' => method_exists($user, 'hasEmailAuthentication') && $user->hasEmailAuthentication(),
                ],
                'options' => [
                    'appRecoveryCodes' => $appProvider instanceof AppAuthentication && $appProvider->isRecoverable(),
                ],
            ],
            /* @chisel-2fa */
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            /* @end-chisel-2fa */
            /* @chisel-passkeys */
            'canManagePasskeys' => Features::canManagePasskeys(),
            'passkeys' => Features::canManagePasskeys()
                ? $twoFactorAuthenticationRequest->user()
                    ->passkeys()
                    ->latest()
                    ->get()
                    ->map(fn ($passkey): array => [
                        'id' => (string) $passkey->getKey(),
                        'name' => $passkey->name,
                        'authenticator' => $passkey->authenticator,
                        'last_used_at' => $passkey->last_used_at?->toIso8601String(),
                        'created_at' => $passkey->created_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all()
                : [],
            /* @end-chisel-passkeys */
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];

        /* @chisel-2fa */
        if (Features::canManageTwoFactorAuthentication()) {
            $twoFactorAuthenticationRequest->ensureStateIsValid();

            $props['twoFactorEnabled'] = $twoFactorAuthenticationRequest->user()->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
        /* @end-chisel-2fa */

        return Inertia::render('settings/Security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $passwordUpdateRequest): RedirectResponse
    {
        FeatureRegistry::initialize();

        $user = $passwordUpdateRequest->user();

        if (! FeatureRegistry::isFeatureAvailableForUser($user, 'settings_password')) {
            return back()->with('error', 'Password changes are not available for your role');
        }

        $user->update([
            'password' => $passwordUpdateRequest->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}

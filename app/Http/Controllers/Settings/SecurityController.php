<?php

namespace App\Http\Controllers\Settings;

use App\Features\FeatureRegistry;
use App\Http\Controllers\Controller;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function edit(Request $request): Response
    {
        FeatureRegistry::initialize();

        filament()->setCurrentPanel('admin');

        $panel = Filament::getPanel('admin');
        $providers = $panel?->getMultiFactorAuthenticationProviders() ?? [];

        $appProvider = $providers['app'] ?? null;
        $emailProvider = $providers['email_code'] ?? null;

        $user = $request->user();
        $mfaAppAvailable = FeatureRegistry::isFeatureAvailableForUser($user, 'settings_mfa_app');
        $mfaEmailAvailable = FeatureRegistry::isFeatureAvailableForUser($user, 'settings_mfa_email');

        return Inertia::render('settings/Security', [
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
        ]);
    }
}

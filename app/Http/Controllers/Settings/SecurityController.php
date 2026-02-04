<?php

namespace App\Http\Controllers\Settings;

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
        filament()->setCurrentPanel('admin');

        $panel = Filament::getPanel('admin');
        $providers = $panel?->getMultiFactorAuthenticationProviders() ?? [];

        $appProvider = $providers['app'] ?? null;
        $emailProvider = $providers['email_code'] ?? null;

        $user = $request->user();

        return Inertia::render('settings/Security', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'emailVerified' => $user instanceof MustVerifyEmail ? $user->hasVerifiedEmail() : true,
            'filamentMfa' => [
                'providers' => [
                    'app' => $appProvider instanceof AppAuthentication,
                    'email' => $emailProvider instanceof EmailAuthentication,
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


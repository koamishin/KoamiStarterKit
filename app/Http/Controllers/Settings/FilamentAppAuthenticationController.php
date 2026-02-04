<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\FilamentAppAuthenticationEnableRequest;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FilamentAppAuthenticationController extends Controller
{
    public function setup(Request $request): JsonResponse
    {
        $provider = $this->getAppAuthenticationProvider();

        $user = $request->user();

        if (! $user instanceof HasAppAuthentication) {
            abort(500);
        }

        $secret = $provider->generateSecret();

        $recoveryCodes = $provider->isRecoverable()
            ? $provider->generateRecoveryCodes()
            : null;

        $encrypted = encrypt([
            'secret' => $secret,
            'recoveryCodes' => $recoveryCodes,
            'userId' => $user->getAuthIdentifier(),
        ]);

        return response()->json([
            'encrypted' => $encrypted,
            'secret' => $secret,
            'qrCodeDataUri' => $provider->generateQrCodeDataUri($secret),
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function enable(FilamentAppAuthenticationEnableRequest $request): JsonResponse
    {
        $provider = $this->getAppAuthenticationProvider();

        $user = $request->user();

        if (
            (! $user instanceof Authenticatable)
            || (! $user instanceof HasAppAuthentication)
            || (! $user instanceof HasAppAuthenticationRecovery)
        ) {
            abort(500);
        }

        $encrypted = decrypt($request->validated('encrypted'));

        if (($encrypted['userId'] ?? null) !== $user->getAuthIdentifier()) {
            abort(403);
        }

        if (! $provider->verifyCode($request->validated('code'), $encrypted['secret'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => __('The provided authentication code is invalid.'),
            ]);
        }

        DB::transaction(function () use ($encrypted, $provider, $user): void {
            $provider->saveSecret($user, $encrypted['secret'] ?? null);

            if ($provider->isRecoverable()) {
                $provider->saveRecoveryCodes($user, $encrypted['recoveryCodes'] ?? null);
            }
        });

        return response()->json([
            'recoveryCodes' => $encrypted['recoveryCodes'] ?? null,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $provider = $this->getAppAuthenticationProvider();

        $user = $request->user();

        if (
            (! $user instanceof Authenticatable)
            || (! $user instanceof HasAppAuthentication)
            || (! $user instanceof HasAppAuthenticationRecovery)
        ) {
            abort(500);
        }

        DB::transaction(function () use ($provider, $user): void {
            $provider->saveSecret($user, null);
            $provider->saveRecoveryCodes($user, null);
        });

        return response()->json();
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $provider = $this->getAppAuthenticationProvider();

        $user = $request->user();

        if ((! $user instanceof Authenticatable) || (! $user instanceof HasAppAuthenticationRecovery)) {
            abort(500);
        }

        if (! $provider->isRecoverable()) {
            abort(404);
        }

        $recoveryCodes = $provider->generateRecoveryCodes();

        DB::transaction(function () use ($provider, $recoveryCodes, $user): void {
            $provider->saveRecoveryCodes($user, $recoveryCodes);
        });

        return response()->json([
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    private function getAppAuthenticationProvider(): AppAuthentication
    {
        filament()->setCurrentPanel('admin');

        $panel = Filament::getPanel('admin');

        if (! $panel) {
            abort(404);
        }

        $provider = $panel->getMultiFactorAuthenticationProviders()['app'] ?? null;

        if (! $provider instanceof AppAuthentication) {
            abort(404);
        }

        return $provider;
    }
}

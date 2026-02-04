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
        $appAuthentication = $this->getAppAuthenticationProvider();

        $user = $request->user();

        if (! $user instanceof HasAppAuthentication) {
            abort(500);
        }

        $secret = $appAuthentication->generateSecret();

        $recoveryCodes = $appAuthentication->isRecoverable()
            ? $appAuthentication->generateRecoveryCodes()
            : null;

        $encrypted = encrypt([
            'secret' => $secret,
            'recoveryCodes' => $recoveryCodes,
            'userId' => $user->getAuthIdentifier(),
        ]);

        return response()->json([
            'encrypted' => $encrypted,
            'secret' => $secret,
            'qrCodeDataUri' => $appAuthentication->generateQrCodeDataUri($secret),
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function enable(FilamentAppAuthenticationEnableRequest $filamentAppAuthenticationEnableRequest): JsonResponse
    {
        $appAuthentication = $this->getAppAuthenticationProvider();

        $user = $filamentAppAuthenticationEnableRequest->user();

        if (
            (! $user instanceof Authenticatable)
            || (! $user instanceof HasAppAuthentication)
            || (! $user instanceof HasAppAuthenticationRecovery)
        ) {
            abort(500);
        }

        $encrypted = decrypt($filamentAppAuthenticationEnableRequest->validated('encrypted'));

        if (($encrypted['userId'] ?? null) !== $user->getAuthIdentifier()) {
            abort(403);
        }

        if (! $appAuthentication->verifyCode($filamentAppAuthenticationEnableRequest->validated('code'), $encrypted['secret'] ?? null)) {
            throw ValidationException::withMessages([
                'code' => __('The provided authentication code is invalid.'),
            ]);
        }

        DB::transaction(function () use ($encrypted, $appAuthentication, $user): void {
            $appAuthentication->saveSecret($user, $encrypted['secret'] ?? null);

            if ($appAuthentication->isRecoverable()) {
                $appAuthentication->saveRecoveryCodes($user, $encrypted['recoveryCodes'] ?? null);
            }
        });

        return response()->json([
            'recoveryCodes' => $encrypted['recoveryCodes'] ?? null,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $appAuthentication = $this->getAppAuthenticationProvider();

        $user = $request->user();

        if (
            (! $user instanceof Authenticatable)
            || (! $user instanceof HasAppAuthentication)
            || (! $user instanceof HasAppAuthenticationRecovery)
        ) {
            abort(500);
        }

        DB::transaction(function () use ($appAuthentication, $user): void {
            $appAuthentication->saveSecret($user, null);
            $appAuthentication->saveRecoveryCodes($user, null);
        });

        return response()->json();
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $appAuthentication = $this->getAppAuthenticationProvider();

        $user = $request->user();

        if ((! $user instanceof Authenticatable) || (! $user instanceof HasAppAuthenticationRecovery)) {
            abort(500);
        }

        if (! $appAuthentication->isRecoverable()) {
            abort(404);
        }

        $recoveryCodes = $appAuthentication->generateRecoveryCodes();

        DB::transaction(function () use ($appAuthentication, $recoveryCodes, $user): void {
            $appAuthentication->saveRecoveryCodes($user, $recoveryCodes);
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

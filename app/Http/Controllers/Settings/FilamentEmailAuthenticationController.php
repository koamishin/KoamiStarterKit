<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\FilamentEmailAuthenticationEnableRequest;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FilamentEmailAuthenticationController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $provider = $this->getEmailAuthenticationProvider();

        $user = $request->user();

        if ((! $user instanceof Authenticatable) || (! $user instanceof HasEmailAuthentication)) {
            abort(500);
        }

        if ($user instanceof MustVerifyEmail && (! $user->hasVerifiedEmail())) {
            throw ValidationException::withMessages([
                'email' => __('Please verify your email address before enabling email authentication.'),
            ]);
        }

        if (! $provider->sendCode($user)) {
            return response()->json([
                'message' => __('Too many requests. Please wait before requesting another code.'),
            ], 429);
        }

        return response()->json();
    }

    public function resend(Request $request): JsonResponse
    {
        return $this->start($request);
    }

    public function enable(FilamentEmailAuthenticationEnableRequest $request): JsonResponse
    {
        $provider = $this->getEmailAuthenticationProvider();

        $user = $request->user();

        if ((! $user instanceof Authenticatable) || (! $user instanceof HasEmailAuthentication)) {
            abort(500);
        }

        if (! $provider->verifyCode($request->validated('code'))) {
            throw ValidationException::withMessages([
                'code' => __('The provided authentication code is invalid.'),
            ]);
        }

        DB::transaction(function () use ($provider, $user): void {
            $provider->enableEmailAuthentication($user);
        });

        return response()->json();
    }

    public function disable(Request $request): JsonResponse
    {
        $this->getEmailAuthenticationProvider();

        $user = $request->user();

        if ((! $user instanceof Authenticatable) || (! $user instanceof HasEmailAuthentication)) {
            abort(500);
        }

        DB::transaction(function () use ($user): void {
            $user->toggleEmailAuthentication(false);
        });

        return response()->json();
    }

    private function getEmailAuthenticationProvider(): EmailAuthentication
    {
        filament()->setCurrentPanel('admin');

        $panel = Filament::getPanel('admin');

        if (! $panel) {
            abort(404);
        }

        $provider = $panel->getMultiFactorAuthenticationProviders()['email_code'] ?? null;

        if (! $provider instanceof EmailAuthentication) {
            abort(404);
        }

        return $provider;
    }
}

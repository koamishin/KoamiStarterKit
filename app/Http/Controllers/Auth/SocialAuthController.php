<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\SocialLoginProvider;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Settings\ApplicationFeaturesSettings;
use App\Settings\SocialLoginSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SocialLoginSettings $socialLoginSettings,
        private readonly ApplicationFeaturesSettings $applicationFeaturesSettings,
    ) {}

    public function redirect(Request $request, string $provider): RedirectResponse|Response
    {
        $providerEnum = SocialLoginProvider::tryFromSlug($provider);

        if (!$providerEnum instanceof \App\Enums\SocialLoginProvider) {
            abort(404);
        }

        if (! $this->socialLoginSettings->isProviderEnabled($providerEnum)) {
            return redirect()
                ->route('login')
                ->withErrors(['social' => 'This social login provider is not enabled.']);
        }

        $credentials = $this->socialLoginSettings->resolveCredentials($providerEnum);
        if ($credentials === null) {
            return redirect()
                ->route('login')
                ->withErrors(['social' => 'This social login provider is not configured.']);
        }

        config([
            "services.{$providerEnum->value}.client_id" => $credentials['client_id'],
            "services.{$providerEnum->value}.client_secret" => $credentials['client_secret'],
            "services.{$providerEnum->value}.redirect" => $credentials['redirect'],
        ]);

        $driver = $this->buildDriver($providerEnum);

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $providerEnum = SocialLoginProvider::tryFromSlug($provider);

        if (!$providerEnum instanceof \App\Enums\SocialLoginProvider) {
            abort(404);
        }

        if (! $this->socialLoginSettings->isProviderEnabled($providerEnum)) {
            return redirect()
                ->route('login')
                ->withErrors(['social' => 'This social login provider is not enabled.']);
        }

        $credentials = $this->socialLoginSettings->resolveCredentials($providerEnum);
        if ($credentials === null) {
            return redirect()
                ->route('login')
                ->withErrors(['social' => 'This social login provider is not configured.']);
        }

        config([
            "services.{$providerEnum->value}.client_id" => $credentials['client_id'],
            "services.{$providerEnum->value}.client_secret" => $credentials['client_secret'],
            "services.{$providerEnum->value}.redirect" => $credentials['redirect'],
        ]);

        try {
            $socialUser = $this->buildDriver($providerEnum)->user();
        } catch (Throwable $e) {
            Log::warning('Social login callback failed', [
                'provider' => $providerEnum->value,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('login')
                ->withErrors(['social' => 'Unable to authenticate with '.($providerEnum->label()).'. Please try again.']);
        }

        $user = DB::transaction(function () use ($providerEnum, $socialUser): User {
            $existingAccount = SocialAccount::query()
                ->where('provider', $providerEnum->value)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($existingAccount instanceof SocialAccount) {
                $user = $existingAccount->user;
            } else {
                $user = User::query()->where('email', $socialUser->getEmail())->first();

                if ($user instanceof User) {
                    if ($user->email_verified_at === null) {
                        abort(409, 'A user with that email exists but is not yet verified. Please verify your email first.');
                    }
                } else {
                    $user = $this->createUserFromSocial($socialUser);
                }
            }

            $this->upsertSocialAccount($user, $providerEnum, $socialUser);

            $this->refreshProfilePhoto($user, $socialUser);

            return $user;
        });

        if (isset($user) && $user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function buildDriver(SocialLoginProvider $socialLoginProvider): ProviderContract
    {
        return Socialite::driver($socialLoginProvider->value);
    }

    private function createUserFromSocial(\Laravel\Socialite\Contracts\User $socialUser): User
    {
        $defaultRole = $this->applicationFeaturesSettings->default_user_role;

        $user = User::query()->create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'password' => null,
            'email_verified_at' => now(),
        ]);

        if (filled($defaultRole)) {
            $user->assignRole($defaultRole);
        } else {
            $user->assignRole('user');
        }

        return $user;
    }

    private function upsertSocialAccount(User $user, SocialLoginProvider $socialLoginProvider, \Laravel\Socialite\Contracts\User $socialUser): SocialAccount
    {
        return SocialAccount::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $socialLoginProvider->value,
            ],
            [
                'provider_id' => $socialUser->getId(),
                'nickname' => $socialUser->getNickname(),
                'avatar_url' => $socialUser->getAvatar(),
                'access_token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken,
                'expires_at' => $socialUser->expiresIn ? now()->addSeconds((int) $socialUser->expiresIn) : null,
            ],
        );
    }

    private function refreshProfilePhoto(User $user, \Laravel\Socialite\Contracts\User $socialUser): void
    {
        $avatar = $socialUser->getAvatar();

        if (! filled($avatar)) {
            return;
        }

        $user->forceFill(['profile_photo_path' => $avatar])->save();
    }
}

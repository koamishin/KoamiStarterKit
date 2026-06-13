<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\SocialLoginProvider;
use Spatie\LaravelSettings\Settings;

class SocialLoginSettings extends Settings
{
    public ?string $github_client_id = null;

    public ?string $github_client_secret = null;

    public ?string $github_redirect_uri = null;

    public bool $github_enabled = false;

    public ?string $google_client_id = null;

    public ?string $google_client_secret = null;

    public ?string $google_redirect_uri = null;

    public bool $google_enabled = false;

    public ?string $facebook_client_id = null;

    public ?string $facebook_client_secret = null;

    public ?string $facebook_redirect_uri = null;

    public bool $facebook_enabled = false;

    public static function group(): string
    {
        return 'social_login';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'github_client_id' => null,
            'github_client_secret' => null,
            'github_redirect_uri' => null,
            'github_enabled' => false,
            'google_client_id' => null,
            'google_client_secret' => null,
            'google_redirect_uri' => null,
            'google_enabled' => false,
            'facebook_client_id' => null,
            'facebook_client_secret' => null,
            'facebook_redirect_uri' => null,
            'facebook_enabled' => false,
        ];
    }

    public function isProviderEnabled(SocialLoginProvider $provider): bool
    {
        $enabled = match ($provider) {
            SocialLoginProvider::Github => $this->github_enabled,
            SocialLoginProvider::Google => $this->google_enabled,
            SocialLoginProvider::Facebook => $this->facebook_enabled,
        };

        if (! $enabled) {
            return false;
        }

        return $this->resolveCredentials($provider) !== null;
    }

    /**
     * Resolve credentials with .env precedence: if .env has a client id, .env wins.
     *
     * @return array{client_id: string, client_secret: string, redirect: string}|null
     */
    public function resolveCredentials(SocialLoginProvider $provider): ?array
    {
        $envClientId = config("services.{$provider->value}.client_id");
        $envClientSecret = config("services.{$provider->value}.client_secret");
        $envRedirect = config("services.{$provider->value}.redirect");

        if (filled($envClientId) && filled($envClientSecret)) {
            return [
                'client_id' => (string) $envClientId,
                'client_secret' => (string) $envClientSecret,
                'redirect' => filled($envRedirect)
                    ? (string) $envRedirect
                    : url($provider->defaultRedirectPath()),
            ];
        }

        $clientId = match ($provider) {
            SocialLoginProvider::Github => $this->github_client_id,
            SocialLoginProvider::Google => $this->google_client_id,
            SocialLoginProvider::Facebook => $this->facebook_client_id,
        };

        $clientSecret = match ($provider) {
            SocialLoginProvider::Github => $this->github_client_secret,
            SocialLoginProvider::Google => $this->google_client_secret,
            SocialLoginProvider::Facebook => $this->facebook_client_secret,
        };

        $redirect = match ($provider) {
            SocialLoginProvider::Github => $this->github_redirect_uri,
            SocialLoginProvider::Google => $this->google_redirect_uri,
            SocialLoginProvider::Facebook => $this->facebook_redirect_uri,
        };

        if (! filled($clientId) || ! filled($clientSecret)) {
            return null;
        }

        return [
            'client_id' => (string) $clientId,
            'client_secret' => (string) $clientSecret,
            'redirect' => filled($redirect) ? (string) $redirect : url($provider->defaultRedirectPath()),
        ];
    }

    public function isUsingEnv(SocialLoginProvider $provider): bool
    {
        $clientId = config("services.{$provider->value}.client_id");
        $clientSecret = config("services.{$provider->value}.client_secret");

        return filled($clientId) && filled($clientSecret);
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialLoginProvider: string
{
    case Github = 'github';
    case Google = 'google';
    case Facebook = 'facebook';

    public function label(): string
    {
        return match ($this) {
            self::Github => 'GitHub',
            self::Google => 'Google',
            self::Facebook => 'Facebook',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Github => 'OAuth via GitHub accounts',
            self::Google => 'OAuth via Google accounts',
            self::Facebook => 'OAuth via Facebook accounts',
        };
    }

    public function heroicon(): string
    {
        return match ($this) {
            self::Github => 'selfhst-github',
            self::Google => 'selfhst-google',
            self::Facebook => 'selfhst-facebook',
        };
    }

    public function driver(): string
    {
        return $this->value;
    }

    public function defaultRedirectPath(): string
    {
        return "/auth/{$this->value}/callback";
    }

    /**
     * @return array<string, string>
     */
    public function envKeys(): array
    {
        $upper = strtoupper($this->value);

        return [
            'client_id' => "{$upper}_CLIENT_ID",
            'client_secret' => "{$upper}_CLIENT_SECRET",
            'redirect' => "{$upper}_REDIRECT_URI",
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryFromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $slug) {
                return $case;
            }
        }

        return null;
    }
}

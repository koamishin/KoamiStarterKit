<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SystemSettings extends Settings
{
    public string $site_name;

    public string $site_description;

    public ?string $site_logo_url = null;

    public ?string $site_favicon_url = null;

    public string $timezone;

    public string $date_format;

    public string $time_format;

    public bool $registration_enabled;

    public bool $email_verification_required;

    public bool $two_factor_authentication_enabled;

    public ?string $default_user_role = null;

    public static function group(): string
    {
        return 'system';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'site_name' => config('app.name', 'KoamiStarterKit'),
            'site_description' => 'A modern Laravel application starter kit',
            'site_logo_url' => null,
            'site_favicon_url' => null,
            'timezone' => config('app.timezone', 'UTC'),
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'registration_enabled' => true,
            'email_verification_required' => true,
            'two_factor_authentication_enabled' => true,
            'default_user_role' => 'user',
        ];
    }
}

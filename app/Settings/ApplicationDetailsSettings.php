<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ApplicationDetailsSettings extends Settings
{
    public string $site_name;

    public string $site_description;

    public ?string $site_logo_url = null;

    public ?string $site_favicon_url = null;

    public string $timezone;

    public string $date_format;

    public string $time_format;

    public ?string $contact_email = null;

    public ?string $support_url = null;

    public static function group(): string
    {
        return 'application_details';
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
            'contact_email' => null,
            'support_url' => null,
        ];
    }
}

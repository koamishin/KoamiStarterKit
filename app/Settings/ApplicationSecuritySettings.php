<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ApplicationSecuritySettings extends Settings
{
    public int $password_min_length;

    public bool $password_requires_uppercase;

    public bool $password_requires_lowercase;

    public bool $password_requires_numbers;

    public bool $password_requires_symbols;

    public int $session_lifetime;

    public bool $single_session;

    public int $login_rate_limit;

    public int $login_rate_limit_decay;

    public static function group(): string
    {
        return 'application_security';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'password_min_length' => 8,
            'password_requires_uppercase' => true,
            'password_requires_lowercase' => true,
            'password_requires_numbers' => true,
            'password_requires_symbols' => false,
            'session_lifetime' => 120,
            'single_session' => false,
            'login_rate_limit' => 5,
            'login_rate_limit_decay' => 60,
        ];
    }
}

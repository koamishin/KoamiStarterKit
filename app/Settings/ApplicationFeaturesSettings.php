<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ApplicationFeaturesSettings extends Settings
{
    public bool $registration_enabled;

    public bool $email_verification_required;

    public bool $two_factor_authentication_enabled;

    public bool $password_reset_enabled;

    public bool $user_impersonation_enabled;

    public ?string $default_user_role = null;

    public bool $activity_log_enabled;

    public bool $notifications_enabled;

    public string $auth_layout;

    public static function group(): string
    {
        return 'application_features';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'registration_enabled' => true,
            'email_verification_required' => true,
            'two_factor_authentication_enabled' => true,
            'password_reset_enabled' => true,
            'user_impersonation_enabled' => true,
            'default_user_role' => 'user',
            'activity_log_enabled' => true,
            'notifications_enabled' => true,
            'auth_layout' => 'simple',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getAvailableLayouts(): array
    {
        return [
            'simple' => 'Simple Layout',
            'card' => 'Card Layout',
            'split' => 'Split Layout',
        ];
    }
}

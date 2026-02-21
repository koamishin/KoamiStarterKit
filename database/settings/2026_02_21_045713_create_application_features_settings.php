<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('application_features.registration_enabled', true);
        $this->migrator->add('application_features.email_verification_required', true);
        $this->migrator->add('application_features.two_factor_authentication_enabled', true);
        $this->migrator->add('application_features.password_reset_enabled', true);
        $this->migrator->add('application_features.user_impersonation_enabled', true);
        $this->migrator->add('application_features.default_user_role', 'user');
        $this->migrator->add('application_features.activity_log_enabled', true);
        $this->migrator->add('application_features.notifications_enabled', true);
    }
};

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('application_security.password_min_length', 8);
        $this->migrator->add('application_security.password_requires_uppercase', true);
        $this->migrator->add('application_security.password_requires_lowercase', true);
        $this->migrator->add('application_security.password_requires_numbers', true);
        $this->migrator->add('application_security.password_requires_symbols', false);
        $this->migrator->add('application_security.session_lifetime', 120);
        $this->migrator->add('application_security.single_session', false);
        $this->migrator->add('application_security.login_rate_limit', 5);
        $this->migrator->add('application_security.login_rate_limit_decay', 60);
    }
};

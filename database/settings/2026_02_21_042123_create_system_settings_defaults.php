<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('system.site_name', config('app.name', 'KoamiStarterKit'));
        $this->migrator->add('system.site_description', 'A modern Laravel application starter kit');
        $this->migrator->add('system.site_logo_url', null);
        $this->migrator->add('system.site_favicon_url', null);
        $this->migrator->add('system.timezone', config('app.timezone', 'UTC'));
        $this->migrator->add('system.date_format', 'Y-m-d');
        $this->migrator->add('system.time_format', 'H:i:s');
        $this->migrator->add('system.registration_enabled', true);
        $this->migrator->add('system.email_verification_required', true);
        $this->migrator->add('system.two_factor_authentication_enabled', true);
        $this->migrator->add('system.default_user_role', 'user');
    }
};

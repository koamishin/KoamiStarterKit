<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('application_details.site_logo_path', null);
        $this->migrator->add('application_details.site_favicon_path', null);
        $this->migrator->add('application_details.support_phone', null);
    }
};

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('application_details.site_name', config('app.name', 'KoamiStarterKit'));
        $this->migrator->add('application_details.site_description', 'A modern Laravel application starter kit');
        $this->migrator->add('application_details.site_logo_url', null);
        $this->migrator->add('application_details.site_favicon_url', null);
        $this->migrator->add('application_details.timezone', config('app.timezone', 'UTC'));
        $this->migrator->add('application_details.date_format', 'Y-m-d');
        $this->migrator->add('application_details.time_format', 'H:i:s');
        $this->migrator->add('application_details.contact_email', null);
        $this->migrator->add('application_details.support_url', null);
    }
};

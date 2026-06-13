<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('social_login.github_client_id', null);
        $this->migrator->add('social_login.github_client_secret', null);
        $this->migrator->add('social_login.github_redirect_uri', null);
        $this->migrator->add('social_login.github_enabled', false);

        $this->migrator->add('social_login.google_client_id', null);
        $this->migrator->add('social_login.google_client_secret', null);
        $this->migrator->add('social_login.google_redirect_uri', null);
        $this->migrator->add('social_login.google_enabled', false);

        $this->migrator->add('social_login.facebook_client_id', null);
        $this->migrator->add('social_login.facebook_client_secret', null);
        $this->migrator->add('social_login.facebook_redirect_uri', null);
        $this->migrator->add('social_login.facebook_enabled', false);
    }
};

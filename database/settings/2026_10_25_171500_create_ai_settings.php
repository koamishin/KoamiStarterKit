<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('ai.enabled', false);
        $this->migrator->add('ai.provider', 'openai');
        $this->migrator->add('ai.api_key', null);
        $this->migrator->add('ai.model', null);
        $this->migrator->add('ai.base_url', null);
        $this->migrator->add('ai.mcp_enabled', false);
        $this->migrator->add('ai.mcp_servers', []);
    }
};

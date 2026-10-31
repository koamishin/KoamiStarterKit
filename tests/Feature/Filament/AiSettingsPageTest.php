<?php

declare(strict_types=1);

use App\Ai\Agents\ApplicationAgent;
use App\Filament\Clusters\Settings\Pages\AiSettingsPage;
use App\Models\User;
use App\Settings\AiSettings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);

    app()->forgetInstance(AiSettings::class);
});

test('ai settings page is registered in the admin panel', function (): void {
    $panel = Filament::getPanel('admin');

    $pages = $panel->getPages();

    $hasPage = collect($pages)->contains(
        fn ($page): bool => $page === AiSettingsPage::class
    );

    expect($hasPage)->toBeTrue();
});

test('super admin users can access the ai settings page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    $response = $this->get(AiSettingsPage::getUrl());

    $response->assertSuccessful();
});

test('ai settings can be saved', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(AiSettingsPage::class)
        ->fillForm([
            'enabled' => true,
            'provider' => 'anthropic',
            'api_key' => 'sk-ant-test',
            'model' => 'claude-sonnet-4-5',
            'base_url' => null,
            'mcp_enabled' => true,
            'mcp_servers' => [
                [
                    'name' => 'everything',
                    'connection' => 'web',
                    'url' => 'https://mcp.example.com',
                    'token' => 'mcp-token',
                    'timeout' => 45,
                    'command' => null,
                    'arguments' => [],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    Notification::assertNotified('AI settings saved successfully');

    $aiSettings = app(AiSettings::class);
    $aiSettings->refresh();

    expect($aiSettings->enabled)->toBeTrue()
        ->and($aiSettings->provider)->toBe('anthropic')
        ->and($aiSettings->api_key)->toBe('sk-ant-test')
        ->and($aiSettings->model)->toBe('claude-sonnet-4-5')
        ->and($aiSettings->mcp_enabled)->toBeTrue()
        ->and($aiSettings->mcpServers())->toHaveCount(1)
        ->and($aiSettings->mcpServers()[0]['url'])->toBe('https://mcp.example.com')
        ->and($aiSettings->mcpServers()[0]['token'])->toBe('mcp-token')
        ->and($aiSettings->mcpServers()[0]['timeout'])->toBe(45);
});

test('the api key is required when ai is enabled and no environment key exists', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(AiSettingsPage::class)
        ->fillForm([
            'enabled' => true,
            'provider' => 'openai',
            'api_key' => null,
        ])
        ->call('save')
        ->assertHasFormErrors(['api_key' => 'required']);
});

test('the connection test prompts the configured agent', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    $aiSettings = app(AiSettings::class);
    $aiSettings->enabled = true;
    $aiSettings->provider = 'openai';
    $aiSettings->api_key = 'sk-test';
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    ApplicationAgent::fake(['ok']);

    Livewire::test(AiSettingsPage::class)
        ->callAction(TestAction::make('test_connection')->schemaComponent(true));

    ApplicationAgent::assertPrompted('Reply with the single word: ok');

    Notification::assertNotified('Connection successful');
});

test('the connection test warns when ai is not configured', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(AiSettingsPage::class)
        ->fillForm([
            'enabled' => true,
            'provider' => 'openai',
            'api_key' => null,
        ])
        ->callAction(TestAction::make('test_connection')->schemaComponent(true));

    Notification::assertNotified('AI is not configured yet');
});

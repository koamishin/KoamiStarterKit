<?php

use App\Filament\Clusters\Settings\Pages\ApplicationDetailsSettingsPage;
use App\Filament\Clusters\Settings\Pages\ApplicationFeaturesSettingsPage;
use App\Filament\Clusters\Settings\Pages\ApplicationSecuritySettingsPage;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\User;
use App\Settings\ApplicationDetailsSettings;
use App\Settings\ApplicationFeaturesSettings;
use App\Settings\ApplicationSecuritySettings;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);

    app()->forgetInstance(ApplicationDetailsSettings::class);
    app()->forgetInstance(ApplicationFeaturesSettings::class);
    app()->forgetInstance(ApplicationSecuritySettings::class);
});

test('settings cluster is registered in the admin panel', function (): void {
    $panel = Filament::getPanel('admin');

    $clusters = $panel->getClusters();

    $hasSettingsCluster = collect($clusters)->contains(
        fn ($cluster): bool => $cluster === SettingsCluster::class
    );

    expect($hasSettingsCluster)->toBeTrue();
});

test('application details settings page is registered', function (): void {
    $panel = Filament::getPanel('admin');

    $pages = $panel->getPages();

    $hasPage = collect($pages)->contains(
        fn ($page): bool => $page === ApplicationDetailsSettingsPage::class
    );

    expect($hasPage)->toBeTrue();
});

test('application features settings page is registered', function (): void {
    $panel = Filament::getPanel('admin');

    $pages = $panel->getPages();

    $hasPage = collect($pages)->contains(
        fn ($page): bool => $page === ApplicationFeaturesSettingsPage::class
    );

    expect($hasPage)->toBeTrue();
});

test('application security settings page is registered', function (): void {
    $panel = Filament::getPanel('admin');

    $pages = $panel->getPages();

    $hasPage = collect($pages)->contains(
        fn ($page): bool => $page === ApplicationSecuritySettingsPage::class
    );

    expect($hasPage)->toBeTrue();
});

test('super admin users can access application details settings page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    $response = $this->get(ApplicationDetailsSettingsPage::getUrl());

    $response->assertSuccessful();
});

test('super admin users can access application features settings page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    $response = $this->get(ApplicationFeaturesSettingsPage::getUrl());

    $response->assertSuccessful();
});

test('super admin users can access application security settings page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    $response = $this->get(ApplicationSecuritySettingsPage::getUrl());

    $response->assertSuccessful();
});

test('application details settings can be saved', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(ApplicationDetailsSettingsPage::class)
        ->fillForm([
            'site_name' => 'Updated Site Name',
            'site_description' => 'Updated Description',
            'timezone' => 'America/New_York',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $applicationDetailsSettings = app(ApplicationDetailsSettings::class);
    $applicationDetailsSettings->refresh();

    expect($applicationDetailsSettings->site_name)->toBe('Updated Site Name');
    expect($applicationDetailsSettings->site_description)->toBe('Updated Description');
    expect($applicationDetailsSettings->timezone)->toBe('America/New_York');
});

test('application features settings can be saved', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(ApplicationFeaturesSettingsPage::class)
        ->fillForm([
            'registration_enabled' => false,
            'email_verification_required' => false,
            'two_factor_authentication_enabled' => false,
            'password_reset_enabled' => true,
            'auth_layout' => 'card',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $applicationFeaturesSettings = app(ApplicationFeaturesSettings::class);
    $applicationFeaturesSettings->refresh();

    expect($applicationFeaturesSettings->registration_enabled)->toBeFalse();
    expect($applicationFeaturesSettings->email_verification_required)->toBeFalse();
    expect($applicationFeaturesSettings->two_factor_authentication_enabled)->toBeFalse();
    expect($applicationFeaturesSettings->password_reset_enabled)->toBeTrue();
    expect($applicationFeaturesSettings->auth_layout)->toBe('card');
});

test('application security settings can be saved', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(ApplicationSecuritySettingsPage::class)
        ->fillForm([
            'password_min_length' => 12,
            'password_requires_uppercase' => true,
            'password_requires_lowercase' => true,
            'password_requires_numbers' => true,
            'password_requires_symbols' => true,
            'session_lifetime' => 60,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $applicationSecuritySettings = app(ApplicationSecuritySettings::class);
    $applicationSecuritySettings->refresh();

    expect($applicationSecuritySettings->password_min_length)->toBe(12);
    expect($applicationSecuritySettings->password_requires_symbols)->toBeTrue();
    expect($applicationSecuritySettings->session_lifetime)->toBe(60);
});

test('site name is required for application details', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(ApplicationDetailsSettingsPage::class)
        ->fillForm([
            'site_name' => '',
            'site_description' => 'Test Description',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ])
        ->call('save')
        ->assertHasFormErrors(['site_name']);
});

test('password min length must be at least 6', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(ApplicationSecuritySettingsPage::class)
        ->fillForm([
            'password_min_length' => 3,
            'session_lifetime' => 120,
            'login_rate_limit' => 5,
            'login_rate_limit_decay' => 60,
        ])
        ->call('save')
        ->assertHasFormErrors(['password_min_length']);
});

test('auth layout can be changed to split', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(ApplicationFeaturesSettingsPage::class)
        ->fillForm([
            'auth_layout' => 'split',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $applicationFeaturesSettings = app(ApplicationFeaturesSettings::class);
    $applicationFeaturesSettings->refresh();

    expect($applicationFeaturesSettings->auth_layout)->toBe('split');
});

test('auth layout defaults to simple', function (): void {
    $applicationFeaturesSettings = app(ApplicationFeaturesSettings::class);

    expect($applicationFeaturesSettings->auth_layout)->toBe('simple');
});

test('available auth layouts are returned', function (): void {
    $layouts = ApplicationFeaturesSettings::getAvailableLayouts();

    expect($layouts)->toHaveCount(3);
    expect($layouts)->toHaveKey('simple');
    expect($layouts)->toHaveKey('card');
    expect($layouts)->toHaveKey('split');
});

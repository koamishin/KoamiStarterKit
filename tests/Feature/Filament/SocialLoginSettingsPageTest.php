<?php

declare(strict_types=1);

use App\Filament\Clusters\Settings\Pages\SocialLoginSettingsPage;
use App\Models\User;
use App\Settings\SocialLoginSettings;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);

    app()->forgetInstance(SocialLoginSettings::class);
});

test('social login settings page is registered in the admin panel', function (): void {
    $panel = Filament::getPanel('admin');

    $pages = $panel->getPages();

    $hasPage = collect($pages)->contains(
        fn ($page): bool => $page === SocialLoginSettingsPage::class
    );

    expect($hasPage)->toBeTrue();
});

test('super admin users can access the social login settings page', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    $response = $this->get(SocialLoginSettingsPage::getUrl());

    $response->assertSuccessful();
});

test('social login settings can be saved', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(SocialLoginSettingsPage::class)
        ->fillForm([
            'github_enabled' => true,
            'github_client_id' => 'gh-id',
            'github_client_secret' => 'gh-secret',
            'google_enabled' => false,
            'facebook_enabled' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $socialLoginSettings = app(SocialLoginSettings::class);
    $socialLoginSettings->refresh();

    expect($socialLoginSettings->github_enabled)->toBeTrue();
    expect($socialLoginSettings->github_client_id)->toBe('gh-id');
    expect($socialLoginSettings->github_client_secret)->toBe('gh-secret');
    expect($socialLoginSettings->google_enabled)->toBeFalse();
});

test('env override state is exposed on the page', function (): void {
    config()->set('services.github.client_id', 'env-id');
    config()->set('services.github.client_secret', 'env-secret');
    config()->set('services.google.client_id');
    config()->set('services.google.client_secret');

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(SocialLoginSettingsPage::class)
        ->assertSet('providerStatuses', function (array $statuses): bool {
            $bySlug = collect($statuses)->keyBy('slug');

            return ($bySlug['github']['using_env'] ?? false) === true
                && ($bySlug['google']['using_env'] ?? true) === false
                && ($bySlug['facebook']['using_env'] ?? true) === false;
        });
});

test('enabling a provider without credentials fails validation', function (): void {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    Livewire::test(SocialLoginSettingsPage::class)
        ->fillForm([
            'github_enabled' => true,
            'github_client_id' => '',
            'github_client_secret' => '',
        ])
        ->call('save')
        ->assertHasFormErrors(['github_client_id', 'github_client_secret']);
});

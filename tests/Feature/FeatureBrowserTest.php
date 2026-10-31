<?php

use App\Models\FeatureFlag;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Vite;

beforeEach(function (): void {
    $this->app->forgetInstance(Vite::class);
    app(Vite::class)->useHotFile(storage_path('framework/testing-no-vite-hot'));
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::query()->where('email', 'admin@admin.com')->firstOrFail());
});

test('browser profile beta consent can be enabled and revoked', function (): void {
    $webpage = visit('/settings/profile')->assertSee('Stable experience');
    $webpage->click('[aria-label="Opt in to beta access"]')->assertSee('Enrolled in beta')->assertNoJavaScriptErrors();
    expect(User::query()->where('email', 'admin@admin.com')->firstOrFail()->beta_opted_in_at)->not->toBeNull();
    $webpage->click('[aria-label="Opt in to beta access"]')->assertSee('Stable experience');
    expect(User::query()->where('email', 'admin@admin.com')->firstOrFail()->beta_opted_in_at)->toBeNull();
});

test('browser admin can open feature management and definition editor', function (): void {
    visit('/admin/feature-flag/feature-flags')
        ->assertSee('Feature Management')
        ->assertSee('Profile Editing')
        ->click('New feature flag')
        ->assertSee('Ship code once.')
        ->assertSee('Deterministic user cohorts.')
        ->assertNoJavaScriptErrors();
});

test('mobile profile keeps beta opt in usable', function (): void {
    visit('/settings/profile')->on()->mobile()
        ->assertSee('Early access program')
        ->click('[aria-label="Opt in to beta access"]')
        ->assertSee('Enrolled in beta')
        ->assertNoJavaScriptErrors();
});

test('browser feature preferences persist without inertia response errors', function (): void {
    $flag = FeatureFlag::factory()->create([
        'key' => 'browser-beta', 'name' => 'Browser beta', 'stage' => 'beta', 'user_controllable' => true,
    ]);
    $webpage = visit('/settings/features')->assertSee('Your feature preferences');
    $webpage->click('[aria-label="Opt in to beta access"]')->assertSee('Enrolled in beta');
    $webpage->click('[aria-label="Enable Browser beta"]')->assertNoJavaScriptErrors();
    $webpage->assertSee('Browser beta');
    $webpage->assertAttribute('[aria-label="Enable Browser beta"]', 'aria-checked', 'false');
    expect($flag->assignments()->where('kind', 'preference')->firstOrFail()->active)->toBeFalse();
});

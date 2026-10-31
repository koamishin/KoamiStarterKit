<?php

declare(strict_types=1);

use App\Enums\RoleEnums;
use App\Models\User;
use App\Settings\ApplicationDetailsSettings;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    if (File::exists(storage_path('installed'))) {
        File::delete(storage_path('installed'));
    }
});

afterEach(function (): void {
    if (File::exists(storage_path('installed'))) {
        File::delete(storage_path('installed'));
    }
});

test('setup screen can be visited when not installed', function (): void {
    // Ensure no users exist
    User::query()->delete();

    $response = $this->get(route('setup'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $assert): AssertableInertia => $assert
        ->component('Setup')
        ->has('requirements')
        ->has('timezones')
        ->where('requirements.php.satisfied', true)
    );
});

test('setup redirects to home when already installed', function (): void {
    Role::findOrCreate(RoleEnums::SUPER_ADMIN->value, 'web');
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnums::SUPER_ADMIN->value);

    $response = $this->get(route('setup'));

    $response->assertRedirect(route('home'));
});

test('setup validates required fields', function (): void {
    User::query()->delete();

    $response = $this->post(route('setup.store'), [
        'site_name' => '',
        'admin_name' => '',
        'admin_email' => 'not-an-email',
        'admin_password' => 'short',
        'admin_password_confirmation' => 'mismatch',
    ]);

    $response->assertSessionHasErrors([
        'site_name',
        'timezone',
        'admin_name',
        'admin_email',
        'admin_password',
    ]);
});

test('setup initializes super admin account and settings', function (): void {
    User::query()->delete();

    $response = $this->post(route('setup.store'), [
        'site_name' => 'Self-Hosted Production',
        'site_description' => 'Our self-hosted app instance',
        'timezone' => 'America/New_York',
        'admin_name' => 'Root Admin',
        'admin_email' => 'root@example.com',
        'admin_password' => 'SuperSecret123!',
        'admin_password_confirmation' => 'SuperSecret123!',
    ]);

    $response->assertRedirect('/admin');

    $admin = User::query()->where('email', 'root@example.com')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->hasRole(RoleEnums::SUPER_ADMIN->value))->toBeTrue();

    $this->assertAuthenticatedAs($admin);
    expect(File::exists(storage_path('installed')))->toBeTrue();

    $applicationDetailsSettings = app(ApplicationDetailsSettings::class);
    expect($applicationDetailsSettings->site_name)->toBe('Self-Hosted Production')
        ->and($applicationDetailsSettings->timezone)->toBe('America/New_York');
});

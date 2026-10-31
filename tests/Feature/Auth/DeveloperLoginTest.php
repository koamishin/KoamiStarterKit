<?php

declare(strict_types=1);

use App\Enums\RoleEnums;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

test('developer login returns 404 in non-local environments', function (): void {
    $user = User::factory()->create();

    $this->post(route('developer-login'), [
        'email' => $user->email,
    ])->assertNotFound();

    $this->assertGuest();
});

test('developer login logs in user and redirects to dashboard in local environment', function (): void {
    $this->app['env'] = 'local';

    $user = User::factory()->create();

    $response = $this->post(route('developer-login'), [
        'email' => $user->email,
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

test('developer login respects intended redirect URL in local environment', function (): void {
    $this->app['env'] = 'local';

    $user = User::factory()->create();

    session(['url.intended' => route('notifications.index')]);

    $response = $this->post(route('developer-login'), [
        'email' => $user->email,
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('notifications.index'));
});

test('developer login requires existing email in local environment', function (): void {
    $this->app['env'] = 'local';

    $response = $this->post(route('developer-login'), [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('already authenticated users cannot use developer login', function (): void {
    $this->app['env'] = 'local';

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('developer-login'), [
        'email' => $user->email,
    ]);

    $response->assertRedirect(route('dashboard'));
});

test('login screen does not include developer users in non-local environment', function (): void {
    User::factory()->create();

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $assert): AssertableInertia => $assert
        ->component('auth/Login')
        ->where('developerUsers', [])
    );
});

test('login screen includes developer users with role labels in local environment', function (): void {
    $this->app['env'] = 'local';

    Role::firstOrCreate(['name' => RoleEnums::SUPER_ADMIN->value, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => RoleEnums::USER->value, 'guard_name' => 'web']);

    $admin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@admin.com']);
    $admin->assignRole(RoleEnums::SUPER_ADMIN->value);

    $regular = User::factory()->create(['name' => 'Regular User', 'email' => 'user@user.com']);
    $regular->assignRole(RoleEnums::USER->value);

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $assert): AssertableInertia => $assert
        ->component('auth/Login')
        ->has('developerUsers', 2)
        ->where('developerUsers.0.email', 'admin@admin.com')
        ->where('developerUsers.0.role', 'Super Admin')
        ->where('developerUsers.1.email', 'user@user.com')
        ->where('developerUsers.1.role', 'User')
    );
});

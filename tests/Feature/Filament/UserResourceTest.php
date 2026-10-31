<?php

declare(strict_types=1);

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('unauthenticated users are redirected to login', function (): void {
    $this->get(UserResource::getUrl('index'))
        ->assertRedirect('/admin/login');
});

test('regular users cannot access the user management index page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(UserResource::getUrl('index'))
        ->assertForbidden();
});

test('super admin can access the user management index page with overview stats', function (): void {
    $admin = User::factory()->create([
        'name' => 'System Superadmin',
        'email' => 'superadmin@example.com',
    ]);
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get(UserResource::getUrl('index'));

    $response->assertOk()
        ->assertSee('Users')
        ->assertSee('Total Users')
        ->assertSee('Email Verified')
        ->assertSee('Security & MFA')
        ->assertSee('Staff & Admins');
});

test('user management index page displays users, roles, email status, and security badges', function (): void {
    $admin = User::factory()->create([
        'name' => 'System Superadmin',
        'email' => 'superadmin@example.com',
    ]);
    $admin->assignRole('super_admin');

    $verifiedUser = User::factory()->create([
        'name' => 'Alice Verified',
        'email' => 'alice@example.com',
        'email_verified_at' => now(),
    ]);

    $unverifiedUser = User::factory()->create([
        'name' => 'Bob Unverified',
        'email' => 'bob@example.com',
        'email_verified_at' => null,
    ]);

    $response = $this->actingAs($admin)->get(UserResource::getUrl('index'));

    $response->assertOk()
        ->assertSee('Alice Verified')
        ->assertSee('alice@example.com')
        ->assertSee('Bob Unverified')
        ->assertSee('bob@example.com')
        ->assertSee('Verified')
        ->assertSee('Unverified');
});

test('user management tabs filter records appropriately', function (): void {
    $admin = User::factory()->create([
        'name' => 'System Superadmin',
        'email' => 'superadmin@example.com',
        'email_verified_at' => now(),
    ]);
    $admin->assignRole('super_admin');

    $verifiedUser = User::factory()->create([
        'name' => 'Alice Target',
        'email' => 'alice.target@example.com',
        'email_verified_at' => now(),
    ]);

    $unverifiedUser = User::factory()->create([
        'name' => 'Bob Target',
        'email' => 'bob.target@example.com',
        'email_verified_at' => null,
    ]);

    // Verified tab
    $this->actingAs($admin)
        ->get(UserResource::getUrl('index', ['tab' => 'verified']))
        ->assertOk()
        ->assertSee('Alice Target')
        ->assertDontSee('Bob Target');

    // Unverified tab
    $this->actingAs($admin)
        ->get(UserResource::getUrl('index', ['tab' => 'unverified']))
        ->assertOk()
        ->assertSee('Bob Target')
        ->assertDontSee('Alice Target');

    // Super Admins tab
    $this->actingAs($admin)
        ->get(UserResource::getUrl('index', ['tab' => 'super_admins']))
        ->assertOk()
        ->assertSee('System Superadmin')
        ->assertDontSee('Bob Target');
});

test('user management search filters records by name and email', function (): void {
    $admin = User::factory()->create([
        'name' => 'System Superadmin',
        'email' => 'superadmin@example.com',
    ]);
    $admin->assignRole('super_admin');

    $targetUser = User::factory()->create([
        'name' => 'Charlie Target',
        'email' => 'charlie.target@example.com',
    ]);

    $hiddenUser = User::factory()->create([
        'name' => 'Dave Ghost',
        'email' => 'dave.ghost@example.com',
    ]);

    $this->actingAs($admin)
        ->get(UserResource::getUrl('index', ['search' => 'Charlie Target']))
        ->assertOk()
        ->assertSee('Charlie Target')
        ->assertDontSee('Dave Ghost');
});

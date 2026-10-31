<?php

declare(strict_types=1);

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\SelfHostedHealthWidget;
use App\Filament\Widgets\SelfHostedQuickActionsWidget;
use App\Filament\Widgets\SelfHostedVitalsOverviewWidget;
use App\Filament\Widgets\UserGrowthChartWidget;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

afterEach(function (): void {
    Artisan::call('config:clear');
    Artisan::call('up');
});

test('unauthenticated users are redirected to login', function (): void {
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

test('regular users cannot access the admin panel', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('super admin can access the self-hosted admin dashboard', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertOk()
        ->assertSee('Self-Hosted Control Center')
        ->assertSee('Node Telemetry')
        ->assertSee('Instance Operations')
        ->assertSee('Recent Security & System Events');
});

test('dashboard provides expected self-hosted widgets', function (): void {
    $dashboard = new Dashboard;
    $widgets = $dashboard->getWidgets();

    expect($widgets)->toContain(SelfHostedHealthWidget::class)
        ->toContain(SelfHostedVitalsOverviewWidget::class)
        ->toContain(SelfHostedQuickActionsWidget::class)
        ->toContain(UserGrowthChartWidget::class)
        ->toContain(RecentActivityWidget::class);
});

test('quick actions widget can clear cache without errors', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    Livewire::test(SelfHostedQuickActionsWidget::class)
        ->call('clearCache')
        ->assertHasNoErrors();
});

test('quick actions widget can run optimize without errors', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    Livewire::test(SelfHostedQuickActionsWidget::class)
        ->call('optimizeApp')
        ->assertHasNoErrors();
});

test('health widget renders telemetry metrics', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    Livewire::test(SelfHostedHealthWidget::class)
        ->assertSee('PHP')
        ->assertSee('Laravel')
        ->assertSee('Disk Storage')
        ->assertSee('Memory Usage');
});

test('admins can toggle maintenance mode and remain inside admin panel', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    // Toggle maintenance mode on
    Livewire::test(SelfHostedQuickActionsWidget::class)
        ->call('toggleMaintenance')
        ->assertHasNoErrors();

    expect(app()->isDownForMaintenance())->toBeTrue();

    // Super admin can still access the admin dashboard
    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Self-Hosted Control Center');

    // Super admin can still toggle maintenance mode off
    Livewire::test(SelfHostedQuickActionsWidget::class)
        ->call('toggleMaintenance')
        ->assertHasNoErrors();

    expect(app()->isDownForMaintenance())->toBeFalse();
});

test('non-admins receive 503 during maintenance while super admins can access application', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $regularUser = User::factory()->create();

    Artisan::call('down');
    expect(app()->isDownForMaintenance())->toBeTrue();

    // Guest on frontend receives 503
    $this->get('/')
        ->assertStatus(503);

    // Regular user on frontend receives 503
    $this->actingAs($regularUser)
        ->get('/')
        ->assertStatus(503);

    // Super admin can access admin panel during maintenance
    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();

    // Super admin can also access frontend during maintenance
    $this->actingAs($admin)
        ->get('/')
        ->assertOk();
});

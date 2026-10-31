<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spiggle\FilaWarden\Filament\Pages\DatabaseHealthPage;
use Spiggle\FilaWarden\Filament\Pages\DeploymentAuditorPage;
use Spiggle\FilaWarden\Filament\Pages\ErrorLogPage;
use Spiggle\FilaWarden\Filament\Pages\ExecutiveDashboardPage;
use Spiggle\FilaWarden\Filament\Pages\InfrastructureMonitorPage;
use Spiggle\FilaWarden\Filament\Pages\QueueMonitorPage;
use Spiggle\FilaWarden\Filament\Pages\RiskFilesPage;
use Spiggle\FilaWarden\Filament\Pages\SchedulerMonitorPage;
use Spiggle\FilaWarden\Filament\Pages\SslMonitorPage;
use Spiggle\FilaWarden\Filament\Widgets\DeploymentStatusWidget;
use Spiggle\FilaWarden\Filament\Widgets\HealthScoreOverviewWidget;
use Spiggle\FilaWarden\Filament\Widgets\SystemResourcesGaugeWidget;
use Spiggle\FilaWarden\FilaWardenPlugin;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

test('filawarden plugin is registered in admin panel', function (): void {
    $panel = Filament::getPanel('admin');

    expect($panel->hasPlugin('filawarden'))->toBeTrue();
    expect($panel->getPlugin('filawarden'))->toBeInstanceOf(FilaWardenPlugin::class);
});

test('unauthenticated users cannot access filawarden pages', function (): void {
    $this->get('/admin/filawarden/dashboard')
        ->assertRedirect('/admin/login');
});

test('non-admin users are forbidden from filawarden pages', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/filawarden/dashboard')
        ->assertForbidden();
});

test('super admin can access filawarden pages', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get('/admin/filawarden/dashboard')
        ->assertOk()
        ->assertSee('Operations Intelligence');

    $this->actingAs($admin)
        ->get('/admin/filawarden/deployment-auditor')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/filawarden/infrastructure')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/filawarden/database-health')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/filawarden/scheduler-monitor')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/filawarden/queue-monitor')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/filawarden/risk-files')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/filawarden/error-logs')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/filawarden/ssl-monitor')
        ->assertOk();
});

test('filawarden livewire widgets and pages render successfully', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);

    Livewire::test(HealthScoreOverviewWidget::class)->assertOk();
    Livewire::test(SystemResourcesGaugeWidget::class)->assertOk();
    Livewire::test(DeploymentStatusWidget::class)->assertOk();

    Livewire::test(ExecutiveDashboardPage::class)->assertOk();
    Livewire::test(DeploymentAuditorPage::class)->assertOk();
    Livewire::test(InfrastructureMonitorPage::class)->assertOk();
    Livewire::test(DatabaseHealthPage::class)->assertOk();
    Livewire::test(SchedulerMonitorPage::class)->assertOk();
    Livewire::test(QueueMonitorPage::class)->assertOk();
    Livewire::test(RiskFilesPage::class)->assertOk();
    Livewire::test(ErrorLogPage::class)->assertOk();
    Livewire::test(SslMonitorPage::class)->assertOk();
});

test('filawarden artisan commands execute', function (): void {
    $this->artisan('filawarden:collect')->assertSuccessful();
    $this->artisan('filawarden:audit')->assertExitCode(1); // Exit 1 because APP_DEBUG is true in local/test
    $this->artisan('filawarden:audit --json')->assertSuccessful();
});

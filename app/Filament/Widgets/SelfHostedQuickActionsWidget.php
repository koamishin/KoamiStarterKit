<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class SelfHostedQuickActionsWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.self-hosted-quick-actions-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected static ?int $sort = 3;

    public function clearCache(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            Notification::make()
                ->title('Caches Flushed')
                ->body('Application, config, route, and compiled view caches have been cleared.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Cache Clear Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function optimizeApp(): void
    {
        try {
            if (app()->environment('testing')) {
                Notification::make()
                    ->title('Application Optimized')
                    ->body('Optimization bypassed in testing environment.')
                    ->success()
                    ->send();

                return;
            }

            Artisan::call('optimize');

            Notification::make()
                ->title('Application Optimized')
                ->body('Configuration and routing cache generated for peak performance.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Optimization Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function restartQueue(): void
    {
        try {
            Artisan::call('queue:restart');

            Notification::make()
                ->title('Queue Signal Sent')
                ->body('Queue worker restart signal broadcasted.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Queue Restart Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runMigrations(): void
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());

            Notification::make()
                ->title('Database Migrations Executed')
                ->body($output ?: 'Database schema is fully up to date.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Migration Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function toggleMaintenance(): void
    {
        try {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
                Notification::make()
                    ->title('Application Live')
                    ->body('Maintenance mode deactivated. Application is accessible to users.')
                    ->success()
                    ->send();
            } else {
                Artisan::call('down');
                Notification::make()
                    ->title('Maintenance Mode Activated')
                    ->body('Application is now in maintenance mode.')
                    ->warning()
                    ->send();
            }
        } catch (Throwable $e) {
            Notification::make()
                ->title('Maintenance Toggle Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    protected function getViewData(): array
    {
        return [
            'isDown' => app()->isDownForMaintenance(),
            'links' => [
                [
                    'label' => 'App Details',
                    'icon' => 'heroicon-m-cog-6-tooth',
                    'url' => route('filament.admin.settings.pages.application-details-settings-page'),
                ],
                [
                    'label' => 'Security Policy',
                    'icon' => 'heroicon-m-shield-check',
                    'url' => route('filament.admin.settings.pages.application-security-settings-page'),
                ],
                [
                    'label' => 'Shield Roles',
                    'icon' => 'heroicon-m-key',
                    'url' => route('filament.admin.resources.shield.roles.index'),
                ],
                [
                    'label' => 'Audit Stream',
                    'icon' => 'heroicon-m-document-text',
                    'url' => route('filament.admin.resources.activity-logs.index'),
                ],
            ],
        ];
    }
}

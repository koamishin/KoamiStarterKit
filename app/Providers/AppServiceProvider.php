<?php

namespace App\Providers;

use App\Ai\LaravelMcpServerConnector;
use App\Ai\McpServerConnector;
use App\Features\FeatureEvaluator;
use App\Features\FeatureRegistry;
use App\Features\ManagedFeatureDriver;
use Carbon\CarbonImmutable;
use Filament\Panel;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Events\FeatureUpdated;
use Laravel\Pennant\Feature;
use Laravel\Pennant\FeatureManager;
use Nwidart\Modules\Facades\Module;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->bind(McpServerConnector::class, LaravelMcpServerConnector::class);
        $this->app->scoped(FeatureEvaluator::class);

        $this->registerFilamentPlugins();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureFeatures();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureFeatures(): void
    {
        FeatureRegistry::initialize();

        $featureManager = app(FeatureManager::class);

        $featureManager->extend('managed', fn ($app): ManagedFeatureDriver => new ManagedFeatureDriver(
            $app['db'], $app['events'], $app['config'], 'database', [],
        ));

        $this->app['events']->listen(FeatureUpdated::class, function (): void {
            app(FeatureEvaluator::class)->flush();
            Feature::flushCache();
        });

        foreach (FeatureRegistry::all() as $feature) {
            $featureManager->define($feature->key, fn () => $feature->default);
        }
    }

    protected function registerFilamentPlugins(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() !== 'admin') {
                return;
            }

            foreach (Module::allEnabled() as $module) {
                $plugin = sprintf('Modules\\%s\\%sPlugin', $module->getStudlyName(), $module->getStudlyName());

                if (class_exists($plugin) && method_exists($plugin, 'make')) {
                    $panel->plugin($plugin::make());
                }
            }
        });
    }
}

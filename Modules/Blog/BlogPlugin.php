<?php

declare(strict_types=1);

namespace Modules\Blog;

use Filament\Contracts\Plugin;
use Filament\Panel;

class BlogPlugin implements Plugin
{
    public function getId(): string
    {
        return 'blog';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__.'/Filament/Resources', for: 'Modules\\Blog\\Filament\\Resources')
            ->discoverPages(in: __DIR__.'/Filament/Pages', for: 'Modules\\Blog\\Filament\\Pages')
            ->discoverClusters(in: __DIR__.'/Filament/Clusters', for: 'Modules\\Blog\\Filament\\Clusters')
            ->discoverWidgets(in: __DIR__.'/Filament/Widgets', for: 'Modules\\Blog\\Filament\\Widgets');
    }

    public function boot(Panel $panel): void
    {
        //
    }
}

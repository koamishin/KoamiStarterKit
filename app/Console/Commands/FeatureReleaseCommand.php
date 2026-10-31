<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Features\FeatureManifest;
use Illuminate\Console\Command;

class FeatureReleaseCommand extends Command
{
    protected $signature = 'features:release {version : Exact release version to promote}';

    protected $description = 'Promote beta features assigned to a release to stable, preserving rollout safeguards';

    public function handle(): int
    {
        $count = FeatureManifest::release((string) $this->argument('version'));
        $this->info("Promoted {$count} beta features to stable.");

        return self::SUCCESS;
    }
}

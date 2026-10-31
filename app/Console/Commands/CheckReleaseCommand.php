<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Release\ReleaseService;
use Illuminate\Console\Command;

class CheckReleaseCommand extends Command
{
    protected $signature = 'app:check-release {--force : Bypass release cache}';

    protected $description = 'Check upstream GitHub repository for new semantic releases';

    public function handle(ReleaseService $releaseService): int
    {
        $this->components->info('Checking upstream releases for '.$releaseService->repository().'...');

        $check = $releaseService->check((bool) $this->option('force'));

        $this->table(['Key', 'Value'], [
            ['Current Version', $check['current_version']],
            ['Latest Version', $check['latest_version']],
            ['Update Available', $check['is_update_available'] ? 'YES' : 'NO'],
            ['Release Title', $check['release_title']],
            ['Published At', $check['published_at'] ?? 'N/A'],
            ['URL', $check['html_url']],
        ]);

        if ($check['is_update_available']) {
            $this->components->warn("A newer release (v{$check['latest_version']}) is available.");
        } else {
            $this->components->info("Instance is up to date (v{$check['current_version']}).");
        }

        return self::SUCCESS;
    }
}

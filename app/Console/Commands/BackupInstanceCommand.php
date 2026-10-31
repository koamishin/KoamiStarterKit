<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backup\InstanceBackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupInstanceCommand extends Command
{
    protected $signature = 'app:backup';

    protected $description = 'Create a disaster recovery ZIP archive of database and storage';

    public function handle(InstanceBackupService $instanceBackupService): int
    {
        $this->components->info('Creating disaster recovery backup archive...');

        try {
            $zipPath = $instanceBackupService->create();
            $this->components->info("Backup archive generated successfully: {$zipPath}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->components->error("Backup generation failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}

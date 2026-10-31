<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Services\Release\ReleaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class InstanceBackupService
{
    public function __construct(
        protected ReleaseService $releaseService
    ) {}

    public function create(): string
    {
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Y-m-d-His');
        $zipFile = $backupDir."/backup-{$timestamp}.zip";

        $zipArchive = new ZipArchive;
        if ($zipArchive->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Unable to create backup archive at {$zipFile}");
        }

        // 1. Add Manifest
        $manifest = [
            'app_name' => config('app.name'),
            'app_version' => $this->releaseService->currentVersion(),
            'database_driver' => DB::getDriverName(),
            'created_at' => now()->toIso8601String(),
        ];
        $zipArchive->addFromString('manifest.json', (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 2. Add Database
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $sqlitePath = (string) config('database.connections.sqlite.database');
            if (File::exists($sqlitePath)) {
                $zipArchive->addFile($sqlitePath, 'database/database.sqlite');
            }
        } else {
            // For non-sqlite, export tables count / basic metadata
            $tables = DB::getSchemaBuilder()->getTableListing();
            $zipArchive->addFromString('database/tables.json', (string) json_encode(['tables' => $tables], JSON_PRETTY_PRINT));
        }

        // 3. Add Public Storage uploads if any exist
        $publicStorage = storage_path('app/public');
        if (File::isDirectory($publicStorage)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($publicStorage, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'storage/'.substr($filePath, strlen($publicStorage) + 1);
                    $zipArchive->addFile($filePath, str_replace('\\', '/', $relativePath));
                }
            }
        }

        $zipArchive->close();

        return $zipFile;
    }
}

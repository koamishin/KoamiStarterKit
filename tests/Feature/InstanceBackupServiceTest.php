<?php

declare(strict_types=1);

use App\Services\Backup\InstanceBackupService;
use Illuminate\Support\Facades\File;

test('backup service creates valid disaster recovery zip archive', function (): void {
    $instanceBackupService = app(InstanceBackupService::class);
    $zipPath = $instanceBackupService->create();

    expect(File::exists($zipPath))->toBeTrue();

    $zip = new ZipArchive;
    expect($zip->open($zipPath))->toBeTrue();

    expect($zip->locateName('manifest.json'))->not->toBeFalse();

    $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
    expect($manifest)->toHaveKeys(['app_name', 'app_version', 'database_driver', 'created_at']);

    $zip->close();
    File::delete($zipPath);
});

<?php

namespace App\Console\Commands;

use App\Features\FeatureManifest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FeatureManifestCommand extends Command
{
    protected $signature = 'features:manifest {operation : sync, export, or import} {path?} {--overwrite : Replace existing definitions when importing}';

    protected $description = 'Sync, export, or import portable feature definitions without per-user assignments';

    public function handle(): int
    {
        $operation = $this->argument('operation');
        if ($operation === 'sync') {
            FeatureManifest::sync();
        } elseif ($operation === 'export' && $this->argument('path')) {
            File::put($this->argument('path'), json_encode(FeatureManifest::export(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } elseif ($operation === 'import' && $this->argument('path')) {
            $manifest = json_decode(File::get($this->argument('path')), true, flags: JSON_THROW_ON_ERROR);
            FeatureManifest::import($manifest, (bool) $this->option('overwrite'));
        } else {
            $this->error('Use sync, export <path>, or import <path>.');

            return self::FAILURE;
        }
        $this->info('Feature manifest processed successfully.');

        return self::SUCCESS;
    }
}

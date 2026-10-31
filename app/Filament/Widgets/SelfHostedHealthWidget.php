<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Settings\AiSettings;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Throwable;

class SelfHostedHealthWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.self-hosted-health-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    protected function getViewData(): array
    {
        // 1. Host & Operating System Telemetry
        $hostname = gethostname() ?: 'localhost';
        $os = php_uname('s').' '.php_uname('r');
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $octaneServer = config('octane.server');
        $environment = app()->environment();
        $isDebug = (bool) config('app.debug');
        $isMaintenance = app()->isDownForMaintenance();
        $timezone = config('app.timezone', 'UTC');

        // Server uptime from /proc/uptime if available
        $uptimeFormatted = null;
        if (is_readable('/proc/uptime')) {
            $uptimeSeconds = (int) floatval(explode(' ', (string) file_get_contents('/proc/uptime'))[0]);
            $days = intdiv($uptimeSeconds, 86400);
            $hours = intdiv($uptimeSeconds % 86400, 3600);
            $minutes = intdiv($uptimeSeconds % 3600, 60);
            $uptimeFormatted = "{$days}d {$hours}h {$minutes}m";
        }

        // 2. Resource Utilization (Memory, Disk, Load)
        $memUsageBytes = memory_get_usage(true);
        $memPeakBytes = memory_get_peak_usage(true);
        $memLimitRaw = ini_get('memory_limit');
        $memLimitFormatted = $memLimitRaw === '-1' ? 'Unlimited' : $memLimitRaw;

        $totalDisk = (float) @disk_total_space(base_path());
        $freeDisk = (float) @disk_free_space(base_path());
        $usedDisk = max(0, $totalDisk - $freeDisk);
        $diskPercentage = $totalDisk > 0 ? round(($usedDisk / $totalDisk) * 100, 1) : 0.0;

        $cpuLoad = function_exists('sys_getloadavg') ? sys_getloadavg() : null;

        // 3. Database Health
        $dbDriver = (string) config('database.default', 'sqlite');
        $dbStatus = false;
        $dbLatency = null;
        $dbSizeFormatted = null;

        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatency = round((microtime(true) - $start) * 1000, 2);
            $dbStatus = true;

            if ($dbDriver === 'sqlite') {
                $dbPath = config("database.connections.{$dbDriver}.database");
                if (is_string($dbPath) && file_exists($dbPath)) {
                    $dbSizeFormatted = Number::fileSize(filesize($dbPath));
                }
            }
        } catch (Throwable) {
            $dbStatus = false;
        }

        // 4. Cache & Queue
        $cacheDriver = (string) config('cache.default', 'file');
        $cacheStatus = false;
        try {
            $testKey = 'health_check_'.microtime(true);
            Cache::put($testKey, 'ok', 5);
            $cacheStatus = Cache::get($testKey) === 'ok';
            Cache::forget($testKey);
        } catch (Throwable) {
            $cacheStatus = false;
        }

        $queueDriver = (string) config('queue.default', 'sync');
        $pendingJobs = 0;
        $failedJobs = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('jobs')) {
                $pendingJobs = DB::table('jobs')->count();
            }
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }
        } catch (Throwable) {
            // silent fallback
        }

        // 5. Storage Permissions
        $storageWritable = is_writable(storage_path());
        $bootstrapCacheWritable = is_writable(base_path('bootstrap/cache'));

        // 6. AI Engine
        $aiEnabled = false;
        $aiProvider = 'Disabled';
        try {
            $aiSettings = app(AiSettings::class);
            $aiEnabled = (bool) $aiSettings->enabled;
            $aiProvider = $aiSettings->provider ?: 'openai';
        } catch (Throwable) {
            // fallback
        }

        // 7. Security / Self-Host Checks Aggregation
        $warnings = [];
        if ($environment !== 'local' && $isDebug) {
            $warnings[] = 'Debug mode is active on non-local environment!';
        }
        if ($diskPercentage >= 90) {
            $warnings[] = 'Disk space is critically low (>90% used).';
        }
        if (! $storageWritable) {
            $warnings[] = 'Storage directory is not writable!';
        }
        if (! $bootstrapCacheWritable) {
            $warnings[] = 'Bootstrap cache directory is not writable!';
        }
        if ($failedJobs > 0) {
            $warnings[] = "{$failedJobs} failed background queue jobs require attention.";
        }

        $isHealthy = empty($warnings) && $dbStatus && $cacheStatus;

        return [
            'isHealthy' => $isHealthy,
            'warnings' => $warnings,
            'hostname' => $hostname,
            'os' => $os,
            'phpVersion' => $phpVersion,
            'laravelVersion' => $laravelVersion,
            'octaneServer' => $octaneServer,
            'environment' => $environment,
            'isDebug' => $isDebug,
            'isMaintenance' => $isMaintenance,
            'timezone' => $timezone,
            'uptimeFormatted' => $uptimeFormatted,
            'memUsageFormatted' => Number::fileSize($memUsageBytes),
            'memPeakFormatted' => Number::fileSize($memPeakBytes),
            'memLimitFormatted' => $memLimitFormatted,
            'totalDiskFormatted' => Number::fileSize((int) $totalDisk),
            'freeDiskFormatted' => Number::fileSize((int) $freeDisk),
            'usedDiskFormatted' => Number::fileSize((int) $usedDisk),
            'diskPercentage' => $diskPercentage,
            'cpuLoad' => $cpuLoad,
            'dbDriver' => $dbDriver,
            'dbStatus' => $dbStatus,
            'dbLatency' => $dbLatency,
            'dbSizeFormatted' => $dbSizeFormatted,
            'cacheDriver' => $cacheDriver,
            'cacheStatus' => $cacheStatus,
            'queueDriver' => $queueDriver,
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
            'storageWritable' => $storageWritable,
            'bootstrapCacheWritable' => $bootstrapCacheWritable,
            'aiEnabled' => $aiEnabled,
            'aiProvider' => $aiProvider,
        ];
    }
}

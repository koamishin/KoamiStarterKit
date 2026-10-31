<?php

declare(strict_types=1);

namespace App\Services\Testing;

use App\Services\Release\ReleaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Number;
use RuntimeException;
use Throwable;

class SystemDiagnosticService
{
    public function __construct(
        protected ReleaseService $releaseService
    ) {}

    /**
     * @return array<string, array{name: string, status: 'ok'|'warning'|'fail', message: string, latency_ms: ?float}>
     */
    public function runAll(): array
    {
        return [
            'database' => $this->testDatabase(),
            'storage' => $this->testStorage(),
            'cache' => $this->testCache(),
            'release' => $this->testUpstreamRelease(),
        ];
    }

    /**
     * @return array{name: string, status: 'ok'|'warning'|'fail', message: string, latency_ms: ?float}
     */
    public function testDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);
            $driver = DB::getDriverName();

            return [
                'name' => 'Database Engine ('.$driver.')',
                'status' => 'ok',
                'message' => 'Connection verified and responsive.',
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'name' => 'Database Engine',
                'status' => 'fail',
                'message' => 'Database query failed: '.$e->getMessage(),
                'latency_ms' => null,
            ];
        }
    }

    /**
     * @return array{name: string, status: 'ok'|'warning'|'fail', message: string, latency_ms: ?float}
     */
    public function testStorage(): array
    {
        $start = microtime(true);
        $testPath = storage_path('framework/testing/diag_test_'.microtime(true).'.tmp');

        try {
            File::ensureDirectoryExists(dirname($testPath));
            File::put($testPath, 'diagnostic-payload');
            $content = File::get($testPath);
            File::delete($testPath);

            if ($content !== 'diagnostic-payload') {
                throw new RuntimeException('Payload mismatch during storage read/write check.');
            }

            $latency = round((microtime(true) - $start) * 1000, 2);
            $freeDisk = (float) @disk_free_space(base_path());
            $freeFormatted = $freeDisk > 0 ? Number::fileSize($freeDisk) : 'Unknown';

            return [
                'name' => 'Local Filesystem Storage',
                'status' => 'ok',
                'message' => "Read/write verified ({$freeFormatted} free disk space).",
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'name' => 'Local Filesystem Storage',
                'status' => 'fail',
                'message' => 'Storage read/write failed: '.$e->getMessage(),
                'latency_ms' => null,
            ];
        }
    }

    /**
     * @return array{name: string, status: 'ok'|'warning'|'fail', message: string, latency_ms: ?float}
     */
    public function testCache(): array
    {
        $start = microtime(true);
        $key = 'diag_cache_'.microtime(true);

        try {
            Cache::put($key, 'cache-ok', 10);
            $val = Cache::get($key);
            Cache::forget($key);

            if ($val !== 'cache-ok') {
                throw new RuntimeException('Cache payload mismatch.');
            }

            $latency = round((microtime(true) - $start) * 1000, 2);
            $driver = (string) config('cache.default', 'file');

            return [
                'name' => 'Cache Store ('.$driver.')',
                'status' => 'ok',
                'message' => 'Read/write/purge cycles verified.',
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'name' => 'Cache Store',
                'status' => 'fail',
                'message' => 'Cache operations failed: '.$e->getMessage(),
                'latency_ms' => null,
            ];
        }
    }

    /**
     * @return array{name: string, status: 'ok'|'warning'|'fail', message: string, latency_ms: ?float}
     */
    public function testUpstreamRelease(): array
    {
        $start = microtime(true);
        $check = $this->releaseService->check(force: true);
        $latency = round((microtime(true) - $start) * 1000, 2);

        if ($check['status'] === 'ok') {
            $msg = $check['is_update_available']
                ? "Update available: v{$check['latest_version']} (current: v{$check['current_version']})"
                : "Up to date on v{$check['current_version']}";

            return [
                'name' => 'Upstream GitHub Releases',
                'status' => 'ok',
                'message' => $msg,
                'latency_ms' => $latency,
            ];
        }

        return [
            'name' => 'Upstream GitHub Releases',
            'status' => 'warning',
            'message' => $check['release_notes'],
            'latency_ms' => $latency,
        ];
    }
}

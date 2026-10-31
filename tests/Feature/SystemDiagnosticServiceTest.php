<?php

declare(strict_types=1);

use App\Services\Testing\SystemDiagnosticService;

test('diagnostic service tests database connection and latency', function (): void {
    $service = app(SystemDiagnosticService::class);
    $result = $service->testDatabase();

    expect($result['status'])->toBe('ok')
        ->and($result['latency_ms'])->toBeGreaterThanOrEqual(0);
});

test('diagnostic service tests storage write read delete cycles', function (): void {
    $service = app(SystemDiagnosticService::class);
    $result = $service->testStorage();

    expect($result['status'])->toBe('ok')
        ->and($result['latency_ms'])->toBeGreaterThanOrEqual(0);
});

test('diagnostic service tests cache store', function (): void {
    $service = app(SystemDiagnosticService::class);
    $result = $service->testCache();

    expect($result['status'])->toBe('ok')
        ->and($result['latency_ms'])->toBeGreaterThanOrEqual(0);
});

test('diagnostic service runs all checks', function (): void {
    $service = app(SystemDiagnosticService::class);
    $all = $service->runAll();

    expect($all)->toHaveKeys(['database', 'storage', 'cache', 'release'])
        ->and($all['database']['status'])->toBe('ok')
        ->and($all['storage']['status'])->toBe('ok')
        ->and($all['cache']['status'])->toBe('ok');
});

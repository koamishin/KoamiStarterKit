<?php

declare(strict_types=1);

use App\Services\Release\ReleaseService;
use Illuminate\Support\Facades\Http;

test('release service returns current version from version json', function (): void {
    $releaseService = app(ReleaseService::class);
    $version = $releaseService->currentVersion();

    expect($version)->not->toBeEmpty();
});

test('release service correctly detects updates via github api', function (): void {
    Http::fake([
        'https://api.github.com/repos/*/releases/latest' => Http::response([
            'tag_name' => 'v99.0.0',
            'name' => 'Major Release v99',
            'body' => 'Exciting new features.',
            'published_at' => '2026-10-31T00:00:00Z',
            'html_url' => 'https://github.com/example/release',
        ], 200),
    ]);

    $releaseService = app(ReleaseService::class);
    $result = $releaseService->check(force: true);

    expect($result['is_update_available'])->toBeTrue()
        ->and($result['latest_version'])->toBe('99.0.0')
        ->and($result['release_title'])->toBe('Major Release v99')
        ->and($result['status'])->toBe('ok');
});

test('release service reports up to date when current version is equal or newer', function (): void {
    $current = app(ReleaseService::class)->currentVersion();

    Http::fake([
        'https://api.github.com/repos/*/releases/latest' => Http::response([
            'tag_name' => 'v'.$current,
            'name' => 'Current Release',
            'body' => 'Current release notes.',
            'published_at' => '2026-10-31T00:00:00Z',
            'html_url' => 'https://github.com/example/release',
        ], 200),
    ]);

    $releaseService = app(ReleaseService::class);
    $result = $releaseService->check(force: true);

    expect($result['is_update_available'])->toBeFalse()
        ->and($result['status'])->toBe('ok');
});

test('release service falls back gracefully when api fails', function (): void {
    Http::fake([
        'https://api.github.com/repos/*/releases/latest' => Http::response(null, 500),
    ]);

    $releaseService = app(ReleaseService::class);
    $result = $releaseService->check(force: true);

    expect($result['status'])->toBe('error')
        ->and($result['is_update_available'])->toBeFalse();
});

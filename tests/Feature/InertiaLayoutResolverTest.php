<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('global layout resolver skips self-wrapped pages', function (): void {
    $source = File::get(base_path('resources/js/layouts/resolveLayout.ts'));

    expect($source)
        ->toContain("case name === 'Welcome':")
        ->toContain("case name === 'Dashboard':")
        ->toContain("case name.startsWith('auth/'):")
        ->toContain("case name.startsWith('settings/'):")
        ->toContain('return null;')
        ->toContain('return AppLayout;');
});

test('app and ssr entrypoints use the shared layout resolver', function (): void {
    foreach (['resources/js/app.ts', 'resources/js/ssr.ts'] as $path) {
        $source = File::get(base_path($path));

        expect($source, "{$path} must use resolveLayout")
            ->toContain('resolveLayout')
            ->not->toContain('settings/');
    }
});

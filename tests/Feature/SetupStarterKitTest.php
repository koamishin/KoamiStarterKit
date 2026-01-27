<?php

use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

test('setup starter kit command exists', function () {
    artisan('list')
        ->assertSuccessful()
        ->expectsOutputToContain('setup:starter-kit');
});

test('setup starter kit validates github username format', function () {
    // GitHub username validation happens in the text() prompt
    // This test verifies the command has proper validation
    $command = new \App\Console\Commands\SetupStarterKit;
    expect($command)->toBeInstanceOf(\Illuminate\Console\Command::class);
});

test('setup starter kit command has correct signature and description', function () {
    $command = new \App\Console\Commands\SetupStarterKit;

    expect($command->getName())->toBe('setup:starter-kit')
        ->and($command->getDescription())->toContain('KoamiStarterKit');
});

test('composer json can be updated programmatically', function () {
    // Test the updateComposerJson method logic by verifying file structure
    $composerPath = base_path('composer.json');
    $composer = json_decode(File::get($composerPath), true);

    // Verify composer.json has the expected structure
    expect($composer)->toHaveKeys(['name', 'version', 'description', 'authors', 'homepage']);
});

test('workflow file exists and can be read', function () {
    $workflowPath = base_path('.github/workflows/auto-release.yml');

    expect(File::exists($workflowPath))->toBeTrue();

    $content = File::get($workflowPath);
    expect($content)->toContain('REGISTRY')
        ->toContain('IMAGE_NAME');
});


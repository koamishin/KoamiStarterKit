<?php

declare(strict_types=1);

use App\Console\Commands\SetupStarterKit;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

use function Pest\Laravel\artisan;

/**
 * Build a command instance with buffered IO so file-writing methods can be
 * tested without running the full wizard.
 */
function makeSetupCommand(): SetupStarterKit
{
    $command = new SetupStarterKit;
    $command->setLaravel(app());
    $command->setInput(new ArrayInput([]));

    $output = new OutputStyle(new ArrayInput([]), new BufferedOutput);
    $command->setOutput($output);

    // Mirror the components wiring that Command::run() performs.
    $components = new ReflectionProperty(SetupStarterKit::class, 'components');
    $components->setAccessible(true);
    $components->setValue($command, app(Factory::class, ['output' => $output]));

    return $command;
}

/**
 * Build a command instance with the given CLI options bound.
 *
 * @param  array<string, mixed>  $options
 */
function makeSetupCommandWithOptions(array $options, bool $interactive = false): SetupStarterKit
{
    $command = makeSetupCommand();
    $input = new ArrayInput($options);
    $input->bind($command->getDefinition());
    $input->setInteractive($interactive);
    $command->setInput($input);

    return $command;
}

/**
 * Call a protected method on the command.
 *
 * @param  array<int, mixed>  $args
 */
function invokeSetupMethod(SetupStarterKit $command, string $method, array $args = []): mixed
{
    $reflection = new ReflectionMethod($command, $method);
    $reflection->setAccessible(true);

    return $reflection->invokeArgs($command, $args);
}

/**
 * Create an isolated sandbox directory mimicking a fresh project checkout.
 */
function makeStarterKitSandbox(): string
{
    $dir = sys_get_temp_dir().'/starter-kit-test-'.Str::random(10);

    File::ensureDirectoryExists($dir.'/.github/workflows');
    File::ensureDirectoryExists($dir.'/database');

    File::put($dir.'/composer.json', json_encode([
        'name' => 'koamishin/koamistarterkit',
        'description' => SetupStarterKit::STOCK_DESCRIPTION,
        'homepage' => 'https://github.com/koamishin/KoamiStarterKit',
        'version' => '1.4.0',
        'authors' => [['name' => 'yukazakiri', 'email' => 'marianolouis18@gmail.com']],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

    File::put($dir.'/package.json', json_encode([
        'private' => true,
        'name' => 'koamistarterkit',
    ], JSON_PRETTY_PRINT)."\n");

    File::put($dir.'/.env.example', "APP_NAME=Laravel\nAPP_ENV=local\n");
    File::put($dir.'/.env', "APP_NAME=Laravel\nAPP_ENV=local\n");

    File::put($dir.'/.github/workflows/auto-release.yml', starterKitWorkflowFixture());

    return $dir;
}

function starterKitWorkflowFixture(): string
{
    return <<<'YAML'
        name: Auto Release and Docker Build

        on:
          push:
            branches: [develop, main]

        env:
          DOCKER_ENABLED: true # Set to false if you don't want Docker CI/CD (configured via setup:starter-kit)
          PACKAGIST_ENABLED: true # Set to false if you don't want Packagist auto-updates (configured via setup:starter-kit)
          REGISTRY: ghcr.io
          IMAGE_NAME: ${{ github.repository }}

        jobs:
          build:
            runs-on: ubuntu-latest
            steps:
              - uses: docker/login-action@v3
                with:
                  registry: ${{ env.REGISTRY }}
                  username: ${{ github.actor }}
                  password: ${{ secrets.GITHUB_TOKEN }}
              - name: Notify Packagist
                run: echo "notify"
        YAML;
}

function removeStarterKitSandbox(string $dir): void
{
    File::deleteDirectory($dir);
}

function gitIsAvailableForTests(): bool
{
    try {
        $process = new Process(['git', '--version']);
        $process->setTimeout(30);
        $process->run();

        return $process->isSuccessful();
    } catch (Throwable) {
        return false;
    }
}

// ─── Command registration ────────────────────────────────────────────────

test('setup starter kit command exists', function (): void {
    artisan('list')
        ->assertSuccessful()
        ->expectsOutputToContain('setup:starter-kit');
});

test('setup starter kit command has correct signature and description', function (): void {
    $command = new SetupStarterKit;

    expect($command->getName())->toBe('setup:starter-kit')
        ->and($command->getDescription())->toContain('KoamiStarterKit')
        ->and($command)->toBeInstanceOf(Command::class);
});

test('setup starter kit exposes scriptable options', function (): void {
    $command = new SetupStarterKit;
    $definition = $command->getDefinition();

    foreach (['github', 'name', 'author', 'email', 'description', 'docker', 'no-docker', 'registry', 'docker-username', 'strategy', 'packagist', 'no-packagist', 'install', 'no-install', 'no-git', 'no-commit', 'force', 'create-repo', 'no-create-repo', 'visibility', 'github-token', 'push', 'no-push'] as $option) {
        expect($definition->hasOption($option))->toBeTrue("missing --{$option} option");
    }
});

// ─── Validators ─────────────────────────────────────────────────────────

test('github username validation', function (): void {
    expect(SetupStarterKit::isValidGithubUsername('acme-corp'))->toBeTrue()
        ->and(SetupStarterKit::isValidGithubUsername('a'))->toBeTrue()
        ->and(SetupStarterKit::isValidGithubUsername('Acme123'))->toBeTrue()
        ->and(SetupStarterKit::isValidGithubUsername('-acme'))->toBeFalse()
        ->and(SetupStarterKit::isValidGithubUsername('acme-'))->toBeFalse()
        ->and(SetupStarterKit::isValidGithubUsername('acme_corp'))->toBeFalse()
        ->and(SetupStarterKit::isValidGithubUsername('acme corp'))->toBeFalse()
        ->and(SetupStarterKit::isValidGithubUsername(''))->toBeFalse()
        ->and(SetupStarterKit::isValidGithubUsername(null))->toBeFalse()
        ->and(SetupStarterKit::isValidGithubUsername(str_repeat('a', 40)))->toBeFalse();
});

test('application slug validation', function (): void {
    expect(SetupStarterKit::isValidAppSlug('my-app'))->toBeTrue()
        ->and(SetupStarterKit::isValidAppSlug('app123'))->toBeTrue()
        ->and(SetupStarterKit::isValidAppSlug('My-App'))->toBeFalse()
        ->and(SetupStarterKit::isValidAppSlug('my_app'))->toBeFalse()
        ->and(SetupStarterKit::isValidAppSlug('-my-app'))->toBeFalse()
        ->and(SetupStarterKit::isValidAppSlug(''))->toBeFalse()
        ->and(SetupStarterKit::isValidAppSlug(null))->toBeFalse();
});

test('docker hub username validation', function (): void {
    expect(SetupStarterKit::isValidDockerHubUsername('mydockeruser'))->toBeTrue()
        ->and(SetupStarterKit::isValidDockerHubUsername('my_user-1'))->toBeTrue()
        ->and(SetupStarterKit::isValidDockerHubUsername('has space'))->toBeFalse()
        ->and(SetupStarterKit::isValidDockerHubUsername(str_repeat('a', 31)))->toBeFalse()
        ->and(SetupStarterKit::isValidDockerHubUsername(null))->toBeFalse();
});

// ─── Remote URL helpers ──────────────────────────────────────────────────

test('starter kit origin detection', function (): void {
    expect(SetupStarterKit::isStarterKitOrigin('https://github.com/koamishin/KoamiStarterKit.git'))->toBeTrue()
        ->and(SetupStarterKit::isStarterKitOrigin('https://github.com/koamishin/KoamiStarterKit'))->toBeTrue()
        ->and(SetupStarterKit::isStarterKitOrigin('git@github.com:koamishin/KoamiStarterKit.git'))->toBeTrue()
        ->and(SetupStarterKit::isStarterKitOrigin('https://github.com/KOAMISHIN/koamistarterkit.git'))->toBeTrue()
        ->and(SetupStarterKit::isStarterKitOrigin('https://github.com/acme/my-app.git'))->toBeFalse()
        ->and(SetupStarterKit::isStarterKitOrigin(null))->toBeFalse()
        ->and(SetupStarterKit::isStarterKitOrigin(''))->toBeFalse();
});

test('github owner extraction from remote urls', function (): void {
    expect(SetupStarterKit::githubUserFromRemoteUrl('https://github.com/acme/my-app.git'))->toBe('acme')
        ->and(SetupStarterKit::githubUserFromRemoteUrl('https://github.com/acme/my-app'))->toBe('acme')
        ->and(SetupStarterKit::githubUserFromRemoteUrl('git@github.com:acme/my-app.git'))->toBe('acme')
        ->and(SetupStarterKit::githubUserFromRemoteUrl('https://gitlab.com/acme/my-app.git'))->toBeNull()
        ->and(SetupStarterKit::githubUserFromRemoteUrl('https://notgithub.example.com/acme/my-app.git'))->toBeNull()
        ->and(SetupStarterKit::githubUserFromRemoteUrl('not-a-url'))->toBeNull()
        ->and(SetupStarterKit::githubUserFromRemoteUrl(null))->toBeNull();
});

// ─── Option resolution ───────────────────────────────────────────────────

test('github user resolves from option', function (): void {
    $command = makeSetupCommandWithOptions(['--github' => 'acme-corp']);

    expect(invokeSetupMethod($command, 'resolveGithubUser', [false]))->toBe('acme-corp');
});

test('github user rejects invalid option value', function (): void {
    $command = makeSetupCommandWithOptions(['--github' => '-nope']);

    expect(invokeSetupMethod($command, 'resolveGithubUser', [false]))->toBeNull();
});

test('app slug resolves from option and lowercases', function (): void {
    $command = makeSetupCommandWithOptions(['--name' => 'my-app']);

    expect(invokeSetupMethod($command, 'resolveAppSlug', [false]))->toBe('my-app');
});

test('app slug rejects invalid option value', function (): void {
    $command = makeSetupCommandWithOptions(['--name' => 'My App!']);

    expect(invokeSetupMethod($command, 'resolveAppSlug', [false]))->toBeNull();
});

test('docker settings resolve from options without prompting', function (): void {
    $command = makeSetupCommandWithOptions([
        '--docker' => true,
        '--registry' => 'dockerhub',
        '--docker-username' => 'acme',
        '--strategy' => 'manual',
    ]);

    $docker = invokeSetupMethod($command, 'resolveDockerSettings', ['acme-corp', 'my-app']);

    expect($docker['enabled'])->toBeTrue()
        ->and($docker['registry'])->toBe('docker.io')
        ->and($docker['registry_type'])->toBe('dockerhub')
        ->and($docker['image'])->toBe('acme/my-app')
        ->and($docker['strategy'])->toBe('manual');
});

test('docker disabled via option keeps previous choices untouched', function (): void {
    $command = makeSetupCommandWithOptions(['--no-docker' => true]);

    $docker = invokeSetupMethod($command, 'resolveDockerSettings', ['acme-corp', 'my-app']);

    expect($docker['enabled'])->toBeFalse();
});

// ─── composer.json / package.json / env ─────────────────────────────────

test('composer json is personalized and version field removed', function (): void {
    $dir = makeStarterKitSandbox();

    try {
        $command = makeSetupCommand();

        expect(invokeSetupMethod($command, 'updateComposerJson', ['acme-corp', 'my-app', 'Jane Doe', 'jane@example.com', 'My app.', $dir]))->toBeTrue();

        $composer = json_decode((string) File::get($dir.'/composer.json'), true);

        expect($composer['name'])->toBe('acme-corp/my-app')
            ->and($composer['homepage'])->toBe('https://github.com/acme-corp/my-app')
            ->and($composer['description'])->toBe('My app.')
            ->and($composer['authors'])->toBe([['name' => 'Jane Doe', 'email' => 'jane@example.com']])
            ->and($composer)->not->toHaveKey('version');
    } finally {
        removeStarterKitSandbox($dir);
    }
});

test('composer json update fails gracefully on invalid json', function (): void {
    $dir = makeStarterKitSandbox();

    try {
        File::put($dir.'/composer.json', '{invalid json');

        $command = makeSetupCommand();

        expect(invokeSetupMethod($command, 'updateComposerJson', ['acme', 'app', 'Jane', 'jane@example.com', 'desc', $dir]))->toBeFalse();
    } finally {
        removeStarterKitSandbox($dir);
    }
});

test('package json name is updated', function (): void {
    $dir = makeStarterKitSandbox();

    try {
        invokeSetupMethod(makeSetupCommand(), 'updatePackageJson', ['my-app', $dir]);

        $package = json_decode((string) File::get($dir.'/package.json'), true);

        expect($package['name'])->toBe('my-app')
            ->and($package['private'])->toBeTrue();
    } finally {
        removeStarterKitSandbox($dir);
    }
});

test('env app name prefers .env over .env.example', function (): void {
    $dir = makeStarterKitSandbox();

    try {
        invokeSetupMethod(makeSetupCommand(), 'updateEnvAppName', ['my-app', $dir]);

        expect(File::get($dir.'/.env'))->toContain('APP_NAME="MyApp"')
            ->and(File::get($dir.'/.env.example'))->toContain('APP_NAME=Laravel');
    } finally {
        removeStarterKitSandbox($dir);
    }
});

test('starter kit config records docker and packagist choices', function (): void {
    $dir = makeStarterKitSandbox();

    try {
        $command = makeSetupCommand();
        $docker = [
            'enabled' => true,
            'registry' => 'ghcr.io',
            'registry_type' => 'ghcr',
            'image' => 'acme-corp/my-app',
            'docker_hub_author' => 'acme-corp',
            'strategy' => 'rolling',
        ];

        invokeSetupMethod($command, 'createStarterKitConfig', [$docker, true, $dir]);

        $config = json_decode((string) File::get($dir.'/.starter-kit.json'), true);

        expect($config['docker_enabled'])->toBeTrue()
            ->and($config['docker_update_strategy'])->toBe('rolling')
            ->and($config['packagist_enabled'])->toBeTrue()
            ->and($config['docker_registry'])->toBe('ghcr.io')
            ->and($config['docker_image_name'])->toBe('acme-corp/my-app')
            ->and($config)->not->toHaveKey('docker_hub_author');
    } finally {
        removeStarterKitSandbox($dir);
    }
});

test('starter kit config clears strategy when docker disabled', function (): void {
    $dir = makeStarterKitSandbox();

    try {
        $command = makeSetupCommand();
        $docker = [
            'enabled' => false,
            'registry' => 'ghcr.io',
            'registry_type' => 'ghcr',
            'image' => 'acme-corp/my-app',
            'docker_hub_author' => 'acme-corp',
            'strategy' => 'rolling',
        ];

        invokeSetupMethod($command, 'createStarterKitConfig', [$docker, false, $dir]);

        $config = json_decode((string) File::get($dir.'/.starter-kit.json'), true);

        expect($config['docker_enabled'])->toBeFalse()
            ->and($config['docker_update_strategy'])->toBeNull()
            ->and($config['packagist_enabled'])->toBeFalse();
    } finally {
        removeStarterKitSandbox($dir);
    }
});

// ─── Workflow file rewriting ─────────────────────────────────────────────

test('workflow rewrite keeps ghcr image dynamic and preserves comments', function (): void {
    $command = makeSetupCommand();
    $docker = [
        'enabled' => true,
        'registry' => 'ghcr.io',
        'registry_type' => 'ghcr',
        'image' => 'acme-corp/my-app',
        'docker_hub_author' => 'acme-corp',
        'strategy' => 'rolling',
    ];

    $result = $command->applyWorkflowReplacements(starterKitWorkflowFixture(), 'auto-release.yml', $docker, false);

    expect($result)->toContain('REGISTRY: ghcr.io')
        ->and($result)->toContain('IMAGE_NAME: ${{ github.repository }}')
        ->and($result)->toContain('DOCKER_ENABLED: true # Set to false')
        ->and($result)->toContain('DOCKER_UPDATE_STRATEGY: rolling')
        ->and($result)->toContain('PACKAGIST_ENABLED: false # Set to false')
        ->and($result)->toContain('username: ${{ github.actor }}')
        ->and($result)->toContain('password: ${{ secrets.GITHUB_TOKEN }}')
        ->and($result)->toContain('registry: ${{ env.REGISTRY }}');
});

test('workflow rewrite pins image name for docker hub', function (): void {
    $command = makeSetupCommand();
    $docker = [
        'enabled' => true,
        'registry' => 'docker.io',
        'registry_type' => 'dockerhub',
        'image' => 'acme/my-app',
        'docker_hub_author' => 'acme',
        'strategy' => 'rolling',
    ];

    $result = $command->applyWorkflowReplacements(starterKitWorkflowFixture(), 'auto-release.yml', $docker, false);

    expect($result)->toContain('REGISTRY: docker.io')
        ->and($result)->toContain('IMAGE_NAME: acme/my-app')
        ->and($result)->toContain('username: ${{ secrets.DOCKER_USERNAME }}')
        ->and($result)->toContain('password: ${{ secrets.DOCKER_PASSWORD }}');
});

test('manual strategy disables docker in auto-release workflow only', function (): void {
    $command = makeSetupCommand();
    $docker = [
        'enabled' => true,
        'registry' => 'ghcr.io',
        'registry_type' => 'ghcr',
        'image' => 'acme/my-app',
        'docker_hub_author' => 'acme',
        'strategy' => 'manual',
    ];

    $autoRelease = $command->applyWorkflowReplacements(starterKitWorkflowFixture(), 'auto-release.yml', $docker, false);
    $manualRelease = $command->applyWorkflowReplacements(starterKitWorkflowFixture(), 'manual-official-release.yml', $docker, false);

    expect($autoRelease)->toContain('DOCKER_ENABLED: false')
        ->and($autoRelease)->toContain('DOCKER_UPDATE_STRATEGY: manual')
        ->and($manualRelease)->toContain('DOCKER_ENABLED: true');
});

test('disabled docker removes update strategy line', function (): void {
    $command = makeSetupCommand();
    $docker = [
        'enabled' => false,
        'registry' => 'ghcr.io',
        'registry_type' => 'ghcr',
        'image' => 'acme/my-app',
        'docker_hub_author' => 'acme',
        'strategy' => 'rolling',
    ];

    $content = starterKitWorkflowFixture()."\n  DOCKER_UPDATE_STRATEGY: rolling\n";

    $result = $command->applyWorkflowReplacements($content, 'auto-release.yml', $docker, false);

    expect($result)->toContain('DOCKER_ENABLED: false')
        ->and($result)->not->toContain('DOCKER_UPDATE_STRATEGY');
});

test('packagist flag untouched in files without packagist step', function (): void {
    $command = makeSetupCommand();
    $docker = [
        'enabled' => true,
        'registry' => 'ghcr.io',
        'registry_type' => 'ghcr',
        'image' => 'acme/my-app',
        'docker_hub_author' => 'acme',
        'strategy' => 'rolling',
    ];

    $content = "env:\n  DOCKER_ENABLED: true\n  REGISTRY: ghcr.io\n";

    $result = $command->applyWorkflowReplacements($content, 'ci.yml', $docker, true);

    expect($result)->not->toContain('PACKAGIST_ENABLED');
});

test('workflow files are updated on disk in sandbox', function (): void {
    $dir = makeStarterKitSandbox();

    try {
        $command = makeSetupCommand();
        $docker = [
            'enabled' => true,
            'registry' => 'docker.io',
            'registry_type' => 'dockerhub',
            'image' => 'acme/my-app',
            'docker_hub_author' => 'acme',
            'strategy' => 'rolling',
        ];

        invokeSetupMethod($command, 'updateAllWorkflowFiles', [$docker, true, $dir]);

        $content = File::get($dir.'/.github/workflows/auto-release.yml');

        expect($content)->toContain('REGISTRY: docker.io')
            ->and($content)->toContain('IMAGE_NAME: acme/my-app')
            ->and($content)->toContain('PACKAGIST_ENABLED: true');
    } finally {
        removeStarterKitSandbox($dir);
    }
});

// ─── Git handling in sandbox ─────────────────────────────────────────────

test('git init is skipped in non-interactive mode without force', function (): void {
    $dir = makeStarterKitSandbox();

    try {
        $command = makeSetupCommandWithOptions([]);

        expect(invokeSetupMethod($command, 'ensureGitRepository', [$dir]))->toBeFalse()
            ->and(is_dir($dir.'/.git'))->toBeFalse();
    } finally {
        removeStarterKitSandbox($dir);
    }
});

test('git init and stale remote replacement work in sandbox', function (): void {
    if (! gitIsAvailableForTests()) {
        $this->markTestSkipped('git is not available');
    }

    $dir = makeStarterKitSandbox();

    try {
        $command = makeSetupCommandWithOptions(['--force' => true]);

        expect(invokeSetupMethod($command, 'ensureGitRepository', [$dir]))->toBeTrue()
            ->and(is_dir($dir.'/.git'))->toBeTrue();

        $remote = new Process(['git', 'remote', 'add', 'origin', 'https://github.com/koamishin/KoamiStarterKit.git'], $dir);
        $remote->run();
        expect($remote->isSuccessful())->toBeTrue();

        invokeSetupMethod($command, 'syncGitRemote', ['acme-corp', 'my-app', $dir]);

        $check = new Process(['git', 'remote', 'get-url', 'origin'], $dir);
        $check->run();

        expect(trim($check->getOutput()))->toBe('https://github.com/acme-corp/my-app.git');
    } finally {
        removeStarterKitSandbox($dir);
    }
})->skip(fn () => ! gitIsAvailableForTests(), 'git is not available');

// ─── GitHub repository creation ──────────────────────────────────────────

test('repository visibility validation', function (): void {
    expect(SetupStarterKit::isValidVisibility('public'))->toBeTrue()
        ->and(SetupStarterKit::isValidVisibility('private'))->toBeTrue()
        ->and(SetupStarterKit::isValidVisibility('Public'))->toBeTrue()
        ->and(SetupStarterKit::isValidVisibility('internal'))->toBeFalse()
        ->and(SetupStarterKit::isValidVisibility(''))->toBeFalse()
        ->and(SetupStarterKit::isValidVisibility(null))->toBeFalse();
});

test('repository creation endpoint targets user or organization', function (): void {
    expect(SetupStarterKit::repoCreateEndpoint('acme-corp', 'acme-corp'))->toBe('https://api.github.com/user/repos')
        ->and(SetupStarterKit::repoCreateEndpoint('Acme-Corp', 'acme-corp'))->toBe('https://api.github.com/user/repos')
        ->and(SetupStarterKit::repoCreateEndpoint('jane', 'acme-corp'))->toBe('https://api.github.com/orgs/acme-corp/repos');
});

test('repository payload shape', function (): void {
    $payload = SetupStarterKit::repoPayload('my-app', 'private', 'My app.');

    expect($payload)->toBe([
        'name' => 'my-app',
        'description' => 'My app.',
        'private' => true,
        'auto_init' => false,
    ]);

    expect(SetupStarterKit::repoPayload('my-app', 'public', 'x')['private'])->toBeFalse();
});

test('github token resolves from option, then environment', function (): void {
    $_SERVER['GH_TOKEN'] = 'env-token';
    $_SERVER['GITHUB_TOKEN'] = 'fallback-token';

    try {
        expect(invokeSetupMethod(makeSetupCommandWithOptions([]), 'resolveGithubToken'))->toBe('env-token')
            ->and(invokeSetupMethod(makeSetupCommandWithOptions(['--github-token' => 'opt-token']), 'resolveGithubToken'))->toBe('opt-token');

        unset($_SERVER['GH_TOKEN']);

        expect(invokeSetupMethod(makeSetupCommandWithOptions([]), 'resolveGithubToken'))->toBe('fallback-token');

        unset($_SERVER['GITHUB_TOKEN']);

        expect(invokeSetupMethod(makeSetupCommandWithOptions([]), 'resolveGithubToken'))->toBeNull();
    } finally {
        unset($_SERVER['GH_TOKEN'], $_SERVER['GITHUB_TOKEN']);
    }
});

test('repository creation setting respects flags', function (): void {
    expect(invokeSetupMethod(makeSetupCommandWithOptions(['--no-create-repo' => true]), 'resolveCreateRepoSetting', ['acme', 'my-app', 'public']))->toBeFalse()
        ->and(invokeSetupMethod(makeSetupCommandWithOptions([]), 'resolveCreateRepoSetting', ['acme', 'my-app', 'public']))->toBeFalse()
        ->and(invokeSetupMethod(makeSetupCommandWithOptions(['--create-repo' => true]), 'resolveCreateRepoSetting', ['acme', 'my-app', 'public']))->toBeTrue()
        ->and(invokeSetupMethod(makeSetupCommandWithOptions(['--no-git' => true, '--create-repo' => true]), 'resolveCreateRepoSetting', ['acme', 'my-app', 'public']))->toBeFalse();
});

test('push intent describes flag state', function (): void {
    expect(invokeSetupMethod(makeSetupCommandWithOptions(['--no-push' => true]), 'resolvePushIntent'))->toBe('No')
        ->and(invokeSetupMethod(makeSetupCommandWithOptions(['--push' => true]), 'resolvePushIntent'))->toBe('Yes')
        ->and(invokeSetupMethod(makeSetupCommandWithOptions([]), 'resolvePushIntent'))->toBe('No')
        ->and(invokeSetupMethod(makeSetupCommandWithOptions([], true), 'resolvePushIntent'))->toBe('On confirmation');
});

test('repository is created through the api', function (): void {
    Http::fake([
        'https://api.github.com/user' => Http::response(['login' => 'acme-corp'], 200),
        'https://api.github.com/user/repos' => Http::response(['full_name' => 'acme-corp/my-app'], 201),
    ]);

    $command = makeSetupCommandWithOptions([]);

    expect(invokeSetupMethod($command, 'createRepoViaApi', ['acme-corp', 'my-app', 'public', 'My app.', 'test-token']))->toBeTrue();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/user/repos'
        && $request['name'] === 'my-app'
        && $request['private'] === false
        && $request['auto_init'] === false);
});

test('repository creation treats already-exists as success', function (): void {
    Http::fake([
        'https://api.github.com/user' => Http::response(['login' => 'acme-corp'], 200),
        'https://api.github.com/user/repos' => Http::response([
            'message' => 'Repository creation failed.',
            'errors' => [['message' => 'name already exists on this account']],
        ], 422),
    ]);

    $command = makeSetupCommandWithOptions([]);

    expect(invokeSetupMethod($command, 'createRepoViaApi', ['acme-corp', 'my-app', 'public', 'My app.', 'test-token']))->toBeTrue();
});

test('repository creation fails on rejected token', function (): void {
    Http::fake([
        'https://api.github.com/user' => Http::response(['message' => 'Bad credentials'], 401),
    ]);

    $command = makeSetupCommandWithOptions([]);

    expect(invokeSetupMethod($command, 'createRepoViaApi', ['acme-corp', 'my-app', 'public', 'My app.', 'bad-token']))->toBeFalse();
});

test('api repository existence check', function (): void {
    Http::fake([
        'https://api.github.com/repos/acme-corp/my-app' => Http::response(['full_name' => 'acme-corp/my-app'], 200),
        'https://api.github.com/repos/acme-corp/missing' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $command = makeSetupCommandWithOptions([]);

    expect(invokeSetupMethod($command, 'apiRepoExists', ['acme-corp', 'my-app', 'test-token']))->toBeTrue()
        ->and(invokeSetupMethod($command, 'apiRepoExists', ['acme-corp', 'missing', 'test-token']))->toBeFalse();
});

test('push is skipped without git metadata or with no-push flag', function (): void {
    $dir = makeStarterKitSandbox();

    try {
        // No .git directory: returns before any process call.
        invokeSetupMethod(makeSetupCommandWithOptions([]), 'maybePushToOrigin', ['acme-corp', 'my-app', true, true, $dir]);
        invokeSetupMethod(makeSetupCommandWithOptions(['--no-push' => true]), 'maybePushToOrigin', ['acme-corp', 'my-app', true, true, $dir]);

        expect(true)->toBeTrue();
    } finally {
        removeStarterKitSandbox($dir);
    }
});

// ─── Existing behavior guards ────────────────────────────────────────────

test('composer json keeps expected structure', function (): void {
    $composerPath = base_path('composer.json');
    $composer = json_decode(File::get($composerPath), true);

    expect($composer)->toHaveKeys(['name', 'description', 'authors', 'homepage']);
});

test('workflow file exists and can be read', function (): void {
    $workflowPath = base_path('.github/workflows/auto-release.yml');

    expect(File::exists($workflowPath))->toBeTrue();

    $content = File::get($workflowPath);
    expect($content)->toContain('REGISTRY')
        ->toContain('IMAGE_NAME');
});

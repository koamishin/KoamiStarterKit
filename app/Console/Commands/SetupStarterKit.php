<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class SetupStarterKit extends Command
{
    /**
     * The stock composer description shipped with the starter kit template.
     *
     * Used to detect whether the description was already personalized so
     * re-running the wizard never clobbers a custom description.
     */
    public const STOCK_DESCRIPTION = 'KoamiStarterKit - A modern Laravel starter kit with Vue 3, Inertia.js, Tailwind CSS, Fortify authentication, and Wayfinder routing. Production-ready with Octane, comprehensive testing setup with Pest, and automated CI/CD workflows.';

    /**
     * The name and signature of the console command.
     *
     * Every prompt has a matching option so the wizard is fully scriptable
     * and safe to run with `composer create-project --no-interaction`.
     */
    protected $signature = 'setup:starter-kit
        {--github= : GitHub username or organization that will own the repository}
        {--name= : Application slug (lowercase letters, numbers, hyphens, e.g. my-app)}
        {--author= : Human-readable author name for composer.json}
        {--email= : Author email address for composer.json}
        {--description= : One-line composer.json description}
        {--docker : Enable Docker CI/CD workflows}
        {--no-docker : Disable Docker CI/CD workflows}
        {--registry= : Docker registry to publish to (ghcr or dockerhub)}
        {--docker-username= : Docker Hub username or organization (dockerhub only)}
        {--strategy= : Docker release strategy (rolling or manual)}
        {--install : Run local install steps (key:generate, storage:link, migrate)}
        {--no-install : Skip local install steps}
        {--no-git : Skip Git initialization, remote setup, and commits}
        {--no-commit : Skip creating the initial commit}
        {--create-repo : Create the GitHub repository automatically when possible}
        {--no-create-repo : Never attempt to create the GitHub repository}
        {--visibility= : Visibility of a newly created GitHub repository (public or private)}
        {--github-token= : Personal access token used for the GitHub API fallback}
        {--push : Push the initial commit to GitHub}
        {--no-push : Skip pushing to GitHub}
        {--force : Apply changes without asking for confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Personalize a KoamiStarterKit clone for your project (composer, Docker, CI workflows, Git). Safe to re-run.';

    /**
     * @var array<string, mixed>
     */
    protected array $previousConfig = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('docker') && $this->option('no-docker')) {
            $this->components->error('The --docker and --no-docker options are mutually exclusive.');

            return self::FAILURE;
        }

        if ($this->option('install') && $this->option('no-install')) {
            $this->components->error('The --install and --no-install options are mutually exclusive.');

            return self::FAILURE;
        }

        if ($this->option('create-repo') && $this->option('no-create-repo')) {
            $this->components->error('The --create-repo and --no-create-repo options are mutually exclusive.');

            return self::FAILURE;
        }

        if ($this->option('push') && $this->option('no-push')) {
            $this->components->error('The --push and --no-push options are mutually exclusive.');

            return self::FAILURE;
        }

        $visibility = strtolower((string) ($this->option('visibility') ?? 'public'));

        if (! self::isValidVisibility($visibility)) {
            $this->components->error("Invalid --visibility value [{$this->option('visibility')}]. Expected public or private.");

            return self::FAILURE;
        }

        $registry = $this->option('registry');

        if ($registry !== null && ! in_array(strtolower($registry), ['ghcr', 'dockerhub'], true)) {
            $this->components->error("Invalid --registry value [{$registry}]. Expected ghcr or dockerhub.");

            return self::FAILURE;
        }

        $strategy = $this->option('strategy');

        if ($strategy !== null && ! in_array(strtolower($strategy), ['rolling', 'manual'], true)) {
            $this->components->error("Invalid --strategy value [{$strategy}]. Expected rolling or manual.");

            return self::FAILURE;
        }

        $this->previousConfig = $this->readStarterKitConfig();

        $this->components->info('Welcome to the KoamiStarterKit setup wizard.');
        $this->line('This personalizes your application: composer.json, Docker CI/CD workflows, environment name, and Git.');
        $this->line('Every question can be answered up front with an option — run with --help to script unattended installs.');
        $this->newLine();

        $identity = $this->resolveProjectIdentity();

        if ($identity === null) {
            return self::FAILURE;
        }

        $docker = $this->resolveDockerSettings($identity['github'], $identity['slug']);
        $runInstall = $this->resolveInstallSetting();
        $createRepo = $this->resolveCreateRepoSetting($identity['github'], $identity['slug'], $visibility);

        $this->displaySummary($identity, $docker, $runInstall, $createRepo, $visibility);

        if (! $this->shouldApplyChanges()) {
            $this->components->warn('Setup cancelled — no changes were made.');

            return self::SUCCESS;
        }

        $gitInitialized = $this->ensureGitRepository();

        $this->updateComposerJson($identity['github'], $identity['slug'], $identity['author'], $identity['email'], $identity['description']);
        $this->updatePackageJson($identity['slug']);
        $this->updateEnvAppName($identity['slug']);
        $this->createStarterKitConfig($docker);
        $this->updateAllWorkflowFiles($docker);
        $this->displayRequiredSecrets($docker['enabled'], $docker['registry_type']);

        if ($runInstall) {
            $this->runLocalInstall();
        }

        $repoReady = false;
        $committed = false;

        if (! $this->option('no-git') && (is_dir(base_path().'/.git') || $gitInitialized)) {
            $this->syncGitRemote($identity['github'], $identity['slug']);

            if ($createRepo) {
                $repoReady = $this->maybeCreateGithubRepository($identity['github'], $identity['slug'], $visibility, $identity['description']);
            }
        }

        if ($gitInitialized) {
            $committed = $this->createInitialCommit($identity['slug'], $identity['author'], $identity['email']);
        }

        $this->maybePushToOrigin($identity['github'], $identity['slug'], $repoReady, $committed);

        $this->newLine();
        $this->components->info('KoamiStarterKit setup finished successfully.');
        $this->line('Next steps:');
        $this->line('  1. Review composer.json, .github/workflows/*.yml, and .starter-kit.json');
        $this->line('  2. Add GitHub Secrets (Settings → Secrets and variables → Actions) listed above');
        $this->line('  3. Run: composer install && npm install && php artisan migrate');
        $this->line('  4. Run: composer run dev to start the development server');

        if ($docker['enabled'] && $docker['strategy'] === 'manual') {
            $this->newLine();
            $this->line('Manual Docker releases selected: publish images via Actions → "Manual Official Release".');
        }

        return self::SUCCESS;
    }

    /**
     * Resolve project identity from options, inferred values, or prompts.
     *
     * @return array{github: string, slug: string, author: string, email: string, description: string}|null
     */
    protected function resolveProjectIdentity(): ?array
    {
        $interactive = $this->input->isInteractive();

        $github = $this->resolveGithubUser($interactive);

        if ($github === null) {
            return null;
        }

        $slug = $this->resolveAppSlug($interactive);

        if ($slug === null) {
            return null;
        }

        $composer = $this->readComposerJson();
        $existingAuthor = $composer['authors'][0]['name'] ?? null;
        $existingEmail = $composer['authors'][0]['email'] ?? null;

        $author = $this->option('author') ?? $existingAuthor;

        if ($author === null || trim($author) === '') {
            if (! $interactive) {
                $author = $github;
            } else {
                $author = $this->ask('Author Name', $github);

                while (trim((string) $author) === '') {
                    $this->components->error('Author name cannot be empty.');
                    $author = $this->ask('Author Name', $github);
                }
            }
        }

        $email = $this->option('email') ?? $existingEmail;

        if ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (! $interactive) {
                $email = is_string($existingEmail) && $existingEmail !== '' ? $existingEmail : 'hello@example.com';
                $this->components->warn('No valid author email found — using "'.$email.'". Re-run php artisan setup:starter-kit to personalize.');
            } else {
                $email = $this->ask('Author Email', is_string($existingEmail) ? $existingEmail : null);

                while (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->components->error('Please enter a valid email address.');
                    $email = $this->ask('Author Email');
                }
            }
        }

        $description = $this->option('description') ?? $this->defaultDescription($composer, $slug);

        return [
            'github' => $github,
            'slug' => $slug,
            'author' => trim((string) $author),
            'email' => $email,
            'description' => $description,
        ];
    }

    /**
     * Resolve the GitHub username or organization.
     */
    protected function resolveGithubUser(bool $interactive): ?string
    {
        $fromOption = $this->option('github');

        if (is_string($fromOption) && $fromOption !== '') {
            if (! self::isValidGithubUsername($fromOption)) {
                $this->components->error("Invalid --github value [{$fromOption}]. Use alphanumeric characters and hyphens (max 39).");

                return null;
            }

            return $fromOption;
        }

        $inferred = $this->inferGithubUser();

        if ($inferred !== null) {
            return $inferred;
        }

        if (! $interactive) {
            $this->components->warn('Could not determine the GitHub owner — using "my-org" as a placeholder. Re-run php artisan setup:starter-kit to personalize.');

            return 'my-org';
        }

        $answer = $this->ask('GitHub Username or Organization', 'my-org');

        while (! self::isValidGithubUsername((string) $answer)) {
            $this->components->error('GitHub usernames may only contain alphanumeric characters and hyphens (max 39, cannot start or end with a hyphen).');
            $answer = $this->ask('GitHub Username or Organization', 'my-org');
        }

        return (string) $answer;
    }

    /**
     * Resolve the application slug.
     */
    protected function resolveAppSlug(bool $interactive): ?string
    {
        $fromOption = $this->option('name');

        if (is_string($fromOption) && $fromOption !== '') {
            if (! self::isValidAppSlug($fromOption)) {
                $this->components->error("Invalid --name value [{$fromOption}]. Use lowercase letters, numbers, and hyphens.");

                return null;
            }

            return strtolower($fromOption);
        }

        $inferred = $this->inferAppSlug();

        if ($inferred !== null) {
            return $inferred;
        }

        if (! $interactive) {
            return 'my-app';
        }

        $answer = $this->ask('Application Name (lowercase, hyphens only)', 'my-app');

        while (! self::isValidAppSlug((string) $answer)) {
            $this->components->error('Application name must be lowercase with only numbers and hyphens (cannot start or end with a hyphen).');
            $answer = $this->ask('Application Name (lowercase, hyphens only)', 'my-app');
        }

        return strtolower((string) $answer);
    }

    /**
     * Infer the GitHub owner from the git remote or composer.json.
     */
    protected function inferGithubUser(): ?string
    {
        [$ok, $remote] = $this->runGit(['remote', 'get-url', 'origin']);

        if ($ok) {
            $user = self::githubUserFromRemoteUrl(trim($remote));

            if ($user !== null && ! self::isStarterKitOrigin(trim($remote))) {
                return $user;
            }
        }

        $composer = $this->readComposerJson();

        if (isset($composer['name']) && str_contains($composer['name'], '/')) {
            [$vendor] = explode('/', $composer['name'], 2);

            if (self::isValidGithubUsername($vendor) && strtolower($vendor) !== 'koamishin') {
                return $vendor;
            }
        }

        return null;
    }

    /**
     * Infer the application slug from composer.json or the directory name.
     */
    protected function inferAppSlug(): ?string
    {
        $composer = $this->readComposerJson();

        if (isset($composer['name']) && str_contains($composer['name'], '/')) {
            [, $package] = explode('/', $composer['name'], 2);

            if (self::isValidAppSlug($package) && strtolower($package) !== 'koamistarterkit') {
                return strtolower($package);
            }
        }

        $candidate = Str::slug(basename(base_path()));

        if (self::isValidAppSlug($candidate)) {
            return $candidate;
        }

        return null;
    }

    /**
     * Resolve Docker settings from options, previous config, or prompts.
     *
     * @return array{enabled: bool, registry: string, registry_type: string, image: string, docker_hub_author: string, strategy: string}
     */
    protected function resolveDockerSettings(string $github, string $slug): array
    {
        $interactive = $this->input->isInteractive();
        $previous = $this->previousConfig;

        $enabled = $this->option('docker') ? true : ($this->option('no-docker') ? false : null);

        if ($enabled === null) {
            if ($interactive) {
                $this->line('Docker CI/CD builds and publishes your container image on every push to main (or on manual release).');
                $enabled = $this->confirm('Enable Docker CI/CD for this application?', false);
            } else {
                $enabled = (bool) ($previous['docker_enabled'] ?? false);
            }
        }

        $registryType = strtolower((string) ($this->option('registry') ?? $previous['docker_registry_type'] ?? 'ghcr'));

        if (! in_array($registryType, ['ghcr', 'dockerhub'], true)) {
            $registryType = 'ghcr';
        }

        $dockerHubAuthor = (string) ($this->option('docker-username') ?? $previous['docker_hub_author'] ?? $github);

        $strategy = strtolower((string) ($this->option('strategy') ?? $previous['docker_update_strategy'] ?? 'rolling'));

        if (! in_array($strategy, ['rolling', 'manual'], true)) {
            $strategy = 'rolling';
        }

        if ($enabled && $interactive && $this->option('registry') === null) {
            $registryType = $this->choice(
                'Which Docker registry would you like to use?',
                [
                    'ghcr' => 'GitHub Container Registry (ghcr.io) — zero-config, recommended',
                    'dockerhub' => 'Docker Hub (docker.io) — public registry with broad ecosystem support',
                ],
                $registryType
            );
        }

        if ($enabled && $registryType === 'dockerhub') {
            if ($interactive && $this->option('docker-username') === null) {
                $dockerHubAuthor = $this->ask('Docker Hub Username or Organization', $dockerHubAuthor);

                while (! self::isValidDockerHubUsername((string) $dockerHubAuthor)) {
                    $this->components->error('Docker Hub names may only contain alphanumeric characters, underscores, and hyphens (max 30).');
                    $dockerHubAuthor = $this->ask('Docker Hub Username or Organization', $github);
                }
            }

            if (! self::isValidDockerHubUsername($dockerHubAuthor)) {
                $dockerHubAuthor = $github;
            }
        }

        if ($enabled && $interactive && $this->option('strategy') === null) {
            $this->line('Rolling builds an image on every push (tags: 1.2.3-beta.1, beta, latest, sha-abc1234).');
            $this->line('Manual only builds on release trigger (tags: 1.2.3, 1.2, 1, latest, sha-abc1234).');
            $strategy = $this->choice(
                'Docker image update strategy',
                [
                    'rolling' => 'Rolling releases — auto-build on every push to main',
                    'manual' => 'Manual releases — build only on explicit release trigger',
                ],
                $strategy
            );
        }

        $registry = $registryType === 'ghcr' ? 'ghcr.io' : 'docker.io';
        $image = strtolower(($registryType === 'dockerhub' ? $dockerHubAuthor : $github).'/'.$slug);

        return [
            'enabled' => $enabled,
            'registry' => $registry,
            'registry_type' => $registryType,
            'image' => $image,
            'docker_hub_author' => $dockerHubAuthor,
            'strategy' => $strategy,
        ];
    }

    /**
     * Resolve whether to run the local install steps.
     */
    protected function resolveInstallSetting(): bool
    {
        if ($this->option('install')) {
            return true;
        }

        if ($this->option('no-install')) {
            return false;
        }

        if ($this->input->isInteractive()) {
            return $this->confirm('Run local install now? (app key, storage link, database migrate)', true);
        }

        return false;
    }

    /**
     * Determine whether changes should be applied.
     */
    protected function shouldApplyChanges(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return true;
        }

        return $this->confirm('Apply these settings?', true);
    }

    /**
     * Display the resolved configuration summary.
     *
     * @param  array{github: string, slug: string, author: string, email: string, description: string}  $identity
     * @param  array{enabled: bool, registry: string, registry_type: string, image: string, docker_hub_author: string, strategy: string}  $docker
     */
    protected function displaySummary(array $identity, array $docker, bool $runInstall, bool $createRepo, string $visibility): void
    {
        $rows = [
            ['Composer Package', $identity['github'].'/'.$identity['slug']],
            ['Author', "{$identity['author']} <{$identity['email']}>"],
            ['GitHub Repository', "https://github.com/{$identity['github']}/{$identity['slug']}"],
        ];

        if ($docker['enabled']) {
            $rows[] = ['Docker Registry', $docker['registry_type'] === 'ghcr' ? 'ghcr.io (GitHub Container Registry)' : 'docker.io (Docker Hub)'];
            $rows[] = ['Docker Image', $docker['registry'].'/'.$docker['image']];
            $rows[] = ['Docker Strategy', $docker['strategy'] === 'rolling' ? 'Rolling (auto-build on push)' : 'Manual (release on trigger only)'];
        } else {
            $rows[] = ['Docker', 'Not configured'];
        }

        $rows[] = ['Local Install', $runInstall ? 'Yes (key, storage link, migrate)' : 'Skipped'];
        $rows[] = ['Create GitHub Repo', $createRepo ? "Yes ({$visibility})" : 'No'];
        $rows[] = ['Push to GitHub', $this->resolvePushIntent()];

        $this->table(['Setting', 'Value'], $rows);
    }

    /**
     * Initialize a Git repository when none exists.
     *
     * Returns true when a repository was freshly initialized in this run.
     */
    protected function ensureGitRepository(?string $basePath = null): bool
    {
        $basePath ??= base_path();

        if ($this->option('no-git')) {
            return false;
        }

        if (is_dir($basePath.'/.git')) {
            $this->line('Git repository already initialized — skipping.');

            return false;
        }

        if (! $this->gitIsAvailable($basePath)) {
            $this->components->warn('Git is not installed. Skipping repository initialization.');

            return false;
        }

        if (! $this->input->isInteractive() && ! $this->option('force')) {
            $this->line('No Git repository detected — skipping initialization in non-interactive mode (run git init manually).');

            return false;
        }

        if ($this->input->isInteractive() && ! $this->option('force') && ! $this->confirm('No Git repository detected. Initialize one?', true)) {
            $this->components->warn('Skipped Git initialization. Run "git init" manually when ready.');

            return false;
        }

        [$ok] = $this->runGit(['init', '-b', 'main'], $basePath);

        if (! $ok) {
            [$ok] = $this->runGit(['init'], $basePath);
        }

        if (! $ok) {
            $this->components->error('Failed to initialize the Git repository. Run "git init" manually.');

            return false;
        }

        $this->runGit(['branch', '-M', 'main'], $basePath);
        $this->line('Initialized an empty Git repository.');

        return true;
    }

    /**
     * Point the origin remote at the new repository.
     *
     * Clones of the starter kit still point at koamishin/KoamiStarterKit;
     * those stale remotes are replaced automatically.
     */
    protected function syncGitRemote(string $github, string $slug, ?string $basePath = null): void
    {
        $basePath ??= base_path();

        if ($this->option('no-git') || ! is_dir($basePath.'/.git') || ! $this->gitIsAvailable($basePath)) {
            return;
        }

        $expected = "https://github.com/{$github}/{$slug}.git";

        [$ok, $current] = $this->runGit(['remote', 'get-url', 'origin'], $basePath);
        $current = $ok ? trim($current) : null;

        if ($current === null) {
            $shouldAdd = $this->option('force') || ! $this->input->isInteractive() || $this->confirm('Add a GitHub remote origin?', true);

            if (! $shouldAdd) {
                return;
            }

            $url = $this->input->isInteractive() && ! $this->option('force')
                ? (string) $this->ask('Remote Repository URL', $expected)
                : $expected;

            [$added] = $this->runGit(['remote', 'add', 'origin', $url], $basePath);

            if ($added) {
                $this->line("Added remote origin → {$url}");
            } else {
                $this->components->warn('Could not add the git remote. Add it manually with: git remote add origin '.$url);
            }

            return;
        }

        if (self::isStarterKitOrigin($current)) {
            $this->runGit(['remote', 'set-url', 'origin', $expected], $basePath);
            $this->line("Replaced starter-kit remote with → {$expected}");

            return;
        }

        if ($current === $expected) {
            $this->line("Remote origin already points to → {$expected}");

            return;
        }

        if ($this->input->isInteractive() && $this->confirm("Remote origin currently points to {$current}. Replace it with {$expected}?", false)) {
            $this->runGit(['remote', 'set-url', 'origin', $expected], $basePath);
            $this->line("Updated remote origin → {$expected}");
        } else {
            $this->line("Kept existing remote origin → {$current}");
        }
    }

    /**
     * Create the initial commit with the author's identity.
     *
     * Returns true when a commit was created in this run.
     */
    protected function createInitialCommit(string $slug, string $author, string $email, ?string $basePath = null): bool
    {
        $basePath ??= base_path();

        if ($this->option('no-git') || $this->option('no-commit')) {
            return false;
        }

        $shouldCommit = $this->option('force') || ! $this->input->isInteractive() || $this->confirm('Create an initial commit with all current files?', true);

        if (! $shouldCommit) {
            $this->line('Skipped. Create your first commit manually whenever you are ready.');

            return false;
        }

        [$staged] = $this->runGit(['add', '-A'], $basePath);

        if (! $staged) {
            $this->components->error('Failed to stage files. Run "git add -A" manually.');

            return false;
        }

        $message = "Initial commit: initialize {$slug}\n\nInitialized from KoamiStarterKit — a modern Laravel starter kit with Vue 3, Inertia.js, Tailwind CSS, and Fortify authentication.";

        [$committed] = $this->runGit(['-c', "user.name={$author}", '-c', "user.email={$email}", 'commit', '-m', $message], $basePath);

        if ($committed) {
            $this->line('Created initial commit.');

            return true;
        }

        $this->components->warn('Could not create the initial commit (is there anything to commit?). Run "git commit" manually.');

        return false;
    }

    /**
     * Describe the push intent for the summary table.
     */
    protected function resolvePushIntent(): string
    {
        if ($this->option('no-push') || $this->option('no-git')) {
            return 'No';
        }

        if ($this->option('push') || $this->option('force')) {
            return 'Yes';
        }

        return $this->input->isInteractive() ? 'On confirmation' : 'No';
    }

    /**
     * Resolve whether the wizard should attempt to create the GitHub repository.
     */
    protected function resolveCreateRepoSetting(string $github, string $slug, string $visibility): bool
    {
        if ($this->option('no-git') || $this->option('no-create-repo')) {
            return false;
        }

        if ($this->option('create-repo') || $this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        $this->line("The wizard can create github.com/{$github}/{$slug} ({$visibility}) for you right now — no manual repo setup needed.");

        return $this->confirm("Create the GitHub repository {$github}/{$slug} automatically?", true);
    }

    /**
     * Create the GitHub repository, trying gh CLI first and the API second.
     *
     * Returns true when the repository exists afterwards (created or already present).
     */
    protected function maybeCreateGithubRepository(string $github, string $slug, string $visibility, string $description, ?string $basePath = null): bool
    {
        $basePath ??= base_path();

        if ($this->ghIsAvailable($basePath) && $this->ghIsAuthenticated($basePath)) {
            if ($this->ghRepoExists($github, $slug, $basePath)) {
                $this->line("GitHub repository {$github}/{$slug} already exists — reusing it.");

                return true;
            }

            return $this->createRepoViaGh($github, $slug, $visibility, $description, $basePath);
        }

        $token = $this->resolveGithubToken();

        if ($token !== null) {
            if ($this->apiRepoExists($github, $slug, $token)) {
                $this->line("GitHub repository {$github}/{$slug} already exists — reusing it.");

                return true;
            }

            return $this->createRepoViaApi($github, $slug, $visibility, $description, $token);
        }

        if (! $this->input->isInteractive()) {
            $this->components->warn('Could not create the GitHub repository automatically (no gh CLI session or token found).');
            $this->printManualRepoInstructions($github, $slug);

            return false;
        }

        return $this->guidedRepoFallback($github, $slug, $visibility, $description);
    }

    /**
     * Create the repository with the gh CLI.
     */
    protected function createRepoViaGh(string $github, string $slug, string $visibility, string $description, ?string $basePath = null): bool
    {
        $arguments = ['repo', 'create', "{$github}/{$slug}", $visibility === 'private' ? '--private' : '--public'];

        if (trim($description) !== '') {
            $arguments[] = '--description';
            $arguments[] = $description;
        }

        [$ok, $output] = $this->runGh($arguments, $basePath);

        if ($ok) {
            $this->line("Created GitHub repository → https://github.com/{$github}/{$slug}");

            return true;
        }

        if (str_contains(strtolower($output), 'already exists')) {
            $this->line("GitHub repository {$github}/{$slug} already exists — reusing it.");

            return true;
        }

        $this->components->warn("gh could not create the repository: {$output}");

        if ($this->input->isInteractive()) {
            return $this->guidedRepoFallback($github, $slug, $visibility, $description);
        }

        $this->printManualRepoInstructions($github, $slug);

        return false;
    }

    /**
     * Create the repository with the GitHub REST API.
     */
    protected function createRepoViaApi(string $github, string $slug, string $visibility, string $description, string $token): bool
    {
        try {
            $userResponse = Http::withToken($token)
                ->accept('application/vnd.github+json')
                ->timeout(30)
                ->get('https://api.github.com/user');

            if (! $userResponse->successful()) {
                $this->components->warn('The GitHub token was rejected. Check its value and try again.');

                return false;
            }

            $endpoint = self::repoCreateEndpoint((string) $userResponse->json('login'), $github);

            $response = Http::withToken($token)
                ->accept('application/vnd.github+json')
                ->timeout(30)
                ->post($endpoint, self::repoPayload($slug, $visibility, $description));

            if ($response->successful()) {
                $this->line("Created GitHub repository → https://github.com/{$github}/{$slug}");

                return true;
            }

            if ($response->status() === 422 && str_contains(strtolower((string) $response->body()), 'already exists')) {
                $this->line("GitHub repository {$github}/{$slug} already exists — reusing it.");

                return true;
            }

            $this->components->warn('GitHub API could not create the repository (HTTP '.$response->status().').');

            return false;
        } catch (\Throwable $e) {
            $this->components->warn('GitHub API request failed ('.$e->getMessage().').');

            return false;
        }
    }

    /**
     * Help the user create the repository when automation has no credentials.
     *
     * Offers a token prompt as a last automation attempt before falling back
     * to the manual link.
     */
    protected function guidedRepoFallback(string $github, string $slug, string $visibility, string $description): bool
    {
        $this->line('Automatic creation needs authentication. Easiest path: install the GitHub CLI and sign in once.');
        $this->line('  Windows: winget install --id GitHub.cli   macOS: brew install gh   Linux: see https://github.com/cli/cli');
        $this->line('  Then run: gh auth login');

        if ($this->confirm('Have a personal access token instead? (paste it to create the repo now)', false)) {
            $token = $this->secret('GitHub token (classic token with repo scope, or fine-grained with Administration: read/write)');

            if (is_string($token) && trim($token) !== '') {
                if ($this->apiRepoExists($github, $slug, trim($token))) {
                    $this->line("GitHub repository {$github}/{$slug} already exists — reusing it.");

                    return true;
                }

                return $this->createRepoViaApi($github, $slug, $visibility, $description, trim($token));
            }
        }

        $this->printManualRepoInstructions($github, $slug);

        return false;
    }

    /**
     * Print the manual repository creation fallback.
     */
    protected function printManualRepoInstructions(string $github, string $slug): void
    {
        $this->line("Create it manually in one click: https://github.com/new (owner {$github}, name {$slug}), then:");
        $this->line("  git remote set-url origin https://github.com/{$github}/{$slug}.git");
    }

    /**
     * Check whether the repository already exists via the API.
     */
    protected function apiRepoExists(string $github, string $slug, string $token): bool
    {
        try {
            $response = Http::withToken($token)
                ->accept('application/vnd.github+json')
                ->timeout(30)
                ->get("https://api.github.com/repos/{$github}/{$slug}");

            return $response->status() === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check whether the repository already exists via gh.
     */
    protected function ghRepoExists(string $github, string $slug, ?string $basePath = null): bool
    {
        [$ok] = $this->runGh(['repo', 'view', "{$github}/{$slug}", '--json', 'name'], $basePath);

        return $ok;
    }

    /**
     * Resolve the GitHub token from the option or the environment.
     */
    protected function resolveGithubToken(): ?string
    {
        $fromOption = $this->option('github-token');

        if (is_string($fromOption) && trim($fromOption) !== '') {
            return trim($fromOption);
        }

        foreach (['GH_TOKEN', 'GITHUB_TOKEN'] as $variable) {
            $value = env($variable);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Push the initial commit to the new repository when requested.
     */
    protected function maybePushToOrigin(string $github, string $slug, bool $repoReady, bool $committed, ?string $basePath = null): void
    {
        $basePath ??= base_path();

        if ($this->option('no-git') || $this->option('no-push')) {
            return;
        }

        if (! is_dir($basePath.'/.git') || ! $this->gitIsAvailable($basePath)) {
            return;
        }

        [$ok, $current] = $this->runGit(['remote', 'get-url', 'origin'], $basePath);
        $expected = "https://github.com/{$github}/{$slug}.git";

        if (! $ok || trim($current) !== $expected) {
            return;
        }

        $wantsPush = $this->option('push') || $this->option('force');

        if (! $wantsPush && ! $committed) {
            return;
        }

        if (! $wantsPush) {
            if (! $this->input->isInteractive()) {
                $wantsPush = $repoReady;
            } else {
                $wantsPush = $this->confirm("Push the initial commit to {$github}/{$slug} now?", $repoReady);
            }
        }

        if (! $wantsPush) {
            $this->line('Skipped push. When ready: git push -u origin main');

            return;
        }

        [$pushed, $output] = $this->runGit(['push', '-u', 'origin', 'main'], $basePath);

        if ($pushed) {
            $this->line("Pushed to GitHub → https://github.com/{$github}/{$slug}");
        } else {
            $this->components->warn('Push failed — the repository may not exist yet or credentials are missing.');
            $this->line('Create the repo, then run: git push -u origin main');
            $this->line($output);
        }
    }

    /**
     * Update composer.json with the new project details.
     */
    protected function updateComposerJson(string $github, string $slug, string $author, string $email, string $description, ?string $basePath = null): bool
    {
        $basePath ??= base_path();
        $composerPath = $basePath.'/composer.json';

        if (! File::exists($composerPath)) {
            $this->components->error("composer.json not found at {$composerPath}");

            return false;
        }

        $composer = json_decode((string) File::get($composerPath), true);

        if (! is_array($composer)) {
            $this->components->error('Failed to parse composer.json — it may contain invalid JSON.');

            return false;
        }

        $composer['name'] = strtolower($github.'/'.$slug);
        $composer['description'] = $description;
        $composer['homepage'] = "https://github.com/{$github}/{$slug}";
        $composer['authors'] = [
            ['name' => $author, 'email' => $email],
        ];

        unset($composer['version']);

        File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        $this->line('Updated composer.json.');

        return true;
    }

    /**
     * Set the private package name in package.json.
     */
    protected function updatePackageJson(string $slug, ?string $basePath = null): void
    {
        $basePath ??= base_path();
        $packagePath = $basePath.'/package.json';

        if (! File::exists($packagePath)) {
            return;
        }

        $package = json_decode((string) File::get($packagePath), true);

        if (! is_array($package)) {
            $this->components->warn('Skipped package.json — it contains invalid JSON.');

            return;
        }

        $package['name'] = strtolower($slug);

        File::put($packagePath, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        $this->line('Updated package.json.');
    }

    /**
     * Set APP_NAME in .env (or .env.example when .env is missing).
     */
    protected function updateEnvAppName(string $slug, ?string $basePath = null): void
    {
        $basePath ??= base_path();
        $envPath = File::exists($basePath.'/.env') ? $basePath.'/.env' : $basePath.'/.env.example';

        if (! File::exists($envPath)) {
            return;
        }

        $appName = Str::studly($slug);
        $content = (string) File::get($envPath);

        if (preg_match('/^APP_NAME=.*$/m', $content)) {
            $content = preg_replace('/^APP_NAME=.*$/m', 'APP_NAME="'.$appName.'"', $content);
        } else {
            $content = 'APP_NAME="'.$appName."\"\n".$content;
        }

        File::put($envPath, $content);
        $this->line('Set APP_NAME to "'.$appName.'" in '.basename($envPath).'.');
    }

    /**
     * Create the starter kit state file.
     *
     * @param  array{enabled: bool, registry: string, registry_type: string, image: string, docker_hub_author: string, strategy: string}  $docker
     */
    protected function createStarterKitConfig(array $docker, ?string $basePath = null): void
    {
        $basePath ??= base_path();

        $config = [
            'docker_enabled' => $docker['enabled'],
            'docker_update_strategy' => $docker['enabled'] ? $docker['strategy'] : null,
            'docker_registry' => $docker['registry'],
            'docker_registry_type' => $docker['registry_type'],
            'docker_image_name' => $docker['image'],
            'configured_at' => now()->toIso8601String(),
        ];

        if ($docker['registry_type'] === 'dockerhub' && $docker['docker_hub_author'] !== '') {
            $config['docker_hub_author'] = $docker['docker_hub_author'];
        }

        File::put($basePath.'/.starter-kit.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        $this->line('Wrote .starter-kit.json configuration.');
    }

    /**
     * Update every GitHub workflow file with the Docker settings.
     *
     * Also strips any leftover Packagist automation so re-running the wizard
     * cleans up projects scaffolded from older kit versions.
     *
     * @param  array{enabled: bool, registry: string, registry_type: string, image: string, docker_hub_author: string, strategy: string}  $docker
     */
    protected function updateAllWorkflowFiles(array $docker, ?string $basePath = null): void
    {
        $basePath ??= base_path();
        $workflowDir = $basePath.'/.github/workflows';
        $workflowFiles = ['auto-release.yml', 'docker-latest.yml', 'manual-official-release.yml'];

        foreach ($workflowFiles as $workflowFile) {
            $filePath = $workflowDir.'/'.$workflowFile;

            if (! File::exists($filePath)) {
                continue;
            }

            File::put($filePath, $this->applyWorkflowReplacements(
                content: (string) File::get($filePath),
                workflowFile: $workflowFile,
                docker: $docker,
            ));

            $this->line("Updated .github/workflows/{$workflowFile}.");
        }
    }

    /**
     * Apply Docker settings to a single workflow file's content.
     *
     * @param  array{enabled: bool, registry: string, registry_type: string, image: string, docker_hub_author: string, strategy: string}  $docker
     */
    public function applyWorkflowReplacements(string $content, string $workflowFile, array $docker): string
    {
        $content = (string) preg_replace(
            '/^(\s*REGISTRY:\s*).+$/m',
            '${1}'.$docker['registry'],
            $content
        );

        $content = $this->replaceImageName($content, $docker);

        if ($docker['enabled']) {
            $content = $this->updateDockerCredentials($content, $docker['registry_type']);
        }

        $content = $this->updateDockerEnabledVar($content, $docker['enabled'], $docker['strategy'], $workflowFile);
        $content = $this->updateDockerUpdateStrategyVar($content, $docker['enabled'] ? $docker['strategy'] : null);

        return $this->removePackagistLeftovers($content);
    }

    /**
     * Replace the IMAGE_NAME env value.
     *
     * GHCR keeps the dynamic `${{ github.repository }}` default so forks and
     * renames keep working; Docker Hub needs the pinned `user/image` name.
     *
     * @param  array{enabled: bool, registry: string, registry_type: string, image: string, docker_hub_author: string, strategy: string}  $docker
     */
    protected function replaceImageName(string $content, array $docker): string
    {
        $target = $docker['registry_type'] === 'dockerhub' ? $docker['image'] : '${{ github.repository }}';

        return (string) preg_replace_callback(
            '/^(\s*IMAGE_NAME:\s*).+$/m',
            static fn (array $matches): string => $matches[1].$target,
            $content
        );
    }

    /**
     * Update or add the DOCKER_ENABLED environment variable.
     */
    public function updateDockerEnabledVar(string $content, bool $enabled, string $dockerUpdateStrategy, string $workflowFile): string
    {
        $effectiveEnabled = $enabled;

        if ($workflowFile === 'auto-release.yml' && $dockerUpdateStrategy === 'manual') {
            $effectiveEnabled = false;
        }

        $enabledStr = $effectiveEnabled ? 'true' : 'false';

        if (preg_match('/^(\s*DOCKER_ENABLED:\s*)(true|false)/m', $content)) {
            return (string) preg_replace(
                '/^(\s*DOCKER_ENABLED:\s*)(true|false)/m',
                '${1}'.$enabledStr,
                $content
            );
        }

        return (string) preg_replace(
            '/^env:\n(\s*REGISTRY:)/m',
            "env:\n  DOCKER_ENABLED: {$enabledStr}  # Set to false if you don't want Docker CI/CD (configured via setup:starter-kit)\n$1",
            $content,
            1
        );
    }

    /**
     * Update, add, or remove the DOCKER_UPDATE_STRATEGY environment variable.
     */
    public function updateDockerUpdateStrategyVar(string $content, ?string $strategy): string
    {
        if ($strategy === null) {
            return (string) preg_replace('/^\s*DOCKER_UPDATE_STRATEGY:.*\n?/m', '', $content);
        }

        if (preg_match('/^(\s*DOCKER_UPDATE_STRATEGY:\s*)(rolling|manual)/m', $content)) {
            return (string) preg_replace(
                '/^(\s*DOCKER_UPDATE_STRATEGY:\s*)(rolling|manual)/m',
                '${1}'.$strategy,
                $content
            );
        }

        if (preg_match('/^(\s*DOCKER_ENABLED:\s*(?:true|false).*)$/m', $content, $matches)) {
            return str_replace(
                $matches[1],
                $matches[1]."\n  DOCKER_UPDATE_STRATEGY: {$strategy}  # rolling=auto-build on push, manual=release on explicit trigger",
                $content
            );
        }

        return $content;
    }

    /**
     * Strip Packagist automation leftovers from a workflow file.
     *
     * Removes PACKAGIST_* environment lines, the "Notify Packagist" step,
     * and fixes the notify section comment. Idempotent: files without
     * Packagist content are returned unchanged.
     */
    public function removePackagistLeftovers(string $content): string
    {
        $content = (string) preg_replace('/^\s*PACKAGIST_(ENABLED|USERNAME|TOKEN):.*\n?/m', '', $content);

        $content = (string) preg_replace(
            '/^ {6}- name: Notify Packagist\n(?:^ {8,}.*\n?)+/m',
            '',
            $content
        );

        return str_replace(
            'log summary, ping Packagist, and send a Discord notification',
            'log summary and send a Discord notification',
            $content
        );
    }

    /**
     * Update Docker registry credentials in a workflow file based on registry type.
     */
    public function updateDockerCredentials(string $content, string $registryType): string
    {
        if ($registryType === 'ghcr') {
            $content = (string) preg_replace(
                '/username: \$\{\{ secrets\.DOCKER_USERNAME \}\}/',
                'username: ${{ github.actor }}',
                $content
            );

            return (string) preg_replace(
                '/password: \$\{\{ secrets\.DOCKER_PASSWORD \}\}/',
                'password: ${{ secrets.GITHUB_TOKEN }}',
                $content
            );
        }

        $content = (string) preg_replace(
            '/username: \$\{\{ github\.actor \}\}/',
            'username: ${{ secrets.DOCKER_USERNAME }}',
            $content
        );

        return (string) preg_replace(
            '/password: \$\{\{ secrets\.GITHUB_TOKEN \}\}/',
            'password: ${{ secrets.DOCKER_PASSWORD }}',
            $content
        );
    }

    /**
     * Run the local install steps, warning (not failing) on errors.
     */
    protected function runLocalInstall(?string $basePath = null): void
    {
        $basePath ??= base_path();

        $this->newLine();
        $this->components->info('Running local install steps...');

        if (! File::exists($basePath.'/.env') && File::exists($basePath.'/.env.example')) {
            File::copy($basePath.'/.env.example', $basePath.'/.env');
            $this->line('Created .env from .env.example.');
        }

        $sqlitePath = $basePath.'/database/database.sqlite';

        if (config('database.default') === 'sqlite' && ! File::exists($sqlitePath)) {
            File::ensureDirectoryExists(dirname($sqlitePath));
            File::put($sqlitePath, '');
        }

        $this->callSilentlyOrWarn('key:generate', [], 'Could not generate the application key.');
        $this->callSilentlyOrWarn('storage:link', [], 'Could not create the storage symlink.');
        $this->callSilentlyOrWarn('migrate', ['--force' => true], 'Could not run database migrations. Check your DB_* settings and run: php artisan migrate');
    }

    /**
     * Run an Artisan command and warn instead of failing when it errors.
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function callSilentlyOrWarn(string $command, array $arguments, string $warning): void
    {
        try {
            $exitCode = $this->call($command, $arguments);

            if ($exitCode !== 0) {
                $this->components->warn($warning);
            }
        } catch (\Throwable $e) {
            $this->components->warn($warning.' ('.$e->getMessage().')');
        }
    }

    /**
     * Display required GitHub secrets and recommended .env variables.
     */
    protected function displayRequiredSecrets(bool $useDocker, string $registryType = 'dockerhub'): void
    {
        $this->newLine();
        $this->components->info('GitHub Secrets to configure (Settings → Secrets and variables → Actions)');

        if ($useDocker && $registryType === 'dockerhub') {
            $this->table(
                ['Secret Name', 'Description'],
                [
                    ['DOCKER_USERNAME', 'Your Docker Hub username'],
                    ['DOCKER_PASSWORD', 'Docker Hub access token (hub.docker.com → Account Settings → Security)'],
                ]
            );
        } elseif ($useDocker) {
            $this->line('GitHub Container Registry uses the built-in GITHUB_TOKEN — no Docker secrets needed.');
        }

        $this->table(
            ['Optional Secret', 'Purpose'],
            [
                ['DISCORD_WEBHOOK_URL', 'Discord webhook for release notifications'],
            ]
        );

        $this->table(
            ['Variable', 'Purpose'],
            [
                ['APP_NAME', 'The display name of your application'],
                ['APP_URL', 'The full URL where your application is served'],
                ['DB_CONNECTION', 'Database driver (sqlite, mysql, pgsql)'],
                ['MAIL_MAILER', 'Mail transport (log, smtp, resend, etc.)'],
                ['QUEUE_CONNECTION', 'Queue driver (sync, database, redis)'],
            ],
        );
    }

    /**
     * Read composer.json from the given base path.
     *
     * @return array<string, mixed>
     */
    protected function readComposerJson(?string $basePath = null): array
    {
        $basePath ??= base_path();
        $composerPath = $basePath.'/composer.json';

        if (! File::exists($composerPath)) {
            return [];
        }

        $composer = json_decode((string) File::get($composerPath), true);

        return is_array($composer) ? $composer : [];
    }

    /**
     * Read the previous starter-kit state file, if any.
     *
     * @return array<string, mixed>
     */
    protected function readStarterKitConfig(?string $basePath = null): array
    {
        $basePath ??= base_path();
        $configPath = $basePath.'/.starter-kit.json';

        if (! File::exists($configPath)) {
            return [];
        }

        $config = json_decode((string) File::get($configPath), true);

        return is_array($config) ? $config : [];
    }

    /**
     * Default composer description: keep custom ones, replace the stock template text.
     *
     * @param  array<string, mixed>  $composer
     */
    protected function defaultDescription(array $composer, string $slug): string
    {
        $current = $composer['description'] ?? null;

        if (is_string($current) && $current !== '' && $current !== self::STOCK_DESCRIPTION) {
            return $current;
        }

        return Str::studly($slug).' — a Laravel application built with KoamiStarterKit.';
    }

    /**
     * Check whether git is available for the given working directory.
     */
    protected function gitIsAvailable(?string $basePath = null): bool
    {
        [$ok] = $this->runGit(['--version'], $basePath ?? base_path());

        return $ok;
    }

    /**
     * Run a git command safely without shell interpolation.
     *
     * @param  array<int, string>  $arguments
     * @return array{bool, string}
     */
    protected function runGit(array $arguments, ?string $cwd = null): array
    {
        return $this->runBinary('git', $arguments, $cwd);
    }

    /**
     * Run a gh CLI command safely without shell interpolation.
     *
     * @param  array<int, string>  $arguments
     * @return array{bool, string}
     */
    protected function runGh(array $arguments, ?string $cwd = null): array
    {
        return $this->runBinary('gh', $arguments, $cwd, 120);
    }

    /**
     * Run an external binary with argument arrays (no shell involved).
     *
     * @param  array<int, string>  $arguments
     * @return array{bool, string}
     */
    protected function runBinary(string $binary, array $arguments, ?string $cwd = null, int $timeout = 60): array
    {
        try {
            $process = new Process(array_merge([$binary], $arguments), $cwd);
            $process->setTimeout($timeout);
            $process->run();

            return [$process->isSuccessful(), trim($process->getOutput()."\n".$process->getErrorOutput())];
        } catch (\Throwable) {
            return [false, ''];
        }
    }

    /**
     * Check whether the GitHub CLI is installed.
     */
    protected function ghIsAvailable(?string $basePath = null): bool
    {
        [$ok] = $this->runGh(['--version'], $basePath ?? base_path());

        return $ok;
    }

    /**
     * Check whether the GitHub CLI has an active session.
     */
    protected function ghIsAuthenticated(?string $basePath = null): bool
    {
        [$ok] = $this->runGh(['auth', 'status'], $basePath ?? base_path());

        return $ok;
    }

    /**
     * Validate a repository visibility value.
     */
    public static function isValidVisibility(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['public', 'private'], true);
    }

    /**
     * Select the repository creation endpoint for a user or an organization.
     */
    public static function repoCreateEndpoint(string $authenticatedLogin, string $owner): string
    {
        if (strtolower($authenticatedLogin) === strtolower($owner)) {
            return 'https://api.github.com/user/repos';
        }

        return "https://api.github.com/orgs/{$owner}/repos";
    }

    /**
     * Build the repository creation payload for the GitHub API.
     *
     * @return array{name: string, description: string, private: bool, auto_init: bool}
     */
    public static function repoPayload(string $slug, string $visibility, string $description): array
    {
        return [
            'name' => $slug,
            'description' => mb_substr($description, 0, 350),
            'private' => strtolower($visibility) === 'private',
            'auto_init' => false,
        ];
    }

    /**
     * Validate a GitHub username or organization name.
     */
    public static function isValidGithubUsername(?string $value): bool
    {
        if (! is_string($value) || $value === '' || strlen($value) > 39) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$/', $value);
    }

    /**
     * Validate a lowercase application slug.
     */
    public static function isValidAppSlug(?string $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $value);
    }

    /**
     * Validate a Docker Hub username or organization name.
     */
    public static function isValidDockerHubUsername(?string $value): bool
    {
        if (! is_string($value) || $value === '' || strlen($value) > 30) {
            return false;
        }

        return (bool) preg_match('/^\w([a-zA-Z0-9_-]*\w)?$/', $value);
    }

    /**
     * Detect remotes that still point at the starter kit template repository.
     */
    public static function isStarterKitOrigin(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        return (bool) preg_match('#koamishin/KoamiStarterKit(\.git)?$#i', trim($url));
    }

    /**
     * Extract the GitHub owner from an origin URL, if it is a GitHub URL.
     */
    public static function githubUserFromRemoteUrl(?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $url = trim($url);

        if (preg_match('#^https?://(?:www\.)?github\.com/([^/]+)/[^/]+?(\.git)?$#i', $url, $matches)) {
            return self::isValidGithubUsername($matches[1]) ? $matches[1] : null;
        }

        if (preg_match('#^git@github\.com:([^/]+)/[^/]+?(\.git)?$#i', $url, $matches)) {
            return self::isValidGithubUsername($matches[1]) ? $matches[1] : null;
        }

        return null;
    }
}

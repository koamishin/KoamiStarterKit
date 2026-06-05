<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class SetupStarterKit extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'setup:starter-kit';

    /**
     * The console command description.
     */
    protected $description = 'Initialize your Laravel application from KoamiStarterKit with your project settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        intro('🚀 Welcome to KoamiStarterKit Setup');

        note(
            "This wizard will personalize your application by:\n".
            '  • Updating composer.json with your project details'."\n".
            '  • Configuring Docker CI/CD workflows for container builds'."\n".
            '  • Setting up GitHub Actions for automated releases'."\n".
            '  • Initializing a Git repository with a meaningful first commit'
        );

        info('KoamiStarterKit is designed for building Laravel applications (not Composer packages).');
        info('It comes with Vue 3, Inertia.js, Tailwind CSS, Fortify auth, and production-ready CI/CD.');

        if (! confirm('Ready to begin?', default: true)) {
            warning('Setup cancelled. You can run this command again anytime.');

            return self::SUCCESS;
        }

        // ──────────────────────────────────────────────
        // Step 1: Collect basic project information
        // ──────────────────────────────────────────────

        info('── Step 1 of 6: Project Identity');

        $githubUsername = text(
            label: 'GitHub Username or Organization',
            placeholder: 'e.g., your-org',
            required: true,
            validate: fn ($value): ?string => match (true) {
                ! preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$/', (string) $value) => 'GitHub username may only contain alphanumeric characters and hyphens, and cannot start or end with a hyphen.',
                strlen((string) $value) > 39 => 'GitHub usernames cannot exceed 39 characters.',
                default => null
            },
            hint: 'This sets your Composer vendor name, repository URL, and Docker image namespace.'
        );

        $packageName = text(
            label: 'Application Name (lowercase, hyphens only)',
            placeholder: 'e.g., my-awesome-app',
            default: 'my-app',
            required: true,
            validate: fn ($value): ?string => match (true) {
                ! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', (string) $value) => 'Application name must be lowercase with only hyphens, and cannot start or end with a hyphen.',
                default => null
            },
            hint: 'Used for the Composer package name, Docker image name, and GitHub repository name.'
        );

        $authorName = text(
            label: 'Author Name',
            placeholder: 'e.g., Jane Doe',
            required: true,
            hint: 'Added to composer.json as the package author.'
        );

        $authorEmail = text(
            label: 'Author Email',
            placeholder: 'e.g., jane@example.com',
            required: true,
            validate: fn ($value): ?string => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'Please enter a valid email address.',
                default => null
            },
            hint: 'Added to composer.json as the author email.'
        );

        // ──────────────────────────────────────────────
        // Step 2: Docker configuration
        // ──────────────────────────────────────────────

        info('── Step 2 of 6: Docker Setup');

        note(
            "Docker CI/CD automates building and publishing your application's container image.\n".
            'When enabled, every push to your main branch (or a manual trigger) will build a new Docker image.'
        );

        $useDocker = confirm(
            label: 'Enable Docker CI/CD for this application?',
            default: false,
            hint: 'Recommended if you deploy with containers or Kubernetes.'
        );

        $dockerRegistry = 'docker.io';
        $dockerImageName = strtolower($githubUsername.'/'.$packageName);
        $dockerHubAuthor = '';
        $registryType = 'dockerhub';
        $dockerUpdateStrategy = 'rolling';

        if ($useDocker) {
            $registryType = select(
                label: 'Which Docker registry would you like to use?',
                options: [
                    'ghcr' => 'GitHub Container Registry (ghcr.io) — zero-config with GitHub Actions, recommended ⭐',
                    'dockerhub' => 'Docker Hub (docker.io) — public registry with broad ecosystem support',
                ],
                default: 'ghcr',
                hint: 'GHCR uses your GitHub token automatically — no extra secrets required.'
            );

            if ($registryType === 'dockerhub') {
                $dockerRegistry = 'docker.io';

                $dockerHubAuthor = text(
                    label: 'Docker Hub Username or Organization',
                    placeholder: 'e.g., yourdockeruser',
                    default: $githubUsername,
                    required: true,
                    validate: fn ($value): ?string => match (true) {
                        ! preg_match('/^\w([a-zA-Z0-9_-]*\w)?$/', (string) $value) => 'Docker Hub usernames may only contain alphanumeric characters, underscores, and hyphens.',
                        strlen((string) $value) > 30 => 'Docker Hub usernames cannot exceed 30 characters.',
                        default => null
                    },
                    hint: 'Your Docker Hub username or organization name.'
                );

                $dockerImageName = text(
                    label: 'Docker Image Name',
                    placeholder: 'e.g., dockerhubuser/image-name',
                    default: strtolower($dockerHubAuthor.'/'.$packageName),
                    required: true,
                    hint: 'Full image name including your Docker Hub username or org.'
                );
            } else {
                $dockerRegistry = 'ghcr.io';

                $dockerImageName = text(
                    label: 'Docker Image Name',
                    placeholder: 'e.g., github-org/image-name',
                    default: strtolower($githubUsername.'/'.$packageName),
                    required: true,
                    hint: 'For GHCR, this typically matches your GitHub username or org.'
                );
            }

            // ──────────────────────────────────────────────
            // Step 2b: Docker update strategy
            // ──────────────────────────────────────────────

            note(
                "How would you like new Docker images to be released?\n\n".
                '  🚀 Rolling releases — A new Docker image is built and published automatically on every push to the main branch. Ideal for continuous delivery workflows where you always want the latest code running. The auto-release workflow handles this for you.'."\n\n".
                '  📦 Manual releases — Docker images are only built when you manually trigger a release via the GitHub Actions "Manual Official Release" workflow. This gives you full control over when a new version ships, perfect for scheduled or gated releases.'
            );

            $dockerUpdateStrategy = select(
                label: 'Docker image update strategy',
                options: [
                    'rolling' => 'Rolling releases — auto-build on every push to main 🚀',
                    'manual' => 'Manual releases — build only on explicit release trigger 📦',
                ],
                default: 'rolling',
                hint: 'Rolling is great for rapid iteration; manual gives you release control.'
            );

            if ($dockerUpdateStrategy === 'manual') {
                note(
                    'With manual releases, Docker images will not be built on every push.'."\n".
                    'Instead, use the "Manual Official Release" workflow from the Actions tab in GitHub.'."\n".
                    'The docker-latest workflow is also available to push the latest tag on demand.'
                );
            }
        }

        // ──────────────────────────────────────────────
        // Step 3: Packagist integration
        // ──────────────────────────────────────────────

        info('── Step 3 of 6: Packagist Integration');

        note(
            'Packagist is the main Composer package repository. Enable this only if you plan to distribute your project as a reusable Composer package.'."\n".
            'For most applications, you can safely skip this.'
        );

        $usePackagist = confirm(
            label: 'Enable automated Packagist updates?',
            default: false,
            hint: 'Only needed if you publish this project as a Composer package on packagist.org.'
        );

        // ──────────────────────────────────────────────
        // Step 4: Review summary
        // ──────────────────────────────────────────────

        info('── Step 4 of 6: Review Configuration');

        $summaryRows = [
            ['Application Name', $packageName],
            ['Composer Package', $githubUsername.'/'.$packageName],
            ['Author', "{$authorName} <{$authorEmail}>"],
            ['GitHub Repository', "https://github.com/{$githubUsername}/{$packageName}"],
        ];

        if ($useDocker) {
            $summaryRows[] = ['Docker Registry', $registryType === 'ghcr' ? 'ghcr.io (GitHub Container Registry)' : 'docker.io (Docker Hub)'];
            $summaryRows[] = ['Docker Image', $dockerImageName];
            $summaryRows[] = ['Docker Update Strategy', $dockerUpdateStrategy === 'rolling' ? 'Rolling (auto-build on push)' : 'Manual (release on trigger only)'];

            if ($registryType === 'dockerhub') {
                $summaryRows[] = ['Docker Hub User', $dockerHubAuthor];
            }
        } else {
            $summaryRows[] = ['Docker', 'Not configured'];
        }

        $summaryRows[] = ['Packagist Updates', $usePackagist ? 'Enabled' : 'Disabled'];

        table(
            headers: ['Setting', 'Value'],
            rows: $summaryRows,
        );

        if (! confirm('Apply these settings?', default: true, hint: 'This will update composer.json, workflow files, and create configuration files.')) {
            warning('Setup cancelled — no changes were made.');

            return self::SUCCESS;
        }

        // ──────────────────────────────────────────────
        // Step 5: Apply changes
        // ──────────────────────────────────────────────

        info('── Step 5 of 6: Applying Changes');

        // Check and initialize git repository if needed
        $gitInitialized = $this->initializeGitRepository($githubUsername, $packageName);

        // Update composer.json
        $this->updateComposerJson($githubUsername, $packageName, $authorName, $authorEmail);

        // Create starter kit config
        $this->createStarterKitConfig($useDocker, $dockerRegistry, $dockerImageName, $registryType, $dockerHubAuthor, $usePackagist, $dockerUpdateStrategy);

        // Update workflow files
        $this->updateAllWorkflowFiles($useDocker, $dockerRegistry, $dockerImageName, $registryType, $usePackagist, $dockerUpdateStrategy);

        // Display required environment variables
        $this->displayRequiredSecrets($useDocker, $registryType);

        // Create initial commit if git was just initialized
        if ($gitInitialized) {
            $this->createInitialCommit($packageName);
        }

        // ──────────────────────────────────────────────
        // Step 6: Done
        // ──────────────────────────────────────────────

        info('── Step 6 of 6: Setup Complete');

        outro('✅ KoamiStarterKit setup finished successfully!');

        note(
            "Next steps to get started:\n".
            '  1️⃣  Review the updated files (composer.json, .github/workflows/*.yml, .starter-kit.json)'."\n".
            '  2️⃣  Set up GitHub Secrets: Settings → Secrets and variables → Actions'."\n".
            '  3️⃣  Run: composer install && npm install'."\n".
            '  4️⃣  Run: php artisan migrate'."\n".
            '  5️⃣  Run: composer run dev to start the development server'
        );

        if ($gitInitialized) {
            note(
                "Your Git repository is ready! To push your first commit:\n".
                '  git push -u origin main'
            );
        }

        if ($useDocker && $dockerUpdateStrategy === 'manual') {
            note(
                "Since you chose manual Docker releases, remember:\n".
                '  • Go to the Actions tab → "Manual Official Release" to publish a new Docker image.'."\n".
                '  • The auto-release workflow will still create GitHub releases, but skip the Docker build.'."\n".
                '  • You can change this later by editing DOCKER_UPDATE_STRATEGY in the workflow files.'
            );
        }

        return self::SUCCESS;
    }

    /**
     * Check if git is initialized and initialize if not.
     */
    protected function initializeGitRepository(string $githubUsername, string $packageName): bool
    {
        $gitDir = base_path('.git');

        if (is_dir($gitDir)) {
            info('✓ Git repository already initialized — skipping.');

            return false;
        }

        note(
            'No Git repository was detected. Initializing one is strongly recommended for version control and tracking your changes over time.'
        );

        $initializeGit = confirm(
            label: 'Initialize a new Git repository?',
            default: true,
            hint: 'Recommended. You can always remove the .git folder later.'
        );

        if (! $initializeGit) {
            warning('⚠ Skipped Git initialization. Run "git init" manually when ready.');

            return false;
        }

        $gitResult = spin(
            callback: function () use (&$output, &$returnCode): bool {
                exec('git init 2>&1', $output, $returnCode);

                return $returnCode === 0;
            },
            message: 'Initializing Git repository...'
        );

        if (! $gitResult) {
            error('Failed to initialize Git repository. Please run "git init" manually.');

            return false;
        }

        info('✓ Initialized an empty Git repository in '.base_path().'/.git/');

        // Prompt for remote URL
        $addRemote = confirm(
            label: 'Add a GitHub remote origin?',
            default: true,
            hint: 'Links your local repository to GitHub so you can push and pull changes.'
        );

        if ($addRemote) {
            $defaultUrl = "https://github.com/{$githubUsername}/{$packageName}.git";

            $remoteUrl = text(
                label: 'Remote Repository URL',
                placeholder: 'e.g., '.$defaultUrl,
                default: $defaultUrl,
                hint: 'You can use HTTPS (recommended) or SSH: git@github.com:user/repo.git'
            );

            spin(
                callback: function () use ($remoteUrl): bool {
                    exec("git remote add origin {$remoteUrl} 2>&1", output: $addOutput, result_code: $addReturnCode);

                    return $addReturnCode === 0;
                },
                message: "Adding remote origin: {$remoteUrl}"
            );

            info("✓ Added remote origin → {$remoteUrl}");
        }

        return true;
    }

    /**
     * Create an elegant initial commit.
     */
    protected function createInitialCommit(string $packageName): void
    {
        $createCommit = confirm(
            label: 'Create an initial commit with all current files?',
            default: true,
            hint: 'Stages everything and creates a single "Initial commit" with a friendly message.'
        );

        if (! $createCommit) {
            info('ℹ Skipped. You can create your first commit manually whenever you are ready.');

            return;
        }

        $stageResult = spin(
            callback: function () use (&$addReturnCode): bool {
                exec('git add -A 2>&1', output: $addOutput, result_code: $addReturnCode);

                return $addReturnCode === 0;
            },
            message: 'Staging all files...'
        );

        if (! $stageResult) {
            error('Failed to stage files. Please run "git add -A" manually.');

            return;
        }

        $commitMessage = "🎉 Initial commit: Initialize {$packageName}\n\n".
            "Initialized from KoamiStarterKit — a modern Laravel starter kit\n".
            "with Vue 3, Inertia.js, Tailwind CSS, Fortify authentication,\n".
            'and production-ready CI/CD workflows.';

        $commitResult = spin(
            callback: function () use ($commitMessage, &$commitReturnCode): bool {
                exec('git commit -m '.escapeshellarg($commitMessage).' 2>&1', output: $commitOutput, result_code: $commitReturnCode);

                return $commitReturnCode === 0;
            },
            message: 'Creating initial commit...'
        );

        if ($commitResult) {
            info('✓ Created initial commit');
            info("  \"🎉 Initial commit: Initialize {$packageName}\"");
        } else {
            error('Failed to create the initial commit. Run "git commit -m \"Initial commit\"" manually.');
        }
    }

    /**
     * Update composer.json with user information.
     */
    protected function updateComposerJson(string $githubUsername, string $packageName, string $authorName, string $authorEmail): bool
    {
        return spin(
            callback: function () use ($githubUsername, $packageName, $authorName, $authorEmail): bool {
                $composerPath = base_path('composer.json');

                if (! File::exists($composerPath)) {
                    error('composer.json not found at '.$composerPath);

                    return false;
                }

                $composer = json_decode(File::get($composerPath), true);

                if ($composer === null) {
                    error('Failed to parse composer.json — it may contain invalid JSON.');

                    return false;
                }

                $composer['name'] = strtolower($githubUsername.'/'.$packageName);
                $composer['description'] = 'KoamiStarterKit - A modern Laravel starter kit with Vue 3, Inertia.js, Tailwind CSS, Fortify authentication, and Wayfinder routing. Production-ready with Octane, comprehensive testing setup with Pest, and automated CI/CD workflows.';
                $composer['homepage'] = "https://github.com/{$githubUsername}/{$packageName}";
                $composer['authors'] = [
                    [
                        'name' => $authorName,
                        'email' => $authorEmail,
                    ],
                ];

                File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

                return true;
            },
            message: 'Updating composer.json...'
        ) && (info('✓ Updated composer.json') || true);
    }

    /**
     * Create starter kit configuration file.
     */
    protected function createStarterKitConfig(bool $dockerEnabled, string $registry, string $imageName, string $registryType, string $dockerHubAuthor, bool $packagistEnabled, string $dockerUpdateStrategy): void
    {
        spin(
            callback: function () use ($dockerEnabled, $registry, $imageName, $registryType, $dockerHubAuthor, $packagistEnabled, $dockerUpdateStrategy): bool {
                $config = [
                    'docker_enabled' => $dockerEnabled,
                    'docker_update_strategy' => $dockerEnabled ? $dockerUpdateStrategy : null,
                    'packagist_enabled' => $packagistEnabled,
                    'docker_registry' => $registry,
                    'docker_registry_type' => $registryType,
                    'docker_image_name' => $imageName,
                    'configured_at' => now()->toIso8601String(),
                ];

                if ($registryType === 'dockerhub' && $dockerHubAuthor !== '') {
                    $config['docker_hub_author'] = $dockerHubAuthor;
                }

                File::put(base_path('.starter-kit.json'), json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

                return true;
            },
            message: 'Creating .starter-kit.json...'
        );

        info('✓ Created .starter-kit.json configuration');
    }

    /**
     * Update all GitHub workflow files with Docker settings.
     */
    protected function updateAllWorkflowFiles(bool $dockerEnabled, string $registry, string $imageName, string $registryType, bool $packagistEnabled, string $dockerUpdateStrategy): void
    {
        $workflowDir = base_path('.github/workflows');
        $workflowFiles = ['auto-release.yml', 'docker-latest.yml', 'manual-official-release.yml'];

        foreach ($workflowFiles as $workflowFile) {
            $filePath = $workflowDir.'/'.$workflowFile;

            if (! File::exists($filePath)) {
                continue;
            }

            $content = File::get($filePath);

            // Update registry and image name
            $content = preg_replace(
                '/REGISTRY: .+/',
                "REGISTRY: {$registry}",
                $content
            );

            $content = preg_replace(
                '/IMAGE_NAME: .+/',
                "IMAGE_NAME: {$imageName}",
                (string) $content
            );

            // Update Docker login credentials based on registry type
            if ($dockerEnabled) {
                $content = $this->updateDockerCredentials($content, $registryType);
            }

            // Update DOCKER_ENABLED environment variable
            $content = $this->updateDockerEnabledVar($content, $dockerEnabled, $dockerUpdateStrategy, $workflowFile);

            // Update DOCKER_UPDATE_STRATEGY environment variable
            $content = $this->updateDockerUpdateStrategyVar($content, $dockerEnabled ? $dockerUpdateStrategy : null);

            // Update PACKAGIST_ENABLED environment variable
            $content = $this->updatePackagistEnabledVar($content, $packagistEnabled);

            File::put($filePath, $content);

            info("✓ Updated .github/workflows/{$workflowFile}");
        }
    }

    /**
     * Update or add DOCKER_ENABLED environment variable.
     */
    protected function updateDockerEnabledVar(string $content, bool $enabled, string $dockerUpdateStrategy, string $workflowFile): string
    {
        // For auto-release.yml with manual strategy, disable Docker in this workflow
        $effectiveEnabled = $enabled;

        if ($workflowFile === 'auto-release.yml' && $dockerUpdateStrategy === 'manual') {
            $effectiveEnabled = false;
        }

        $enabledStr = $effectiveEnabled ? 'true' : 'false';

        // Check if DOCKER_ENABLED already exists
        if (preg_match('/DOCKER_ENABLED: (true|false)/', $content)) {
            return preg_replace(
                '/DOCKER_ENABLED: (true|false)/',
                "DOCKER_ENABLED: {$enabledStr}",
                $content
            );
        }

        // If not, inject it before REGISTRY
        $envSection = "env:\n  DOCKER_ENABLED: {$enabledStr}  # Set to false if you don't want Docker CI/CD (configured via setup:starter-kit)\n  REGISTRY:";

        return preg_replace(
            '/env:\n  REGISTRY:/',
            $envSection,
            $content,
            1
        );
    }

    /**
     * Update or add DOCKER_UPDATE_STRATEGY environment variable.
     */
    protected function updateDockerUpdateStrategyVar(string $content, ?string $strategy): string
    {
        if ($strategy === null) {
            // Remove DOCKER_UPDATE_STRATEGY if Docker is not enabled
            if (preg_match('/DOCKER_UPDATE_STRATEGY: .+/', $content)) {
                return preg_replace('/  DOCKER_UPDATE_STRATEGY: [^\n]*\n/', '', $content);
            }

            return $content;
        }

        // Check if DOCKER_UPDATE_STRATEGY already exists
        if (preg_match('/DOCKER_UPDATE_STRATEGY: (rolling|manual)/', $content)) {
            return preg_replace(
                '/DOCKER_UPDATE_STRATEGY: (rolling|manual)/',
                "DOCKER_UPDATE_STRATEGY: {$strategy}",
                $content
            );
        }

        // If not, inject it right after DOCKER_ENABLED
        if (preg_match('/DOCKER_ENABLED: (true|false).*\n/', $content, $matches)) {
            $replacement = $matches[0]."  DOCKER_UPDATE_STRATEGY: {$strategy}  # rolling=auto-build on push, manual=release on explicit trigger\n";

            return str_replace($matches[0], $replacement, $content);
        }

        return $content;
    }

    /**
     * Update or add PACKAGIST_ENABLED environment variable.
     */
    protected function updatePackagistEnabledVar(string $content, bool $enabled): string
    {
        // Only apply to files that actually have the Packagist notification step
        if (! str_contains($content, 'Notify Packagist')) {
            return $content;
        }

        $enabledStr = $enabled ? 'true' : 'false';

        // Check if PACKAGIST_ENABLED already exists
        if (preg_match('/PACKAGIST_ENABLED: (true|false)/', $content)) {
            return preg_replace(
                '/PACKAGIST_ENABLED: (true|false)/',
                "PACKAGIST_ENABLED: {$enabledStr}",
                $content
            );
        }

        // If not, inject it before REGISTRY
        if (! str_contains($content, 'REGISTRY:')) {
            return $content;
        }

        $envSection = "  PACKAGIST_ENABLED: {$enabledStr}  # Set to false if you don't want Packagist auto-updates (configured via setup:starter-kit)\n  REGISTRY:";

        return preg_replace(
            '/  REGISTRY:/',
            $envSection,
            $content,
            1
        );
    }

    /**
     * Update Docker credentials in workflow file based on registry type.
     */
    protected function updateDockerCredentials(string $content, string $registryType): string
    {
        if ($registryType === 'ghcr') {
            // Update username for GitHub Container Registry
            $content = preg_replace(
                '/username: \$\{\{ secrets\.DOCKER_USERNAME \}\}/',
                'username: \${{ github.actor }}',
                $content
            );

            // Update password for GitHub Container Registry
            return preg_replace(
                '/password: \$\{\{ secrets\.DOCKER_PASSWORD \}\}/',
                'password: \${{ secrets.GITHUB_TOKEN }}',
                (string) $content
            );
        }

        // Ensure Docker Hub credentials are set
        $content = preg_replace(
            '/username: \$\{\{ github\.actor \}\}/',
            'username: \${{ secrets.DOCKER_USERNAME }}',
            $content
        );

        return preg_replace(
            '/password: \$\{\{ secrets\.GITHUB_TOKEN \}\}/',
            'password: \${{ secrets.DOCKER_PASSWORD }}',
            (string) $content
        );
    }

    /**
     * Display required environment variables and GitHub secrets.
     */
    protected function displayRequiredSecrets(bool $useDocker, string $registryType = 'dockerhub'): void
    {
        $this->newLine();
        info('🔐 GitHub Secrets to Configure');

        note(
            'GitHub Secrets are stored in your repository and used by GitHub Actions to authenticate with external services.'."\n".
            'Go to: Settings → Secrets and variables → Actions → New repository secret'
        );

        $secrets = [];

        if ($useDocker) {
            if ($registryType === 'dockerhub') {
                $secrets[] = ['DOCKER_USERNAME', 'Your Docker Hub username', 'Required for pushing Docker images'];
                $secrets[] = ['DOCKER_PASSWORD', 'Docker Hub access token (not password)', 'Create at: https://hub.docker.com/settings/security'];
            } else {
                info('ℹ GitHub Container Registry uses the built-in GITHUB_TOKEN — no additional secrets needed for Docker authentication.');
                $this->newLine();
            }
        }

        $secrets[] = ['DISCORD_WEBHOOK_URL', 'Discord webhook for release notifications', 'Optional — for automated Discord announcements'];
        $secrets[] = ['PACKAGIST_USERNAME', 'Packagist.org username', 'Optional — for automated Packagist package updates'];
        $secrets[] = ['PACKAGIST_TOKEN', 'Packagist API token', 'Optional — for automated Packagist package updates'];

        table(
            headers: ['Secret Name', 'Description', 'Notes'],
            rows: $secrets,
        );

        $this->newLine();
        info('📝 Recommended .env Variables');

        note(
            'Review and update your .env file with the variables below. These control how your application behaves in production.'
        );

        table(
            headers: ['Variable', 'Purpose'],
            rows: [
                ['APP_NAME', 'The display name of your application'],
                ['APP_URL', 'The full URL where your application is served'],
                ['DB_CONNECTION', 'Database driver (sqlite, mysql, pgsql)'],
                ['MAIL_MAILER', 'Mail transport (log, smtp, mailgun, resend, etc.)'],
                ['CACHE_STORE', 'Cache backend (file, redis, database)'],
                ['QUEUE_CONNECTION', 'Queue driver (sync, database, redis)'],
            ],
        );
    }
}

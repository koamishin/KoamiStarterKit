<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class SetupStarterKit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:starter-kit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize your Laravel application from KoamiStarterKit with your project settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        intro('🚀 KoamiStarterKit Setup');

        info('This command will initialize your application with your project settings.');
        info('It will update composer.json, workflow files, and Docker configuration.');
        info('Perfect for creating Laravel applications (not composer packages).');

        if (! confirm('Do you want to continue?', default: true)) {
            warning('Setup cancelled.');

            return self::SUCCESS;
        }

        // Collect user information
        $githubUsername = text(
            label: 'GitHub Username/Organization',
            placeholder: 'e.g., yourusername',
            required: true,
            validate: fn ($value): ?string => match (true) {
                ! preg_match('/^[a-zA-Z0-9-]+$/', (string) $value) => 'GitHub username can only contain alphanumeric characters and hyphens.',
                default => null
            },
            hint: 'Used for composer vendor name and GitHub repository URL'
        );

        $packageName = text(
            label: 'Application Name (lowercase, no spaces)',
            placeholder: 'e.g., my-awesome-app',
            default: 'my-app',
            required: true,
            validate: fn ($value): ?string => match (true) {
                ! preg_match('/^[a-z0-9-]+$/', (string) $value) => 'Application name must be lowercase with hyphens only.',
                default => null
            },
            hint: 'Used for composer package name, Docker image name, and GitHub repository name'
        );

        $authorName = text(
            label: 'Author Name',
            placeholder: 'e.g., John Doe',
            required: true,
            hint: 'Your name will be added to composer.json authors'
        );

        $authorEmail = text(
            label: 'Author Email',
            placeholder: 'e.g., john@example.com',
            required: true,
            validate: fn ($value): ?string => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'Please enter a valid email address.',
                default => null
            },
            hint: 'Your email will be added to composer.json authors'
        );

        $useDocker = confirm(
            label: 'Do you want to set up Docker for your application?',
            default: false,
            hint: 'Configure Docker CI/CD for building and publishing container images'
        );

        $usePackagist = confirm(
            label: 'Do you want to enable automated Packagist updates?',
            default: false,
            hint: 'Only needed if you plan to publish your project as a composer package'
        );

        $dockerRegistry = 'docker.io';
        $dockerImageName = strtolower($githubUsername.'/'.$packageName);
        $dockerHubAuthor = '';
        $registryType = 'dockerhub';

        if ($useDocker) {
            $registryType = select(
                label: 'Which Docker registry do you want to use?',
                options: [
                    'ghcr' => 'GitHub Container Registry (ghcr.io) - Recommended for GitHub users',
                    'dockerhub' => 'Docker Hub (docker.io) - Public registry',
                ],
                default: 'ghcr',
                hint: 'GHCR integrates seamlessly with GitHub Actions; Docker Hub is widely used'
            );

            if ($registryType === 'dockerhub') {
                $dockerRegistry = 'docker.io';

                $dockerHubAuthor = text(
                    label: 'Docker Hub Username/Organization',
                    placeholder: 'e.g., yourdockerhubusername',
                    default: $githubUsername,
                    required: true,
                    validate: fn ($value): ?string => match (true) {
                        ! preg_match('/^[a-zA-Z0-9_-]+$/', (string) $value) => 'Docker Hub username can only contain alphanumeric characters, underscores, and hyphens.',
                        default => null
                    },
                    hint: 'Your Docker Hub username or organization name'
                );

                $dockerImageName = text(
                    label: 'Docker Image Name',
                    placeholder: 'e.g., dockerhubuser/image-name',
                    default: strtolower($dockerHubAuthor.'/'.$packageName),
                    required: true,
                    hint: 'Full image name including username/organization'
                );
            } else {
                $dockerRegistry = 'ghcr.io';

                $dockerImageName = text(
                    label: 'Docker Image Name',
                    placeholder: 'e.g., github-username/image-name',
                    default: strtolower($githubUsername.'/'.$packageName),
                    required: true,
                    hint: 'For GHCR, this should match your GitHub username/org'
                );
            }
        }

        // Show summary
        $this->newLine();
        info('📋 Configuration Summary:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Application Name', $packageName],
                ['Composer Package', $githubUsername.'/'.$packageName],
                ['Author Name', $authorName],
                ['Author Email', $authorEmail],
                ['GitHub Repository', "https://github.com/{$githubUsername}/".ucfirst($packageName)],
                ['Docker Registry', $useDocker ? $dockerRegistry : 'Not configured'],
                ['Packagist Updates', $usePackagist ? 'Enabled' : 'Disabled'],
                ['Docker Hub Author', $useDocker && $registryType === 'dockerhub' ? $dockerHubAuthor : 'N/A'],
                ['Docker Image', $useDocker ? $dockerImageName : 'Not configured'],
            ]
        );

        if (! confirm('Apply these changes?', default: true)) {
            warning('Setup cancelled.');

            return self::SUCCESS;
        }

        // Check and initialize git repository if needed
        $gitInitialized = $this->initializeGitRepository($githubUsername, $packageName);

        // Update composer.json
        $this->updateComposerJson($githubUsername, $packageName, $authorName, $authorEmail);

        // Create starter kit config
        $this->createStarterKitConfig($useDocker, $dockerRegistry, $dockerImageName, $registryType, $dockerHubAuthor, $usePackagist);

        // Update workflow files
        $this->updateAllWorkflowFiles($useDocker, $dockerRegistry, $dockerImageName, $registryType, $usePackagist);

        // Display required environment variables
        $this->displayRequiredSecrets($useDocker, $registryType);

        // Create initial commit if git was just initialized
        if ($gitInitialized) {
            $this->createInitialCommit($packageName);
        }

        outro('✅ KoamiStarterKit setup complete!');

        info('Next steps:');
        info('  1. Review the updated files');
        info('  2. Configure GitHub Secrets (if using workflows)');
        info('  3. Run: composer install && npm install');
        info('  4. Run: php artisan migrate');
        info('  5. Run: composer run dev');

        return self::SUCCESS;
    }

    /**
     * Check if git is initialized and initialize if not.
     */
    protected function initializeGitRepository(string $githubUsername, string $packageName): bool
    {
        $gitDir = base_path('.git');

        if (is_dir($gitDir)) {
            info('✓ Git repository already initialized');

            return false;
        }

        $initializeGit = confirm(
            label: 'No Git repository found. Initialize one?',
            default: true,
            hint: 'Recommended for version control and tracking your application changes'
        );

        if (! $initializeGit) {
            warning('⚠ Git repository not initialized. Consider initializing manually with: git init');

            return false;
        }

        // Initialize git repository
        exec('git init', $output, $returnCode);

        if ($returnCode !== 0) {
            warning('⚠ Failed to initialize git repository. Please run: git init');

            return false;
        }

        info('✓ Initialized empty Git repository');

        // Prompt for remote URL
        $addRemote = confirm(
            label: 'Add GitHub remote repository?',
            default: true,
            hint: 'This will add your GitHub repository as the origin remote'
        );

        if ($addRemote) {
            $remoteUrl = text(
                label: 'GitHub Repository URL',
                placeholder: "e.g., https://github.com/{$githubUsername}/{$packageName}.git",
                default: "https://github.com/{$githubUsername}/{$packageName}.git",
                hint: 'You can use HTTPS or SSH URL (e.g., git@github.com:user/repo.git)'
            );

            exec("git remote add origin {$remoteUrl}", $remoteOutput, $remoteReturnCode);

            if ($remoteReturnCode === 0) {
                info("✓ Added remote origin: {$remoteUrl}");
            } else {
                warning('⚠ Failed to add remote. You can add it manually with: git remote add origin <url>');
            }
        }

        return true;
    }

    /**
     * Create an elegant initial commit.
     */
    protected function createInitialCommit(string $packageName): void
    {
        $createCommit = confirm(
            label: 'Create initial commit?',
            default: true,
            hint: 'This will stage all files and create an initial commit'
        );

        if (! $createCommit) {
            info('ℹ️  You can create your initial commit manually later.');

            return;
        }

        // Stage all files
        exec('git add -A', $addOutput, $addReturnCode);

        if ($addReturnCode !== 0) {
            warning('⚠ Failed to stage files. Please run: git add -A');

            return;
        }

        // Create initial commit with elegant message
        $commitMessage = "🎉 Initial commit: Initialize {$packageName}\n\n".
            "Initialized from KoamiStarterKit - A modern Laravel 12 starter kit\n".
            "with Vue 3, Inertia.js, Tailwind CSS, Fortify authentication,\n".
            'and production-ready CI/CD workflows.';

        exec('git commit -m '.escapeshellarg($commitMessage), $commitOutput, $commitReturnCode);

        if ($commitReturnCode === 0) {
            info('✓ Created initial commit');
            info('  Commit message:');
            $this->line("  \"🎉 Initial commit: Initialize {$packageName}\"");
        } else {
            warning('⚠ Failed to create initial commit. Please run: git commit -m "Initial commit"');
        }
    }

    /**
     * Update composer.json with user information.
     */
    protected function updateComposerJson(string $githubUsername, string $packageName, string $authorName, string $authorEmail): void
    {
        $composerPath = base_path('composer.json');
        $composer = json_decode(File::get($composerPath), true);

        // Update package information
        $composer['name'] = strtolower($githubUsername.'/'.$packageName);
        $composer['description'] = 'KoamiStarterKit - A modern Laravel 12 starter kit with Vue 3, Inertia.js, Tailwind CSS, Fortify authentication, and Wayfinder routing. Production-ready with Octane, comprehensive testing setup with Pest, and automated CI/CD workflows.';
        $composer['homepage'] = "https://github.com/{$githubUsername}/".ucfirst($packageName);

        // Update authors
        $composer['authors'] = [
            [
                'name' => $authorName,
                'email' => $authorEmail,
            ],
        ];

        // Write back to file
        File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        info('✓ Updated composer.json');
    }

    /**
     * Create starter kit configuration file.
     */
    protected function createStarterKitConfig(bool $dockerEnabled, string $registry, string $imageName, string $registryType, string $dockerHubAuthor, bool $packagistEnabled): void
    {
        $config = [
            'docker_enabled' => $dockerEnabled,
            'packagist_enabled' => $packagistEnabled,
            'docker_registry' => $registry,
            'docker_registry_type' => $registryType,
            'docker_image_name' => $imageName,
            'configured_at' => now()->toIso8601String(),
        ];

        if ($registryType === 'dockerhub' && $dockerHubAuthor !== '') {
            $config['docker_hub_author'] = $dockerHubAuthor;
        }

        File::put(base_path('.starter-kit.json'), json_encode($config, JSON_PRETTY_PRINT)."\n");

        info('✓ Created .starter-kit.json configuration');
    }

    /**
     * Update all GitHub workflow files with Docker settings.
     */
    protected function updateAllWorkflowFiles(bool $dockerEnabled, string $registry, string $imageName, string $registryType, bool $packagistEnabled): void
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
            $content = $this->updateDockerEnabledVar($content, $dockerEnabled);

            // Update PACKAGIST_ENABLED environment variable
            $content = $this->updatePackagistEnabledVar($content, $packagistEnabled);

            File::put($filePath, $content);

            info("✓ Updated .github/workflows/{$workflowFile}");
        }
    }

    /**
     * Update or add DOCKER_ENABLED environment variable.
     */
    protected function updateDockerEnabledVar(string $content, bool $enabled): string
    {
        $enabledStr = $enabled ? 'true' : 'false';

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
        info('🔐 Required GitHub Secrets:');

        $secrets = [];

        if ($useDocker) {
            if ($registryType === 'dockerhub') {
                $secrets[] = ['DOCKER_USERNAME', 'Your Docker Hub username', 'Required for Docker image publishing'];
                $secrets[] = ['DOCKER_PASSWORD', 'Your Docker Hub access token', 'Create at: https://hub.docker.com/settings/security'];
            } else {
                info('ℹ️  GitHub Container Registry uses GITHUB_TOKEN automatically - no additional secrets needed for Docker!');
                $this->newLine();
            }
        }

        $secrets[] = ['DISCORD_WEBHOOK_URL', 'Discord webhook for notifications', 'Optional - for Discord release notifications'];
        $secrets[] = ['PACKAGIST_USERNAME', 'Packagist username', 'Optional - for automated Packagist updates'];
        $secrets[] = ['PACKAGIST_TOKEN', 'Packagist API token', 'Optional - for automated Packagist updates'];

        $this->table(
            ['Secret Name', 'Description', 'Notes'],
            $secrets
        );

        info('To add GitHub Secrets:');
        info('  1. Go to your repository on GitHub');
        info('  2. Navigate to: Settings → Secrets and variables → Actions');
        info('  3. Click "New repository secret"');
        info('  4. Add each secret listed above');

        $this->newLine();
        info('📝 Recommended Environment Variables (.env):');

        $envVars = [
            ['APP_NAME', 'Your application name'],
            ['APP_URL', 'Your application URL'],
            ['DB_CONNECTION', 'Database connection (sqlite, mysql, pgsql)'],
            ['MAIL_MAILER', 'Mail service (log, smtp, mailgun, etc.)'],
            ['CACHE_STORE', 'Cache driver (file, redis, database)'],
            ['QUEUE_CONNECTION', 'Queue driver (sync, database, redis)'],
        ];

        $this->table(
            ['Variable', 'Description'],
            $envVars
        );

        info('Review and update your .env file based on your requirements.');
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
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
    protected $description = 'Configure KoamiStarterKit with your GitHub username and project settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        intro('🚀 KoamiStarterKit Setup');

        info('This command will configure your starter kit with your GitHub username and project settings.');
        info('It will update composer.json, workflow files, and Docker configuration.');

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
            }
        );

        $packageName = text(
            label: 'Package Name (lowercase, no spaces)',
            placeholder: 'e.g., my-awesome-app',
            default: 'laravel-app',
            required: true,
            validate: fn ($value): ?string => match (true) {
                ! preg_match('/^[a-z0-9-]+$/', (string) $value) => 'Package name must be lowercase with hyphens only.',
                default => null
            }
        );

        $authorName = text(
            label: 'Author Name',
            placeholder: 'e.g., John Doe',
            required: true
        );

        $authorEmail = text(
            label: 'Author Email',
            placeholder: 'e.g., john@example.com',
            required: true,
            validate: fn ($value): ?string => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'Please enter a valid email address.',
                default => null
            }
        );

        $useDocker = confirm(
            label: 'Do you want to configure Docker settings?',
            default: true,
            hint: 'This will update workflow files to use your Docker registry'
        );

        $dockerRegistry = 'docker.io';
        $dockerImageName = strtolower($githubUsername.'/'.$packageName);

        if ($useDocker) {
            $dockerRegistry = text(
                label: 'Docker Registry',
                placeholder: 'e.g., docker.io, ghcr.io',
                default: 'docker.io',
                required: true
            );

            $dockerImageName = text(
                label: 'Docker Image Name',
                placeholder: 'e.g., username/image-name',
                default: strtolower($githubUsername.'/'.$packageName),
                required: true
            );
        }

        // Show summary
        $this->newLine();
        info('📋 Configuration Summary:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Composer Package', $githubUsername.'/'.$packageName],
                ['Author Name', $authorName],
                ['Author Email', $authorEmail],
                ['GitHub Repository', "https://github.com/{$githubUsername}/".ucfirst($packageName)],
                ['Docker Registry', $useDocker ? $dockerRegistry : 'Not configured'],
                ['Docker Image', $useDocker ? $dockerImageName : 'Not configured'],
            ]
        );

        if (! confirm('Apply these changes?', default: true)) {
            warning('Setup cancelled.');

            return self::SUCCESS;
        }

        // Update composer.json
        $this->updateComposerJson($githubUsername, $packageName, $authorName, $authorEmail);

        // Create starter kit config
        $this->createStarterKitConfig($useDocker, $dockerRegistry, $dockerImageName);

        // Update workflow files
        $this->updateAllWorkflowFiles($useDocker, $dockerRegistry, $dockerImageName);

        // Display required environment variables
        $this->displayRequiredSecrets($useDocker);

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
    protected function createStarterKitConfig(bool $dockerEnabled, string $registry, string $imageName): void
    {
        $config = [
            'docker_enabled' => $dockerEnabled,
            'docker_registry' => $registry,
            'docker_image_name' => $imageName,
            'configured_at' => now()->toIso8601String(),
        ];

        File::put(base_path('.starter-kit.json'), json_encode($config, JSON_PRETTY_PRINT)."\n");

        info('✓ Created .starter-kit.json configuration');
    }

    /**
     * Update all GitHub workflow files with Docker settings.
     */
    protected function updateAllWorkflowFiles(bool $dockerEnabled, string $registry, string $imageName): void
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

            // Add conditional check for Docker jobs if Docker is not enabled
            if (! $dockerEnabled) {
                // Add a check at the top of the file to skip Docker jobs
                $content = $this->addDockerEnabledCheck($content, $workflowFile);
            }

            File::put($filePath, $content);

            info("✓ Updated .github/workflows/{$workflowFile}");
        }
    }

    /**
     * Add Docker enabled check to workflow file.
     */
    protected function addDockerEnabledCheck(string $content, string $filename): string
    {
        // Add environment variable at the top to control Docker jobs
        if (str_contains($filename, 'auto-release') || str_contains($filename, 'docker-latest') || str_contains($filename, 'manual')) {
            // Add a comment and environment check
            $envSection = "env:\n  DOCKER_ENABLED: false  # Set to true after configuring Docker in setup\n  REGISTRY:";

            $content = preg_replace(
                '/env:\n  REGISTRY:/',
                $envSection,
                $content,
                1
            );
        }

        return $content;
    }

    /**
     * Display required environment variables and GitHub secrets.
     */
    protected function displayRequiredSecrets(bool $useDocker): void
    {
        $this->newLine();
        info('🔐 Required GitHub Secrets:');

        $secrets = [];

        if ($useDocker) {
            $secrets[] = ['DOCKER_USERNAME', 'Your Docker Hub username', 'Required for Docker image publishing'];
            $secrets[] = ['DOCKER_PASSWORD', 'Your Docker Hub access token', 'Create at: https://hub.docker.com/settings/security'];
        }

        $secrets[] = ['DISCORD_WEBHOOK_URL', 'Discord webhook for notifications', 'Optional - for Discord release notifications'];

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

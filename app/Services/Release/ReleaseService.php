<?php

declare(strict_types=1);

namespace App\Services\Release;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class ReleaseService
{
    public const DEFAULT_REPOSITORY = 'koamishin/KoamiStarterKit-React';

    public const CACHE_KEY = 'self_hosted_upstream_release_check';

    public const CACHE_TTL_SECONDS = 21600; // 6 hours

    public function currentVersion(): string
    {
        $versionFile = base_path('version.json');

        if (File::exists($versionFile)) {
            try {
                /** @var array{version?: string} $data */
                $data = json_decode((string) File::get($versionFile), true, 512, JSON_THROW_ON_ERROR);
                if (! empty($data['version'])) {
                    return $data['version'];
                }
            } catch (Throwable) {
                // fallback
            }
        }

        return (string) config('app.version', '1.4.0');
    }

    public function repository(): string
    {
        $versionFile = base_path('version.json');

        if (File::exists($versionFile)) {
            try {
                /** @var array{metadata?: array{repository?: string}} $data */
                $data = json_decode((string) File::get($versionFile), true, 512, JSON_THROW_ON_ERROR);
                if (! empty($data['metadata']['repository'])) {
                    return $data['metadata']['repository'];
                }
            } catch (Throwable) {
                // fallback
            }
        }

        return (string) config('app.github_repository', self::DEFAULT_REPOSITORY);
    }

    /**
     * @return array{
     *     current_version: string,
     *     latest_version: string,
     *     is_update_available: bool,
     *     release_title: string,
     *     release_notes: string,
     *     published_at: ?string,
     *     html_url: string,
     *     checked_at: string,
     *     status: 'ok'|'error'
     * }
     */
    public function check(bool $force = false): array
    {
        if ($force) {
            Cache::forget(self::CACHE_KEY);
        }

        /** @var array{current_version: string, latest_version: string, is_update_available: bool, release_title: string, release_notes: string, published_at: ?string, html_url: string, checked_at: string, status: 'ok'|'error'} */
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            $current = $this->currentVersion();
            $repo = $this->repository();

            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'User-Agent' => 'KoamiStarterKit-Instance/'.$current,
                        'Accept' => 'application/vnd.github.v3+json',
                    ])
                    ->get("https://api.github.com/repos/{$repo}/releases/latest");

                if ($response->successful()) {
                    /** @var array{tag_name?: string, name?: string, body?: string, published_at?: string, html_url?: string} $json */
                    $json = $response->json();
                    $tagName = (string) ($json['tag_name'] ?? '');
                    $latestVersion = ltrim($tagName, 'v');
                    $cleanCurrent = ltrim($current, 'v');

                    $isUpdateAvailable = ! empty($latestVersion) && version_compare($cleanCurrent, $latestVersion, '<');

                    return [
                        'current_version' => $current,
                        'latest_version' => ! empty($latestVersion) ? $latestVersion : $current,
                        'is_update_available' => $isUpdateAvailable,
                        'release_title' => (string) ($json['name'] ?? $tagName),
                        'release_notes' => (string) ($json['body'] ?? 'No release notes provided.'),
                        'published_at' => (string) ($json['published_at'] ?? null),
                        'html_url' => (string) ($json['html_url'] ?? "https://github.com/{$repo}/releases"),
                        'checked_at' => now()->toIso8601String(),
                        'status' => 'ok',
                    ];
                }
            } catch (Throwable) {
                // Silently fallback if offline or API unavailable
            }

            return [
                'current_version' => $current,
                'latest_version' => $current,
                'is_update_available' => false,
                'release_title' => 'Upstream Unavailable',
                'release_notes' => 'Could not contact GitHub releases API. Verify internet connectivity.',
                'published_at' => null,
                'html_url' => "https://github.com/{$repo}/releases",
                'checked_at' => now()->toIso8601String(),
                'status' => 'error',
            ];
        });
    }
}

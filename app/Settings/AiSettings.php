<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\AiProvider;
use App\Enums\McpConnection;
use Spatie\LaravelSettings\Settings;

class AiSettings extends Settings
{
    public bool $enabled = false;

    public string $provider = 'openai';

    public ?string $api_key = null;

    public ?string $model = null;

    public ?string $base_url = null;

    public bool $mcp_enabled = false;

    public array $mcp_servers = [];

    public static function group(): string
    {
        return 'ai';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'provider' => 'openai',
            'api_key' => null,
            'model' => null,
            'base_url' => null,
            'mcp_enabled' => false,
            'mcp_servers' => [],
        ];
    }

    public function resolvedProvider(): AiProvider
    {
        return AiProvider::tryFrom($this->provider) ?? AiProvider::OpenAI;
    }

    public function resolvedModel(): ?string
    {
        return filled($this->model) ? $this->model : $this->resolvedProvider()->defaultModel();
    }

    /**
     * Determine whether the AI provider has everything it needs to be used.
     */
    public function isConfigured(): bool
    {
        $aiProvider = $this->resolvedProvider();

        if (! $aiProvider->requiresApiKey()) {
            return true;
        }

        return filled($this->api_key) || $this->usesEnvApiKey();
    }

    /**
     * Determine whether the API key is provided by the environment instead of the settings.
     */
    public function usesEnvApiKey(): bool
    {
        return filled(config("ai.providers.{$this->resolvedProvider()->value}.key"));
    }

    /**
     * Determine whether a base URL saved in the environment overrides the saved setting.
     */
    public function usesEnvBaseUrl(): bool
    {
        return filled($this->envValue($this->resolvedProvider()->envUrlKey()));
    }

    /**
     * Return the normalized and validated MCP server definitions.
     *
     * @return array<int, array{name: string, connection: string, url: string|null, token: string|null, timeout: int, command: string|null, arguments: array<int, string>}>
     */
    public function mcpServers(): array
    {
        return collect($this->mcp_servers)
            ->map(fn (mixed $server): ?array => is_array($server) ? $this->normalizeServer($server) : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $server
     * @return array{name: string, connection: string, url: string|null, token: string|null, timeout: int, command: string|null, arguments: array<int, string>}|null
     */
    private function normalizeServer(array $server): ?array
    {
        $name = trim((string) ($server['name'] ?? ''));
        $connection = (string) ($server['connection'] ?? McpConnection::Web->value);

        if ($name === '' || ! in_array($connection, McpConnection::values(), true)) {
            return null;
        }

        $isWeb = $connection === McpConnection::Web->value;

        if ($isWeb && blank($server['url'] ?? null)) {
            return null;
        }

        if (! $isWeb && blank($server['command'] ?? null)) {
            return null;
        }

        return [
            'name' => $name,
            'connection' => $connection,
            'url' => $isWeb ? trim((string) $server['url']) : null,
            'token' => filled($server['token'] ?? null) ? (string) $server['token'] : null,
            'timeout' => max(1, (int) ($server['timeout'] ?? 30)),
            'command' => $isWeb ? null : trim((string) $server['command']),
            'arguments' => $isWeb
                ? []
                : array_values(array_filter(
                    array_map(strval(...), (array) ($server['arguments'] ?? [])),
                    filled(...),
                )),
        ];
    }

    private function envValue(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return $value === false ? null : (string) $value;
    }
}

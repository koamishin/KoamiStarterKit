<?php

declare(strict_types=1);

namespace App\Ai;

use App\Ai\Agents\ApplicationAgent;
use App\Settings\AiSettings;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

class AiConfiguration
{
    public function __construct(
        public readonly AiSettings $settings,
        protected readonly McpServerConnector $connector,
    ) {}

    /**
     * Apply the stored settings to the AI SDK configuration at runtime.
     */
    public function apply(): void
    {
        $aiProvider = $this->settings->resolvedProvider();

        config()->set('ai.default', $aiProvider->value);

        if (blank(config("ai.providers.{$aiProvider->value}.key")) && filled($this->settings->api_key)) {
            config()->set("ai.providers.{$aiProvider->value}.key", $this->settings->api_key);
        }

        if ($aiProvider->supportsCustomBaseUrl()
            && ! $this->settings->usesEnvBaseUrl()
            && filled($this->settings->base_url)) {
            config()->set("ai.providers.{$aiProvider->value}.url", $this->settings->base_url);
        }
    }

    /**
     * Determine whether AI is enabled and fully configured.
     */
    public function isEnabled(): bool
    {
        return $this->settings->enabled && $this->settings->isConfigured();
    }

    /**
     * Determine whether MCP tooling is enabled with at least one valid server.
     */
    public function isMcpEnabled(): bool
    {
        return $this->settings->mcp_enabled && $this->settings->mcpServers() !== [];
    }

    /**
     * Gather the tools exposed by every configured MCP server.
     *
     * @return array<int, object>
     */
    public function mcpTools(): array
    {
        if (! $this->settings->mcp_enabled) {
            return [];
        }

        $tools = [];

        foreach ($this->settings->mcpServers() as $server) {
            try {
                foreach ($this->connector->tools($server) as $tool) {
                    $tools[] = $tool;
                }
            } catch (Throwable $exception) {
                logger()->warning("Unable to load tools from the [{$server['name']}] MCP server.", [
                    'server' => $server['name'],
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $tools;
    }

    /**
     * Prompt the application agent with the configured provider and model.
     */
    public function prompt(#[\SensitiveParameter] string $question): AgentResponse
    {
        $this->apply();

        return app(ApplicationAgent::class)->prompt(
            $question,
            provider: $this->settings->provider,
            model: $this->settings->resolvedModel(),
        );
    }
}

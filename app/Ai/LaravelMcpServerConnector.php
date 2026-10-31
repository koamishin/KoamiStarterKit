<?php

declare(strict_types=1);

namespace App\Ai;

use App\Enums\McpConnection;
use Laravel\Mcp\Client;

class LaravelMcpServerConnector implements McpServerConnector
{
    /**
     * List the tools exposed by the given MCP server definition.
     *
     * @param  array{name: string, connection: string, url: string|null, token: string|null, timeout: int, command: string|null, arguments: array<int, string>}  $server
     * @return iterable<object>
     */
    #[\Override]
    public function tools(array $server): iterable
    {
        return $this->client($server)->tools();
    }

    /**
     * Create the MCP client for the given server definition.
     *
     * @param  array{name: string, connection: string, url: string|null, token: string|null, timeout: int, command: string|null, arguments: array<int, string>}  $server
     */
    protected function client(array $server): Client
    {
        if ($server['connection'] === McpConnection::Local->value) {
            return Client::local($server['command'], $server['arguments']);
        }

        $webClient = Client::web($server['url'])
            ->withTimeout((float) $server['timeout']);

        if (filled($server['token'])) {
            $webClient->withToken($server['token']);
        }

        return $webClient;
    }
}

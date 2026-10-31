<?php

declare(strict_types=1);

namespace App\Ai;

use Laravel\Ai\Contracts\Tool;

interface McpServerConnector
{
    /**
     * List the tools exposed by the given MCP server definition.
     *
     * @param  array{name: string, connection: string, url: string|null, token: string|null, timeout: int, command: string|null, arguments: array<int, string>}  $server
     * @return iterable<Tool|object>
     */
    public function tools(array $server): iterable;
}

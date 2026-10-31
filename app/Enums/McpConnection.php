<?php

declare(strict_types=1);

namespace App\Enums;

enum McpConnection: string
{
    case Web = 'web';
    case Local = 'local';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Remote (HTTP)',
            self::Local => 'Local (command)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Web => 'Connect to an HTTP-accessible MCP server',
            self::Local => 'Run an MCP server as a local command',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (McpConnection $mcpConnection): array => [$mcpConnection->value => $mcpConnection->label()])
            ->all();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

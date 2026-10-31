<?php

declare(strict_types=1);

use App\Ai\AiConfiguration;
use App\Ai\LaravelMcpServerConnector;
use App\Ai\McpServerConnector;
use App\Enums\AiProvider;
use App\Settings\AiSettings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Client;
use Laravel\Mcp\WebClient;

function fakeMcpTool(): Tool
{
    return new class implements Tool
    {
        public function description(): string
        {
            return 'A fake MCP tool.';
        }

        public function handle(Request $request): string
        {
            return 'fake';
        }

        public function schema(JsonSchema $schema): array
        {
            return [];
        }
    };
}

function fakeMcpServerConnector(array $tools = [], ?Throwable $throwable = null): McpServerConnector
{
    return new class($tools, $throwable) implements McpServerConnector
    {
        public array $servers = [];

        public function __construct(
            protected array $tools,
            protected ?Throwable $throws,
        ) {}

        public function tools(array $server): iterable
        {
            $this->servers[] = $server['name'];

            if ($this->throws instanceof Throwable) {
                throw $this->throws;
            }

            return $this->tools;
        }
    };
}

beforeEach(function (): void {
    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);

    app()->forgetInstance(AiSettings::class);
});

test('stored settings are applied to the ai config at runtime', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->enabled = true;
    $aiSettings->provider = AiProvider::Anthropic->value;
    $aiSettings->api_key = 'sk-database';
    $aiSettings->base_url = 'https://proxy.example.com/v1';
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    app(AiConfiguration::class)->apply();

    expect(config('ai.default'))->toBe('anthropic')
        ->and(config('ai.providers.anthropic.key'))->toBe('sk-database')
        ->and(config('ai.providers.anthropic.url'))->toBe('https://proxy.example.com/v1');
});

test('environment values win over stored settings', function (): void {
    config()->set('ai.providers.openai.key', 'env-key');

    putenv('OPENAI_URL=https://env.example.com/v1');
    $_ENV['OPENAI_URL'] = 'https://env.example.com/v1';

    try {
        $aiSettings = app(AiSettings::class);
        $aiSettings->enabled = true;
        $aiSettings->provider = AiProvider::OpenAI->value;
        $aiSettings->api_key = 'sk-database';
        $aiSettings->base_url = 'https://proxy.example.com/v1';
        $aiSettings->save();

        app()->forgetInstance(AiSettings::class);

        app(AiConfiguration::class)->apply();

        expect(config('ai.providers.openai.key'))->toBe('env-key')
            ->and(config('ai.providers.openai.url'))->not->toBe('https://proxy.example.com/v1');
    } finally {
        putenv('OPENAI_URL');
        unset($_ENV['OPENAI_URL']);
    }
});

test('custom base urls are ignored for providers that do not support them', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->enabled = true;
    $aiSettings->provider = AiProvider::Groq->value;
    $aiSettings->api_key = 'gsk-test';
    $aiSettings->base_url = 'https://proxy.example.com/v1';
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    app(AiConfiguration::class)->apply();

    expect(config('ai.default'))->toBe('groq')
        ->and(config('ai.providers.groq.url'))->not->toBe('https://proxy.example.com/v1');
});

test('ai is only enabled when the toggle is on and credentials exist', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->enabled = false;
    $aiSettings->provider = AiProvider::OpenAI->value;
    $aiSettings->api_key = 'sk-test';

    expect(app(AiConfiguration::class)->isEnabled())->toBeFalse();

    $aiSettings->enabled = true;
    $aiSettings->api_key = null;

    expect(app(AiConfiguration::class)->isEnabled())->toBeFalse();

    $aiSettings->api_key = 'sk-test';

    expect(app(AiConfiguration::class)->isEnabled())->toBeTrue();
});

test('mcp tools are gathered from every configured server', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->mcp_enabled = true;
    $aiSettings->mcp_servers = [
        ['name' => 'first', 'connection' => 'web', 'url' => 'https://first.example.com'],
        ['name' => 'second', 'connection' => 'local', 'command' => 'npx', 'arguments' => ['-y', 'server']],
    ];
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    $mcpServerConnector = fakeMcpServerConnector([fakeMcpTool()]);
    $this->app->instance(McpServerConnector::class, $mcpServerConnector);

    $tools = app(AiConfiguration::class)->mcpTools();

    expect($tools)->toHaveCount(2)
        ->and($mcpServerConnector->servers)->toBe(['first', 'second'])
        ->and(app(AiConfiguration::class)->isMcpEnabled())->toBeTrue();
});

test('invalid mcp servers are skipped', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->mcp_enabled = true;
    $aiSettings->mcp_servers = [
        ['name' => 'broken'],
        ['name' => 'working', 'connection' => 'web', 'url' => 'https://working.example.com'],
    ];
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    $mcpServerConnector = fakeMcpServerConnector([fakeMcpTool()]);
    $this->app->instance(McpServerConnector::class, $mcpServerConnector);

    expect(app(AiConfiguration::class)->mcpTools())->toHaveCount(1)
        ->and($mcpServerConnector->servers)->toBe(['working']);
});

test('mcp tools are empty when mcp is disabled', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->mcp_enabled = false;
    $aiSettings->mcp_servers = [
        ['name' => 'first', 'connection' => 'web', 'url' => 'https://first.example.com'],
    ];
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    $mcpServerConnector = fakeMcpServerConnector([fakeMcpTool()]);
    $this->app->instance(McpServerConnector::class, $mcpServerConnector);

    expect(app(AiConfiguration::class)->mcpTools())->toBe([])
        ->and(app(AiConfiguration::class)->isMcpEnabled())->toBeFalse()
        ->and($mcpServerConnector->servers)->toBe([]);
});

test('a failing mcp server does not break tool discovery', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->mcp_enabled = true;
    $aiSettings->mcp_servers = [
        ['name' => 'offline', 'connection' => 'web', 'url' => 'https://offline.example.com'],
    ];
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    $this->app->instance(
        McpServerConnector::class,
        fakeMcpServerConnector(throwable: new RuntimeException('connection refused')),
    );

    expect(app(AiConfiguration::class)->mcpTools())->toBe([]);
});

test('the laravel mcp connector builds clients from server definitions', function (): void {
    $connector = new LaravelMcpServerConnector;

    $reflection = new ReflectionMethod($connector, 'client');

    $web = $reflection->invoke($connector, [
        'name' => 'web-server',
        'connection' => 'web',
        'url' => 'https://mcp.example.com',
        'token' => 'secret',
        'timeout' => 30,
        'command' => null,
        'arguments' => [],
    ]);

    $local = $reflection->invoke($connector, [
        'name' => 'local-server',
        'connection' => 'local',
        'url' => null,
        'token' => null,
        'timeout' => 30,
        'command' => 'npx',
        'arguments' => ['-y', 'server'],
    ]);

    expect($web)->toBeInstanceOf(WebClient::class)
        ->and($local)->toBeInstanceOf(Client::class)
        ->and($local)->not->toBeInstanceOf(WebClient::class);
});

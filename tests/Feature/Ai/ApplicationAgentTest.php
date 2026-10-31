<?php

declare(strict_types=1);

use App\Ai\Agents\ApplicationAgent;
use App\Ai\McpServerConnector;
use App\Ai\Tools\SiteInformationTool;
use App\Settings\AiSettings;
use App\Settings\SystemSettings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

beforeEach(function (): void {
    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);

    app()->forgetInstance(AiSettings::class);
    app()->forgetInstance(SystemSettings::class);
});

test('agent instructions mention the application', function (): void {
    $systemSettings = app(SystemSettings::class);

    $instructions = (string) app(ApplicationAgent::class)->instructions();

    expect($instructions)->toContain($systemSettings->site_name)
        ->and($instructions)->toContain($systemSettings->site_description);
});

test('agent exposes the site information tool', function (): void {
    $tools = collect(app(ApplicationAgent::class)->tools());

    expect($tools->first())->toBeInstanceOf(SiteInformationTool::class);
});

test('the site information tool returns the configured site details', function (): void {
    $systemSettings = app(SystemSettings::class);
    $systemSettings->site_name = 'Acme';
    $systemSettings->site_description = 'Everything Acme.';
    $systemSettings->save();

    app()->forgetInstance(SystemSettings::class);

    $payload = json_decode(
        (string) app(SiteInformationTool::class)->handle(new Request),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($payload['site_name'])->toBe('Acme')
        ->and($payload['site_description'])->toBe('Everything Acme.')
        ->and($payload['timezone'])->toBe(app(SystemSettings::class)->timezone);
});

test('agent exposes mcp tools when mcp is enabled', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->mcp_enabled = true;
    $aiSettings->mcp_servers = [
        ['name' => 'demo', 'connection' => 'web', 'url' => 'https://mcp.example.com'],
    ];
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    $mcpTool = new class implements Tool
    {
        public function description(): string
        {
            return 'An MCP provided tool.';
        }

        public function handle(Request $request): string
        {
            return 'mcp';
        }

        public function schema(JsonSchema $schema): array
        {
            return [];
        }
    };

    $this->app->instance(McpServerConnector::class, new class($mcpTool) implements McpServerConnector
    {
        public function __construct(protected Tool $tool) {}

        public function tools(array $server): iterable
        {
            return [$this->tool];
        }
    });

    $tools = collect(app(ApplicationAgent::class)->tools());

    expect($tools)->toHaveCount(2)
        ->and($tools->contains($mcpTool))->toBeTrue();
});

test('agent only exposes local tools when mcp is disabled', function (): void {
    $tools = collect(app(ApplicationAgent::class)->tools());

    expect($tools)->toHaveCount(1)
        ->and($tools->first())->toBeInstanceOf(SiteInformationTool::class);
});

test('agent prompts can be faked', function (): void {
    ApplicationAgent::fake(['The answer is 42.']);

    $agentResponse = app(ApplicationAgent::class)->prompt('What is the answer?');

    expect($agentResponse->text)->toBe('The answer is 42.');

    ApplicationAgent::assertPrompted('What is the answer?');
});

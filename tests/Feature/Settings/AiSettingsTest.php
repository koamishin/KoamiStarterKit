<?php

declare(strict_types=1);

use App\Enums\AiProvider;
use App\Settings\AiSettings;

beforeEach(function (): void {
    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);

    app()->forgetInstance(AiSettings::class);
});

test('ai is disabled by default', function (): void {
    $aiSettings = app(AiSettings::class);

    expect($aiSettings->enabled)->toBeFalse()
        ->and($aiSettings->provider)->toBe('openai')
        ->and($aiSettings->model)->toBeNull()
        ->and($aiSettings->mcp_enabled)->toBeFalse()
        ->and($aiSettings->mcp_servers)->toBe([])
        ->and($aiSettings->isConfigured())->toBeFalse();
});

test('unknown providers fall back to openai', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->provider = 'not-a-provider';

    expect($aiSettings->resolvedProvider())->toBe(AiProvider::OpenAI);
});

test('resolved model falls back to the provider default', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->provider = AiProvider::Anthropic->value;
    $aiSettings->model = null;

    expect($aiSettings->resolvedModel())->toBe(AiProvider::Anthropic->defaultModel());

    $aiSettings->model = 'claude-custom';

    expect($aiSettings->resolvedModel())->toBe('claude-custom');
});

test('a provider is only configured once it has an api key', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->provider = AiProvider::OpenAI->value;
    $aiSettings->api_key = null;

    expect($aiSettings->isConfigured())->toBeFalse();

    $aiSettings->api_key = 'sk-test';

    expect($aiSettings->isConfigured())->toBeTrue();
});

test('environment api keys count as configuration', function (): void {
    config()->set('ai.providers.openai.key', 'env-key');

    $aiSettings = app(AiSettings::class);
    $aiSettings->provider = AiProvider::OpenAI->value;
    $aiSettings->api_key = null;

    expect($aiSettings->usesEnvApiKey())->toBeTrue()
        ->and($aiSettings->isConfigured())->toBeTrue();
});

test('providers without an api key are always configured', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->provider = AiProvider::Ollama->value;
    $aiSettings->api_key = null;

    expect($aiSettings->isConfigured())->toBeTrue();
});

test('mcp server definitions are normalized and validated', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->mcp_servers = [
        [
            'name' => ' github ',
            'connection' => 'web',
            'url' => 'https://mcp.example.com',
            'token' => 'secret',
            'timeout' => '45',
            'command' => 'ignored',
            'arguments' => ['ignored'],
        ],
        [
            'name' => 'local-server',
            'connection' => 'local',
            'command' => 'npx',
            'arguments' => ['-y', '@modelcontextprotocol/server-everything', ''],
        ],
        ['name' => 'missing-url', 'connection' => 'web'],
        ['name' => '', 'connection' => 'web', 'url' => 'https://mcp.example.com'],
        ['connection' => 'web', 'url' => 'https://mcp.example.com'],
        'not-an-array',
    ];

    $servers = $aiSettings->mcpServers();

    expect($servers)->toHaveCount(2)
        ->and($servers[0])->toBe([
            'name' => 'github',
            'connection' => 'web',
            'url' => 'https://mcp.example.com',
            'token' => 'secret',
            'timeout' => 45,
            'command' => null,
            'arguments' => [],
        ])
        ->and($servers[1])->toBe([
            'name' => 'local-server',
            'connection' => 'local',
            'url' => null,
            'token' => null,
            'timeout' => 30,
            'command' => 'npx',
            'arguments' => ['-y', '@modelcontextprotocol/server-everything'],
        ]);
});

test('normalized mcp servers never expose a zero timeout', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->mcp_servers = [
        ['name' => 'github', 'connection' => 'web', 'url' => 'https://mcp.example.com', 'timeout' => 0],
    ];

    expect($aiSettings->mcpServers()[0]['timeout'])->toBe(1);
});

<?php

declare(strict_types=1);

use App\Ai\Agents\ApplicationAgent;
use App\Settings\AiSettings;

beforeEach(function (): void {
    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);

    app()->forgetInstance(AiSettings::class);
});

test('the command fails when ai is disabled', function (): void {
    $this->artisan('ai:ask', ['question' => 'Hello?'])
        ->expectsOutputToContain('AI is not enabled')
        ->assertFailed();
});

test('the command fails when credentials are missing', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->enabled = true;
    $aiSettings->provider = 'openai';
    $aiSettings->api_key = null;
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    $this->artisan('ai:ask', ['question' => 'Hello?'])
        ->expectsOutputToContain('AI is not enabled')
        ->assertFailed();
});

test('the command asks the agent and prints the response', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->enabled = true;
    $aiSettings->provider = 'openai';
    $aiSettings->api_key = 'sk-test';
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    ApplicationAgent::fake(['Hello from the fake agent.']);

    $this->artisan('ai:ask', ['question' => 'Hello?'])
        ->expectsOutputToContain('Hello from the fake agent.')
        ->assertSuccessful();

    ApplicationAgent::assertPrompted('Hello?');
});

test('the command fails gracefully when the provider request fails', function (): void {
    $aiSettings = app(AiSettings::class);
    $aiSettings->enabled = true;
    $aiSettings->provider = 'openai';
    $aiSettings->api_key = 'sk-test';
    $aiSettings->save();

    app()->forgetInstance(AiSettings::class);

    ApplicationAgent::fake(function (): string {
        throw new RuntimeException('The provider is unavailable.');
    });

    $this->artisan('ai:ask', ['question' => 'Hello?'])
        ->expectsOutputToContain('The provider is unavailable.')
        ->assertFailed();
});

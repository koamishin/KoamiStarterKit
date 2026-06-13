<?php

declare(strict_types=1);

use App\Enums\SocialLoginProvider;
use App\Settings\SocialLoginSettings;

beforeEach(function (): void {
    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);
    app()->forgetInstance(SocialLoginSettings::class);
});

test('provider is disabled by default', function (): void {
    $settings = app(SocialLoginSettings::class);

    foreach (SocialLoginProvider::cases() as $provider) {
        expect($settings->isProviderEnabled($provider))->toBeFalse();
    }
});

test('provider is enabled when spatie toggle and credentials are set', function (): void {
    $settings = app(SocialLoginSettings::class);
    $settings->github_enabled = true;
    $settings->github_client_id = 'spatie-id';
    $settings->github_client_secret = 'spatie-secret';
    $settings->save();

    app()->forgetInstance(SocialLoginSettings::class);

    $fresh = app(SocialLoginSettings::class);

    expect($fresh->isProviderEnabled(SocialLoginProvider::Github))->toBeTrue();
});

test('provider is disabled when toggle is true but credentials are missing', function (): void {
    $settings = app(SocialLoginSettings::class);
    $settings->github_enabled = true;
    $settings->github_client_id = null;
    $settings->github_client_secret = null;
    $settings->save();

    app()->forgetInstance(SocialLoginSettings::class);

    $fresh = app(SocialLoginSettings::class);

    expect($fresh->isProviderEnabled(SocialLoginProvider::Github))->toBeFalse();
});

test('env credentials win over spatie credentials', function (): void {
    config()->set('services.github.client_id', 'env-id');
    config()->set('services.github.client_secret', 'env-secret');

    $settings = app(SocialLoginSettings::class);
    $settings->github_enabled = true;
    $settings->github_client_id = 'spatie-id';
    $settings->github_client_secret = 'spatie-secret';
    $settings->save();

    app()->forgetInstance(SocialLoginSettings::class);

    $fresh = app(SocialLoginSettings::class);

    $creds = $fresh->resolveCredentials(SocialLoginProvider::Github);

    expect($creds)->not->toBeNull();
    expect($creds['client_id'])->toBe('env-id');
    expect($creds['client_secret'])->toBe('env-secret');
});

test('spatie credentials are used when env is empty', function (): void {
    config()->set('services.google.client_id', null);
    config()->set('services.google.client_secret', null);

    $settings = app(SocialLoginSettings::class);
    $settings->google_enabled = true;
    $settings->google_client_id = 'spatie-google-id';
    $settings->google_client_secret = 'spatie-google-secret';
    $settings->save();

    app()->forgetInstance(SocialLoginSettings::class);

    $fresh = app(SocialLoginSettings::class);

    $creds = $fresh->resolveCredentials(SocialLoginProvider::Google);

    expect($creds)->not->toBeNull();
    expect($creds['client_id'])->toBe('spatie-google-id');
    expect($creds['client_secret'])->toBe('spatie-google-secret');
});

test('resolve credentials returns null when nothing is configured', function (): void {
    config()->set('services.facebook.client_id', null);
    config()->set('services.facebook.client_secret', null);

    $settings = app(SocialLoginSettings::class);

    expect($settings->resolveCredentials(SocialLoginProvider::Facebook))->toBeNull();
});

test('isUsingEnv reports env presence', function (): void {
    config()->set('services.github.client_id', 'env-id');
    config()->set('services.github.client_secret', 'env-secret');

    $settings = app(SocialLoginSettings::class);

    expect($settings->isUsingEnv(SocialLoginProvider::Github))->toBeTrue();
    expect($settings->isUsingEnv(SocialLoginProvider::Google))->toBeFalse();
});

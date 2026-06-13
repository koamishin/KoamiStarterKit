<?php

declare(strict_types=1);

use App\Enums\SocialLoginProvider;
use App\Settings\SocialLoginSettings;

beforeEach(function (): void {
    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);
    app()->forgetInstance(SocialLoginSettings::class);
});

test('provider is disabled by default', function (): void {
    $socialLoginSettings = app(SocialLoginSettings::class);

    foreach (SocialLoginProvider::cases() as $provider) {
        expect($socialLoginSettings->isProviderEnabled($provider))->toBeFalse();
    }
});

test('provider is enabled when spatie toggle and credentials are set', function (): void {
    $socialLoginSettings = app(SocialLoginSettings::class);
    $socialLoginSettings->github_enabled = true;
    $socialLoginSettings->github_client_id = 'spatie-id';
    $socialLoginSettings->github_client_secret = 'spatie-secret';
    $socialLoginSettings->save();

    app()->forgetInstance(SocialLoginSettings::class);

    $fresh = app(SocialLoginSettings::class);

    expect($fresh->isProviderEnabled(SocialLoginProvider::Github))->toBeTrue();
});

test('provider is disabled when toggle is true but credentials are missing', function (): void {
    $socialLoginSettings = app(SocialLoginSettings::class);
    $socialLoginSettings->github_enabled = true;
    $socialLoginSettings->github_client_id = null;
    $socialLoginSettings->github_client_secret = null;
    $socialLoginSettings->save();

    app()->forgetInstance(SocialLoginSettings::class);

    $fresh = app(SocialLoginSettings::class);

    expect($fresh->isProviderEnabled(SocialLoginProvider::Github))->toBeFalse();
});

test('env credentials win over spatie credentials', function (): void {
    config()->set('services.github.client_id', 'env-id');
    config()->set('services.github.client_secret', 'env-secret');

    $socialLoginSettings = app(SocialLoginSettings::class);
    $socialLoginSettings->github_enabled = true;
    $socialLoginSettings->github_client_id = 'spatie-id';
    $socialLoginSettings->github_client_secret = 'spatie-secret';
    $socialLoginSettings->save();

    app()->forgetInstance(SocialLoginSettings::class);

    $fresh = app(SocialLoginSettings::class);

    $creds = $fresh->resolveCredentials(SocialLoginProvider::Github);

    expect($creds)->not->toBeNull();
    expect($creds['client_id'])->toBe('env-id');
    expect($creds['client_secret'])->toBe('env-secret');
});

test('spatie credentials are used when env is empty', function (): void {
    config()->set('services.google.client_id');
    config()->set('services.google.client_secret');

    $socialLoginSettings = app(SocialLoginSettings::class);
    $socialLoginSettings->google_enabled = true;
    $socialLoginSettings->google_client_id = 'spatie-google-id';
    $socialLoginSettings->google_client_secret = 'spatie-google-secret';
    $socialLoginSettings->save();

    app()->forgetInstance(SocialLoginSettings::class);

    $fresh = app(SocialLoginSettings::class);

    $creds = $fresh->resolveCredentials(SocialLoginProvider::Google);

    expect($creds)->not->toBeNull();
    expect($creds['client_id'])->toBe('spatie-google-id');
    expect($creds['client_secret'])->toBe('spatie-google-secret');
});

test('resolve credentials returns null when nothing is configured', function (): void {
    config()->set('services.facebook.client_id');
    config()->set('services.facebook.client_secret');

    $socialLoginSettings = app(SocialLoginSettings::class);

    expect($socialLoginSettings->resolveCredentials(SocialLoginProvider::Facebook))->toBeNull();
});

test('isUsingEnv reports env presence', function (): void {
    config()->set('services.github.client_id', 'env-id');
    config()->set('services.github.client_secret', 'env-secret');

    $socialLoginSettings = app(SocialLoginSettings::class);

    expect($socialLoginSettings->isUsingEnv(SocialLoginProvider::Github))->toBeTrue();
    expect($socialLoginSettings->isUsingEnv(SocialLoginProvider::Google))->toBeFalse();
});

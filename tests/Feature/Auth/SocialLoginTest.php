<?php

declare(strict_types=1);

use App\Models\SocialAccount;
use App\Models\User;
use App\Settings\SocialLoginSettings;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('migrate', ['--path' => 'database/settings', '--no-interaction' => true]);
    app()->forgetInstance(SocialLoginSettings::class);

    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    config()->set('services.github.client_id', 'test-id');
    config()->set('services.github.client_secret', 'test-secret');
    config()->set('services.google.client_id');
    config()->set('services.google.client_secret');
    config()->set('services.facebook.client_id');
    config()->set('services.facebook.client_secret');
});

function enableGithub(): void
{
    $socialLoginSettings = app(SocialLoginSettings::class);
    $socialLoginSettings->github_enabled = true;
    $socialLoginSettings->github_client_id = 'test-id';
    $socialLoginSettings->github_client_secret = 'test-secret';
    $socialLoginSettings->save();
    app()->forgetInstance(SocialLoginSettings::class);
}

function buildSocialiteUser(string $id, string $email, ?string $name = null, ?string $nickname = null, ?string $avatar = null): SocialiteUser
{
    $user = (new SocialiteUser)->map([
        'id' => $id,
        'nickname' => $nickname ?? 'user'.$id,
        'name' => $name ?? 'User '.$id,
        'email' => $email,
        'avatar' => $avatar ?? 'https://avatars.githubusercontent.com/u/'.$id,
    ]);
    $user->setToken('fake-token');

    return $user;
}

test('callback with new provider id creates a user and social account', function (): void {
    enableGithub();

    Socialite::fake('github', buildSocialiteUser('gh-100', 'octo@example.com', 'Octo Cat', 'octocat', 'https://avatars.githubusercontent.com/u/1'));

    $response = $this->get(route('auth.social.callback', ['provider' => 'github']));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'octo@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Octo Cat');
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->profile_photo_path)->toBe('https://avatars.githubusercontent.com/u/1');

    $account = SocialAccount::query()
        ->where('user_id', $user->id)
        ->where('provider', 'github')
        ->first();

    expect($account)->not->toBeNull();
    expect($account->provider_id)->toBe('gh-100');
    expect($account->nickname)->toBe('octocat');
});

test('callback with existing social account logs in without creating a new user', function (): void {
    enableGithub();

    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'email_verified_at' => now(),
    ]);

    SocialAccount::query()->create([
        'user_id' => $user->id,
        'provider' => 'github',
        'provider_id' => 'gh-200',
        'nickname' => 'oldname',
    ]);

    Socialite::fake('github', buildSocialiteUser('gh-200', 'existing@example.com', 'Updated Name', 'updated', 'https://avatars.githubusercontent.com/u/2'));

    $response = $this->get(route('auth.social.callback', ['provider' => 'github']));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);

    expect(User::query()->where('email', 'existing@example.com')->count())->toBe(1);
    expect($user->fresh()->profile_photo_path)->toBe('https://avatars.githubusercontent.com/u/2');
    expect($user->socialAccounts()->where('provider', 'github')->first()->nickname)->toBe('updated');
});

test('callback auto links to existing verified user with matching email', function (): void {
    enableGithub();

    $user = User::factory()->create([
        'email' => 'linkme@example.com',
        'email_verified_at' => now(),
    ]);

    Socialite::fake('github', buildSocialiteUser('gh-300', 'linkme@example.com', 'Linker', 'linker', 'https://avatars.githubusercontent.com/u/3'));

    $response = $this->get(route('auth.social.callback', ['provider' => 'github']));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);

    expect($user->socialAccounts()->where('provider', 'github')->where('provider_id', 'gh-300')->exists())->toBeTrue();
});

test('callback refuses to link to existing unverified user', function (): void {
    enableGithub();

    User::factory()->unverified()->create([
        'email' => 'unverified@example.com',
    ]);

    Socialite::fake('github', buildSocialiteUser('gh-400', 'unverified@example.com', 'Hacker', 'hacker', 'https://avatars.githubusercontent.com/u/4'));

    $this->get(route('auth.social.callback', ['provider' => 'github']))
        ->assertStatus(409);

    $this->assertGuest();

    expect(SocialAccount::query()->where('provider', 'github')->count())->toBe(0);
    expect(User::query()->count())->toBe(1);
});

test('redirect to a disabled provider returns an error', function (): void {
    $socialLoginSettings = app(SocialLoginSettings::class);
    $socialLoginSettings->github_enabled = false;
    $socialLoginSettings->save();
    app()->forgetInstance(SocialLoginSettings::class);

    $response = $this->get(route('auth.social.redirect', ['provider' => 'github']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['social']);
});

test('callback for a disabled provider returns an error', function (): void {
    $socialLoginSettings = app(SocialLoginSettings::class);
    $socialLoginSettings->github_enabled = false;
    $socialLoginSettings->save();
    app()->forgetInstance(SocialLoginSettings::class);

    $response = $this->get(route('auth.social.callback', ['provider' => 'github']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['social']);
});

test('callback for an unknown provider returns 404', function (): void {
    $this->get(route('auth.social.callback', ['provider' => 'twitter']))
        ->assertNotFound();
});

test('redirect for an unknown provider returns 404', function (): void {
    $this->get(route('auth.social.redirect', ['provider' => 'twitter']))
        ->assertNotFound();
});

test('handleInertiaRequests only exposes enabled providers', function (): void {
    enableGithub();

    $socialLoginSettings = app(SocialLoginSettings::class);
    $socialLoginSettings->google_enabled = true;
    $socialLoginSettings->google_client_id = 'g-id';
    $socialLoginSettings->google_client_secret = 'g-secret';
    $socialLoginSettings->save();
    app()->forgetInstance(SocialLoginSettings::class);

    $response = $this->get(route('login'));
    $response->assertOk();

    $props = $response->viewData('page')['props'];

    $slugs = collect($props['socialProviders'])->pluck('slug')->all();

    expect($slugs)->toContain('github');
    expect($slugs)->toContain('google');
    expect($slugs)->not->toContain('facebook');
});

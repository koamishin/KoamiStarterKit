<?php

use App\Models\User;
use Filament\Auth\MultiFactor\Email\Notifications\VerifyEmailAuthentication;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('profile settings page includes filament mfa configuration', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertInertia(fn (Assert $assert): \Inertia\Testing\AssertableInertia => $assert
            ->component('settings/Security')
            ->has('filamentMfa.providers')
            ->has('filamentMfa.state')
        );
});

test('app mfa can be set up and enabled', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $setupResponse = $this->postJson(route('security.mfa.app.setup'));

    $setupResponse->assertOk();

    $payload = $setupResponse->json();

    filament()->setCurrentPanel('admin');
    $panel = Filament::getPanel('admin');
    $provider = $panel->getMultiFactorAuthenticationProviders()['app'];

    $code = $provider->getCurrentCode($user, $payload['secret']);

    $enableResponse = $this->postJson(route('security.mfa.app.enable'), [
        'encrypted' => $payload['encrypted'],
        'code' => $code,
    ]);

    $enableResponse->assertOk();

    $user->refresh();
    expect($user->getAppAuthenticationSecret())->not->toBeNull();
});

test('app mfa can be disabled', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $setupResponse = $this->postJson(route('security.mfa.app.setup'));
    $payload = $setupResponse->json();

    filament()->setCurrentPanel('admin');
    $panel = Filament::getPanel('admin');
    $provider = $panel->getMultiFactorAuthenticationProviders()['app'];

    $code = $provider->getCurrentCode($user, $payload['secret']);

    $this->postJson(route('security.mfa.app.enable'), [
        'encrypted' => $payload['encrypted'],
        'code' => $code,
    ])->assertOk();

    $this->deleteJson(route('security.mfa.app.disable'))->assertOk();

    $user->refresh();
    expect($user->getAppAuthenticationSecret())->toBeNull();
});

test('email mfa can be enabled and disabled', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Notification::fake();

    $this->postJson(route('security.mfa.email.start'))->assertOk();

    $code = null;

    Notification::assertSentTo($user, VerifyEmailAuthentication::class, function (VerifyEmailAuthentication $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    $this->postJson(route('security.mfa.email.enable'), [
        'code' => $code,
    ])->assertOk();

    $user->refresh();
    expect($user->hasEmailAuthentication())->toBeTrue();

    $this->deleteJson(route('security.mfa.email.disable'))->assertOk();

    $user->refresh();
    expect($user->hasEmailAuthentication())->toBeFalse();
});

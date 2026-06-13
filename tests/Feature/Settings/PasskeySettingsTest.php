<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\PasskeyFactory;

test('profile page exposes the user passkeys to the front end', function (): void {
    $user = User::factory()->create();

    $factory = new PasskeyFactory;

    $first = $factory->forUser($user)->create([
        'name' => 'MacBook Touch ID',
        'last_used_at' => now()->subDay(),
    ]);

    $second = $factory->forUser($user)->create([
        'name' => 'iPhone 15',
        'last_used_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();

    $passkeys = $response->original->getData()['page']['props']['passkeys'];

    expect($passkeys)->toHaveCount(2);

    $byName = collect($passkeys)->keyBy('name')->all();

    expect($byName['MacBook Touch ID']['id'])->toBe((string) $first->id)
        ->and($byName['MacBook Touch ID']['last_used_at'])->not->toBeNull()
        ->and($byName['iPhone 15']['id'])->toBe((string) $second->id)
        ->and($byName['iPhone 15']['last_used_at'])->toBeNull();
});

test('profile page returns an empty passkeys list when the user has none', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();

    $passkeys = $response->original->getData()['page']['props']['passkeys'];

    expect($passkeys)->toBe([]);
});

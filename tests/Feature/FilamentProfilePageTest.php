<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;

test('admin panel uses the custom profile page', function () {
    $panel = Filament::getPanel('admin');

    expect($panel)->not->toBeNull();
    expect($panel->getProfilePage())->toBe(EditProfile::class);
});

test('profile menu item points to the custom profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    filament()->setCurrentPanel('admin');

    $panel = Filament::getPanel('admin');

    $profileAction = collect($panel->getUserMenuItems())
        ->first(fn (Action $action): bool => $action->getName() === 'profile');

    expect($profileAction)->not->toBeNull();
    expect($profileAction->getLabel())->toBe('My profile');
    expect($profileAction->getUrl())->toBe(EditProfile::getUrl());
});

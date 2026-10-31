<?php

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Ipatco\FilamentProfile\Pages\EditProfile;
use Spatie\Permission\Models\Role;

test('admin panel uses the profile plugin page', function (): void {
    $panel = Filament::getPanel('admin');

    expect($panel)->not->toBeNull();
    expect($panel->getProfilePage())->toBe(EditProfile::class);
});

test('profile menu item points to the profile plugin page', function (): void {
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

test('profile page renders the plugin sections', function (): void {
    Role::findOrCreate('super_admin', 'web');

    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get(EditProfile::getUrl())
        ->assertSuccessful()
        ->assertSee(__('filament-profile::profile.sections.profile.title'))
        ->assertSee(__('filament-profile::profile.sections.password.title'))
        ->assertSee(__('filament-profile::profile.sections.delete.title'));
});

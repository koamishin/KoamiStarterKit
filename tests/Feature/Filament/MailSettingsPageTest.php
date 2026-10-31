<?php

declare(strict_types=1);

use App\Enums\RoleEnums;
use App\Filament\Clusters\Settings\Pages\MailSettingsPage;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(RoleEnums::SUPER_ADMIN->value, 'web');
    Role::findOrCreate(RoleEnums::ADMIN->value, 'web');
    Role::findOrCreate(RoleEnums::USER->value, 'web');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('mail settings page is registered in admin panel', function (): void {
    $panel = Filament::getPanel('admin');

    $pages = $panel->getPages();

    expect(collect($pages)->contains(
        fn ($page): bool => $page === MailSettingsPage::class
    ))->toBeTrue();
});

test('super admin can access mail settings page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnums::SUPER_ADMIN->value);

    $this->actingAs($admin)
        ->get(MailSettingsPage::getUrl())
        ->assertOk()
        ->assertSee('Mail Delivery')
        ->assertSee('Active Mail Configuration');
});

test('mail settings page renders active configuration section', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnums::SUPER_ADMIN->value);

    $this->actingAs($admin);

    Livewire::test(MailSettingsPage::class)
        ->assertSee('Active Mail Configuration')
        ->assertSee('Deliverability & Verification')
        ->assertSee('Send Test Email');
});

test('mail settings test mail action dispatches email', function (): void {
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole(RoleEnums::SUPER_ADMIN->value);

    $this->actingAs($admin);

    Livewire::test(MailSettingsPage::class)
        ->callAction(TestAction::make('testMail')->schemaComponent(true), data: [
            'recipient' => 'test@example.com',
        ])
        ->assertHasNoActionErrors();

    Notification::assertNotified('Test Email Sent');
});

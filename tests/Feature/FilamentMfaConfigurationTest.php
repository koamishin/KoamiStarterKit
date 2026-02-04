<?php

use App\Models\User;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Schema;

test('users table has Filament MFA columns', function (): void {
    expect(Schema::hasColumns('users', [
        'app_authentication_secret',
        'app_authentication_recovery_codes',
        'has_email_authentication',
    ]))->toBeTrue();
});

test('user model implements Filament MFA contracts', function (): void {
    $interfaces = class_implements(User::class);

    expect($interfaces)->toContain(HasAppAuthentication::class);
    expect($interfaces)->toContain(HasAppAuthenticationRecovery::class);
    expect($interfaces)->toContain(HasEmailAuthentication::class);
    expect($interfaces)->toContain(MustVerifyEmail::class);
});

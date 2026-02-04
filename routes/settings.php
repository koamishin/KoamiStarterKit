<?php

use App\Http\Controllers\Settings\FilamentAppAuthenticationController;
use App\Http\Controllers\Settings\FilamentEmailAuthenticationController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::prefix('settings/security/mfa')->name('security.mfa.')->group(function (): void {
        Route::post('app/setup', [FilamentAppAuthenticationController::class, 'setup'])->name('app.setup');
        Route::post('app/enable', [FilamentAppAuthenticationController::class, 'enable'])->name('app.enable');
        Route::delete('app', [FilamentAppAuthenticationController::class, 'disable'])->name('app.disable');
        Route::post('app/recovery-codes', [FilamentAppAuthenticationController::class, 'regenerateRecoveryCodes'])->name('app.recovery-codes');

        Route::post('email/start', [FilamentEmailAuthenticationController::class, 'start'])->name('email.start');
        Route::post('email/resend', [FilamentEmailAuthenticationController::class, 'resend'])->name('email.resend');
        Route::post('email/enable', [FilamentEmailAuthenticationController::class, 'enable'])->name('email.enable');
        Route::delete('email', [FilamentEmailAuthenticationController::class, 'disable'])->name('email.disable');
    });
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', fn () => Inertia::render('settings/Appearance'))->name('appearance.edit');
});

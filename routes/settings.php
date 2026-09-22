<?php

use App\Features\FeatureRegistry;
use App\Http\Controllers\Settings\FilamentAppAuthenticationController;
use App\Http\Controllers\Settings\FilamentEmailAuthenticationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
/* @chisel-password-confirmation */
use Illuminate\Auth\Middleware\RequirePassword;
/* @end-chisel-password-confirmation */
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('settings/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('settings/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

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

    Route::get('settings/security', [SecurityController::class, 'edit'])
        /* @chisel-password-confirmation */
        ->middleware(RequirePassword::class)
        /* @end-chisel-password-confirmation */
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/password', fn (): RedirectResponse => redirect('/settings/security'))
        ->name('user-password.edit');

    Route::get('settings/appearance', function () {
        FeatureRegistry::initialize();
        $user = auth()->user();

        return Inertia::render('settings/Appearance', [
            'availableFeatures' => [
                'appearance' => FeatureRegistry::isFeatureAvailableForUser($user, 'settings_appearance'),
            ],
        ]);
    })->name('appearance.edit');
});

/* @chisel-passkeys */
Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
/* @end-chisel-passkeys */

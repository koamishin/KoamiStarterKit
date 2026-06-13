<?php

use App\Enums\SocialLoginProvider;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', fn () => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
});

Route::middleware('web')->group(function (): void {
    /** @phpstan-ignore-next-line */
    Route::impersonate();
    Route::get('impersonate/take-redirect', [ImpersonateController::class, 'takeRedirect'])->name('impersonate.take-redirect');
    Route::get('impersonate/leave-redirect', [ImpersonateController::class, 'leaveRedirect'])->name('impersonate.leave-redirect');
});

$socialProviders = implode('|', SocialLoginProvider::values());

Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->where('provider', $socialProviders)
    ->name('auth.social.redirect');

Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', $socialProviders)
    ->name('auth.social.callback');

require __DIR__.'/settings.php';

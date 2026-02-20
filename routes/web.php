<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', fn () => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
});

Route::middleware('web')->group(function (): void {
    /** @phpstan-ignore-next-line */
    Route::impersonate();
    Route::get('impersonate/take-redirect', [\App\Http\Controllers\ImpersonateController::class, 'takeRedirect'])->name('impersonate.take-redirect');
    Route::get('impersonate/leave-redirect', [\App\Http\Controllers\ImpersonateController::class, 'leaveRedirect'])->name('impersonate.leave-redirect');
});

require __DIR__.'/settings.php';

<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', fn () => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('web')->group(function () {
    Route::impersonate();
    Route::get('impersonate/take-redirect', [\App\Http\Controllers\ImpersonateController::class, 'takeRedirect'])->name('impersonate.take-redirect');
    Route::get('impersonate/leave-redirect', [\App\Http\Controllers\ImpersonateController::class, 'leaveRedirect'])->name('impersonate.leave-redirect');
});

require __DIR__.'/settings.php';

<?php

use Illuminate\Support\Facades\Route;

test('fortify two factor challenge routes are registered', function (): void {
    expect(Route::has('two-factor.login'))->toBeTrue();
    expect(Route::has('two-factor.login.store'))->toBeTrue();
});

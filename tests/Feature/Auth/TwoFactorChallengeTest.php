<?php

use Illuminate\Support\Facades\Route;

test('fortify two factor challenge routes are not registered', function (): void {
    expect(Route::has('two-factor.login'))->toBeFalse();
    expect(Route::has('two-factor.login.store'))->toBeFalse();
});

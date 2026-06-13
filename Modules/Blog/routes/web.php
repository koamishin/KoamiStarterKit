<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');

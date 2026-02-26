<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;

// Favicon route - prevent 404s
Route::get('/favicon.ico', function () {
    return response()->file(public_path('favicon.ico'));
});

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/posts/{task}', [BlogController::class, 'show'])->name('blog.show');

Route::redirect('/', '/tasks');

Route::get('/{any?}', function () {
    return response()->file(public_path('app/index.html'));
})->where('any', '^(?!blog(?:/|$)|posts(?:/|$)).*')->name('home');

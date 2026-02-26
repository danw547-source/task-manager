<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any?}', function () {
    $frontendEntry = public_path('app/index.html');

    if (file_exists($frontendEntry)) {
        return response()->file($frontendEntry);
    }

    return response()->json([
        'message' => 'Frontend build assets are not available yet. Run npm --prefix frontend run build.',
    ]);
})->where('any', '.*')->name('home');

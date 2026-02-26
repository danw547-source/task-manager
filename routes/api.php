<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskEngagementController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:api')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Task Routes - RESTful resource routes
    Route::middleware('auth:api')->group(function () {
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

        Route::apiResource('tasks', TaskController::class);

        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', UserController::class);
        });

        // Task-specific custom routes
        Route::post('/tasks/{task}/reminder', [TaskController::class, 'setReminder']);
        Route::post('/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');

        // Task engagement routes (follow/comments)
        Route::post('/tasks/{task}/follow', [TaskEngagementController::class, 'follow']);
        Route::delete('/tasks/{task}/follow', [TaskEngagementController::class, 'unfollow']);

        Route::get('/tasks/{task}/comments', [TaskEngagementController::class, 'comments']);
        Route::post('/tasks/{task}/comments', [TaskEngagementController::class, 'comment']);

        Route::get('/tasks/messages/unread', [TaskEngagementController::class, 'unreadMessages']);
        Route::post('/tasks/{task}/messages/read', [TaskEngagementController::class, 'markTaskMessagesRead']);
    });
});

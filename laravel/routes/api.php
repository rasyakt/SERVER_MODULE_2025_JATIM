<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ScoreController;

Route::prefix('v1')->group(function () {
    // 1. Public Endpoints (Authentication & Read-Only)
    Route::post('auth/signup', [AuthController::class, 'signup']);
    Route::post('auth/signin', [AuthController::class, 'signin']);
    
    Route::get('games', [GameController::class, 'index']);
    Route::get('games/{slug}', [GameController::class, 'show']);
    Route::get('games/{slug}/scores', [ScoreController::class, 'index']);

    // Non-REST Custom Upload endpoint (manually handles auth inside controller for plain-text response)
    Route::post('games/{slug}/upload', [GameController::class, 'upload']);

    // 2. Authenticated Endpoints (auth:sanctum & ensure not blocked)
    Route::middleware(['auth:sanctum', 'not_blocked'])->group(function () {
        Route::post('auth/signout', [AuthController::class, 'signout']);

        // Game management for developers
        Route::post('games', [GameController::class, 'store']);
        Route::put('games/{slug}', [GameController::class, 'update']);
        Route::delete('games/{slug}', [GameController::class, 'destroy']);

        // Score submission
        Route::post('games/{slug}/scores', [ScoreController::class, 'store']);

        // Profile details
        Route::get('users/{username}', [UserController::class, 'show']);
    });

    // 3. Administrator Endpoints (auth:sanctum, ensure not blocked & ensure admin)
    Route::middleware(['auth:sanctum', 'not_blocked', 'admin'])->group(function () {
        Route::get('admins', [UserController::class, 'getAdmins']);
        
        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{id}', [UserController::class, 'update']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);
    });
});

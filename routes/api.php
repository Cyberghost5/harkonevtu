<?php

use App\Http\Controllers\Api\v1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::get('/docs', [\App\Http\Controllers\ApiDocController::class, 'index']);

Route::prefix('v1')->group(function () {

    // ── Authentication Endpoints (Milestone 1) ───────────────────────────────
    Route::prefix('auth')->group(function () {
        // Guest Auth Routes
        Route::post('/register',        [AuthController::class, 'register']);
        Route::post('/login',           [AuthController::class, 'login']);
        Route::post('/verify-otp',      [AuthController::class, 'verifyOtp']);
        Route::post('/resend-otp',      [AuthController::class, 'resendOtp']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password',  [AuthController::class, 'resetPassword']);

        // Authenticated Auth Routes (Sanctum Bearer Token Required)
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me',     [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // ── User Profile & Account Management Endpoints (Milestone 2) ────────────
    Route::prefix('user')->middleware('auth:sanctum')->group(function () {
        Route::get('/profile',            [\App\Http\Controllers\Api\v1\User\UserController::class, 'profile']);
        Route::put('/profile',            [\App\Http\Controllers\Api\v1\User\UserController::class, 'updateProfile']);
        Route::put('/password',           [\App\Http\Controllers\Api\v1\User\UserController::class, 'updatePassword']);
        Route::put('/pin',                [\App\Http\Controllers\Api\v1\User\UserController::class, 'updatePin']);
        Route::post('/pin/verify',        [\App\Http\Controllers\Api\v1\User\UserController::class, 'verifyPin']);
        Route::post('/pin/reset-request', [\App\Http\Controllers\Api\v1\User\UserController::class, 'requestPinReset']);
        Route::put('/bank',               [\App\Http\Controllers\Api\v1\User\UserController::class, 'updateBank']);
        Route::post('/upgrade-agent',     [\App\Http\Controllers\Api\v1\User\UserController::class, 'upgradeAgent']);
        Route::post('/dva/generate',      [\App\Http\Controllers\Api\v1\User\UserController::class, 'generateDva']);
        Route::delete('/account',         [\App\Http\Controllers\Api\v1\User\UserController::class, 'deleteAccount']);
    });

});

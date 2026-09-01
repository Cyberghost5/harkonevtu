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

});

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

    // ── Airtime Services Endpoints (Milestone 3) ──────────────────────────────
    Route::prefix('airtime')->middleware('auth:sanctum')->group(function () {
        Route::get('/networks',          [\App\Http\Controllers\Api\v1\Airtime\AirtimeController::class, 'networks']);
        Route::post('/network-lookup',   [\App\Http\Controllers\Api\v1\Airtime\AirtimeController::class, 'lookup']);
        Route::post('/purchase',         [\App\Http\Controllers\Api\v1\Airtime\AirtimeController::class, 'purchase']);
    });

    // ── Data Services Endpoints (Milestone 3) ─────────────────────────────────
    Route::prefix('data')->middleware('auth:sanctum')->group(function () {
        Route::get('/networks',          [\App\Http\Controllers\Api\v1\Data\DataApiController::class, 'networks']);
        Route::post('/plans',            [\App\Http\Controllers\Api\v1\Data\DataApiController::class, 'plans']);
        Route::post('/purchase',         [\App\Http\Controllers\Api\v1\Data\DataApiController::class, 'purchase']);
    });

    // ── Bills & Utilities Services Endpoints (Milestone 4) ────────────────────
    Route::prefix('bills')->middleware('auth:sanctum')->group(function () {
        // Electricity
        Route::get('/electricity/discos',          [\App\Http\Controllers\Api\v1\Bills\BillsApiController::class, 'electricityDiscos']);
        Route::post('/electricity/validate-meter', [\App\Http\Controllers\Api\v1\Bills\BillsApiController::class, 'validateMeter']);
        Route::post('/electricity/purchase',       [\App\Http\Controllers\Api\v1\Bills\BillsApiController::class, 'purchaseElectricity']);

        // Cable TV
        Route::get('/cable/providers',             [\App\Http\Controllers\Api\v1\Bills\BillsApiController::class, 'cableProviders']);
        Route::post('/cable/plans',                [\App\Http\Controllers\Api\v1\Bills\BillsApiController::class, 'cablePlans']);
        Route::post('/cable/validate-card',        [\App\Http\Controllers\Api\v1\Bills\BillsApiController::class, 'validateSmartcard']);
        Route::post('/cable/purchase',             [\App\Http\Controllers\Api\v1\Bills\BillsApiController::class, 'purchaseCable']);

        // Exam Pins
        Route::get('/exam-pins/types',             [\App\Http\Controllers\Api\v1\Bills\BillsApiController::class, 'examTypes']);
        Route::post('/exam-pins/purchase',         [\App\Http\Controllers\Api\v1\Bills\BillsApiController::class, 'purchaseExamPin']);
    });

    // ── Wallet & Payment Gateway Endpoints (Milestone 5) ──────────────────────
    Route::prefix('wallet')->middleware('auth:sanctum')->group(function () {
        Route::get('/balance',                     [\App\Http\Controllers\Api\v1\Wallet\WalletApiController::class, 'balance']);
        Route::get('/transactions',                [\App\Http\Controllers\Api\v1\Wallet\WalletApiController::class, 'transactions']);
        Route::get('/transactions/{reference}',    [\App\Http\Controllers\Api\v1\Wallet\WalletApiController::class, 'transactionDetails']);
    });

    Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
        Route::post('/initialize',                 [\App\Http\Controllers\Api\v1\Payment\PaymentApiController::class, 'initializePayment']);
        Route::post('/verify',                     [\App\Http\Controllers\Api\v1\Payment\PaymentApiController::class, 'verifyPayment']);
        Route::get('/dva-accounts',                [\App\Http\Controllers\Api\v1\Payment\PaymentApiController::class, 'dvaAccounts']);
        Route::post('/manual-request',             [\App\Http\Controllers\Api\v1\Payment\PaymentApiController::class, 'manualFundingRequest']);
        Route::post('/redeem-coupon',              [\App\Http\Controllers\Api\v1\Payment\PaymentApiController::class, 'redeemCoupon']);
    });

    // ── Milestone 6: Specialized Services & Support APIs ─────────────────────
    // Public App Config, Onboarding & Pricing
    Route::get('/app-config',                      [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'appConfig']);
    Route::get('/config',                          [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'appConfig']);
    Route::get('/onboarding',                      [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'onboardingSlides']);
    Route::get('/onboarding-slides',               [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'onboardingSlides']);
    Route::get('/pricing',                         [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'publicPricing']);

    // Authenticated Extra Services
    Route::middleware('auth:sanctum')->group(function () {
        // Betting
        Route::get('/betting/platforms',           [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'bettingPlatforms']);
        Route::post('/betting/validate-account',   [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'validateBettingAccount']);
        Route::post('/betting/fund',               [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'fundBettingAccount']);

        // Airtime to Cash
        Route::get('/airtime-to-cash/settings',    [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'airtimeToCashSettings']);
        Route::post('/airtime-to-cash/submit',     [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'submitAirtimeToCash']);
        Route::get('/airtime-to-cash/history',     [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'airtimeToCashHistory']);

        // Voucher Printing
        Route::post('/vouchers/generate',          [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'generateVouchers']);
        Route::get('/vouchers/history',            [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'voucherHistory']);

        // Referrals
        Route::get('/referrals/summary',           [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'referralSummary']);
        Route::get('/referrals/history',           [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'referralHistory']);
        Route::post('/referrals/withdraw',         [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'withdrawReferral']);

        // Support
        Route::get('/support/contact',             [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'supportContact']);
        Route::post('/support/inquiry',            [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'submitSupportInquiry']);

        // KYC Verification
        Route::get('/kyc/status',                  [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'kycStatus']);
        Route::post('/kyc/submit',                 [\App\Http\Controllers\Api\v1\Extra\ExtraApiController::class, 'submitKyc']);
    });

});

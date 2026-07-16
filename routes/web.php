<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WalletFundingController;
use App\Http\Controllers\AirtimeController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ElectricityController;
use App\Http\Controllers\CableController;
use App\Http\Controllers\ExamPinController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminFundingController;
use App\Http\Controllers\Admin\AdminApiLogController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\LockScreenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::view('/privacy-policy', 'privacy')->name('privacy-policy');
Route::view('/terms-and-conditions', 'terms-conditions')->name('terms-conditions');
Route::view('/terms-of-service', 'terms-of-service')->name('terms-of-service');

// ── Guest-only routes ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',          [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',         [AuthController::class, 'login']);

    Route::get('/register',       [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',      [AuthController::class, 'register']);

    Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',        [AuthController::class, 'resetPassword'])->name('password.update');
});

// ── Login OTP (pre-auth state - no guest or auth middleware) ──────────────────
Route::get('/login/verify-otp',  [AuthController::class, 'showLoginOtp'])->name('login.otp');
Route::post('/login/verify-otp', [AuthController::class, 'verifyLoginOtp'])->name('login.otp.verify');
Route::post('/login/resend-otp', [AuthController::class, 'resendLoginOtp'])->name('login.otp.resend');

// ── Auth-only (no verification / PIN required - verification + PIN setup flow) ─
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Email verification
    Route::get('/verify-email', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verifyEmail'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/resend', [VerificationController::class, 'resendEmail'])
        ->middleware('throttle:6,1')->name('verification.send');

    // Phone verification
    Route::get('/verify-phone',          [VerificationController::class, 'showPhoneVerification'])->name('verification.phone');
    Route::post('/verify-phone/send',    [VerificationController::class, 'sendPhoneOtp'])->name('verification.phone.send');
    Route::post('/verify-phone/confirm', [VerificationController::class, 'verifyPhone'])->name('verification.phone.confirm');

    // PIN setup (middleware ensures not-yet-set users are here)
    Route::get('/setup-pin',  [PinController::class, 'showSetup'])->name('pin.setup');
    Route::post('/setup-pin', [PinController::class, 'storePin'])->name('pin.store');
});

// ── Protected routes (auth + verified + PIN set) ──────────────────────────────
Route::middleware(['auth', 'ensure.verified', 'ensure.pin', 'ensure.not_locked'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // KYC Verification
    Route::get('/kyc',       [\App\Http\Controllers\KycController::class, 'index'])->name('kyc.index');
    Route::post('/kyc',      [\App\Http\Controllers\KycController::class, 'submit'])->name('kyc.submit');

    // Stop Impersonation
    Route::post('/impersonate/stop', [\App\Http\Controllers\Admin\AdminUserController::class, 'stopImpersonate'])->name('users.impersonate.stop');

    // PIN confirmation AJAX
    Route::post('/pin/verify', [PinController::class, 'verify'])->name('pin.verify');

    // ── Wallet Funding ────────────────────────────────────────────────────────
    Route::prefix('wallet/fund')->name('wallet.fund.')->group(function () {
        // Pages
        Route::get('/gateway', [WalletFundingController::class, 'gateway'])->name('gateway');
        Route::get('/auto',    [WalletFundingController::class, 'autoBankTransfer'])->name('auto');
        Route::get('/manual',  [WalletFundingController::class, 'manual'])->name('manual');
        Route::get('/coupon',  [WalletFundingController::class, 'coupon'])->name('coupon');

        // Gateway: initiate + verify
        Route::post('/gateway/initiate',           [WalletFundingController::class, 'initiateGateway'])->name('gateway.initiate');
        Route::post('/gateway/verify/paystack',    [WalletFundingController::class, 'verifyPaystack'])->name('gateway.verify.paystack');
        Route::post('/gateway/verify/flutterwave', [WalletFundingController::class, 'verifyFlutterwave'])->name('gateway.verify.flutterwave');
        Route::get('/gateway/flutterwave/callback',[WalletFundingController::class, 'flutterwaveRedirectCallback'])->name('gateway.flutterwave.callback');

        // Manual proof of payment
        Route::post('/manual/submit', [WalletFundingController::class, 'submitManual'])->name('manual.submit');

        // Auto bank transfer (DVA)
        Route::post('/auto/generate', [WalletFundingController::class, 'generateVirtualAccount'])->name('auto.generate');

        // Coupon redemption
        Route::post('/coupon/redeem', [WalletFundingController::class, 'redeemCoupon'])->name('coupon.redeem');
    });

    // ── Services ──────────────────────────────────────────────────────────────
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/airtime',           [AirtimeController::class, 'index'])->name('airtime');
        Route::post('/airtime/purchase', [AirtimeController::class, 'purchase'])->name('airtime.purchase');

        Route::get('/data',              [DataController::class, 'index'])->name('data');
        Route::post('/data/plans',       [DataController::class, 'getPlans'])->name('data.plans');
        Route::post('/data/purchase',    [DataController::class, 'purchase'])->name('data.purchase');

        Route::get('/electricity',                 [ElectricityController::class, 'index'])->name('electricity');
        Route::post('/electricity/validate-meter', [ElectricityController::class, 'validateMeter'])->name('electricity.validate');
        Route::post('/electricity/purchase',       [ElectricityController::class, 'purchase'])->name('electricity.purchase');

        Route::get('/cable',                   [CableController::class, 'index'])->name('cable');
        Route::post('/cable/plans',            [CableController::class, 'getPlans'])->name('cable.plans');
        Route::post('/cable/validate-card',    [CableController::class, 'validateCard'])->name('cable.validate');
        Route::post('/cable/purchase',         [CableController::class, 'purchase'])->name('cable.purchase');

        Route::get('/epins',           [ExamPinController::class, 'index'])->name('epins');
        Route::post('/epins/purchase', [ExamPinController::class, 'purchase'])->name('epins.purchase');

        Route::get('/airtime-to-cash',         [\App\Http\Controllers\AirtimeToCashController::class, 'index'])->name('airtime-to-cash');
        Route::post('/airtime-to-cash/submit', [\App\Http\Controllers\AirtimeToCashController::class, 'submit'])->name('airtime-to-cash.submit');

        Route::get('/print-pins',            [\App\Http\Controllers\VoucherPrintingController::class, 'index'])->name('print-pins');
        Route::post('/print-pins/generate',  [\App\Http\Controllers\VoucherPrintingController::class, 'generate'])->name('print-pins.generate');
        Route::get('/print-pins/print',      [\App\Http\Controllers\VoucherPrintingController::class, 'printVouchers'])->name('print-pins.print');

        Route::get('/betting',            [\App\Http\Controllers\BettingController::class, 'index'])->name('betting');
        Route::post('/betting/validate',  [\App\Http\Controllers\BettingController::class, 'validateCustomer'])->name('betting.validate');
        Route::post('/betting/purchase',  [\App\Http\Controllers\BettingController::class, 'purchase'])->name('betting.purchase');
    });

    // ── Referral ──────────────────────────────────────────────────────────────
    Route::get('/referral',           [ReferralController::class, 'index'])->name('referral');
    Route::post('/referral/withdraw', [ReferralController::class, 'withdraw'])->name('referral.withdraw');

    // ── Pricing ───────────────────────────────────────────────────────────────
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

    // ── Transaction History ───────────────────────────────────────────────────
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');

    // ── Contact Support ──────────────────────────────────────────────────────
    Route::get('/support', [SupportController::class, 'index'])->name('support');

    // ── User Settings ──────────────────────────────────────────────────────
    Route::get('/settings',                         [UserSettingsController::class, 'index'])->name('settings');
    Route::put('/settings/profile',                 [UserSettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::match(['post', 'put'], '/settings/notification', [UserSettingsController::class, 'updateNotification'])->name('settings.notification');
    Route::post('/settings/upgrade-agent',          [UserSettingsController::class, 'upgradeToAgent'])->name('settings.upgrade-agent');
    Route::put('/settings/password',                [UserSettingsController::class, 'changePassword'])->name('settings.password.change');
    Route::put('/settings/pin/change',              [UserSettingsController::class, 'changePin'])->name('settings.pin.change');
    Route::post('/settings/pin/reset-request',      [UserSettingsController::class, 'requestPinReset'])->name('settings.pin.reset.request');
    Route::put('/settings/bank',                    [UserSettingsController::class, 'updateBankDetails'])->name('settings.bank.update');
    Route::post('/settings/api/generate',           [UserSettingsController::class, 'generateApiToken'])->name('settings.api.generate');
    Route::delete('/settings/delete',               [UserSettingsController::class, 'deleteAccount'])->name('settings.delete');

    // Biometrics Setup
    Route::post('/settings/biometrics/register/options', [UserSettingsController::class, 'biometricRegisterOptions'])->name('settings.biometrics.register.options');
    Route::post('/settings/biometrics/register/verify',  [UserSettingsController::class, 'biometricRegisterVerify'])->name('settings.biometrics.register.verify');
    Route::delete('/settings/biometrics/{id}',           [UserSettingsController::class, 'biometricDelete'])->name('settings.biometrics.delete');
});

// ── PIN Reset (no auth needed - accessed via email link) ──────────────────────
Route::get('/settings/pin/reset/{token}',  [UserSettingsController::class, 'showPinReset'])->name('settings.pin.reset.form');
Route::post('/settings/pin/reset/{token}', [UserSettingsController::class, 'resetPin'])->name('settings.pin.reset.submit');

// ── Lock Screen Routes ────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/lockscreen',                       [LockScreenController::class, 'show'])->name('lockscreen');
    Route::post('/lockscreen/unlock-password',      [LockScreenController::class, 'unlockPassword'])->name('lockscreen.unlock');
    Route::post('/lockscreen/fingerprint/options',  [LockScreenController::class, 'fingerprintOptions'])->name('lockscreen.biometric.options');
    Route::post('/lockscreen/fingerprint/verify',   [LockScreenController::class, 'fingerprintVerify'])->name('lockscreen.biometric.verify');
});

// ── Payment Webhooks (no auth, no CSRF) ──────────────────────────────────────
Route::withoutMiddleware(['web'])->group(function () {
    Route::post('/webhook/paystack',    [WalletFundingController::class, 'paystackWebhook'])->name('webhook.paystack');
    Route::post('/webhook/flutterwave', [WalletFundingController::class, 'flutterwaveWebhook'])->name('webhook.flutterwave');
    Route::post('/webhook/monnify',     [WalletFundingController::class, 'monnifyWebhook'])->name('webhook.monnify');
});

// ── Admin Panel ───────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin', 'ensure.not_locked'])->group(function () {
    Route::get('/',                    [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('/users',                       [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create',                [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users',                      [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}',                [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit',           [AdminUserController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}',              [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}',             [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/adjust-wallet', [AdminUserController::class, 'adjustWallet'])->name('users.adjust-wallet');
    Route::post('/users/{user}/impersonate',   [AdminUserController::class, 'impersonate'])->name('users.impersonate');

    // Transactions
    Route::get('/transactions',        [AdminTransactionController::class, 'index'])->name('transactions.index');

    // Funding Requests
    Route::get('/funding',             [AdminFundingController::class, 'index'])->name('funding.index');
    Route::post('/funding/{id}/approve', [AdminFundingController::class, 'approve'])->name('funding.approve');
    Route::post('/funding/{id}/reject',  [AdminFundingController::class, 'reject'])->name('funding.reject');

    // Airtime to Cash Requests
    Route::get('/airtime-to-cash',             [\App\Http\Controllers\Admin\AdminAirtimeToCashController::class, 'index'])->name('airtime-to-cash.index');
    Route::post('/airtime-to-cash/{id}/approve', [\App\Http\Controllers\Admin\AdminAirtimeToCashController::class, 'approve'])->name('airtime-to-cash.approve');
    Route::post('/airtime-to-cash/{id}/reject',  [\App\Http\Controllers\Admin\AdminAirtimeToCashController::class, 'reject'])->name('airtime-to-cash.reject');

    // API Logs
    Route::get('/api-logs',            [AdminApiLogController::class, 'index'])->name('api-logs.index');

    // Settings - redirect legacy index to general
    Route::get('/settings',                        fn() => redirect()->route('admin.settings.general'))->name('settings.index');

    // General Settings
    Route::get('/settings/general',                [AdminSettingsController::class, 'general'])->name('settings.general');
    Route::post('/settings/general',               [AdminSettingsController::class, 'updateGeneral'])->name('settings.general.update');

    // Email Settings
    Route::get('/settings/email',                  [AdminSettingsController::class, 'email'])->name('settings.email');
    Route::post('/settings/email',                 [AdminSettingsController::class, 'updateEmail'])->name('settings.email.update');
    Route::post('/settings/email/test',            [AdminSettingsController::class, 'sendTestEmail'])->name('settings.email.test');

    // API Keys Settings
    Route::get('/settings/api-keys',               [AdminSettingsController::class, 'apiKeys'])->name('settings.api-keys');
    Route::post('/settings/api-keys',              [AdminSettingsController::class, 'updateApiKeys'])->name('settings.api-keys.update');

    // API Settings
    Route::get('/settings/api',                    [AdminSettingsController::class, 'api'])->name('settings.api');
    Route::post('/settings/api',                   [AdminSettingsController::class, 'updateApi'])->name('settings.api.update');

    // Account Settings (bank accounts CRUD)
    Route::get('/settings/accounts',               [AdminSettingsController::class, 'accounts'])->name('settings.accounts');
    Route::post('/settings/accounts',              [AdminSettingsController::class, 'storeAccount'])->name('settings.accounts.store');
    Route::patch('/settings/accounts/{id}',        [AdminSettingsController::class, 'updateAccount'])->name('settings.accounts.update');
    Route::delete('/settings/accounts/{id}',       [AdminSettingsController::class, 'destroyAccount'])->name('settings.accounts.destroy');

    // Cable Plan Settings
    Route::get('/settings/cable-plans',            [AdminSettingsController::class, 'cablePlans'])->name('settings.cable-plans');
    Route::post('/settings/cable-plans',           [AdminSettingsController::class, 'storeCablePlan'])->name('settings.cable-plans.store');
    Route::patch('/settings/cable-plans/{id}',     [AdminSettingsController::class, 'updateCablePlan'])->name('settings.cable-plans.update');
    Route::delete('/settings/cable-plans/{id}',    [AdminSettingsController::class, 'destroyCablePlan'])->name('settings.cable-plans.destroy');

    // Data Plan Settings
    Route::get('/settings/data-plans/{network?}',  [AdminSettingsController::class, 'dataPlans'])->name('settings.data-plans');
    Route::post('/settings/data-plans',            [AdminSettingsController::class, 'storeDataPlan'])->name('settings.data-plans.store');
    Route::patch('/settings/data-plans/{id}',      [AdminSettingsController::class, 'updateDataPlan'])->name('settings.data-plans.update');
    Route::delete('/settings/data-plans/{id}',     [AdminSettingsController::class, 'destroyDataPlan'])->name('settings.data-plans.destroy');

    // Coupons
    Route::get('/coupons',             [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::post('/coupons',            [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::patch('/coupons/{coupon}',  [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');

    // Server Monitoring
    Route::get('/monitoring',          [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'index'])->name('monitoring');
    Route::get('/monitoring/data',     [\App\Http\Controllers\Admin\AdminMonitoringController::class, 'data'])->name('monitoring.data');
});

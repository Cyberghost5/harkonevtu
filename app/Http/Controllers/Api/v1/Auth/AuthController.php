<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailOtpMail;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TermiiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Standardized JSON Response Helper.
     * Guarantees 'status' is strictly a boolean (true/false) across all responses.
     */
    protected function jsonResponse(bool $status, string $message, $data = null, int $code = 200, $errors = null): JsonResponse
    {
        $response = [
            'status'  => $status,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Register a new user account via API.
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'         => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $referrer = null;
        if ($request->filled('referral_code')) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
        }

        // Generate unique username
        $baseUsername = Str::slug(explode(' ', $request->name)[0]);
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter++;
        }

        // Generate unique referral code
        do {
            $refCode = strtoupper(Str::random(8));
        } while (User::where('referral_code', $refCode)->exists());

        $user = User::create([
            'name'          => $request->name,
            'username'      => $username,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'password'      => Hash::make($request->password),
            'user_type'     => 'user',
            'is_admin'      => false,
            'is_active'     => true,
            'referral_code' => $refCode,
            'referred_by'   => $referrer ? $referrer->id : null,
        ]);

        // Create Wallet
        Wallet::create([
            'user_id'      => $user->id,
            'balance'      => 0.00,
            'total_funded' => 0.00,
            'total_spent'  => 0.00,
        ]);

        $emailVerificationRequired = AppSetting::get('email_verification', '1') === '1';

        if ($emailVerificationRequired) {
            $emailOtp = (string) rand(100000, 999999);
            Cache::put('api_email_otp_' . $user->id, $emailOtp, now()->addMinutes(15));
            
            try {
                Mail::to($user->email)->send(new VerifyEmailOtpMail($user, $emailOtp));
            } catch (\Exception $e) {
                Log::error('[API Register Email Error] ' . $e->getMessage());
            }

            Log::info('[API Register OTP] User ID: ' . $user->id . ' | Email: ' . $user->email . ' | OTP: ' . $emailOtp);

            return $this->jsonResponse(true, 'Registration successful. A 6-digit verification code has been sent to your email address.', [
                'user_id'                     => $user->id,
                'email'                       => $user->email,
                'requires_email_verification' => true,
                'user'                        => $user->load('wallet'),
            ], 201);
        } else {
            $user->forceFill(['email_verified_at' => now()])->save();
            $token = $user->createToken('mobile-app')->plainTextToken;

            return $this->jsonResponse(true, 'Registration successful.', [
                'token'                       => $token,
                'requires_email_verification' => false,
                'user'                        => $user->load('wallet'),
            ], 201);
        }
    }

    /**
     * Unified OTP Verification logic used by both verifyOtp and verifyEmailOtp.
     */
    protected function processOtpVerification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'email'   => ['nullable', 'string'],
            'phone'   => ['nullable', 'string'],
            'login'   => ['nullable', 'string'],
            'otp'     => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $submittedOtp = trim((string) $request->input('otp'));

        // Locate User
        $user = null;
        if ($request->filled('user_id')) {
            $user = User::find($request->user_id);
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->filled('phone')) {
            $user = User::where('phone', $request->phone)->first();
        } elseif ($request->filled('login')) {
            $loginVal = $request->input('login');
            $user = User::where('email', $loginVal)
                ->orWhere('phone', $loginVal)
                ->orWhere('username', $loginVal)
                ->first();
        }

        if (!$user) {
            return $this->jsonResponse(false, 'User account not found. Please provide a valid user_id, email, or phone.', null, 404);
        }

        // Check across all possible cache keys for this user
        $emailOtpKey    = 'api_email_otp_' . $user->id;
        $loginOtpKey    = 'api_login_otp_' . $user->id;
        $phoneOtpKey    = 'phone_otp_' . $user->id;
        $webLoginOtpKey = 'login_otp_' . $user->id;

        $cachedEmailOtp    = Cache::get($emailOtpKey);
        $cachedLoginOtp    = Cache::get($loginOtpKey);
        $cachedPhoneOtp    = Cache::get($phoneOtpKey);
        $cachedWebLoginOtp = Cache::get($webLoginOtpKey);

        $matched = false;

        if ($cachedEmailOtp && trim((string)$cachedEmailOtp) === $submittedOtp) {
            $matched = true;
            Cache::forget($emailOtpKey);
        }

        if ($cachedLoginOtp && trim((string)$cachedLoginOtp) === $submittedOtp) {
            $matched = true;
            Cache::forget($loginOtpKey);
        }

        if ($cachedPhoneOtp && trim((string)$cachedPhoneOtp) === $submittedOtp) {
            $matched = true;
            Cache::forget($phoneOtpKey);
            $user->phone_verified_at = now();
        }

        if ($cachedWebLoginOtp && trim((string)$cachedWebLoginOtp) === $submittedOtp) {
            $matched = true;
            Cache::forget($webLoginOtpKey);
        }

        if (!$matched) {
            Log::warning("[API OTP Verify Failed] User ID: {$user->id} | Input OTP: {$submittedOtp} | CachedEmail: {$cachedEmailOtp} | CachedLogin: {$cachedLoginOtp} | CachedPhone: {$cachedPhoneOtp}");
            return $this->jsonResponse(false, 'Invalid or expired verification code.', null, 400);
        }

        // Mark email as verified if unverified
        if (!$user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        // Issue fresh Sanctum access token
        $token = $user->createToken('mobile-app')->plainTextToken;

        Log::info("[API OTP Verified Success] User ID: {$user->id} ({$user->email})");

        return $this->jsonResponse(true, 'OTP verified successfully.', [
            'token' => $token,
            'user'  => $user->load('wallet'),
        ]);
    }

    /**
     * Verify 6-digit Email OTP via API.
     * POST /api/v1/auth/verify-email-otp
     */
    public function verifyEmailOtp(Request $request): JsonResponse
    {
        return $this->processOtpVerification($request);
    }

    /**
     * Resend 6-digit Email OTP via API.
     * POST /api/v1/auth/resend-email-otp
     */
    public function resendEmailOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'email'   => ['nullable', 'email', 'exists:users,email'],
            'phone'   => ['nullable', 'string', 'exists:users,phone'],
            'login'   => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $user = null;
        if ($request->filled('user_id')) {
            $user = User::find($request->user_id);
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->filled('phone')) {
            $user = User::where('phone', $request->phone)->first();
        } elseif ($request->filled('login')) {
            $loginVal = $request->input('login');
            $user = User::where('email', $loginVal)
                ->orWhere('phone', $loginVal)
                ->orWhere('username', $loginVal)
                ->first();
        }

        if (!$user) {
            return $this->jsonResponse(false, 'User account not found.', null, 404);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->jsonResponse(false, 'Your email address is already verified.', null, 400);
        }

        $emailOtp = (string) rand(100000, 999999);
        Cache::put('api_email_otp_' . $user->id, $emailOtp, now()->addMinutes(15));

        try {
            Mail::to($user->email)->send(new VerifyEmailOtpMail($user, $emailOtp));
        } catch (\Exception $e) {
            Log::error('[API Resend Email Error] ' . $e->getMessage());
        }

        Log::info('[API Resend OTP] User ID: ' . $user->id . ' | Email: ' . $user->email . ' | OTP: ' . $emailOtp);

        return $this->jsonResponse(true, 'A new 6-digit verification code has been sent to your email address.');
    }

    /**
     * User Login via API.
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $login = $request->input('login');
        $user = User::where('email', $login)
            ->orWhere('phone', $login)
            ->orWhere('username', $login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->jsonResponse(false, 'Invalid login credentials.', null, 401);
        }

        if (!$user->is_active) {
            return $this->jsonResponse(false, 'Your account is deactivated. Please contact support.', null, 403);
        }

        // 1. Enforce Email Verification if globally enabled
        $emailVerificationRequired = AppSetting::get('email_verification', '1') === '1';
        if ($emailVerificationRequired && !$user->hasVerifiedEmail()) {
            $emailOtp = Cache::get('api_email_otp_' . $user->id);
            if (!$emailOtp) {
                $emailOtp = (string) rand(100000, 999999);
                Cache::put('api_email_otp_' . $user->id, $emailOtp, now()->addMinutes(15));
                try {
                    Mail::to($user->email)->send(new VerifyEmailOtpMail($user, $emailOtp));
                } catch (\Exception $e) {
                    Log::error('[API Login Mail Error] ' . $e->getMessage());
                }
                Log::info('[API Login Unverified OTP] User ID: ' . $user->id . ' | Email: ' . $user->email . ' | OTP: ' . $emailOtp);
            }

            return $this->jsonResponse(false, 'Email verification required. Please verify your email address to continue.', [
                'requires_email_verification' => true,
                'user_id'                     => $user->id,
                'email'                       => $user->email,
            ], 403);
        }

        // 2. Check if SMS/Login OTP verification is globally required
        $otpRequired = AppSetting::get('otp_verification', '0') === '1';

        if ($otpRequired) {
            $otp = (string) rand(100000, 999999);
            cache()->put('api_login_otp_' . $user->id, $otp, now()->addMinutes(10));
            Log::info('[API Login SMS OTP] User ID: ' . $user->id . ' | Phone: ' . $user->phone . ' | OTP: ' . $otp);

            // Send OTP via SMS
            $message = "Your " . AppSetting::get('site_name', 'PayPulse') . " login verification code is: " . $otp;
            TermiiService::sendSms($user->phone, $message);

            return $this->jsonResponse(true, 'OTP verification required.', [
                'requires_otp' => true,
                'user_id'      => $user->id,
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->jsonResponse(true, 'Login successful.', [
            'requires_otp' => false,
            'token'        => $token,
            'user'         => $user->load('wallet'),
        ]);
    }

    /**
     * Verify Login OTP.
     * POST /api/v1/auth/verify-otp
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        return $this->processOtpVerification($request);
    }

    /**
     * Resend Login OTP.
     * POST /api/v1/auth/resend-otp
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'email'   => ['nullable', 'email', 'exists:users,email'],
            'phone'   => ['nullable', 'string', 'exists:users,phone'],
            'login'   => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $user = null;
        if ($request->filled('user_id')) {
            $user = User::find($request->user_id);
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->filled('phone')) {
            $user = User::where('phone', $request->phone)->first();
        } elseif ($request->filled('login')) {
            $loginVal = $request->input('login');
            $user = User::where('email', $loginVal)
                ->orWhere('phone', $loginVal)
                ->orWhere('username', $loginVal)
                ->first();
        }

        if (!$user) {
            return $this->jsonResponse(false, 'User account not found.', null, 404);
        }

        $otp = (string) rand(100000, 999999);
        cache()->put('api_login_otp_' . $user->id, $otp, now()->addMinutes(10));
        Log::info('[API Resend SMS OTP] User ID: ' . $user->id . ' | Phone: ' . $user->phone . ' | OTP: ' . $otp);

        $message = "Your " . AppSetting::get('site_name', 'PayPulse') . " login verification code is: " . $otp;
        TermiiService::sendSms($user->phone, $message);

        return $this->jsonResponse(true, 'A new OTP code has been sent to your phone number.');
    }

    /**
     * Request Forgot Password link.
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->jsonResponse(true, 'Password reset link sent to your email.');
        }

        return $this->jsonResponse(false, 'Unable to send password reset link.', null, 400);
    }

    /**
     * Reset Password.
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
        });

        if ($status === Password::PASSWORD_RESET) {
            return $this->jsonResponse(true, 'Password reset successfully.');
        }

        return $this->jsonResponse(false, 'Failed to reset password. Invalid or expired token.', null, 400);
    }

    /**
     * Get Authenticated User Profile & Wallet Details.
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('wallet');

        return $this->jsonResponse(true, 'User profile fetched successfully.', [
            'user'   => $user,
            'wallet' => $user->wallet,
        ]);
    }

    /**
     * Revoke Current Access Token (Logout).
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->jsonResponse(true, 'Logged out successfully.');
    }
}

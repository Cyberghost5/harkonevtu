<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TermiiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->jsonResponse(true, 'Registration successful.', [
            'token' => $token,
            'user'  => $user->load('wallet'),
        ], 201);
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

        // Check if Login OTP verification is globally required
        $otpRequired = AppSetting::get('otp_verification', '0') === '1';

        if ($otpRequired) {
            $otp = (string) rand(100000, 999999);
            cache()->put('api_login_otp_' . $user->id, $otp, now()->addMinutes(10));

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
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'otp'     => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $cachedOtp = cache()->get('api_login_otp_' . $request->user_id);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return $this->jsonResponse(false, 'Invalid or expired OTP code.', null, 400);
        }

        cache()->forget('api_login_otp_' . $request->user_id);

        $user = User::findOrFail($request->user_id);
        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->jsonResponse(true, 'OTP verified successfully.', [
            'token' => $token,
            'user'  => $user->load('wallet'),
        ]);
    }

    /**
     * Resend Login OTP.
     * POST /api/v1/auth/resend-otp
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $user = User::findOrFail($request->user_id);
        $otp = (string) rand(100000, 999999);
        cache()->put('api_login_otp_' . $user->id, $otp, now()->addMinutes(10));

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

<?php

namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Standardized JSON Response Helper.
     * Enforces boolean status (true/false) across all endpoints.
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
     * Get Authenticated User Profile & Balance.
     * GET /api/v1/user/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('wallet');
        $virtualAccounts = VirtualAccount::where('user_id', $user->id)
            ->forEnabledGateways()
            ->get();

        return $this->jsonResponse(true, 'User profile retrieved successfully.', [
            'user'             => $user,
            'wallet'           => $user->wallet,
            'virtual_accounts' => $virtualAccounts,
        ]);
    }

    /**
     * Update Contact Information & Profile Avatar Photo.
     * PUT/POST /api/v1/user/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'   => ['sometimes', 'required', 'string', 'max:255'],
            'email'  => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'  => ['sometimes', 'required', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $data = [];
        if ($request->has('name'))  $data['name']  = $request->name;
        if ($request->has('email')) $data['email'] = $request->email;
        if ($request->has('phone')) $data['phone'] = $request->phone;

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $storedPath = $file->store('avatars', 'public');
            $data['avatar'] = $storedPath;
        }

        if (!empty($data)) {
            $user->update($data);
        }

        return $this->jsonResponse(true, 'Profile updated successfully.', [
            'user' => $user->fresh('wallet'),
        ]);
    }

    /**
     * Change Login Password.
     * PUT /api/v1/user/password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->jsonResponse(false, 'Your current password is incorrect.', null, 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->jsonResponse(true, 'Password updated successfully.');
    }

    /**
     * Set or Update 4-digit Transaction PIN.
     * PUT /api/v1/user/pin
     */
    public function updatePin(Request $request): JsonResponse
    {
        $user = $request->user();

        $rules = [
            'pin' => ['required', 'numeric', 'digits:4', 'confirmed'],
        ];

        if ($user->transaction_pin) {
            $rules['current_pin'] = ['required', 'numeric', 'digits:4'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        if ($user->transaction_pin && !Hash::check($request->current_pin, $user->transaction_pin)) {
            return $this->jsonResponse(false, 'Your current transaction PIN is incorrect.', null, 400);
        }

        $user->update([
            'transaction_pin' => Hash::make($request->pin),
        ]);

        return $this->jsonResponse(true, 'Transaction PIN updated successfully.');
    }

    /**
     * Verify 4-digit Transaction PIN.
     * POST /api/v1/user/pin/verify
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'pin' => ['required', 'numeric', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        if (!$user->transaction_pin) {
            return $this->jsonResponse(false, 'Transaction PIN has not been set yet.', null, 400);
        }

        if (!Hash::check($request->pin, $user->transaction_pin)) {
            return $this->jsonResponse(false, 'Invalid transaction PIN.', null, 400);
        }

        return $this->jsonResponse(true, 'Transaction PIN verified successfully.');
    }

    /**
     * Request PIN Reset Email.
     * POST /api/v1/user/pin/reset-request
     */
    public function requestPinReset(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = (string) rand(100000, 999999);
        cache()->put('api_pin_reset_' . $user->id, $token, now()->addMinutes(15));

        // Send Email code / link
        $user->notify(new \App\Notifications\ResetPinNotification($token));

        return $this->jsonResponse(true, 'PIN reset instructions sent to your email address.');
    }

    /**
     * Update Settlement Bank Details.
     * PUT /api/v1/user/bank
     */
    public function updateBank(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'bank_name'           => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'numeric', 'digits:10'],
            'bank_account_name'   => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $user->update([
            'bank_name'           => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'bank_account_name'   => $request->bank_account_name,
        ]);

        return $this->jsonResponse(true, 'Bank details updated successfully.', [
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Upgrade Account to Agent Tier.
     * POST /api/v1/user/upgrade-agent
     */
    public function upgradeAgent(Request $request): JsonResponse
    {
        $user = $request->user()->load('wallet');

        if ($user->user_type === 'agent') {
            return $this->jsonResponse(false, 'Your account is already upgraded to Agent tier.', [
                'user' => $user,
            ], 400);
        }

        // 1. Explicit confirmation guard
        if (!$request->boolean('confirm_upgrade')) {
            return $this->jsonResponse(false, 'Explicit confirmation is required to upgrade to Agent tier.', null, 422);
        }

        // 2. Mandatory Transaction PIN verification
        $providedPin = $request->pin ?? $request->transaction_pin;
        if (!$providedPin) {
            return $this->jsonResponse(false, 'Transaction PIN is required to authorize account upgrade.', null, 422, [
                'pin' => ['Transaction PIN is required.']
            ]);
        }

        if (!$user->verifyPin($providedPin)) {
            return $this->jsonResponse(false, 'Incorrect transaction PIN. Please try again.', null, 422, [
                'pin' => ['Incorrect transaction PIN.']
            ]);
        }

        $wallet = $user->wallet;
        if (!$wallet) {
            return $this->jsonResponse(false, 'Wallet not found for this account.', null, 404);
        }

        $upgradeFee = (float) AppSetting::get('agent_upgrade_fee', 2500);

        if ($upgradeFee > 0) {
            if (!$wallet->hasSufficientBalance($upgradeFee)) {
                return $this->jsonResponse(
                    false,
                    'Insufficient wallet balance. Upgrade fee is ₦' . number_format($upgradeFee, 2) . ', your balance is ₦' . number_format($wallet->balance, 2) . '.',
                    null,
                    400
                );
            }

            // Deduct upgrade fee atomically using wallet debit helper
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($user, $upgradeFee) {
                    $w = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    if (!$w->hasSufficientBalance($upgradeFee)) {
                        throw new \Exception('Insufficient wallet balance for agent upgrade.');
                    }

                    $reference = 'UPG-' . strtoupper(Str::random(10));
                    $w->debit(
                        $upgradeFee,
                        'Account upgrade to Agent tier',
                        $reference,
                        ['service' => 'agent_upgrade', 'fee' => $upgradeFee]
                    );

                    $user->update(['user_type' => 'agent']);
                });
            } catch (\Throwable $e) {
                return $this->jsonResponse(false, 'Upgrade transaction failed: ' . $e->getMessage(), null, 500);
            }
        } else {
            $user->update(['user_type' => 'agent']);
        }

        return $this->jsonResponse(true, 'Congratulations! Your account has been upgraded to Agent tier.', [
            'user' => $user->fresh('wallet'),
        ]);
    }

    /**
     * Generate Dedicated Virtual Accounts (Monnify / Paystack DVA).
     * POST /api/v1/user/dva/generate
     */
    public function generateDva(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'bvn' => ['required', 'numeric', 'digits:11'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $activeProviders = VirtualAccount::getActiveProviders();

        if (empty($activeProviders)) {
            return $this->jsonResponse(false, 'Virtual account generation is currently disabled or no payment gateway is available.', null, 400);
        }

        $res = \App\Services\VirtualAccountService::generateDva($user, $request->bvn);

        if (empty($res['accounts'])) {
            return $this->jsonResponse(false, 'Could not generate virtual accounts. ' . (!empty($res['errors']) ? implode(' | ', $res['errors']) : 'No payment gateways are currently active.'), null, 400);
        }

        if ($user->kyc_status !== 'verified') {
            $user->update(['kyc_status' => 'verified']);
        }

        return $this->jsonResponse(true, 'Virtual bank accounts generated successfully.', [
            'accounts' => $res['accounts'],
        ]);
    }

    /**
     * Soft-delete Account.
     * DELETE /api/v1/user/account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->jsonResponse(false, 'Incorrect password confirmation.', null, 400);
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->jsonResponse(true, 'Your account has been deleted successfully.');
    }

    /**
     * Update Device Push Notification Token & Device Type.
     * POST /api/v1/user/device-token
     */
    public function updateDeviceToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_device_token' => ['required', 'string'],
            'device_type'      => ['nullable', 'string', 'in:android,ios,web'],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $user = $request->user();
        $user->fcm_device_token = $request->fcm_device_token;
        if ($request->filled('device_type')) {
            $user->device_type = strtolower($request->device_type);
        }
        $user->save();

        return $this->jsonResponse(true, 'Device push notification token updated successfully.', [
            'fcm_device_token' => $user->fcm_device_token,
            'device_type'      => $user->device_type,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

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
        $virtualAccounts = VirtualAccount::where('user_id', $user->id)->get();

        return $this->jsonResponse(true, 'User profile retrieved successfully.', [
            'user'             => $user,
            'wallet'           => $user->wallet,
            'virtual_accounts' => $virtualAccounts,
        ]);
    }

    /**
     * Update Contact Information.
     * PUT /api/v1/user/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,' . $user->id],
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse(false, 'Validation failed.', null, 422, $validator->errors());
        }

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

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
            return $this->jsonResponse(true, 'Your account is already upgraded to Agent tier.');
        }

        $upgradeFee = (float) AppSetting::get('agent_upgrade_fee', 0);

        if ($upgradeFee > 0) {
            if ($user->wallet->balance < $upgradeFee) {
                return $this->jsonResponse(false, 'Insufficient wallet balance. Upgrade fee is ₦' . number_format($upgradeFee, 2), null, 400);
            }

            // Deduct upgrade fee atomically
            \Illuminate\Support\Facades\DB::transaction(function () use ($user, $upgradeFee) {
                $wallet = $user->wallet()->lockForUpdate()->first();
                $balBefore = $wallet->balance;
                $wallet->decrement('balance', $upgradeFee);
                $wallet->increment('total_spent', $upgradeFee);

                \App\Models\WalletTransaction::create([
                    'user_id'        => $user->id,
                    'wallet_id'      => $wallet->id,
                    'type'           => 'debit',
                    'amount'         => $upgradeFee,
                    'balance_before' => $balBefore,
                    'balance_after'  => $wallet->balance,
                    'reference'      => 'UPG-' . strtoupper(Str::random(10)),
                    'description'    => 'Account upgrade to Agent tier',
                    'status'         => 'success',
                ]);

                $user->update(['user_type' => 'agent']);
            });
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

        $monnifyApiKey    = AppSetting::get('monnify_api_key');
        $monnifySecretKey = AppSetting::get('monnify_secret_key');
        $monnifyContract  = AppSetting::get('monnify_contract_code');
        $monnifyMode      = AppSetting::get('monnify_mode', 'sandbox');

        if (!$monnifyApiKey || !$monnifySecretKey || !$monnifyContract) {
            return $this->jsonResponse(false, 'Virtual account service is currently unavailable.', null, 400);
        }

        $baseUrl = $monnifyMode === 'live'
            ? 'https://api.monnify.com'
            : 'https://sandbox.monnify.com';

        // 1. Authenticate with Monnify
        $authRes = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($monnifyApiKey . ':' . $monnifySecretKey),
        ])->post($baseUrl . '/api/v1/auth/login');

        if (!$authRes->successful() || !isset($authRes->json()['responseBody']['accessToken'])) {
            return $this->jsonResponse(false, 'Could not authenticate with bank provider.', null, 400);
        }

        $accessToken = $authRes->json()['responseBody']['accessToken'];

        // 2. Create Reserved Account
        $accRef = 'DVA-' . $user->id . '-' . time();
        $dvaRes = Http::withToken($accessToken)->post($baseUrl . '/api/v2/bank-transfer/reserved-accounts', [
            'accountReference' => $accRef,
            'accountName'      => $user->name,
            'currencyCode'     => 'NGN',
            'contractCode'     => $monnifyContract,
            'customerEmail'    => $user->email,
            'customerName'     => $user->name,
            'bvn'              => $request->bvn,
            'getAllAvailableBanks' => true,
        ]);

        if (!$dvaRes->successful() || !isset($dvaRes->json()['responseBody']['accounts'])) {
            $msg = $dvaRes->json()['responseMessage'] ?? 'Failed to generate virtual accounts.';
            return $this->jsonResponse(false, $msg, null, 400);
        }

        $accountsData = $dvaRes->json()['responseBody']['accounts'];
        $createdAccounts = [];

        foreach ($accountsData as $acc) {
            $va = VirtualAccount::updateOrCreate(
                [
                    'user_id'     => $user->id,
                    'provider'    => 'monnify',
                    'bank_code'   => $acc['bankCode'] ?? null,
                ],
                [
                    'bank_name'      => $acc['bankName'],
                    'account_number' => $acc['accountNumber'],
                    'account_name'   => $acc['accountName'],
                    'reference'      => $accRef,
                ]
            );
            $createdAccounts[] = $va;
        }

        return $this->jsonResponse(true, 'Virtual bank accounts generated successfully.', [
            'accounts' => $createdAccounts,
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
}

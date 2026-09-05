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
        $virtualAccounts = VirtualAccount::where('user_id', $user->id)->get();

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
            $data['avatar'] = url('storage/' . $storedPath);
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

        $bvn     = $request->bvn;
        $results = [];
        $errors  = [];

        // ── 1. Paystack DVA (Wema Bank + Titan Bank) ──────────────────────────
        $paystackSecret = config('services.paystack.secret_key') ?: AppSetting::get('paystack_secret_key');
        if ($paystackSecret) {
            try {
                $customerCode = $this->getOrCreatePaystackCustomer($user, $bvn, $paystackSecret);

                foreach (['wema-bank', 'titan-paystack'] as $bankCode) {
                    $existing = VirtualAccount::where('user_id', $user->id)
                        ->where('provider', 'paystack')
                        ->where('bank_code', $bankCode)
                        ->first();

                    if ($existing) {
                        $results[] = $existing;
                        continue;
                    }

                    $payload = [
                        'customer'       => $customerCode,
                        'preferred_bank' => $bankCode,
                        'phone'          => $user->phone,
                    ];
                    $start = hrtime(true);
                    $resp = Http::withToken($paystackSecret)
                        ->timeout(20)
                        ->post('https://api.paystack.co/dedicated_account', $payload);
                    $duration = (int) ((hrtime(true) - $start) / 1e6);

                    ApiLog::record([
                        'user_id'     => $user->id,
                        'service'     => 'dva_generate',
                        'provider'    => 'paystack',
                        'reference'   => $bankCode,
                        'endpoint'    => 'https://api.paystack.co/dedicated_account',
                        'method'      => 'POST',
                        'payload'     => $payload,
                        'response'    => $resp->json(),
                        'http_status' => $resp->status(),
                        'duration_ms' => $duration,
                        'success'     => $resp->successful() && $resp->json('status') === true,
                    ]);

                    if ($resp->successful() && $resp->json('status') === true) {
                        $data = $resp->json('data');
                        $va   = VirtualAccount::create([
                            'user_id'        => $user->id,
                            'provider'       => 'paystack',
                            'bank_name'      => $data['bank']['name'],
                            'bank_code'      => $bankCode,
                            'account_number' => $data['account_number'],
                            'account_name'   => $data['account_name'],
                            'metadata'       => $data,
                        ]);
                        $results[] = $va;
                    } else {
                        $errors[] = 'Paystack (' . $bankCode . '): ' . ($resp->json('message') ?? 'Request failed');
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Paystack DVA error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $errors[] = 'Paystack: ' . $e->getMessage();
            }
        }

        // ── 2. Flutterwave DVA ───────────────────────────────────────────────
        $flwSecret = config('services.flutterwave.secret_key') ?: AppSetting::get('flutterwave_secret_key');
        if ($flwSecret) {
            try {
                $existing = VirtualAccount::where('user_id', $user->id)
                    ->where('provider', 'flutterwave')
                    ->first();

                if ($existing) {
                    $results[] = $existing;
                } else {
                    $payload = [
                        'email'        => $user->email,
                        'currency'     => 'NGN',
                        'is_permanent' => true,
                        'bvn'          => $bvn,
                        'tx_ref'       => 'DVA_FLW_' . $user->id . '_' . time(),
                        'narration'    => $user->name,
                    ];
                    $start = hrtime(true);
                    $resp = Http::withToken($flwSecret)
                        ->timeout(20)
                        ->post('https://api.flutterwave.com/v3/virtual-account-numbers', $payload);
                    $duration = (int) ((hrtime(true) - $start) / 1e6);

                    ApiLog::record([
                        'user_id'     => $user->id,
                        'service'     => 'dva_generate',
                        'provider'    => 'flutterwave',
                        'reference'   => $payload['tx_ref'],
                        'endpoint'    => 'https://api.flutterwave.com/v3/virtual-account-numbers',
                        'method'      => 'POST',
                        'payload'     => $payload,
                        'response'    => $resp->json(),
                        'http_status' => $resp->status(),
                        'duration_ms' => $duration,
                        'success'     => $resp->successful() && $resp->json('status') === 'success',
                    ]);

                    if ($resp->successful() && $resp->json('status') === 'success') {
                        $data = $resp->json('data');
                        $va   = VirtualAccount::create([
                            'user_id'        => $user->id,
                            'provider'       => 'flutterwave',
                            'bank_name'      => $data['bank_name'],
                            'bank_code'      => 'flutterwave_dva',
                            'account_number' => $data['account_number'],
                            'account_name'   => $data['account_name'] ?? $user->name,
                            'metadata'       => $data,
                        ]);
                        $results[] = $va;
                    } else {
                        $errors[] = 'Flutterwave: ' . ($resp->json('message') ?? 'Request failed');
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Flutterwave DVA error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $errors[] = 'Flutterwave: ' . $e->getMessage();
            }
        }

        // ── 3. Monnify DVA ───────────────────────────────────────────────────
        $monnifyApiKey = AppSetting::get('monnify_api_key');
        if ($monnifyApiKey) {
            try {
                $existing = VirtualAccount::where('user_id', $user->id)
                    ->where('provider', 'monnify')
                    ->get();

                if ($existing->isNotEmpty()) {
                    foreach ($existing as $acc) {
                        $results[] = $acc;
                    }
                } else {
                    $data = \App\Services\MonnifyService::generateReservedAccounts($user, $bvn);
                    $accountsList = [];
                    if (!empty($data['accounts']) && is_array($data['accounts'])) {
                        $accountsList = $data['accounts'];
                    } elseif (!empty($data['accountNumber'])) {
                        $accountsList = [$data];
                    }

                    foreach ($accountsList as $acc) {
                        $va = VirtualAccount::updateOrCreate(
                            [
                                'user_id'        => $user->id,
                                'provider'       => 'monnify',
                                'account_number' => $acc['accountNumber'],
                            ],
                            [
                                'bank_name'    => $acc['bankName'] ?? 'Monnify Bank',
                                'bank_code'    => $acc['bankCode'] ?? null,
                                'account_name' => $acc['accountName'] ?? $user->name,
                                'metadata'     => $acc,
                            ]
                        );
                        $results[] = $va;
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Monnify DVA error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $errors[] = 'Monnify: ' . $e->getMessage();
            }
        }

        if (empty($results)) {
            return $this->jsonResponse(false, 'Could not generate virtual accounts. ' . implode(' | ', $errors), null, 400);
        }

        return $this->jsonResponse(true, 'Virtual bank accounts generated successfully.', [
            'accounts' => $results,
        ]);
    }

    private function getOrCreatePaystackCustomer(User $user, string $bvn, string $secretKey): string
    {
        $existing = VirtualAccount::where('user_id', $user->id)
            ->where('provider', 'paystack')
            ->whereNotNull('metadata')
            ->first();

        if ($existing) {
            $code = $existing->metadata['customer']['customer_code']
                 ?? $existing->metadata['customer_code']
                 ?? null;
            if ($code) return $code;
        }

        $nameParts = explode(' ', $user->name, 2);
        $payload = [
            'email'      => $user->email,
            'first_name' => $nameParts[0],
            'last_name'  => $nameParts[1] ?? '',
            'phone'      => $user->phone ?? '',
        ];
        $start = hrtime(true);
        $resp = Http::withToken($secretKey)
            ->timeout(20)
            ->post('https://api.paystack.co/customer', $payload);
        $duration = (int) ((hrtime(true) - $start) / 1e6);

        ApiLog::record([
            'user_id'     => $user->id,
            'service'     => 'dva_customer_create',
            'provider'    => 'paystack',
            'endpoint'    => 'https://api.paystack.co/customer',
            'method'      => 'POST',
            'payload'     => $payload,
            'response'    => $resp->json(),
            'http_status' => $resp->status(),
            'duration_ms' => $duration,
            'success'     => $resp->successful() && $resp->json('status') === true,
        ]);

        if (!$resp->successful() || !$resp->json('status')) {
            throw new \RuntimeException($resp->json('message') ?? 'Failed to create Paystack customer');
        }

        return $resp->json('data.customer_code');
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

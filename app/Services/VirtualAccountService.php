<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VirtualAccountService
{
    /**
     * Generate Dedicated Virtual Accounts for a user across active gateways using their BVN.
     *
     * @param User $user
     * @param string $bvn
     * @return array{success: bool, accounts: array<VirtualAccount>, errors: array<string>}
     */
    public static function generateDva(User $user, string $bvn): array
    {
        $results = [];
        $errors  = [];

        $activeProviders = VirtualAccount::getActiveProviders();

        if (empty($activeProviders)) {
            return [
                'success'  => false,
                'accounts' => [],
                'errors'   => ['Virtual account generation is currently disabled or no payment gateway is available.'],
            ];
        }

        // ── 1. Paystack DVA (Wema Bank + Titan Bank) ──────────────────────────
        if (in_array('paystack', $activeProviders, true)) {
            $paystackSecret = config('services.paystack.secret_key') ?: AppSetting::get('paystack_secret_key');
            try {
                $customerCode = static::getOrCreatePaystackCustomer($user, $bvn, $paystackSecret);

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
            } catch (\Throwable $e) {
                Log::error('Paystack DVA error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $errors[] = 'Paystack: ' . $e->getMessage();
            }
        }

        // ── 2. Flutterwave DVA ───────────────────────────────────────────────
        if (in_array('flutterwave', $activeProviders, true)) {
            $flwSecret = config('services.flutterwave.secret_key') ?: AppSetting::get('flutterwave_secret_key');
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
            } catch (\Throwable $e) {
                Log::error('Flutterwave DVA error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $errors[] = 'Flutterwave: ' . $e->getMessage();
            }
        }

        // ── 3. Monnify DVA ───────────────────────────────────────────────────
        if (in_array('monnify', $activeProviders, true)) {
            try {
                $existing = VirtualAccount::where('user_id', $user->id)
                    ->where('provider', 'monnify')
                    ->get();

                if ($existing->isNotEmpty()) {
                    foreach ($existing as $acc) {
                        $results[] = $acc;
                    }
                } else {
                    $data = MonnifyService::generateReservedAccounts($user, $bvn);
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
            } catch (\Throwable $e) {
                Log::error('Monnify DVA error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $errors[] = 'Monnify: ' . $e->getMessage();
            }
        }

        // ── 4. PayScribe DVA (9PSB & PalmPay) ─────────────────────────────────
        if (in_array('payscribe', $activeProviders, true)) {
            try {
                $existing = VirtualAccount::where('user_id', $user->id)
                    ->where('provider', 'payscribe')
                    ->get();

                if ($existing->isNotEmpty()) {
                    foreach ($existing as $acc) {
                        $results[] = $acc;
                    }
                } else {
                    $data = PayscribePaymentService::generateVirtualAccounts($user, $bvn);
                    $accountsList = $data['accounts'] ?? [];

                    foreach ($accountsList as $acc) {
                        $va = VirtualAccount::updateOrCreate(
                            [
                                'user_id'        => $user->id,
                                'provider'       => 'payscribe',
                                'account_number' => $acc['account_number'],
                            ],
                            [
                                'bank_name'    => $acc['bank_name'] ?? 'PayScribe Bank',
                                'bank_code'    => $acc['bank_code'] ?? null,
                                'account_name' => $acc['account_name'] ?? $user->name,
                                'metadata'     => $acc,
                            ]
                        );
                        $results[] = $va;
                    }
                }
            } catch (\Throwable $e) {
                Log::error('PayScribe DVA error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $errors[] = 'PayScribe: ' . $e->getMessage();
            }
        }

        return [
            'success'  => !empty($results),
            'accounts' => $results,
            'errors'   => $errors,
        ];
    }

    /**
     * Retrieve or create a customer code on Paystack using BVN.
     */
    protected static function getOrCreatePaystackCustomer(User $user, string $bvn, string $secretKey): string
    {
        $existing = VirtualAccount::where('user_id', $user->id)
            ->where('provider', 'paystack')
            ->whereNotNull('metadata')
            ->first();

        if ($existing) {
            $code = $existing->metadata['customer']['customer_code']
                 ?? $existing->metadata['customer_code']
                 ?? null;
            if ($code) {
                return $code;
            }
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
            'reference'   => 'dva_cust_' . $user->id,
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
}

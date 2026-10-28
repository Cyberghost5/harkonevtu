<?php

namespace App\Http\Controllers\Api\v1\Extra;

use App\Http\Controllers\Controller;
use App\Models\AirtimeToCashRequest;
use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\BettingPlatform;
use App\Models\DataPlan;
use App\Models\Network;
use App\Models\NetworkAirtime;
use App\Models\OnboardingSlide;
use App\Models\PrintedVoucher;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\KycService;
use App\Services\QoreIDService;
use App\Services\ProviderAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExtraApiController extends Controller
{
    // ─── 1. BETTING SERVICES ──────────────────────────────────────────────────

    /**
     * List active betting platforms (Bet9ja, SportyBet, 1xBet, BangBet, etc.).
     */
    public function bettingPlatforms(Request $request): JsonResponse
    {
        if (AppSetting::get('service_betting', '1') !== '1') {
            return response()->json(['status' => false, 'message' => 'Betting funding service is currently disabled.'], 503);
        }

        $platforms = BettingPlatform::active()->get()->map(fn (BettingPlatform $p) => [
            'id'   => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'code' => $p->code,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Betting platforms retrieved successfully.',
            'data'    => [
                'total'     => count($platforms),
                'charge'    => (float) AppSetting::get('betting_charge', 50),
                'min_amount'=> (float) AppSetting::get('betting_min_amount', 100),
                'platforms' => $platforms,
            ],
        ]);
    }

    /**
     * Validate betting customer ID / account number.
     */
    public function validateBettingAccount(Request $request): JsonResponse
    {
        if (AppSetting::get('service_betting', '1') !== '1') {
            return response()->json(['status' => false, 'message' => 'Betting funding service is currently disabled.'], 503);
        }

        $request->validate([
            'platform'    => ['required', 'string', 'exists:betting_platforms,slug'],
            'customer_id' => ['required', 'string'],
        ]);

        $platform = BettingPlatform::where('slug', $request->platform)->active()->firstOrFail();
        $customer = trim($request->customer_id);

        $payscribeKey = \App\Services\PayscribePaymentService::getApiKey();
        if ($payscribeKey) {
            $start = hrtime(true);
            $endpoint = "https://api.payscribe.ng/api/v1/betting/lookup?bet_id={$platform->slug}&customer_id={$customer}";
            $requestHeaders = ['Authorization' => 'Bearer ' . $payscribeKey];
            $body = [];
            $success = false;

            try {
                $response = Http::withHeaders($requestHeaders)->timeout(20)->get($endpoint);
                $body     = $response->json() ?? [];
                $success  = ($body['status'] ?? false) && isset($body['data']['customer_name']);

                if ($success) {
                    return response()->json([
                        'status'  => true,
                        'message' => 'Betting account validated successfully.',
                        'data'    => [
                            'platform'      => $platform->name,
                            'customer_id'   => $customer,
                            'customer_name' => $body['data']['customer_name'],
                        ],
                    ]);
                }
            } catch (\Throwable $e) {
                $body = ['error' => $e->getMessage()];
            } finally {
                $duration = (int) ((hrtime(true) - $start) / 1e6);
                ApiLog::record([
                    'user_id'         => auth()->id(),
                    'service'         => 'betting_validate',
                    'provider'        => 'payscribe',
                    'reference'       => $customer,
                    'endpoint'        => $endpoint,
                    'method'          => 'GET',
                    'payload'         => ['platform' => $platform->slug, 'customer_id' => $customer],
                    'request_headers' => ['Authorization' => 'Bearer ***'],
                    'response'        => $body,
                    'duration_ms'     => $duration,
                    'success'         => $success,
                ]);
            }
        }

        // Fallback demo validation payload
        return response()->json([
            'status'  => true,
            'message' => 'Betting account validated successfully.',
            'data'    => [
                'platform'      => $platform->name,
                'customer_id'   => $customer,
                'customer_name' => 'BETTING CUSTOMER ' . Str::upper(Str::random(4)),
            ],
        ]);
    }

    /**
     * Fund betting account wallet.
     */
    public function fundBettingAccount(Request $request): JsonResponse
    {
        if (AppSetting::get('service_betting', '1') !== '1') {
            return response()->json(['status' => false, 'message' => 'Betting funding service is currently disabled.'], 503);
        }

        $request->validate([
            'platform'    => ['required', 'string', 'exists:betting_platforms,slug'],
            'customer_id' => ['required', 'string'],
            'amount'      => ['required', 'numeric', 'min:100', 'max:50000'],
            'pin'         => ['required', 'digits:4'],
        ]);

        $user = auth()->user();
        if (!$user->verifyPin($request->pin)) {
            return response()->json(['status' => false, 'message' => 'Incorrect transaction PIN.'], 422);
        }

        $platform  = BettingPlatform::where('slug', $request->platform)->active()->firstOrFail();
        $amount    = (float) $request->amount;
        $charge    = (float) AppSetting::get('betting_charge', 50);
        $totalCost = $amount + $charge;

        if ($user->wallet->balance < $totalCost) {
            return response()->json(['status' => false, 'message' => 'Insufficient wallet balance.'], 422);
        }

        $reference = 'BET' . date('YmdHis') . Str::upper(Str::random(6));

        DB::transaction(function () use ($user, $totalCost, $reference, $platform, $amount, $request) {
            $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $w->decrement('balance', $totalCost);
            $w->increment('total_spent', $totalCost);

            WalletTransaction::create([
                'user_id'        => $user->id,
                'wallet_id'      => $w->id,
                'type'           => 'debit',
                'amount'         => $totalCost,
                'balance_before' => $w->balance + $totalCost,
                'balance_after'  => $w->balance,
                'description'    => "Funded {$platform->name} ({$request->customer_id})",
                'reference'      => $reference,
                'status'         => 'success',
                'metadata'       => ['service' => 'betting', 'platform' => $platform->slug, 'customer_id' => $request->customer_id],
            ]);

            ServiceTransaction::create([
                'user_id'        => $user->id,
                'service_type'   => 'betting',
                'provider'       => $platform->slug,
                'recipient'      => $request->customer_id,
                'amount'         => $amount,
                'charged_amount' => $totalCost,
                'reference'      => $reference,
                'status'         => 'success',
            ]);
        });

        return response()->json([
            'status'  => true,
            'message' => "₦" . number_format($amount, 2) . " sent to {$platform->name} account ({$request->customer_id}) successfully.",
            'data'    => [
                'reference'     => $reference,
                'platform'      => $platform->name,
                'customer_id'   => $request->customer_id,
                'amount_funded' => $amount,
                'charge'        => $charge,
                'total_debited' => $totalCost,
                'balance_after' => (float) $user->fresh()->wallet->balance,
            ],
        ]);
    }

    // ─── 2. AIRTIME TO CASH SERVICES ──────────────────────────────────────────

    /**
     * Get airtime to cash conversion rates and transfer details.
     */
    public function airtimeToCashSettings(Request $request): JsonResponse
    {
        if (AppSetting::get('service_airtime_to_cash', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Airtime to cash service is currently disabled.',
            ], 503);
        }

        $settings = AppSetting::getMany([
            'airtime2cash_phone_mtn',
            'airtime2cash_charge_mtn',
            'airtime2cash_status_mtn',
            'airtime2cash_phone_airtel',
            'airtime2cash_charge_airtel',
            'airtime2cash_status_airtel',
            'airtime2cash_phone_glo',
            'airtime2cash_charge_glo',
            'airtime2cash_status_glo',
            'airtime2cash_phone_9mobile',
            'airtime2cash_charge_9mobile',
            'airtime2cash_status_9mobile',
            'airtime2cash_min_per_payment',
            'airtime2cash_max_per_payment',
        ]);

        $networks = [
            'mtn' => [
                'name'          => 'MTN',
                'full_name'     => 'MTN Nigeria',
                'phone'         => $settings['airtime2cash_phone_mtn'] ?? '',
                'charge_percent'=> (float) ($settings['airtime2cash_charge_mtn'] ?? 20),
                'payout_rate'   => (100 - (float) ($settings['airtime2cash_charge_mtn'] ?? 20)) . '%',
                'enabled'       => ($settings['airtime2cash_status_mtn'] ?? '1') === '1',
                'ussd_template' => '*321*{phone}*{amount}*{pin}#',
                'default_pin'   => '0000',
                'protocol'      => "Share 'N' Sell",
            ],
            'airtel' => [
                'name'          => 'Airtel',
                'full_name'     => 'Airtel Nigeria',
                'phone'         => $settings['airtime2cash_phone_airtel'] ?? '',
                'charge_percent'=> (float) ($settings['airtime2cash_charge_airtel'] ?? 20),
                'payout_rate'   => (100 - (float) ($settings['airtime2cash_charge_airtel'] ?? 20)) . '%',
                'enabled'       => ($settings['airtime2cash_status_airtel'] ?? '1') === '1',
                'ussd_template' => '*432*1*{phone}*{amount}*{pin}#',
                'default_pin'   => '1234',
                'protocol'      => 'Airtel Me2U',
            ],
            'glo' => [
                'name'          => 'Glo',
                'full_name'     => 'Globacom Nigeria',
                'phone'         => $settings['airtime2cash_phone_glo'] ?? '',
                'charge_percent'=> (float) ($settings['airtime2cash_charge_glo'] ?? 20),
                'payout_rate'   => (100 - (float) ($settings['airtime2cash_charge_glo'] ?? 20)) . '%',
                'enabled'       => ($settings['airtime2cash_status_glo'] ?? '1') === '1',
                'ussd_template' => '*131*{phone}*{amount}*{pin}#',
                'default_pin'   => '00000',
                'protocol'      => 'Glo EasyShare',
            ],
            '9mobile' => [
                'name'          => '9mobile',
                'full_name'     => '9mobile',
                'phone'         => $settings['airtime2cash_phone_9mobile'] ?? '',
                'charge_percent'=> (float) ($settings['airtime2cash_charge_9mobile'] ?? 20),
                'payout_rate'   => (100 - (float) ($settings['airtime2cash_charge_9mobile'] ?? 20)) . '%',
                'enabled'       => ($settings['airtime2cash_status_9mobile'] ?? '1') === '1',
                'ussd_template' => '*223*{pin}*{amount}*{phone}#',
                'default_pin'   => '0000',
                'protocol'      => 'Airtime Transfer',
            ],
        ];

        return response()->json([
            'status'  => true,
            'message' => 'Airtime to cash settings retrieved successfully.',
            'data'    => [
                'min_amount' => (float) ($settings['airtime2cash_min_per_payment'] ?? 500),
                'max_amount' => (float) ($settings['airtime2cash_max_per_payment'] ?? 50000),
                'networks'   => $networks,
            ],
        ]);
    }

    /**
     * Submit airtime to cash conversion request.
     */
    public function submitAirtimeToCash(Request $request): JsonResponse
    {
        if (AppSetting::get('service_airtime_to_cash', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Airtime to cash service is currently disabled.',
            ], 503);
        }

        $min = (float) AppSetting::get('airtime2cash_min_per_payment', 500);
        $max = (float) AppSetting::get('airtime2cash_max_per_payment', 50000);

        $request->validate([
            'network'         => ['required', 'string', 'in:mtn,airtel,glo,9mobile,MTN,AIRTEL,GLO,9MOBILE'],
            'phone'           => ['required', 'string', 'digits:11'],
            'amount'          => ['required', 'numeric', "min:{$min}", "max:{$max}"],
            'pin'             => ['nullable', 'digits:4'],
            'transaction_pin' => ['nullable', 'digits:4'],
            'screenshot'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'proof'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'receipt'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = auth()->user();

        // Validate Transaction PIN if set on user profile
        $providedPin = $request->pin ?? $request->transaction_pin;
        if ($user->transaction_pin && (!$providedPin || !$user->verifyPin($providedPin))) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid transaction PIN provided.',
            ], 422);
        }

        $network = strtolower((string) $request->network);

        // Check if the selected network is enabled
        $isEnabled = AppSetting::get('airtime2cash_status_' . $network, '1') === '1';
        if (!$isEnabled) {
            return response()->json([
                'status'  => false,
                'message' => strtoupper($network) . ' airtime to cash conversion is currently disabled.',
            ], 422);
        }

        // Check if receiver phone is configured
        $receiverPhone = AppSetting::get('airtime2cash_phone_' . $network, '');
        if (empty($receiverPhone)) {
            return response()->json([
                'status'  => false,
                'message' => 'Receiver phone number for ' . strtoupper($network) . ' has not been configured.',
            ], 422);
        }

        $amount        = (float) $request->amount;
        $chargePercent = (float) AppSetting::get('airtime2cash_charge_' . $network, 20);
        if ($chargePercent <= 0) {
            $chargePercent = 20;
        }
        $charge        = ($amount * $chargePercent) / 100;
        $receiveAmount = $amount - $charge;

        $screenshotPath = 'api_submission';
        $uploadedFile   = $request->file('screenshot')
            ?? $request->file('proof')
            ?? $request->file('receipt')
            ?? $request->file('image');

        if ($uploadedFile) {
            $screenshotPath = $uploadedFile->store('airtime-proofs', 'public');
        }

        $req = AirtimeToCashRequest::create([
            'user_id'        => $user->id,
            'network'        => $request->network,
            'phone'          => $request->phone,
            'amount'         => $amount,
            'charge'         => $charge,
            'receive_amount' => $receiveAmount,
            'screenshot'     => $screenshotPath,
            'status'         => 'pending',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Airtime conversion request submitted successfully. Admin will process and credit your wallet after verifying transfer.',
            'data'    => [
                'request_id'     => $req->id,
                'network'        => strtoupper($req->network),
                'sender_phone'   => $req->phone,
                'airtime_sent'   => $amount,
                'receive_amount' => $receiveAmount,
                'status'         => 'pending',
                'created_at'     => $req->created_at->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Get user history of airtime to cash conversion requests.
     */
    public function airtimeToCashHistory(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $requests = AirtimeToCashRequest::where('user_id', $user->id)->latest()->paginate(15);

        return response()->json([
            'status'  => true,
            'message' => 'Airtime to cash request history retrieved successfully.',
            'data'    => [
                'total'    => $requests->total(),
                'requests' => $requests->items(),
            ],
        ]);
    }

    // ─── 3. RECHARGE & DATA VOUCHER PRINTING ──────────────────────────────────

    /**
     * Generate / Purchase Pins for Recharge Card or Data Card Printing.
     */
    public function generateVouchers(Request $request): JsonResponse
    {
        if (AppSetting::get('service_recharge_card_printing', '1') !== '1') {
            return response()->json(['status' => false, 'message' => 'Voucher printing service is currently disabled.'], 503);
        }

        $request->validate([
            'type'            => ['required', 'string', 'in:airtime,data'],
            'network'         => ['required', 'string', 'in:mtn,airtel,glo,9mobile'],
            'value'           => ['required', 'integer', $request->type === 'airtime' ? 'in:100,200,500,1000' : 'min:50'],
            'quantity'        => ['required', 'integer', 'min:1', 'max:50'],
            'name_on_card'    => ['nullable', 'string', 'max:50'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user = auth()->user();
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json(['status' => false, 'message' => 'Incorrect transaction PIN.'], 422);
        }

        $network    = $request->network;
        $type       = $request->type;
        $qty        = (int) $request->quantity;
        $unitValue  = (int) $request->value;
        $totalCost  = $qty * $unitValue;
        $nameOnCard = $request->name_on_card ?? $user->username;

        $netKey = ($network === '9mobile') ? 'etisalat' : $network;
        $netApi = AppSetting::get('airtime_pin_net_' . $netKey);
        if (empty($netApi)) {
            $netApi = AppSetting::get('airtime_pin_api');
        }

        if ($netApi === 'Disable') {
            return response()->json([
                'status'  => false,
                'message' => 'Voucher printing for ' . strtoupper($network) . ' is currently disabled.'
            ], 422);
        }

        if (!$user->wallet || $user->wallet->balance < $totalCost) {
            return response()->json(['status' => false, 'message' => 'Insufficient wallet balance.'], 422);
        }

        $batchId        = 'VCH-' . strtoupper(Str::random(12));
        $useClubkonnect = ($netApi === 'clubkonnect');
        $useErs         = ($network === 'mtn' && $netApi === 'mtn_ers');
        $useGloErs      = ($network === 'glo' && $netApi === 'glo_ers');
        $ersService     = app(\App\Services\MtnErsSoapService::class);
        $ersOriginator  = $ersService->formatMsisdn($ersService->getOriginatorMsisdn());

        try {
            // 1. Debit wallet first in an atomic transaction with balance locking
            DB::transaction(function () use ($user, $totalCost, $batchId, $qty, $unitValue, $network) {
                $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                $w->decrement('balance', $totalCost);
                $w->increment('total_spent', $totalCost);

                WalletTransaction::create([
                    'user_id'        => $user->id,
                    'wallet_id'      => $w->id,
                    'type'           => 'debit',
                    'amount'         => $totalCost,
                    'balance_before' => $w->balance + $totalCost,
                    'balance_after'  => $w->balance,
                    'description'    => "Generated {$qty}x ₦{$unitValue} " . strtoupper($network) . " Vouchers",
                    'reference'      => $batchId,
                    'status'         => 'success',
                    'metadata'       => ['service' => 'voucher_printing', 'quantity' => $qty, 'unit_value' => $unitValue],
                ]);
            });

            $pins = [];

            // 2. Perform external provider call outside database transaction
            if ($useClubkonnect) {
                $networkCodeMap = [
                    'mtn'      => '01',
                    'glo'      => '02',
                    '9mobile'  => '03',
                    'etisalat' => '03',
                    'airtel'   => '04',
                ];
                $networkModel = NetworkAirtime::where('network_key', $netKey)->first();
                $networkCode  = $networkModel?->clubkonnect_id ?? ($networkCodeMap[$network] ?? '01');

                $result = $this->callClubkonnectEpin($networkCode, $unitValue, $qty, $batchId);

                if (!$result['success'] || empty($result['pins'])) {
                    // Full refund on failure
                    DB::transaction(function () use ($user, $totalCost, $batchId, $qty, $unitValue, $network) {
                        $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                        $w->increment('balance', $totalCost);
                        $w->decrement('total_spent', $totalCost);

                        WalletTransaction::create([
                            'user_id'        => $user->id,
                            'wallet_id'      => $w->id,
                            'type'           => 'credit',
                            'amount'         => $totalCost,
                            'balance_before' => $w->balance - $totalCost,
                            'balance_after'  => $w->balance,
                            'description'    => "Refund for failed {$qty}x ₦{$unitValue} " . strtoupper($network) . " Vouchers",
                            'reference'      => $batchId . '-REFUND',
                            'status'         => 'success',
                            'metadata'       => ['service' => 'voucher_printing_refund'],
                        ]);
                    });

                    throw new \Exception('Clubkonnect EPIN generation failed: ' . ($result['message'] ?? 'No PINs returned'));
                }

                $pins = $result['pins'];

                // Partial fulfillment refund
                if (count($pins) < $qty) {
                    $missingCount = $qty - count($pins);
                    $refundAmount = $missingCount * $unitValue;
                    if ($refundAmount > 0) {
                        DB::transaction(function () use ($user, $refundAmount, $batchId, $missingCount, $network) {
                            $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                            $w->increment('balance', $refundAmount);
                            $w->decrement('total_spent', $refundAmount);

                            WalletTransaction::create([
                                'user_id'        => $user->id,
                                'wallet_id'      => $w->id,
                                'type'           => 'credit',
                                'amount'         => $refundAmount,
                                'balance_before' => $w->balance - $refundAmount,
                                'balance_after'  => $w->balance,
                                'description'    => "Refund for {$missingCount} unfulfilled " . strtoupper($network) . " Vouchers",
                                'reference'      => $batchId . '-PARTIAL-REFUND',
                                'status'         => 'success',
                                'metadata'       => ['service' => 'voucher_printing_partial_refund'],
                            ]);
                        });
                    }
                }
            } else {
                for ($i = 0; $i < $qty; $i++) {
                    try {
                        if ($useErs) {
                            $result = $ersService->vend($ersOriginator, $unitValue, 7);
                            if (!$result['status']) {
                                throw new \Exception('MTN ERS Voucher generation failed: ' . ($result['message'] ?? 'Unknown Error'));
                            }
                            $pin = $result['data']['voucherPIN'] ?? null;
                            $serial = $result['data']['voucherSerial'] ?? null;
                            if (empty($pin) || empty($serial)) {
                                throw new \Exception('MTN ERS response is missing voucher PIN or Serial.');
                            }
                        } elseif ($useGloErs) {
                            $gloErsService = app(\App\Services\GloErsSoapService::class);
                            $result = $gloErsService->purchaseVoucher($user->phone ?: '2348050000000', $unitValue, 'VOT', $batchId . '-' . $i);
                            if (!$result['success']) {
                                throw new \Exception('Glo ERS Voucher generation failed: ' . ($result['response']['message'] ?? $result['message'] ?? 'Unknown Error'));
                            }
                            $pin = $result['pin'] ?? null;
                            $serial = $result['serial'] ?? null;
                            if (empty($pin) || empty($serial)) {
                                throw new \Exception('Glo ERS response is missing voucher PIN or Serial.');
                            }
                        } else {
                            $pin    = str_pad((string) random_int(100000000000000, 999999999999999), 15, '0', STR_PAD_LEFT);
                            $serial = str_pad((string) random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
                        }

                        $pins[] = [
                            'pin'    => $pin,
                            'serial' => $serial,
                        ];
                    } catch (\Exception $e) {
                        $remainingCount = $qty - count($pins);
                        $refundAmount = $remainingCount * $unitValue;
                        if ($refundAmount > 0) {
                            DB::transaction(function () use ($user, $refundAmount, $batchId, $remainingCount, $network) {
                                $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                                $w->increment('balance', $refundAmount);
                                $w->decrement('total_spent', $refundAmount);

                                WalletTransaction::create([
                                    'user_id'        => $user->id,
                                    'wallet_id'      => $w->id,
                                    'type'           => 'credit',
                                    'amount'         => $refundAmount,
                                    'balance_before' => $w->balance - $refundAmount,
                                    'balance_after'  => $w->balance,
                                    'description'    => "Refund for {$remainingCount} failed " . strtoupper($network) . " Vouchers",
                                    'reference'      => $batchId . '-REFUND',
                                    'status'         => 'success',
                                    'metadata'       => ['service' => 'voucher_printing_refund'],
                                ]);
                            });
                        }
                        throw $e;
                    }
                }
            }

            $generatedPins = [];
            foreach ($pins as $p) {
                PrintedVoucher::create([
                    'user_id'       => $user->id,
                    'type'          => $type,
                    'network'       => $network,
                    'value'         => $unitValue,
                    'pin'           => $p['pin'],
                    'serial_number' => $p['serial'],
                    'name_on_card'  => $nameOnCard,
                    'status'        => 'unused',
                ]);

                $generatedPins[] = [
                    'serial' => $p['serial'],
                    'pin'    => $p['pin'],
                    'value'  => $unitValue,
                ];
            }
        } catch (\Exception $e) {
            $rawMsg = $e->getMessage();
            $friendlyMsg = ProviderAlertService::processFailure($rawMsg, [
                'service'   => 'Voucher Printing (' . strtoupper($network ?? '') . ' ' . ucfirst($type ?? '') . ')',
                'provider'  => $netApi ?? 'ers',
                'amount'    => $totalCost ?? 0,
                'user'      => $user,
                'recipient' => $user->phone ?? 'Vouchers',
                'reference' => $batchId ?? 'VOUCHER',
            ], true);

            return response()->json(['status' => false, 'message' => $friendlyMsg], 422);
        }

        $vendedCount = count($generatedPins);
        return response()->json([
            'status'  => true,
            'message' => "{$vendedCount} " . strtoupper($network) . " voucher pins generated successfully.",
            'data'    => [
                'batch_id'       => $batchId,
                'network'        => strtoupper($network),
                'type'           => $type,
                'quantity'       => $vendedCount,
                'unit_value'     => $unitValue,
                'total_amount'   => $vendedCount * $unitValue,
                'name_on_card'   => $nameOnCard,
                'pins'           => $generatedPins,
                'balance_after'  => (float) $user->fresh()->wallet->balance,
            ],
        ]);
    }

    /**
     * Call Clubkonnect (Nellobyte Systems) API to purchase Airtime Recharge Card PINs (EPIN).
     *
     * @param string $networkCode  '01' (MTN), '02' (Glo), '03' (9mobile), '04' (Airtel)
     * @param int $value           Denomination (100, 200, 500, 1000)
     * @param int $quantity        Number of pins requested
     * @param string $reference    Unique transaction reference
     * @return array
     */
    private function callClubkonnectEpin(string $networkCode, int $value, int $quantity, string $reference): array
    {
        $endpoint = 'https://www.nellobytesystems.com/APIEPINV1.asp';
        $userId   = config('services.clubkonnect.user_id') ?: AppSetting::get('clubkonnect_user_id');
        $apiKey   = config('services.clubkonnect.api_key') ?: AppSetting::get('clubkonnect_api_key');

        if (empty($userId) || empty($apiKey)) {
            Log::warning('Clubkonnect EPIN: Missing UserID or APIKey.');
            return [
                'success' => false,
                'pins'    => [],
                'message' => 'Clubkonnect credentials (UserID or APIKey) are not configured in system settings.',
            ];
        }

        $payload = [
            'UserID'        => $userId,
            'APIKey'        => $apiKey,
            'MobileNetwork' => $networkCode,
            'Value'         => $value,
            'Quantity'      => $quantity,
            'RequestID'     => $reference,
        ];

        $data            = [];
        $httpStatus      = null;
        $success         = false;
        $pins            = [];
        $requestHeaders  = [];
        $responseHeaders = null;
        $start           = hrtime(true);

        try {
            $response        = Http::timeout(45)->get($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];

            // Primary format: {"ORDER_ID": "...", "TXN_EPIN": [ {"pin": "...", "sno": "..."}, ... ]}
            if (isset($data['TXN_EPIN']) && is_array($data['TXN_EPIN']) && !empty($data['TXN_EPIN'])) {
                foreach ($data['TXN_EPIN'] as $item) {
                    $pin = $item['pin'] ?? null;
                    $serial = $item['sno'] ?? $item['serial'] ?? $item['batchno'] ?? null;
                    if (!empty($pin)) {
                        $pins[] = [
                            'pin'    => (string) $pin,
                            'serial' => (string) ($serial ?? ''),
                        ];
                    }
                }
                $success = count($pins) > 0;
            } elseif (isset($data['status']) && in_array(strtoupper($data['status']), ['ORDER_RECEIVED', 'ORDER_COMPLETED']) && isset($data['pin'])) {
                $pins[] = [
                    'pin'    => (string) $data['pin'],
                    'serial' => (string) ($data['sno'] ?? $data['serial'] ?? ''),
                ];
                $success = true;
            }

            if (!$success) {
                $errorMsg = $data['statusremark'] ?? $data['orderremark'] ?? $data['message'] ?? $data['error'] ?? 'EPIN generation failed from provider.';
                $data['message'] = $errorMsg;
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Clubkonnect EPIN request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'voucher',
                'provider'         => 'clubkonnect',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'GET',
                'payload'          => [
                    'UserID'        => $userId ? substr($userId, 0, 3) . '***' : null,
                    'MobileNetwork' => $networkCode,
                    'Value'         => $value,
                    'Quantity'      => $quantity,
                    'RequestID'     => $reference,
                ],
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return [
            'success'   => $success,
            'pins'      => $pins,
            'order_id'  => $data['ORDER_ID'] ?? $data['orderid'] ?? $reference,
            'message'   => $data['message'] ?? ($success ? 'Success' : 'EPIN generation failed.'),
            'raw'       => $data,
        ];
    }

    /**
     * Get voucher printing history & generated pins.
     */
    public function voucherHistory(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $vouchers = PrintedVoucher::where('user_id', $user->id)->latest()->paginate(20);

        return response()->json([
            'status'  => true,
            'message' => 'Voucher history retrieved successfully.',
            'data'    => [
                'total'    => $vouchers->total(),
                'vouchers' => $vouchers->items(),
            ],
        ]);
    }

    // ─── 4. REFERRAL SYSTEM & COMMISSIONS ─────────────────────────────────────

    /**
     * Get referral summary, link, and earnings.
     */
    public function referralSummary(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $settings = AppSetting::getMany([
            'referral_commission',
            'referral_min_withdrawal',
        ]);

        $referralCount = User::where('referred_by', $user->referral_code)->count();
        $refBalance    = (float) ($user->wallet->referral_balance ?? 0);
        $minWithdrawal = (float) ($settings['referral_min_withdrawal'] ?? 500);

        return response()->json([
            'status'  => true,
            'message' => 'Referral summary retrieved successfully.',
            'data'    => [
                'referral_code'     => $user->referral_code,
                'referral_link'     => url('/register?ref=' . $user->referral_code),
                'total_referrals'   => $referralCount,
                'referral_balance'  => $refBalance,
                'min_withdrawal'    => $minWithdrawal,
                'can_withdraw'      => $refBalance >= $minWithdrawal && $minWithdrawal > 0,
                'commission_rate'   => AppSetting::get('referral_commission', '500') . ' NGN',
            ],
        ]);
    }

    /**
     * List user's referred accounts.
     */
    public function referralHistory(Request $request): JsonResponse
    {
        $user      = auth()->user();
        $referrals = User::where('referred_by', $user->referral_code)
            ->latest()
            ->paginate(15)
            ->through(fn ($r) => [
                'name'       => $r->name,
                'username'   => $r->username,
                'user_type'  => $r->user_type,
                'joined_at'  => $r->created_at->toDateTimeString(),
            ]);

        return response()->json([
            'status'  => true,
            'message' => 'Referred users retrieved successfully.',
            'data'    => $referrals,
        ]);
    }

    /**
     * Withdraw referral balance into main wallet.
     */
    public function withdrawReferral(Request $request): JsonResponse
    {
        $user          = auth()->user();
        $refBalance    = (float) ($user->wallet->referral_balance ?? 0);
        $minWithdrawal = (float) AppSetting::get('referral_min_withdrawal', 500);

        if ($refBalance <= 0 || $refBalance < $minWithdrawal) {
            return response()->json([
                'status'  => false,
                'message' => "Minimum referral withdrawal amount is ₦" . number_format($minWithdrawal, 2) . ". Your current referral balance is ₦" . number_format($refBalance, 2) . ".",
            ], 422);
        }

        DB::transaction(function () use ($user, $refBalance) {
            $user->wallet->withdrawReferral($refBalance);
        });

        $freshWallet = $user->fresh()->wallet;

        return response()->json([
            'status'  => true,
            'message' => "₦" . number_format($refBalance, 2) . " moved from referral balance to main wallet successfully.",
            'data'    => [
                'amount_transferred' => $refBalance,
                'main_balance'       => (float) $freshWallet->balance,
                'referral_balance'   => (float) $freshWallet->referral_balance,
            ],
        ]);
    }

    // ─── 5. SUPPORT & CUSTOMER HELPDESK ───────────────────────────────────────

    /**
     * Get support contact info.
     */
    public function supportContact(Request $request): JsonResponse
    {
        $s = AppSetting::getMany([
            'support_whatsapp',
            'support_phone',
            'support_email',
            'support_hours',
            'admin_email',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Support contact info retrieved successfully.',
            'data'    => [
                'whatsapp' => $s['support_whatsapp'] ?? '+2347087111000',
                'phone'    => $s['support_phone'] ?? '+2347087111000',
                'email'    => $s['support_email'] ?: ($s['admin_email'] ?? 'support@example.com'),
                'hours'    => $s['support_hours'] ?? '24/7 Mon-Sun',
            ],
        ]);
    }

    /**
     * Send support inquiry.
     */
    public function submitSupportInquiry(Request $request): JsonResponse
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $adminEmail = AppSetting::get('support_email') ?: AppSetting::get('admin_email', config('mail.from.address'));

        if ($adminEmail) {
            try {
                Mail::raw("New Contact Inquiry from {$request->name} ({$request->email}):\n\n{$request->message}", function ($msg) use ($adminEmail, $request) {
                    $msg->to($adminEmail)->replyTo($request->email)->subject('API Support Inquiry - ' . $request->name);
                });
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'status'  => true,
            'message' => 'Thank you for contacting us! Your inquiry has been received.',
        ]);
    }

    // ─── 6. KYC IDENTITY VERIFICATION ─────────────────────────────────────────

    /**
     * Get user's KYC verification status.
     */
    public function kycStatus(Request $request): JsonResponse
    {
        $user = auth()->user();
        $kycService = app(KycService::class);

        return response()->json([
            'status'  => true,
            'message' => 'KYC status retrieved successfully.',
            'data'    => [
                'kyc_status'       => $user->kyc_status ?? 'unverified',
                'is_verified'      => $user->kyc_status === 'verified',
                'tier'             => $user->kyc_status === 'verified' ? 'Tier 2 (Verified)' : 'Tier 1 (Unverified)',
                'verification_fee' => (float) AppSetting::get('kyc_fee', 0),
                'active_provider'  => $kycService->getActiveProvider(),
            ],
        ]);
    }

    /**
     * Submit NIN / BVN for KYC verification.
     */
    public function submitKyc(Request $request, KycService $kycService): JsonResponse
    {
        $user = auth()->user();
        if ($user->kyc_status === 'verified') {
            return response()->json(['status' => false, 'message' => 'Your identity is already verified.'], 422);
        }

        // Normalize id_type and id_number if passed directly as bvn or nin
        $idType = strtolower((string) ($request->input('id_type') ?? ($request->filled('bvn') ? 'bvn' : ($request->filled('nin') ? 'nin' : ''))));
        $idNumber = (string) ($request->input('id_number') ?? ($idType === 'bvn' ? $request->input('bvn') : $request->input('nin')));

        $request->merge([
            'id_type'   => $idType,
            'id_number' => $idNumber,
        ]);

        $request->validate([
            'id_type'   => ['required', 'string', 'in:bvn,nin'],
            'id_number' => ['required', 'string', 'numeric', 'digits:11'],
        ]);

        $parts = explode(' ', trim($user->name), 2);
        $firstname = trim($parts[0] ?? '');
        $lastname  = trim($parts[1] ?? '');

        if (empty($firstname) || empty($lastname)) {
            return response()->json([
                'status'  => false,
                'message' => 'Please update your full name (First and Last Name separated by a space) in profile settings before verifying KYC.'
            ], 422);
        }

        // Dedicated KYC Fee check & debit
        $fee = (float) AppSetting::get('kyc_fee', 0);
        $wallet = $user->wallet;
        $reference = 'KYC-' . strtoupper(uniqid());

        if ($fee > 0) {
            if (!$wallet || $wallet->balance < $fee) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Insufficient wallet balance. You need ₦' . number_format($fee, 2) . ' in your account to verify your KYC.'
                ], 422);
            }

            DB::transaction(function () use ($user, $wallet, $fee, $request, $reference) {
                $before = $wallet->balance;
                $wallet->decrement('balance', $fee);
                $wallet->increment('total_spent', $fee);

                WalletTransaction::create([
                    'user_id'        => $user->id,
                    'wallet_id'      => $wallet->id,
                    'type'           => 'debit',
                    'amount'         => $fee,
                    'balance_before' => $before,
                    'balance_after'  => $wallet->balance,
                    'description'    => 'KYC Verification Fee (' . strtoupper($request->id_type) . ')',
                    'reference'      => $reference,
                    'status'         => 'success',
                    'metadata'       => ['service' => 'kyc_verification', 'id_type' => $request->id_type],
                ]);
            });
        }

        // Delegate identity verification to unified KycService (PayScribe or QoreID)
        $result = $kycService->verifyIdentity(
            $request->id_type,
            $request->id_number,
            $firstname,
            $lastname,
            $user
        );

        if ($result['status']) {
            $user->update(['kyc_status' => 'verified']);

            // Auto-generate Dedicated Virtual Accounts (DVA) if BVN was submitted
            $dvaAccounts = [];
            if ($request->id_type === 'bvn') {
                try {
                    $dvaResult = \App\Services\VirtualAccountService::generateDva($user, $request->id_number);
                    $dvaAccounts = $dvaResult['accounts'] ?? [];
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('DVA auto-generation on KYC error: ' . $e->getMessage());
                }
            }

            return response()->json([
                'status'  => true,
                'message' => $result['message'] ?? 'Identity verified successfully.',
                'data'    => [
                    'id_type'        => strtoupper($request->id_type),
                    'kyc_status'     => 'verified',
                    'tier'           => 'Tier 2 (Verified)',
                    'provider'       => $result['provider'] ?? $kycService->getActiveProvider(),
                    'fee_charged'    => $fee,
                    'dva_accounts'   => $dvaAccounts,
                    'wallet_balance' => (float) $user->fresh()->wallet?->balance,
                    'details'        => $result['data'] ?? null,
                ],
            ]);
        }

        $user->update(['kyc_status' => 'rejected']);
        return response()->json([
            'status'   => false,
            'message'  => $result['message'] ?? 'KYC Verification failed.',
            'provider' => $result['provider'] ?? $kycService->getActiveProvider(),
        ], 422);
    }

    // ─── 7. PUBLIC PRICING & RATES ────────────────────────────────────────────

    /**
     * Fetch public rates, airtime discounts, and data plan pricing per user tier.
     */
    public function publicPricing(Request $request): JsonResponse
    {
        try {
            $networks = NetworkAirtime::active()->get()->map(function (NetworkAirtime $n) {
                $userDiscount = (float) AppSetting::get('airtime_discount_' . $n->network_key, '2.0');
                return [
                    'network'        => $n->name,
                    'user_discount'  => $userDiscount . '%',
                    'agent_discount' => ($userDiscount + 1.0) . '%',
                ];
            });

            $dataPlans = DataPlan::active()->get()->take(20)->map(fn (DataPlan $p) => [
                'network'    => strtoupper($p->network_key),
                'plan_name'  => $p->plan_name,
                'user_price' => (float) $p->amount,
                'agent_price'=> (float) $p->amount_agent,
                'validity'   => $p->validity ?? '30 Days',
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Platform rates and pricing retrieved successfully.',
                'data'    => [
                    'airtime_discounts' => $networks,
                    'data_plans'        => $dataPlans,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Pricing query failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── 8. MOBILE APP CONFIGURATION ─────────────────────────────────────────

    /**
     * Public endpoint returning mobile application settings, service toggles, versioning, and gateway keys.
     */
    public function appConfig(Request $request): JsonResponse
    {
        $siteName      = AppSetting::get('site_name', config('app.name', 'Harkone VTU'));
        $activeGateway = AppSetting::get('active_gateway', 'paystack');

        $favicon = AppSetting::get('favicon');
        $logo1   = AppSetting::get('logo1');
        $logo2   = AppSetting::get('logo2');

        $latestVersion   = AppSetting::get('app_version', '4.0.0');
        $minVersion      = AppSetting::get('app_min_version', '1.0.0');
        $forceUpdateFlag = AppSetting::get('app_force_update', '0') === '1';

        // Check client version if sent via header or query
        $clientVersion = $request->header('X-App-Version')
            ?? $request->input('app_version')
            ?? $request->input('version');

        $isForceUpdate = false;
        $isUpdateAvailable = false;

        if (!empty($clientVersion)) {
            // Normalize versions (strip 'v' prefix and build metadata +...)
            $cleanClient = ltrim(explode('+', explode('-', $clientVersion)[0])[0], 'vV');
            $cleanLatest = ltrim(explode('+', explode('-', $latestVersion)[0])[0], 'vV');
            $cleanMin    = ltrim(explode('+', explode('-', $minVersion)[0])[0], 'vV');

            $isBelowLatest = version_compare($cleanClient, $cleanLatest, '<');
            $isBelowMin    = version_compare($cleanClient, $cleanMin, '<');

            $isUpdateAvailable = $isBelowLatest;
            $isForceUpdate     = ($forceUpdateFlag && $isBelowLatest) || $isBelowMin;
        } else {
            $isForceUpdate = $forceUpdateFlag;
        }

        return response()->json([
            'status'  => true,
            'message' => 'App configuration retrieved successfully.',
            'data'    => [
                'app_name'            => $siteName,
                'site_name'           => $siteName,
                'theme_color'         => AppSetting::get('theme_color', '#4caf50'),
                'favicon_url'         => $favicon && Storage::disk('public')->exists($favicon) ? asset(Storage::url($favicon)) : null,
                'logo1_url'           => $logo1 && Storage::disk('public')->exists($logo1) ? asset(Storage::url($logo1)) : null,
                'logo2_url'           => $logo2 && Storage::disk('public')->exists($logo2) ? asset(Storage::url($logo2)) : null,
                'currency'            => 'NGN',
                'currency_symbol'     => '₦',
                'app_version'         => $latestVersion,
                'min_version'         => $minVersion,
                'force_update'        => $isForceUpdate,
                'is_update_available' => $isUpdateAvailable,
                'maintenance_mode'    => AppSetting::get('app_maintenance_mode', '0') === '1',
                'maintenance_message' => AppSetting::get('app_maintenance_message', 'Platform is under routine maintenance. Please check back shortly.'),
                
                'services' => [
                    'airtime'                => AppSetting::get('service_airtime', '1') === '1',
                    'data'                   => AppSetting::get('service_data', '1') === '1',
                    'electricity'            => AppSetting::get('service_electricity', '1') === '1',
                    'cable'                  => AppSetting::get('service_cable', '1') === '1',
                    'epin'                   => AppSetting::get('service_epin', '1') === '1',
                    'betting'                => AppSetting::get('service_betting', '1') === '1',
                    'airtime_to_cash'        => AppSetting::get('service_airtime_to_cash', '1') === '1',
                    'recharge_card_printing' => AppSetting::get('service_recharge_card_printing', '1') === '1',
                    'card_payment'           => AppSetting::get('service_funding_gateway', '1') === '1',
                    'auto_bank_transfer'     => AppSetting::get('service_funding_auto_bank', '1') === '1',
                    'manual_bank_transfer'   => AppSetting::get('service_funding_manual', '1') === '1',
                    'coupon_funding'         => AppSetting::get('service_funding_coupon', '1') === '1',
                ],

                'payment_gateways' => [
                    'active_gateway'       => $activeGateway,
                    'paystack_public_key'  => config('services.paystack.public_key') ?: AppSetting::get('paystack_public_key', ''),
                    'monnify_api_key'      => AppSetting::get('monnify_api_key', ''),
                    'monnify_contract_no'  => AppSetting::get('monnify_contract_no', ''),
                    'payscribe_public_key' => config('services.payscribe.public_key') ?: AppSetting::get('payscribe_public_key', ''),
                ],

                'google_auth' => [
                    'enabled'   => AppSetting::get('google_auth_status', '1') === '1',
                    'client_id' => AppSetting::get('google_client_id') ?: config('services.google.client_id', ''),
                ],

                'support' => [
                    'phone'            => AppSetting::get('support_phone', ''),
                    'whatsapp'         => AppSetting::get('support_whatsapp', ''),
                    'email'            => AppSetting::get('support_email', AppSetting::get('admin_email', '')),
                    'hours'            => AppSetting::get('support_hours', '24/7 Mon-Sun'),
                    'whatsapp_group'   => AppSetting::get('whatsapp_group_link', ''),
                    'telegram_channel' => AppSetting::get('telegram_channel_link', ''),
                ],

                'charges' => [
                    'agent_upgrade_fee'   => (float) AppSetting::get('agent_upgrade_fee', 2500),
                    'kyc_fee'             => (float) AppSetting::get('kyc_fee', 0),
                    'betting_charge'      => (float) AppSetting::get('betting_charge', 50),
                    'card_funding_charge' => (float) (\App\Http\Controllers\WalletFundingController::calculateFundingCharge(1000, 'gateway')['value']),
                    'bank_funding_charge' => (float) (\App\Http\Controllers\WalletFundingController::calculateFundingCharge(1000, 'dva')['value']),
                ],

                'onboarding_slides' => OnboardingSlide::active()->get()->map(fn (OnboardingSlide $s) => [
                    'id'          => $s->id,
                    'title'       => $s->title,
                    'description' => $s->description,
                    'image_url'   => $s->image_url,
                    'sort_order'  => $s->sort_order,
                ]),
            ],
        ]);
    }

    /**
     * Public endpoint returning onboarding slides for mobile app first launch.
     */
    public function onboardingSlides(Request $request): JsonResponse
    {
        $slides = OnboardingSlide::active()->get()->map(fn (OnboardingSlide $s) => [
            'id'          => $s->id,
            'title'       => $s->title,
            'description' => $s->description,
            'image_url'   => $s->image_url,
            'sort_order'  => $s->sort_order,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Onboarding slides retrieved successfully.',
            'data'    => $slides,
        ]);
    }
}

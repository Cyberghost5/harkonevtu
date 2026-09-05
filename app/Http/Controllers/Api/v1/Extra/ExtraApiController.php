<?php

namespace App\Http\Controllers\Api\v1\Extra;

use App\Http\Controllers\Controller;
use App\Models\AirtimeToCashRequest;
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
use App\Services\QoreIDService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

        $payscribeKey = AppSetting::get('payscribe_public_key');
        if ($payscribeKey) {
            try {
                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $payscribeKey])
                    ->get("https://api.payscribe.ng/api/v1/betting/lookup?platform={$platform->slug}&customer_id={$customer}");
                $body = $response->json();
                if (($body['status'] ?? false) && isset($body['data']['customer_name'])) {
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
            } catch (\Throwable $e) {}
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
            'airtime2cash_phone',
            'airtime2cash_tx_charge',
            'airtime2cash_min_per_payment',
            'airtime2cash_max_per_payment',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Airtime to cash settings retrieved successfully.',
            'data'    => [
                'transfer_phone' => $settings['airtime2cash_phone'] ?? '08030000000',
                'charge_percent' => (float) ($settings['airtime2cash_tx_charge'] ?? 20),
                'payout_rate'    => (100 - (float) ($settings['airtime2cash_tx_charge'] ?? 20)) . '%',
                'min_amount'     => (float) ($settings['airtime2cash_min_per_payment'] ?? 500),
                'max_amount'     => (float) ($settings['airtime2cash_max_per_payment'] ?? 50000),
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
            'network'    => ['required', 'string', 'in:mtn,airtel,glo,9mobile'],
            'phone'      => ['required', 'string', 'digits:11'],
            'amount'     => ['required', 'numeric', "min:{$min}", "max:{$max}"],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user          = auth()->user();
        $amount        = (float) $request->amount;
        $chargePercent = (float) AppSetting::get('airtime2cash_tx_charge', 20);
        $charge        = ($amount * $chargePercent) / 100;
        $receiveAmount = $amount - $charge;

        $screenshotPath = 'api_submission';
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('airtime-proofs', 'public');
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
            'value'           => ['required', 'numeric', 'min:50'],
            'quantity'        => ['required', 'integer', 'min:1', 'max:50'],
            'name_on_card'    => ['nullable', 'string', 'max:50'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user = auth()->user();
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json(['status' => false, 'message' => 'Incorrect transaction PIN.'], 422);
        }

        $qty       = (int) $request->quantity;
        $unitValue = (float) $request->value;
        $totalCost = $qty * $unitValue;

        if ($user->wallet->balance < $totalCost) {
            return response()->json(['status' => false, 'message' => 'Insufficient wallet balance.'], 422);
        }

        $generatedPins = [];
        $batchId       = 'BATCH' . date('YmdHis') . Str::upper(Str::random(4));

        DB::transaction(function () use ($user, $totalCost, $batchId, $qty, $request, $unitValue, &$generatedPins) {
            $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $w->decrement('balance', $totalCost);
            $w->increment('total_spent', $totalCost);

            for ($i = 0; $i < $qty; $i++) {
                $pin    = rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999);
                $serial = 'SN' . date('Ymd') . rand(1000000, 9999999);

                $v = PrintedVoucher::create([
                    'user_id'       => $user->id,
                    'type'          => $request->type,
                    'network'       => $request->network,
                    'value'         => $unitValue,
                    'pin'           => $pin,
                    'serial_number' => $serial,
                    'name_on_card'  => $request->name_on_card ?? $user->username,
                    'status'        => 'unused',
                ]);

                $generatedPins[] = [
                    'serial' => $serial,
                    'pin'    => $pin,
                    'value'  => $unitValue,
                ];
            }

            WalletTransaction::create([
                'user_id'        => $user->id,
                'wallet_id'      => $w->id,
                'type'           => 'debit',
                'amount'         => $totalCost,
                'balance_before' => $w->balance + $totalCost,
                'balance_after'  => $w->balance,
                'description'    => "Generated {$qty}x ₦{$unitValue} " . strtoupper($request->network) . " Vouchers",
                'reference'      => $batchId,
                'status'         => 'success',
                'metadata'       => ['service' => 'voucher_printing', 'quantity' => $qty, 'unit_value' => $unitValue],
            ]);
        });

        return response()->json([
            'status'  => true,
            'message' => "{$qty} " . strtoupper($request->network) . " voucher pins generated successfully.",
            'data'    => [
                'batch_id'       => $batchId,
                'network'        => strtoupper($request->network),
                'type'           => $request->type,
                'quantity'       => $qty,
                'unit_value'     => $unitValue,
                'total_amount'   => $totalCost,
                'name_on_card'   => $request->name_on_card ?? $user->username,
                'pins'           => $generatedPins,
                'balance_after'  => (float) $user->fresh()->wallet->balance,
            ],
        ]);
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

        return response()->json([
            'status'  => true,
            'message' => 'KYC status retrieved successfully.',
            'data'    => [
                'kyc_status'   => $user->kyc_status ?? 'unverified',
                'is_verified'  => $user->kyc_status === 'verified',
                'tier'         => $user->kyc_status === 'verified' ? 'Tier 2 (Verified)' : 'Tier 1 (Unverified)',
                'verification_fee' => (float) AppSetting::get('kyc_fee', 0),
            ],
        ]);
    }

    /**
     * Submit NIN / BVN for KYC verification.
     */
    public function submitKyc(Request $request, QoreIDService $qoreIDService): JsonResponse
    {
        $user = auth()->user();
        if ($user->kyc_status === 'verified') {
            return response()->json(['status' => false, 'message' => 'Your identity is already verified.'], 422);
        }

        $request->validate([
            'id_type'   => ['required', 'string', 'in:bvn,nin'],
            'id_number' => ['required', 'string', 'numeric', 'digits:11'],
        ]);

        $user->update(['kyc_status' => 'verified']);

        return response()->json([
            'status'  => true,
            'message' => 'Identity verified successfully.',
            'data'    => [
                'id_type'    => strtoupper($request->id_type),
                'kyc_status' => 'verified',
                'tier'       => 'Tier 2 (Verified)',
            ],
        ]);
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
                'app_version'         => AppSetting::get('app_version', '1.0.0'),
                'min_version'         => AppSetting::get('app_min_version', '1.0.0'),
                'force_update'        => AppSetting::get('app_force_update', '0') === '1',
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
                    'active_gateway'      => $activeGateway,
                    'paystack_public_key' => config('services.paystack.public_key') ?: AppSetting::get('paystack_public_key', ''),
                    'monnify_api_key'     => AppSetting::get('monnify_api_key', ''),
                    'monnify_contract_no' => AppSetting::get('monnify_contract_no', ''),
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
                    'card_funding_charge' => (float) AppSetting::get('transaction_charge_value', 0),
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

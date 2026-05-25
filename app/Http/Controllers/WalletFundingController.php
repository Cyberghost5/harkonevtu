<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\FundingRequest;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WalletFundingController extends Controller
{
    // ─── Pages ───────────────────────────────────────────────────────────────

    public function gateway(): View
    {
        $user          = auth()->user();
        $settings      = AppSetting::getMany(['active_gateway', 'transaction_charge_type', 'transaction_charge_value']);
        $activeGateway = $settings['active_gateway'] ?? 'paystack';
        $chargeType    = $settings['transaction_charge_type'] ?? 'flat';
        $chargeValue   = (float) ($settings['transaction_charge_value'] ?? 0);

        $publicKey = $activeGateway === 'flutterwave'
            ? config('services.flutterwave.public_key')
            : config('services.paystack.public_key');

        $previousTx = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->whereJsonContains('metadata->source', 'gateway')
            ->latest()
            ->paginate(10);

        return view('wallet.fund-gateway', compact(
            'user', 'activeGateway', 'chargeType', 'chargeValue', 'publicKey', 'previousTx'
        ));
    }

    public function manual(): View
    {
        $user     = auth()->user();
        $settings = AppSetting::getMany(['bank_name', 'bank_account_name', 'bank_account_number']);

        $previousRequests = FundingRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('wallet.fund-manual', compact('user', 'settings', 'previousRequests'));
    }

    public function coupon(): View
    {
        $user = auth()->user();

        $previousRedemptions = CouponRedemption::where('user_id', $user->id)
            ->with('coupon')
            ->latest()
            ->paginate(10);

        return view('wallet.fund-coupon', compact('user', 'previousRedemptions'));
    }

    public function autoBankTransfer(): View
    {
        $user     = auth()->user();
        $accounts = VirtualAccount::where('user_id', $user->id)->get();

        return view('wallet.fund-auto', compact('user', 'accounts'));
    }

    // ─── Auto Bank Transfer: Generate DVA ────────────────────────────────────

    public function generateVirtualAccount(Request $request): JsonResponse
    {
        $request->validate([
            'bvn' => ['required', 'string', 'digits:11'],
        ]);

        $user    = auth()->user();
        $bvn     = $request->bvn; // Never stored in our DB - forwarded to providers only
        $results = [];
        $errors  = [];

        // ── Paystack DVA (Wema Bank + Titan) ──────────────────────────────────
        try {
            $customerCode = $this->getOrCreatePaystackCustomer($user, $bvn);

            foreach (['wema-bank', 'titan-paystack'] as $bankCode) {
                $existing = VirtualAccount::where('user_id', $user->id)
                    ->where('provider', 'paystack')
                    ->where('bank_code', $bankCode)
                    ->first();

                if ($existing) {
                    $results[] = $existing->only(['id', 'provider', 'bank_name', 'bank_code', 'account_number', 'account_name']);
                    continue;
                }

                $resp = Http::withToken(config('services.paystack.secret_key'))
                    ->timeout(20)
                    ->post('https://api.paystack.co/dedicated_account', [
                        'customer'       => $customerCode,
                        'preferred_bank' => $bankCode,
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
                    $results[] = $va->only(['id', 'provider', 'bank_name', 'bank_code', 'account_number', 'account_name']);
                } else {
                    $errors[] = 'Paystack (' . $bankCode . '): ' . ($resp->json('message') ?? 'Request failed');
                }
            }
        } catch (\Exception $e) {
            Log::error('Paystack DVA error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $errors[] = 'Paystack: ' . $e->getMessage();
        }

        // ── Flutterwave DVA ───────────────────────────────────────────────────
        try {
            $existing = VirtualAccount::where('user_id', $user->id)
                ->where('provider', 'flutterwave')
                ->first();

            if ($existing) {
                $results[] = $existing->only(['id', 'provider', 'bank_name', 'bank_code', 'account_number', 'account_name']);
            } else {
                $resp = Http::withToken(config('services.flutterwave.secret_key'))
                    ->timeout(20)
                    ->post('https://api.flutterwave.com/v3/virtual-account-numbers', [
                        'email'        => $user->email,
                        'is_permanent' => true,
                        'bvn'          => $bvn,
                        'tx_ref'       => 'DVA_FLW_' . $user->id . '_' . time(),
                        'currency'     => 'NGN',
                        'narration'    => $user->name,
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
                    $results[] = $va->only(['id', 'provider', 'bank_name', 'bank_code', 'account_number', 'account_name']);
                } else {
                    $errors[] = 'Flutterwave: ' . ($resp->json('message') ?? 'Request failed');
                }
            }
        } catch (\Exception $e) {
            Log::error('Flutterwave DVA error', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $errors[] = 'Flutterwave: ' . $e->getMessage();
        }

        if (empty($results)) {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate virtual accounts. ' . implode(' | ', $errors),
            ], 422);
        }

        return response()->json(['success' => true, 'accounts' => $results]);
    }

    private function getOrCreatePaystackCustomer(\App\Models\User $user, string $bvn): string
    {
        // Re-use stored customer code if we already created a Paystack DVA
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

        // Create customer on Paystack
        $nameParts = explode(' ', $user->name, 2);
        $resp = Http::withToken(config('services.paystack.secret_key'))
            ->timeout(20)
            ->post('https://api.paystack.co/customer', [
                'email'      => $user->email,
                'first_name' => $nameParts[0],
                'last_name'  => $nameParts[1] ?? '',
                'phone'      => $user->phone ?? '',
            ]);

        if (!$resp->successful() || !$resp->json('status')) {
            throw new \RuntimeException($resp->json('message') ?? 'Failed to create Paystack customer');
        }

        $customerCode = $resp->json('data.customer_code');

        // Submit BVN for identity validation (best-effort, non-blocking)
        Http::withToken(config('services.paystack.secret_key'))
            ->timeout(20)
            ->post("https://api.paystack.co/customer/{$customerCode}/identification", [
                'country'    => 'NG',
                'type'       => 'bvn',
                'value'      => $bvn,
                'first_name' => $nameParts[0],
                'last_name'  => $nameParts[1] ?? '',
            ]);

        return $customerCode;
    }

    // ─── Gateway: Initiate ────────────────────────────────────────────────────

    /**
     * Create a pending transaction and return the gateway config to the JS client.
     */
    public function initiateGateway(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:500', 'max:1000000'],
        ]);

        $user        = auth()->user();
        $amount      = (float) $request->amount;
        $chargeType  = AppSetting::get('transaction_charge_type', 'flat');
        $chargeValue = (float) AppSetting::get('transaction_charge_value', 0);
        $charge      = $chargeType === 'percentage'
            ? round($amount * $chargeValue / 100, 2)
            : (float) $chargeValue;
        $total    = $amount + $charge;
        $gateway  = AppSetting::get('active_gateway', 'paystack');
        $prefix   = $gateway === 'flutterwave' ? 'FLW' : 'PST';
        $reference = $prefix . strtoupper(Str::random(10)) . time();

        // Store intent in cache - no pending tx written to DB
        Cache::put('funding_intent_' . $reference, [
            'user_id' => $user->id,
            'amount'  => $amount,
            'charge'  => $charge,
            'total'   => $total,
            'gateway' => $gateway,
        ], now()->addHour());

        return response()->json([
            'reference' => $reference,
            'amount'    => $amount,
            'charge'    => $charge,
            'total'     => $total,
            'gateway'   => $gateway,
        ]);
    }

    // ─── Gateway: Verify ─────────────────────────────────────────────────────

    public function verifyPaystack(Request $request): JsonResponse
    {
        $request->validate(['reference' => ['required', 'string', 'max:100']]);

        $intent = Cache::get('funding_intent_' . $request->reference);

        if (!$intent || $intent['user_id'] !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired payment reference.'], 404);
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->timeout(15)
            ->get("https://api.paystack.co/transaction/verify/{$request->reference}");

        if (!$response->successful()) {
            return response()->json(['success' => false, 'message' => 'Could not reach Paystack. Please contact support.'], 502);
        }

        $data = $response->json('data');

        if (($data['status'] ?? '') !== 'success') {
            return response()->json(['success' => false, 'message' => 'Payment was not completed.'], 422);
        }

        $paidNaira = ($data['amount'] ?? 0) / 100;
        if ($paidNaira < $intent['total']) {
            return response()->json(['success' => false, 'message' => 'Amount mismatch. Contact support with reference: ' . $request->reference], 422);
        }

        return $this->creditAndRespond($request->reference, $intent);
    }

    public function verifyFlutterwave(Request $request): JsonResponse
    {
        $request->validate([
            'reference'      => ['required', 'string', 'max:100'],
            'transaction_id' => ['required', 'max:100'],  // Flutterwave sends this as integer
        ]);

        $intent = Cache::get('funding_intent_' . $request->reference);

        if (!$intent || $intent['user_id'] !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired payment reference.'], 404);
        }

        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->timeout(15)
            ->get("https://api.flutterwave.com/v3/transactions/{$request->transaction_id}/verify");

        if (!$response->successful()) {
            return response()->json(['success' => false, 'message' => 'Could not reach Flutterwave. Please contact support.'], 502);
        }

        $data = $response->json('data');

        if (($data['status'] ?? '') !== 'successful') {
            return response()->json(['success' => false, 'message' => 'Payment was not completed.'], 422);
        }

        if ((float) ($data['amount'] ?? 0) < $intent['total'] || ($data['currency'] ?? '') !== 'NGN') {
            return response()->json(['success' => false, 'message' => 'Amount or currency mismatch. Contact support.'], 422);
        }

        return $this->creditAndRespond($request->reference, $intent);
    }

    /**
     * Core credit logic - shared by AJAX verify, redirect callback, and webhooks.
     * Returns the new wallet balance, or throws \RuntimeException('already_processed').
     */
    private function performCredit(string $reference, array $intent): float
    {
        if (WalletTransaction::where('reference', $reference)->exists()) {
            throw new \RuntimeException('already_processed');
        }

        DB::transaction(function () use ($reference, $intent) {
            $wallet = Wallet::where('user_id', $intent['user_id'])->lockForUpdate()->first();
            $before = (float) $wallet->balance;

            WalletTransaction::create([
                'user_id'        => $intent['user_id'],
                'wallet_id'      => $wallet->id,
                'type'           => 'credit',
                'amount'         => $intent['amount'],
                'balance_before' => $before,
                'balance_after'  => $before + (float) $intent['amount'],
                'description'    => 'Wallet funding via ' . ucfirst($intent['gateway']),
                'reference'      => $reference,
                'status'         => 'success',
                'metadata'       => [
                    'source'  => 'gateway',
                    'gateway' => $intent['gateway'],
                    'charge'  => $intent['charge'],
                    'total'   => $intent['total'],
                ],
            ]);

            $wallet->increment('balance', $intent['amount']);
            $wallet->increment('total_funded', $intent['amount']);
        });

        Cache::forget('funding_intent_' . $reference);

        return (float) Wallet::where('user_id', $intent['user_id'])->value('balance');
    }

    /**
     * AJAX response wrapper around performCredit (used by verifyPaystack/verifyFlutterwave).
     */
    private function creditAndRespond(string $reference, array $intent): JsonResponse
    {
        try {
            $newBalance = $this->performCredit($reference, $intent);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => 'Transaction already processed.'], 409);
        }

        $formatted = '₦' . number_format((float) $intent['amount'], 2);
        session()->flash('success', $formatted . ' has been added to your wallet.');

        return response()->json([
            'success'  => true,
            'message'  => $formatted . ' added to your wallet successfully.',
            'balance'  => '₦' . number_format($newBalance, 2),
            'redirect' => route('dashboard'),
        ]);
    }

    /**
     * Flutterwave redirect callback - handles bank-transfer / redirect-based payments
     * where the JS popup callback doesn't fire and Flutterwave navigates the page instead.
     *
     * Flutterwave appends: ?status=successful&tx_ref=...&transaction_id=...
     */
    public function flutterwaveRedirectCallback(Request $request): RedirectResponse
    {
        if ($request->query('status') !== 'successful') {
            return redirect()->route('wallet.fund.gateway')
                ->with('error', 'Payment was not completed.');
        }

        $txRef         = (string) $request->query('tx_ref', '');
        $transactionId = (string) $request->query('transaction_id', '');

        // Already credited by the JS verify path - go straight to dashboard
        if (WalletTransaction::where('reference', $txRef)->exists()) {
            return redirect()->route('dashboard');
        }

        $intent = Cache::get('funding_intent_' . $txRef);

        if (!$intent || $intent['user_id'] !== auth()->id()) {
            return redirect()->route('wallet.fund.gateway')
                ->with('error', 'Invalid or expired payment reference.');
        }

        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->timeout(15)
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

        if (!$response->successful()) {
            return redirect()->route('wallet.fund.gateway')
                ->with('error', 'Could not reach Flutterwave. Please contact support.');
        }

        $data = $response->json('data');

        if (($data['status'] ?? '') !== 'successful') {
            return redirect()->route('wallet.fund.gateway')
                ->with('error', 'Payment was not completed.');
        }

        if ((float) ($data['amount'] ?? 0) < $intent['total'] || ($data['currency'] ?? '') !== 'NGN') {
            return redirect()->route('wallet.fund.gateway')
                ->with('error', 'Amount or currency mismatch. Contact support.');
        }

        $formatted = '₦' . number_format((float) $intent['amount'], 2);

        try {
            $this->performCredit($txRef, $intent);
        } catch (\RuntimeException) {
            // Already processed - still redirect to dashboard
        }

        return redirect()->route('dashboard')
            ->with('success', $formatted . ' has been added to your wallet.');
    }

    // ─── Webhooks ─────────────────────────────────────────────────────────────

    /**
     * Paystack webhook - backup for browser-close edge cases.
     */
    public function paystackWebhook(Request $request): \Illuminate\Http\Response
    {
        $signature = $request->header('x-paystack-signature');
        $payload   = $request->getContent();

        if (!hash_equals(
            hash_hmac('sha512', $payload, config('services.paystack.secret_key')),
            (string) $signature
        )) {
            abort(401);
        }

        $event = $request->json('event');
        $data  = $request->json('data');

        if ($event === 'charge.success') {
            $reference = $data['reference'] ?? null;
            if (!$reference) return response('ok');

            // Already processed
            if (WalletTransaction::where('reference', $reference)->exists()) {
                return response('ok');
            }

            $channel = $data['channel'] ?? '';

            if ($channel === 'dedicated_nuban') {
                // DVA payment - find virtual account by receiving account number
                $accountNumber = $data['authorization']['receiver_bank_account_number'] ?? null;
                if ($accountNumber) {
                    $va = VirtualAccount::where('account_number', $accountNumber)->first();
                    if ($va) {
                        $amountNaira = ($data['amount'] ?? 0) / 100; // Paystack uses kobo
                        $dvaIntent   = [
                            'user_id' => $va->user_id,
                            'amount'  => $amountNaira,
                            'charge'  => 0,
                            'total'   => $amountNaira,
                            'gateway' => 'paystack_dva',
                        ];
                        try { $this->performCredit($reference, $dvaIntent); } catch (\RuntimeException) {}
                    }
                }
            } else {
                // Regular card / gateway payment
                $intent = Cache::get('funding_intent_' . $reference);
                if ($intent) {
                    try { $this->performCredit($reference, $intent); } catch (\RuntimeException) {}
                }
            }
        }

        return response('ok');
    }

    /**
     * Flutterwave webhook - backup for browser-close edge cases.
     */
    public function flutterwaveWebhook(Request $request): \Illuminate\Http\Response
    {
        $hash = $request->header('verif-hash');

        if ($hash !== config('services.flutterwave.hash')) {
            abort(401);
        }

        $data = $request->json('data');

        if (($data['status'] ?? '') === 'successful') {
            // Check for DVA payment first (has virtual_account_number field)
            $dvaAccountNumber = $data['virtual_account_number'] ?? null;

            if ($dvaAccountNumber) {
                $reference = $data['flw_ref'] ?? ($data['id'] ? 'FLW_DVA_' . $data['id'] : null);
                if ($reference && !WalletTransaction::where('reference', $reference)->exists()) {
                    $va = VirtualAccount::where('account_number', $dvaAccountNumber)->first();
                    if ($va) {
                        $dvaIntent = [
                            'user_id' => $va->user_id,
                            'amount'  => (float) ($data['amount'] ?? 0),
                            'charge'  => 0,
                            'total'   => (float) ($data['amount'] ?? 0),
                            'gateway' => 'flutterwave_dva',
                        ];
                        try { $this->performCredit($reference, $dvaIntent); } catch (\RuntimeException) {}
                    }
                }
            } else {
                // Regular card / gateway payment
                $reference = $data['tx_ref'] ?? null;
                if (!$reference) return response('ok');

                if (WalletTransaction::where('reference', $reference)->exists()) {
                    return response('ok');
                }

                $intent = Cache::get('funding_intent_' . $reference);
                if ($intent) {
                    try { $this->performCredit($reference, $intent); } catch (\RuntimeException) {}
                }
            }
        }

        return response('ok');
    }

    // ─── Manual Funding ───────────────────────────────────────────────────────

    public function submitManual(Request $request): RedirectResponse
    {
        $request->validate([
            'amount'          => ['required', 'numeric', 'min:500', 'max:1000000'],
            'bank_reference'  => ['required', 'string', 'max:100'],
            'proof_image'     => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:3072'], // 3 MB
        ]);

        $path = $request->file('proof_image')->store('funding-proofs', 'public');

        FundingRequest::create([
            'user_id'        => auth()->id(),
            'amount'         => $request->amount,
            'bank_reference' => $request->bank_reference,
            'proof_image'    => $path,
            'status'         => 'pending',
        ]);

        return back()->with('success', 'Your funding request has been submitted and is awaiting admin approval.');
    }

    // ─── Coupon Funding ───────────────────────────────────────────────────────

    public function redeemCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $user   = auth()->user();
        $code   = strtoupper(trim($request->code));
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon || !$coupon->isUsable()) {
            return response()->json(['success' => false, 'message' => 'This coupon is invalid, expired, or has been fully used.'], 422);
        }

        if ($coupon->hasBeenUsedBy($user->id)) {
            return response()->json(['success' => false, 'message' => 'You have already redeemed this coupon.'], 422);
        }

        $reference = 'CPN' . strtoupper(Str::random(12));

        DB::transaction(function () use ($user, $coupon, $reference) {
            $user->wallet->credit(
                (float) $coupon->amount,
                "Coupon redemption: {$coupon->code}",
                $reference,
                ['source' => 'coupon', 'coupon_id' => $coupon->id]
            );

            $coupon->increment('uses_count');

            CouponRedemption::create([
                'coupon_id'                    => $coupon->id,
                'user_id'                      => $user->id,
                'wallet_transaction_reference' => $reference,
            ]);
        });

        $newBalance = $user->wallet()->value('balance');

        return response()->json([
            'success' => true,
            'message' => '₦' . number_format((float) $coupon->amount, 2) . ' has been added to your wallet.',
            'balance' => '₦' . number_format((float) $newBalance, 2),
        ]);
    }
}

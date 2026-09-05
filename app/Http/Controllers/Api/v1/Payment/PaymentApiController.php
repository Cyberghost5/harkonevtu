<?php

namespace App\Http\Controllers\Api\v1\Payment;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\FundingRequest;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentApiController extends Controller
{
    /**
     * Initialize online wallet top-up via Paystack or Monnify.
     */
    public function initializePayment(Request $request): JsonResponse
    {
        if (AppSetting::get('service_funding_gateway', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Online wallet funding is currently disabled.',
            ], 503);
        }

        $request->validate([
            'amount'  => ['required', 'numeric', 'min:100', 'max:500000'],
            'gateway' => ['nullable', 'string', 'in:paystack,monnify,flutterwave'],
        ]);

        $user          = auth()->user();
        $amount        = (float) $request->amount;
        $activeGateway = $request->gateway ?? AppSetting::get('active_gateway', 'paystack');

        // Calculate transaction charge
        $chargeType  = AppSetting::get('transaction_charge_type', 'flat');
        $chargeValue = (float) AppSetting::get('transaction_charge_value', 0);
        $charge      = ($chargeType === 'percentage') ? ($amount * $chargeValue / 100) : $chargeValue;
        $totalAmount = $amount + $charge;

        $reference = 'PAY' . date('YmdHis') . Str::upper(Str::random(8));

        // Handle Paystack initialization
        if ($activeGateway === 'paystack') {
            $secretKey = config('services.paystack.secret_key') ?: AppSetting::get('paystack_secret_key');
            
            if (!$secretKey) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Paystack secret key is missing. Contact administrator.',
                ], 500);
            }

            $start = hrtime(true);
            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', [
                'amount'       => round($totalAmount * 100), // in kobo
                'email'        => $user->email,
                'reference'    => $reference,
                'callback_url' => url('/api/v1/payments/verify'),
                'metadata'     => [
                    'user_id'         => $user->id,
                    'funding_amount'  => $amount,
                    'fee'             => $charge,
                    'custom_fields'   => [
                        ['display_name' => 'Username', 'variable_name' => 'username', 'value' => $user->username],
                    ],
                ],
            ]);

            $body = $res->json() ?? [];
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            $success = ($body['status'] ?? false) === true;

            ApiLog::record([
                'user_id'     => $user->id,
                'service'     => 'wallet_funding_init',
                'provider'    => 'paystack',
                'reference'   => $reference,
                'endpoint'    => 'https://api.paystack.co/transaction/initialize',
                'method'      => 'POST',
                'payload'     => ['amount' => $totalAmount, 'reference' => $reference],
                'response'    => $body,
                'http_status' => $res->status(),
                'duration_ms' => $duration,
                'success'     => $success,
            ]);

            if (!$success) {
                return response()->json([
                    'status'  => false,
                    'message' => $body['message'] ?? 'Failed to initialize Paystack payment.',
                ], 422);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Payment initialized successfully.',
                'data'    => [
                    'gateway'          => 'paystack',
                    'reference'        => $reference,
                    'authorization_url' => $body['data']['authorization_url'] ?? null,
                    'access_code'      => $body['data']['access_code'] ?? null,
                    'amount'           => $amount,
                    'fee'              => $charge,
                    'total_amount'     => $totalAmount,
                ],
            ]);
        }

        // Default response preview for gateways
        return response()->json([
            'status'  => true,
            'message' => 'Payment reference generated successfully.',
            'data'    => [
                'gateway'      => $activeGateway,
                'reference'    => $reference,
                'amount'       => $amount,
                'fee'          => $charge,
                'total_amount' => $totalAmount,
            ],
        ]);
    }

    /**
     * Verify status of online payment transaction.
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => ['required', 'string'],
        ]);

        $reference = $request->reference;
        $user      = auth()->user();

        // Check if reference has already been credited
        $existing = WalletTransaction::where('reference', $reference)->first();
        if ($existing) {
            return response()->json([
                'status'  => true,
                'message' => 'Transaction already processed.',
                'data'    => [
                    'reference'    => $existing->reference,
                    'amount'       => (float) $existing->amount,
                    'status'       => $existing->status,
                    'credited_at'  => $existing->created_at->toDateTimeString(),
                ],
            ]);
        }

        // Verify with Paystack
        $secretKey = config('services.paystack.secret_key') ?: AppSetting::get('paystack_secret_key');
        if ($secretKey) {
            $endpoint = "https://api.paystack.co/transaction/verify/{$reference}";
            $start = hrtime(true);
            $res = Http::withHeaders(['Authorization' => 'Bearer ' . $secretKey])
                ->get($endpoint);

            $body = $res->json() ?? [];
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            $success = (($body['status'] ?? false) && (($body['data']['status'] ?? '') === 'success'));

            ApiLog::record([
                'user_id'     => $user->id,
                'service'     => 'wallet_funding_verify',
                'provider'    => 'paystack',
                'reference'   => $reference,
                'endpoint'    => $endpoint,
                'method'      => 'GET',
                'payload'     => ['reference' => $reference],
                'response'    => $body,
                'http_status' => $res->status(),
                'duration_ms' => $duration,
                'success'     => $success,
            ]);
            if (($body['status'] ?? false) && (($body['data']['status'] ?? '') === 'success')) {
                $payData = $body['data'];
                $amount  = ((float) $payData['amount']) / 100;
                $fee     = ((float) ($payData['fees'] ?? 0)) / 100;
                $credit  = max(0, $amount - $fee);

                // Credit user wallet
                DB::transaction(function () use ($user, $credit, $reference, $payData) {
                    $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    $w->credit(
                        $credit,
                        'Wallet Funding via Paystack (' . $reference . ')',
                        $reference,
                        ['source' => 'paystack', 'gateway_ref' => $payData['id'] ?? null, 'raw' => $payData]
                    );
                });

                $freshWallet = $user->fresh()->wallet;

                return response()->json([
                    'status'  => true,
                    'message' => '₦' . number_format($credit, 2) . ' credited to your wallet successfully.',
                    'data'    => [
                        'reference'     => $reference,
                        'amount'        => $credit,
                        'balance_after' => (float) ($freshWallet ? $freshWallet->balance : 0),
                        'date'          => now()->toDateTimeString(),
                    ],
                ]);
            }
        }

        return response()->json([
            'status'  => false,
            'message' => 'Payment verification failed or payment is incomplete.',
        ], 422);
    }

    /**
     * Retrieve assigned Dedicated Virtual Bank Accounts (DVA).
     */
    public function dvaAccounts(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $accounts = VirtualAccount::where('user_id', $user->id)->get()->map(fn (VirtualAccount $acc) => [
            'id'             => $acc->id,
            'bank_name'      => $acc->bank_name,
            'account_number' => $acc->account_number,
            'account_name'   => $acc->account_name,
            'provider'       => $acc->provider ?? 'monnify',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Dedicated virtual bank accounts retrieved successfully.',
            'data'    => [
                'total_accounts' => count($accounts),
                'accounts'       => $accounts,
            ],
        ]);
    }

    /**
     * Submit manual bank deposit notification for admin approval.
     */
    public function manualFundingRequest(Request $request): JsonResponse
    {
        if (AppSetting::get('service_funding_manual', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Manual bank transfer funding is currently disabled.',
            ], 503);
        }

        $request->validate([
            'bank_name'      => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:20'],
            'account_name'   => ['required', 'string', 'max:100'],
            'amount'         => ['required', 'numeric', 'min:500', 'max:1000000'],
            'reference'      => ['required', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();

        $fundingReq = FundingRequest::create([
            'user_id'        => $user->id,
            'bank_name'      => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
            'amount'         => $request->amount,
            'reference'      => $request->reference,
            'notes'          => $request->notes,
            'status'         => 'pending',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Manual funding request submitted successfully. Admin will review and credit your wallet shortly.',
            'data'    => [
                'request_id' => $fundingReq->id,
                'reference'  => $fundingReq->reference,
                'amount'     => (float) $fundingReq->amount,
                'status'     => 'pending',
                'created_at' => $fundingReq->created_at->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Redeem voucher / coupon code to credit wallet.
     */
    public function redeemCoupon(Request $request): JsonResponse
    {
        if (AppSetting::get('service_funding_coupon', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Coupon funding is currently disabled.',
            ], 503);
        }

        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $user = auth()->user();
        $code = strtoupper(trim($request->code));

        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon || !$coupon->is_active) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid or expired coupon code.',
            ], 422);
        }

        // Check if coupon has expired
        if ($coupon->expires_at && now()->greaterThan($coupon->expires_at)) {
            return response()->json([
                'status'  => false,
                'message' => 'This coupon code has expired.',
            ], 422);
        }

        // Check redemption limits
        $alreadyRedeemed = CouponRedemption::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyRedeemed) {
            return response()->json([
                'status'  => false,
                'message' => 'You have already redeemed this coupon code.',
            ], 422);
        }

        $amount    = (float) $coupon->amount;
        $reference = 'CPN' . date('YmdHis') . Str::upper(Str::random(6));

        DB::transaction(function () use ($user, $coupon, $amount, $reference) {
            $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $w->credit(
                $amount,
                'Coupon Redemption: ' . $coupon->code,
                $reference,
                ['source' => 'coupon', 'code' => $coupon->code]
            );

            CouponRedemption::create([
                'coupon_id' => $coupon->id,
                'user_id'   => $user->id,
                'amount'    => $amount,
            ]);
        });

        $freshWallet = $user->fresh()->wallet;

        return response()->json([
            'status'  => true,
            'message' => 'Coupon code redeemed successfully! ₦' . number_format($amount, 2) . ' credited to your wallet.',
            'data'    => [
                'coupon_code'   => $coupon->code,
                'amount_credited' => $amount,
                'balance_after' => (float) ($freshWallet ? $freshWallet->balance : 0),
            ],
        ]);
    }
}

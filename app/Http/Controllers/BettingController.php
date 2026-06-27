<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\BettingPlatform;
use App\Models\ServiceTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BettingController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (AppSetting::get('service_betting') !== '1') {
            return redirect()->route('dashboard')->with('error', 'Betting funding service is currently disabled.');
        }

        $user = auth()->user();
        $platforms = BettingPlatform::active()->get();

        $history = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'betting')
            ->latest()
            ->paginate(10);

        $charge = (float) AppSetting::get('betting_charge', '50');
        $minAmount = (float) AppSetting::get('betting_min_amount', '100');
        $dailyLimit = (float) AppSetting::get('betting_daily_limit', '30000');

        return view('services.betting', compact('platforms', 'history', 'charge', 'minAmount', 'dailyLimit'));
    }

    public function validateCustomer(Request $request): JsonResponse
    {
        if (AppSetting::get('service_betting') !== '1') {
            return response()->json(['error' => 'Betting funding service is currently disabled.'], 403);
        }

        $request->validate([
            'platform'    => ['required', 'string', 'exists:betting_platforms,slug'],
            'customer_id' => ['required', 'string'],
        ]);

        $payscribeKey = AppSetting::get('payscribe_secret_key');
        if (!$payscribeKey) {
            return response()->json(['error' => 'Payscribe API credentials not configured.'], 422);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $payscribeKey,
                'Accept'        => 'application/json',
            ])->get('https://api.payscribe.ng/api/v1/betting/lookup/', [
                'bet_id'      => $request->platform,
                'customer_id' => $request->customer_id,
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Unable to connect to service provider lookup.'], 400);
            }

            $res = $response->json();
            if (empty($res['status']) || $res['status'] !== true) {
                return response()->json(['error' => $res['description'] ?? 'Could not validate customer ID.'], 400);
            }

            $customerName = null;
            if (isset($res['details']['customer_name'])) {
                $customerName = $res['details']['customer_name'];
            } elseif (isset($res['message']['details']['name'])) {
                $customerName = $res['message']['details']['name'];
            } else {
                $customerName = $res['description'] ?? 'Validated';
            }

            return response()->json(['customer_name' => $customerName]);
        } catch (\Exception $e) {
            Log::error('Betting lookup error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while validating customer ID.'], 500);
        }
    }

    public function purchase(Request $request): JsonResponse
    {
        if (AppSetting::get('service_betting') !== '1') {
            return response()->json(['success' => false, 'message' => 'Betting funding service is currently disabled.'], 503);
        }

        $minAmount = (float) AppSetting::get('betting_min_amount', '100');
        $charge = (float) AppSetting::get('betting_charge', '50');
        $dailyLimit = (float) AppSetting::get('betting_daily_limit', '30000');

        $request->validate([
            'platform'        => ['required', 'string', 'exists:betting_platforms,slug'],
            'customer_id'     => ['required', 'string'],
            'customer_name'   => ['required', 'string'],
            'amount'          => ['required', 'numeric', 'min:' . $minAmount],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user = auth()->user();

        // 1. Verify PIN
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect transaction PIN. Please try again.',
                'pin_error' => true,
            ], 422);
        }

        $platform = BettingPlatform::where('slug', $request->platform)->active()->first();
        if (!$platform) {
            return response()->json(['success' => false, 'message' => 'Selected betting platform is currently disabled.'], 422);
        }

        $amount = (float) $request->amount;
        $totalCost = $amount + $charge;

        // 2. Check Sufficient Balance
        if (!$user->wallet || !$user->wallet->hasSufficientBalance($totalCost)) {
            return response()->json(['success' => false, 'message' => 'Insufficient wallet balance for this transaction.'], 422);
        }

        // 3. Verify Daily Limit
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'betting')
            ->where('status', 'success')
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');

        if ($todaySpent + $amount > $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => "Transaction declined: Daily betting limit is ₦" . number_format($dailyLimit, 2) . ". You have spent ₦" . number_format($todaySpent, 2) . " today."
            ], 422);
        }

        $payscribeKey = AppSetting::get('payscribe_secret_key');
        if (!$payscribeKey) {
            return response()->json(['success' => false, 'message' => 'API integration keys are not set by the Administrator.'], 422);
        }

        $ref = 'BET-' . strtoupper(Str::random(12));

        try {
            // Call Payscribe API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $payscribeKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->post('https://api.payscribe.ng/api/v1/betting/vend', [
                'bet_id'        => $platform->slug,
                'customer_id'   => $request->customer_id,
                'customer_name' => $request->customer_name,
                'amount'        => $amount,
                'ref'           => $ref,
            ]);

            $res = $response->json();

            // Log API response
            ApiLog::record([
                'user_id'     => $user->id,
                'service'     => 'betting',
                'provider'    => 'payscribe',
                'reference'   => $ref,
                'endpoint'    => 'https://api.payscribe.ng/api/v1/betting/vend',
                'method'      => 'POST',
                'payload'     => $request->except(['transaction_pin']),
                'response'    => $res,
                'http_status' => $response->status(),
                'success'     => ($response->successful() && !empty($res['status']) && $res['status'] === true),
            ]);

            if ($response->successful() && !empty($res['status']) && $res['status'] === true) {
                // Success - perform wallet debit inside transaction
                DB::transaction(function () use ($user, $amount, $charge, $totalCost, $platform, $request, $ref, $res) {
                    $walletTx = $user->wallet->debit(
                        $totalCost,
                        "Betting wallet funding: {$platform->name} (₦" . number_format($amount, 2) . ") to ID: {$request->customer_id}",
                        $ref,
                        ['service_type' => 'betting']
                    );

                    ServiceTransaction::create([
                        'user_id'               => $user->id,
                        'wallet_transaction_id' => $walletTx->id,
                        'service_type'          => 'betting',
                        'provider'              => 'payscribe',
                        'recipient'             => $request->customer_id,
                        'amount'                => $amount,
                        'status'                => 'success',
                        'reference'             => $ref,
                        'api_reference'         => $res['message']['details']['bet_id'] ?? ($res['message']['transaction_id'] ?? null),
                        'api_response'          => $res,
                    ]);
                });

                $newBalanceFormatted = '₦' . number_format($user->wallet->fresh()->balance, 2);

                return response()->json([
                    'success'   => true,
                    'message'   => 'Betting wallet funded successfully!',
                    'balance'   => $newBalanceFormatted,
                    'reference' => $ref,
                ]);
            } else {
                $errMsg = $res['message']['description'] ?? ($res['description'] ?? 'Provider failed to fund betting wallet.');
                return response()->json([
                    'success' => false,
                    'message' => 'Service Error: ' . $errMsg
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Betting vend error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during betting wallet funding. Please try again.'
            ], 500);
        }
    }
}

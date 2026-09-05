<?php

namespace App\Http\Controllers\Api\v1\Bills;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\CablePlan;
use App\Models\CableProvider;
use App\Models\ElectricityDisco;
use App\Models\ExamPinType;
use App\Models\ServiceTransaction;
use App\Models\Wallet;
use App\Services\EasyaccessService;
use App\Services\PayscribeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillsApiController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // 1. ELECTRICITY SERVICES
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * List all active Electricity Distribution Companies (Discos).
     */
    public function electricityDiscos(Request $request): JsonResponse
    {
        if (AppSetting::get('service_electricity', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Electricity service is temporarily unavailable.',
            ], 503);
        }

        $discos = ElectricityDisco::active()->get()->map(fn (ElectricityDisco $d) => [
            'id'                  => $d->id,
            'name'                => $d->name,
            'code'                => $d->code,
            'supported_meter_types' => ['prepaid', 'postpaid'],
            'min_amount'          => 500,
            'max_amount'          => 100000,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Electricity Discos retrieved successfully.',
            'data'    => [
                'total'  => count($discos),
                'discos' => $discos,
            ],
        ]);
    }

    /**
     * Validate meter number and return customer details.
     */
    public function validateMeter(Request $request): JsonResponse
    {
        if (AppSetting::get('service_electricity', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Electricity service is temporarily unavailable.',
            ], 503);
        }

        $request->validate([
            'disco_id'     => ['required', 'integer', 'exists:electricity_discos,id'],
            'meter_type'   => ['required', 'string', 'in:prepaid,postpaid'],
            'meter_number' => ['required', 'string', 'min:5', 'max:30'],
        ]);

        $disco       = ElectricityDisco::findOrFail($request->disco_id);
        $meterType   = strtolower($request->meter_type);
        $meterNumber = trim($request->meter_number);
        $api         = AppSetting::get('electricity_api', 'easyaccess');

        $result = match ($api) {
            'easyaccess' => $this->validateMeterEasyaccess($disco, $meterType, $meterNumber),
            'payscribe'  => $this->validateMeterPayscribe($disco, $meterType, $meterNumber),
            'vtpass'     => $this->validateMeterVtpass($disco, $meterType, $meterNumber),
            default      => $this->validateMeterEasyaccess($disco, $meterType, $meterNumber),
        };

        if (!$result['success']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'] ?? 'Meter validation failed. Please verify the meter number and disco.',
            ], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Meter validated successfully.',
            'data'    => [
                'disco_id'      => $disco->id,
                'disco_name'    => $disco->name,
                'meter_number'  => $meterNumber,
                'meter_type'    => $meterType,
                'customer_name' => $result['customer_name'] ?? 'VALIDATED CUSTOMER',
                'address'       => $result['address'] ?? null,
            ],
        ]);
    }

    /**
     * Purchase electricity token / pay bill.
     */
    public function purchaseElectricity(Request $request): JsonResponse
    {
        if (AppSetting::get('service_electricity', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Electricity service is temporarily unavailable.',
            ], 503);
        }

        $request->validate([
            'disco_id'        => ['required', 'integer', 'exists:electricity_discos,id'],
            'meter_type'      => ['required', 'string', 'in:prepaid,postpaid'],
            'meter_number'    => ['required', 'string', 'min:5', 'max:30'],
            'amount'          => ['required', 'numeric', 'min:500', 'max:100000'],
            'phone'           => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'pin'             => ['nullable', 'digits:4'],
            'transaction_pin' => ['nullable', 'digits:4'],
        ]);

        $user = auth()->user();
        $providedPin = $request->pin ?? $request->transaction_pin;

        if (!$providedPin) {
            return response()->json([
                'status'  => false,
                'message' => 'Transaction PIN is required.',
                'errors'  => ['pin' => ['Transaction PIN is required.']],
            ], 422);
        }

        if (!$user->verifyPin($providedPin)) {
            return response()->json([
                'status'  => false,
                'message' => 'Incorrect transaction PIN. Please try again.',
                'errors'  => ['pin' => ['Incorrect transaction PIN.']],
            ], 422);
        }

        $disco       = ElectricityDisco::active()->where('id', $request->disco_id)->firstOrFail();
        $meterType   = strtolower($request->meter_type);
        $meterNumber = trim($request->meter_number);
        $amount      = (float) $request->amount;
        $phone       = preg_replace('/^\+234/', '0', $request->phone);

        // Daily spending limit
        $dailyLimit = (float) AppSetting::get('electricity_daily_limit', 100000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'electricity')
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $amount) > $dailyLimit) {
            return response()->json([
                'status'  => false,
                'message' => 'Daily electricity spending limit of ₦' . number_format($dailyLimit, 0) . ' reached. Try again tomorrow.',
            ], 422);
        }

        // Wallet balance check
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($amount)) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient wallet balance. You require ₦' . number_format($amount, 2) . ' to complete this transaction.',
                'errors'  => ['balance' => ['Insufficient wallet balance.']],
            ], 422);
        }

        // Debit wallet
        $reference = 'ELE' . date('YmdHis') . Str::upper(Str::random(8));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($user, $amount, $disco, $meterNumber, $reference) {
                $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                if (!$w->hasSufficientBalance($amount)) {
                    throw new \Exception('Insufficient wallet balance. Please fund your wallet.');
                }
                return $w->debit(
                    $amount,
                    $disco->name . ' Electricity (' . $meterNumber . ')',
                    $reference,
                    ['service' => 'electricity', 'disco' => $disco->short_code ?? $disco->slug, 'meter' => $meterNumber]
                );
            });
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        // Call API Gateway
        $api = AppSetting::get('electricity_api', 'easyaccess');
        $apiRes = $this->callElectricityGateway($disco, $meterType, $meterNumber, $amount, $phone, $reference, $api);

        if (!$apiRes['success']) {
            // Refund
            try {
                DB::transaction(function () use ($user, $amount, $reference, $disco, $meterNumber) {
                    $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    $w->credit(
                        $amount,
                        'Refund: ' . $disco->name . ' Electricity failed - ' . $meterNumber,
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Electricity refund failed', ['user_id' => $user->id, 'reference' => $reference, 'error' => $e->getMessage()]);
            }

            return response()->json([
                'status'   => false,
                'refunded' => true,
                'message'  => $apiRes['message'] ?? 'Electricity purchase failed. Wallet balance refunded.',
            ], 422);
        }

        // Record successful transaction
        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'electricity',
            'provider'              => $disco->short_code ?? $disco->slug,
            'recipient'             => $meterNumber,
            'amount'                => $amount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRes['reference'] ?? $reference,
            'api_response'          => array_merge(is_array($apiRes['response']) ? $apiRes['response'] : ['raw' => $apiRes['response']], ['api_provider' => $api]),
        ]);

        $freshWallet = $user->fresh()->wallet;
        $token = $apiRes['token'] ?? $apiRes['response']['token'] ?? 'N/A';
        $units = $apiRes['units'] ?? $apiRes['response']['units'] ?? null;

        return response()->json([
            'status'  => true,
            'message' => '₦' . number_format($amount, 2) . ' ' . $disco->name . ' electricity bill paid successfully.',
            'data'    => [
                'reference'     => $reference,
                'disco'         => $disco->name,
                'meter_number'  => $meterNumber,
                'meter_type'    => $meterType,
                'amount'        => $amount,
                'token'         => $token,
                'units'         => $units,
                'customer_name' => $apiRes['customer_name'] ?? 'CUSTOMER',
                'balance_after' => (float) ($freshWallet ? $freshWallet->balance : 0),
                'date'          => now()->toDateTimeString(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 2. CABLE TV SERVICES
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * List all active Cable TV providers.
     */
    public function cableProviders(Request $request): JsonResponse
    {
        if (AppSetting::get('service_cable', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Cable TV service is temporarily unavailable.',
            ], 503);
        }

        $providers = CableProvider::active()->get()->map(fn (CableProvider $p) => [
            'id'   => $p->id,
            'name' => $p->name,
            'code' => $p->code,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Cable TV providers retrieved successfully.',
            'data'    => [
                'total'     => count($providers),
                'providers' => $providers,
            ],
        ]);
    }

    /**
     * Fetch active plans/packages for a cable provider.
     */
    public function cablePlans(Request $request): JsonResponse
    {
        if (AppSetting::get('service_cable', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Cable TV service is temporarily unavailable.',
            ], 503);
        }

        $request->validate([
            'provider_id' => ['required', 'integer', 'exists:cable_providers,id'],
        ]);

        $provider = CableProvider::active()->where('id', $request->provider_id)->firstOrFail();
        $plans    = CablePlan::where('cable_provider_id', $provider->id)->active()->orderBy('amount', 'asc')->get()->map(fn (CablePlan $p) => [
            'id'        => $p->id,
            'plan_name' => $p->name,
            'amount'    => (float) $p->amount,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Cable plans retrieved successfully.',
            'data'    => [
                'provider_id'   => $provider->id,
                'provider_name' => $provider->name,
                'total_plans'   => count($plans),
                'plans'         => $plans,
            ],
        ]);
    }

    /**
     * Validate Smartcard / IUC number.
     */
    public function validateSmartcard(Request $request): JsonResponse
    {
        if (AppSetting::get('service_cable', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Cable TV service is temporarily unavailable.',
            ], 503);
        }

        $request->validate([
            'provider_id' => ['required', 'integer', 'exists:cable_providers,id'],
            'smartcard'   => ['required', 'string', 'min:5', 'max:20'],
        ]);

        $provider  = CableProvider::findOrFail($request->provider_id);
        $smartcard = trim($request->smartcard);
        $api       = AppSetting::get('cable_api', 'easyaccess');

        $result = match ($api) {
            'easyaccess' => $this->validateCardEasyaccess($provider, $smartcard),
            'payscribe'  => $this->validateCardPayscribe($provider, $smartcard),
            'vtpass'     => $this->validateCardVtpass($provider, $smartcard),
            default      => $this->validateCardEasyaccess($provider, $smartcard),
        };

        if (!$result['success']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'] ?? 'Smartcard validation failed. Please check the IUC/Smartcard number.',
            ], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Smartcard validated successfully.',
            'data'    => [
                'provider_id'   => $provider->id,
                'provider_name' => $provider->name,
                'smartcard'     => $smartcard,
                'customer_name' => $result['customer_name'] ?? 'VALIDATED CUSTOMER',
            ],
        ]);
    }

    /**
     * Purchase / Renew Cable TV Subscription.
     */
    public function purchaseCable(Request $request): JsonResponse
    {
        if (AppSetting::get('service_cable', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Cable TV service is temporarily unavailable.',
            ], 503);
        }

        $request->validate([
            'provider_id'     => ['required', 'integer', 'exists:cable_providers,id'],
            'plan_id'         => ['required', 'integer', 'exists:cable_plans,id'],
            'smartcard'       => ['required', 'string', 'min:5', 'max:20'],
            'phone'           => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'pin'             => ['nullable', 'digits:4'],
            'transaction_pin' => ['nullable', 'digits:4'],
        ]);

        $user = auth()->user();
        $providedPin = $request->pin ?? $request->transaction_pin;

        if (!$providedPin) {
            return response()->json([
                'status'  => false,
                'message' => 'Transaction PIN is required.',
                'errors'  => ['pin' => ['Transaction PIN is required.']],
            ], 422);
        }

        if (!$user->verifyPin($providedPin)) {
            return response()->json([
                'status'  => false,
                'message' => 'Incorrect transaction PIN. Please try again.',
                'errors'  => ['pin' => ['Incorrect transaction PIN.']],
            ], 422);
        }

        $provider  = CableProvider::active()->where('id', $request->provider_id)->firstOrFail();
        $plan      = CablePlan::active()->where('id', $request->plan_id)->where('cable_provider_id', $provider->id)->firstOrFail();
        $smartcard = trim($request->smartcard);
        $phone     = preg_replace('/^\+234/', '0', $request->phone);
        $amount    = (float) $plan->amount;

        // Daily spending limit
        $dailyLimit = (float) AppSetting::get('cable_daily_limit', 100000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'cable')
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $amount) > $dailyLimit) {
            return response()->json([
                'status'  => false,
                'message' => 'Daily cable spending limit of ₦' . number_format($dailyLimit, 0) . ' reached. Try again tomorrow.',
            ], 422);
        }

        // Wallet balance check
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($amount)) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient wallet balance. You require ₦' . number_format($amount, 2) . ' to complete this transaction.',
                'errors'  => ['balance' => ['Insufficient wallet balance.']],
            ], 422);
        }

        // Debit wallet
        $reference = 'CAB' . date('YmdHis') . Str::upper(Str::random(8));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($user, $amount, $provider, $plan, $smartcard, $reference) {
                $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                if (!$w->hasSufficientBalance($amount)) {
                    throw new \Exception('Insufficient wallet balance. Please fund your wallet.');
                }
                return $w->debit(
                    $amount,
                    $provider->name . ' - ' . $plan->name . ' (' . $smartcard . ')',
                    $reference,
                    ['service' => 'cable', 'provider' => $provider->slug, 'smartcard' => $smartcard, 'plan' => $plan->name]
                );
            });
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        // Call API Gateway
        $api = AppSetting::get('cable_api', 'easyaccess');
        $apiRes = $this->callCableGateway($provider, $plan, $smartcard, $phone, $reference, $api);

        if (!$apiRes['success']) {
            // Refund
            try {
                DB::transaction(function () use ($user, $amount, $reference, $provider, $smartcard) {
                    $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    $w->credit(
                        $amount,
                        'Refund: ' . $provider->name . ' subscription failed - ' . $smartcard,
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Cable refund failed', ['user_id' => $user->id, 'reference' => $reference, 'error' => $e->getMessage()]);
            }

            return response()->json([
                'status'   => false,
                'refunded' => true,
                'message'  => $apiRes['message'] ?? 'Cable TV subscription failed. Wallet balance refunded.',
            ], 422);
        }

        // Record transaction
        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'cable',
            'provider'              => $provider->slug,
            'recipient'             => $smartcard,
            'amount'                => $amount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRes['reference'] ?? $reference,
            'api_response'          => array_merge(is_array($apiRes['response']) ? $apiRes['response'] : ['raw' => $apiRes['response']], ['api_provider' => $api]),
        ]);

        $freshWallet = $user->fresh()->wallet;

        return response()->json([
            'status'  => true,
            'message' => $provider->name . ' ' . $plan->name . ' subscribed successfully for smartcard ' . $smartcard . '.',
            'data'    => [
                'reference'     => $reference,
                'provider'      => $provider->name,
                'plan_name'     => $plan->name,
                'smartcard'     => $smartcard,
                'amount_paid'   => $amount,
                'balance_after' => (float) ($freshWallet ? $freshWallet->balance : 0),
                'date'          => now()->toDateTimeString(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 3. EXAM PINS SERVICES
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * List active Exam Pin types and unit prices.
     */
    public function examTypes(Request $request): JsonResponse
    {
        if (AppSetting::get('service_epins', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Exam Pins service is temporarily unavailable.',
            ], 503);
        }

        $types = ExamPinType::active()->orderBy('amount', 'asc')->get()->map(fn (ExamPinType $t) => [
            'id'         => $t->id,
            'name'       => $t->name,
            'code'       => $t->code,
            'unit_price' => (float) $t->amount,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Exam pin types retrieved successfully.',
            'data'    => [
                'total' => count($types),
                'types' => $types,
            ],
        ]);
    }

    /**
     * Purchase Exam Scratch Cards / Tokens.
     */
    public function purchaseExamPin(Request $request): JsonResponse
    {
        if (AppSetting::get('service_epins', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Exam Pins service is temporarily unavailable.',
            ], 503);
        }

        $request->validate([
            'exam_type_id'    => ['required', 'integer', 'exists:exam_pin_types,id'],
            'quantity'        => ['required', 'integer', 'min:1', 'max:10'],
            'phone'           => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'pin'             => ['nullable', 'digits:4'],
            'transaction_pin' => ['nullable', 'digits:4'],
        ]);

        $user = auth()->user();
        $providedPin = $request->pin ?? $request->transaction_pin;

        if (!$providedPin) {
            return response()->json([
                'status'  => false,
                'message' => 'Transaction PIN is required.',
                'errors'  => ['pin' => ['Transaction PIN is required.']],
            ], 422);
        }

        if (!$user->verifyPin($providedPin)) {
            return response()->json([
                'status'  => false,
                'message' => 'Incorrect transaction PIN. Please try again.',
                'errors'  => ['pin' => ['Incorrect transaction PIN.']],
            ], 422);
        }

        $examType = ExamPinType::where('id', $request->exam_type_id)->where('is_active', true)->firstOrFail();
        $quantity = (int) $request->quantity;
        $amount   = (float) ($examType->amount * $quantity);
        $phone    = preg_replace('/^\+234/', '0', $request->phone);

        // Daily spending limit
        $dailyLimit = (float) AppSetting::get('epins_daily_limit', 50000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'epin')
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $amount) > $dailyLimit) {
            return response()->json([
                'status'  => false,
                'message' => 'Daily exam pins spending limit of ₦' . number_format($dailyLimit, 0) . ' reached. Try again tomorrow.',
            ], 422);
        }

        // Wallet balance check
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($amount)) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient wallet balance. You require ₦' . number_format($amount, 2) . ' to complete this transaction.',
                'errors'  => ['balance' => ['Insufficient wallet balance.']],
            ], 422);
        }

        // Debit wallet
        $reference = 'PIN' . date('YmdHis') . Str::upper(Str::random(8));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($user, $amount, $examType, $quantity, $reference) {
                $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                if (!$w->hasSufficientBalance($amount)) {
                    throw new \Exception('Insufficient wallet balance. Please fund your wallet.');
                }
                return $w->debit(
                    $amount,
                    $quantity . 'x ' . $examType->name . ' Scratch Card',
                    $reference,
                    ['service' => 'epin', 'exam_type' => $examType->slug, 'quantity' => $quantity]
                );
            });
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        // Call API Gateway
        $api = AppSetting::get('epins_api', 'easyaccess');
        $apiRes = $this->callExamPinGateway($examType, $quantity, $phone, $reference, $api);

        if (!$apiRes['success']) {
            // Refund
            try {
                DB::transaction(function () use ($user, $amount, $reference, $examType, $quantity) {
                    $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    $w->credit(
                        $amount,
                        'Refund: ' . $quantity . 'x ' . $examType->name . ' failed',
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Exam pin refund failed', ['user_id' => $user->id, 'reference' => $reference, 'error' => $e->getMessage()]);
            }

            return response()->json([
                'status'   => false,
                'refunded' => true,
                'message'  => $apiRes['message'] ?? 'Exam pin purchase failed. Wallet balance refunded.',
            ], 422);
        }

        // Record transaction
        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'epin',
            'provider'              => $examType->slug,
            'recipient'             => $phone,
            'amount'                => $amount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRes['reference'] ?? $reference,
            'api_response'          => array_merge(is_array($apiRes['response']) ? $apiRes['response'] : ['raw' => $apiRes['response']], ['api_provider' => $api]),
        ]);

        $freshWallet = $user->fresh()->wallet;

        return response()->json([
            'status'  => true,
            'message' => $quantity . 'x ' . $examType->name . ' scratch card(s) purchased successfully.',
            'data'    => [
                'reference'     => $reference,
                'exam_name'     => $examType->name,
                'quantity'      => $quantity,
                'total_paid'    => $amount,
                'tokens'        => $apiRes['tokens'] ?? $apiRes['pins'] ?? [],
                'balance_after' => (float) ($freshWallet ? $freshWallet->balance : 0),
                'date'          => now()->toDateTimeString(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GATEWAY HELPER METHODS
    // ══════════════════════════════════════════════════════════════════════════

    private function validateMeterEasyaccess($disco, $meterType, $meterNumber): array
    {
        try {
            $service = app(EasyaccessService::class);
            $res = $service->verifyElectricity($disco->easyaccess_code ?? $disco->slug, $meterNumber, $meterType);
            return [
                'success'       => $res['success'] ?? false,
                'customer_name' => $res['customer_name'] ?? 'VALIDATED CUSTOMER',
                'address'       => $res['address'] ?? null,
                'message'       => $res['message'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validateMeterPayscribe($disco, $meterType, $meterNumber): array
    {
        $endpoint = config('services.payscribe.base_url') . '/electricity/validate';
        $rawBody  = json_encode([
            'service'      => $disco->slug,
            'meter_number' => $meterNumber,
            'amount'       => '1000',
            'meter_type'   => $meterType,
        ]);
        try {
            $httpResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . (config('services.payscribe.secret_key') ?: AppSetting::get('payscribe_secret_key')),
                'Content-Type'  => 'text/plain',
            ])->timeout(20)->withBody($rawBody, 'text/plain')->post($endpoint);

            $data   = $httpResponse->json() ?? [];
            $status = $data['status'] ?? false;
            if ($status) {
                $details = $data['message']['details'] ?? [];
                return [
                    'success'       => true,
                    'customer_name' => $details['customer_name'] ?? 'VALIDATED CUSTOMER',
                    'address'       => $details['address'] ?? null,
                ];
            }
            return ['success' => false, 'message' => $data['description'] ?? 'Meter validation failed.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validateMeterVtpass($disco, $meterType, $meterNumber): array
    {
        $baseUrl  = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
        $endpoint = $baseUrl . '/api/merchant-verify';
        $payload  = [
            'billersCode' => $meterNumber,
            'serviceID'   => $disco->slug,
            'type'        => $meterType,
        ];
        $apiKey    = config('services.vtpass.api_key') ?: AppSetting::get('vtpass_api_key') ?: AppSetting::get('vtpass_public_key');
        $secretKey = config('services.vtpass.secret_key') ?: AppSetting::get('vtpass_secret_key');
        $publicKey = config('services.vtpass.public_key') ?: AppSetting::get('vtpass_public_key') ?: $apiKey;

        try {
            $headers = ['api-key' => $apiKey, 'secret-key' => $secretKey];
            if ($publicKey) {
                $headers['public-key'] = $publicKey;
            }
            $res  = Http::withHeaders($headers)->timeout(20)->post($endpoint, $payload);
            $data = $res->json() ?? [];
            if (($data['code'] ?? '') === '000') {
                $content = $data['content'] ?? [];
                return [
                    'success'       => true,
                    'customer_name' => $content['Customer_Name'] ?? $content['name'] ?? 'VALIDATED CUSTOMER',
                    'address'       => $content['Address'] ?? $content['address'] ?? null,
                ];
            }
            return ['success' => false, 'message' => $data['response_description'] ?? 'Meter validation failed.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function callElectricityGateway($disco, $meterType, $meterNumber, $amount, $phone, $reference, $api): array
    {
        $start = hrtime(true);
        try {
            if ($api === 'easyaccess') {
                $service  = app(EasyaccessService::class);
                $res      = $service->payElectricity($disco->easyaccess_id ?? $disco->slug, $meterNumber, $meterType, $amount, $phone, $reference);
                $duration = (int) ((hrtime(true) - $start) / 1e6);

                ApiLog::record([
                    'user_id'     => auth()->id(),
                    'service'     => 'electricity',
                    'provider'    => 'easyaccess',
                    'reference'   => $reference,
                    'endpoint'    => config('services.easyaccess.base_url') . '/pay_electricity',
                    'method'      => 'POST',
                    'payload'     => ['disco' => $disco->slug, 'meter' => $meterNumber, 'amount' => $amount],
                    'response'    => $res,
                    'duration_ms' => $duration,
                    'success'     => $res['success'] ?? false,
                ]);

                return [
                    'success'   => $res['success'] ?? false,
                    'reference' => $res['reference'] ?? $reference,
                    'token'     => $res['token'] ?? 'N/A',
                    'units'     => $res['units'] ?? null,
                    'response'  => $res,
                ];
            }

            if ($api === 'payscribe') {
                $endpoint = config('services.payscribe.base_url') . '/electricity/pay';
                $payload  = [
                    'service'      => $disco->slug,
                    'meter_number' => $meterNumber,
                    'amount'       => (int) $amount,
                    'phone'        => $phone,
                    'meter_type'   => $meterType,
                    'ref'          => $reference,
                ];
                $res  = Http::withHeaders([
                    'Authorization' => 'Bearer ' . (config('services.payscribe.secret_key') ?: AppSetting::get('payscribe_secret_key')),
                    'Content-Type'  => 'application/json',
                ])->timeout(30)->post($endpoint, $payload);
                $data     = $res->json() ?? [];
                $success  = ($data['status'] ?? false) === true;
                $duration = (int) ((hrtime(true) - $start) / 1e6);

                ApiLog::record([
                    'user_id'     => auth()->id(),
                    'service'     => 'electricity',
                    'provider'    => 'payscribe',
                    'reference'   => $reference,
                    'endpoint'    => $endpoint,
                    'method'      => 'POST',
                    'payload'     => $payload,
                    'response'    => $data,
                    'duration_ms' => $duration,
                    'success'     => $success,
                ]);

                return [
                    'success'   => $success,
                    'reference' => $data['message']['ref'] ?? $reference,
                    'token'     => $data['message']['token'] ?? 'N/A',
                    'units'     => $data['message']['units'] ?? null,
                    'response'  => $data,
                ];
            }

            // Default / VTPass
            $vtpassRef = date('YmdHis') . Str::upper(Str::random(6));
            $baseUrl   = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
            $endpoint  = $baseUrl . '/api/pay';
            $payload   = [
                'request_id'     => $vtpassRef,
                'serviceID'      => $disco->slug,
                'variation_code' => $meterType,
                'billersCode'    => $meterNumber,
                'amount'         => (int) $amount,
                'phone'          => $phone,
            ];
            $apiKey    = config('services.vtpass.api_key') ?: AppSetting::get('vtpass_api_key') ?: AppSetting::get('vtpass_public_key');
            $secretKey = config('services.vtpass.secret_key') ?: AppSetting::get('vtpass_secret_key');
            $publicKey = config('services.vtpass.public_key') ?: AppSetting::get('vtpass_public_key') ?: $apiKey;

            $headers = ['api-key' => $apiKey, 'secret-key' => $secretKey];
            if ($publicKey) {
                $headers['public-key'] = $publicKey;
            }

            $res     = Http::withHeaders($headers)->timeout(30)->post($endpoint, $payload);
            $data    = $res->json() ?? [];
            $code    = $data['code'] ?? '';
            $success = in_array($code, ['000', '099']);
            $txn     = $data['content']['transactions'] ?? [];
            $rawToken= $txn['token'] ?? $data['purchased_code'] ?? $data['Token'] ?? null;
            $token   = $rawToken ? preg_replace('/^Token\s*:\s*/i', '', trim((string) $rawToken)) : 'N/A';
            $units   = $txn['units'] ?? $data['RefundUnits'] ?? $data['FreeUnits'] ?? null;
            $duration= (int) ((hrtime(true) - $start) / 1e6);

            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'electricity',
                'provider'    => 'vtpass',
                'reference'   => $reference,
                'endpoint'    => $endpoint,
                'method'      => 'POST',
                'payload'     => $payload,
                'response'    => $data,
                'duration_ms' => $duration,
                'success'     => $success,
            ]);

            return [
                'success'   => $success,
                'reference' => $txn['transactionId'] ?? $data['requestId'] ?? $vtpassRef,
                'token'     => $token,
                'units'     => $units,
                'response'  => $data,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validateCardEasyaccess($provider, $smartcard): array
    {
        try {
            $service = app(EasyaccessService::class);
            $res     = $service->verifyCable($provider->easyaccess_id ?? $provider->slug, $smartcard);
            return [
                'success'       => $res['success'] ?? false,
                'customer_name' => $res['customer_name'] ?? 'VALIDATED SUBSCRIBER',
                'message'       => $res['message'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validateCardPayscribe($provider, $smartcard): array
    {
        $endpoint = config('services.payscribe.base_url') . '/multichoice/validate';
        $rawBody  = json_encode([
            'service' => $provider->slug,
            'account' => $smartcard,
        ]);
        try {
            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . (config('services.payscribe.secret_key') ?: AppSetting::get('payscribe_secret_key')),
                'Content-Type'  => 'application/json',
            ])->timeout(20)->withBody($rawBody, 'application/json')->post($endpoint);
            $data   = $res->json() ?? [];
            $status = $data['status'] ?? false;
            if ($status) {
                $details = $data['message']['details'] ?? [];
                return [
                    'success'       => true,
                    'customer_name' => $details['customer_name'] ?? 'VALIDATED SUBSCRIBER',
                ];
            }
            return ['success' => false, 'message' => $data['description'] ?? 'Smartcard validation failed.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validateCardVtpass($provider, $smartcard): array
    {
        $baseUrl  = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
        $endpoint = $baseUrl . '/api/merchant-verify';
        $payload  = [
            'serviceID'   => $provider->slug,
            'billersCode' => $smartcard,
        ];
        $apiKey    = config('services.vtpass.api_key') ?: AppSetting::get('vtpass_api_key') ?: AppSetting::get('vtpass_public_key');
        $secretKey = config('services.vtpass.secret_key') ?: AppSetting::get('vtpass_secret_key');
        $publicKey = config('services.vtpass.public_key') ?: AppSetting::get('vtpass_public_key') ?: $apiKey;

        try {
            $headers = ['api-key' => $apiKey, 'secret-key' => $secretKey];
            if ($publicKey) {
                $headers['public-key'] = $publicKey;
            }
            $res  = Http::withHeaders($headers)->timeout(20)->post($endpoint, $payload);
            $data = $res->json() ?? [];
            if (($data['code'] ?? '') === '000') {
                $content = $data['content'] ?? [];
                return [
                    'success'       => true,
                    'customer_name' => $content['Customer_Name'] ?? $content['name'] ?? 'VALIDATED SUBSCRIBER',
                ];
            }
            return ['success' => false, 'message' => $data['response_description'] ?? 'Smartcard validation failed.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function callCableGateway($provider, $plan, $smartcard, $phone, $reference, $api): array
    {
        $start = hrtime(true);
        try {
            if ($api === 'easyaccess') {
                $service  = app(EasyaccessService::class);
                $res      = $service->payCable($provider->easyaccess_id ?? $provider->slug, $plan->easyaccess_id ?? $plan->id, $smartcard, $phone, $reference);
                $duration = (int) ((hrtime(true) - $start) / 1e6);

                ApiLog::record([
                    'user_id'     => auth()->id(),
                    'service'     => 'cable',
                    'provider'    => 'easyaccess',
                    'reference'   => $reference,
                    'endpoint'    => config('services.easyaccess.base_url') . '/pay_tv',
                    'method'      => 'POST',
                    'payload'     => ['provider' => $provider->slug, 'smartcard' => $smartcard, 'plan' => $plan->name],
                    'response'    => $res,
                    'duration_ms' => $duration,
                    'success'     => $res['success'] ?? false,
                ]);

                return [
                    'success'   => $res['success'] ?? false,
                    'reference' => $res['reference'] ?? $reference,
                    'response'  => $res,
                ];
            }

            if ($api === 'payscribe') {
                $endpoint = config('services.payscribe.base_url') . '/multichoice/pay';
                $payload  = [
                    'service' => $provider->slug,
                    'account' => $smartcard,
                    'plan_id' => $plan->payscribe_id ?? $plan->vtpass_id,
                    'ref'     => $reference,
                ];
                $res  = Http::withHeaders([
                    'Authorization' => 'Bearer ' . (config('services.payscribe.secret_key') ?: AppSetting::get('payscribe_secret_key')),
                    'Content-Type'  => 'application/json',
                ])->timeout(30)->post($endpoint, $payload);
                $data     = $res->json() ?? [];
                $success  = ($data['status'] ?? false) === true;
                $duration = (int) ((hrtime(true) - $start) / 1e6);

                ApiLog::record([
                    'user_id'     => auth()->id(),
                    'service'     => 'cable',
                    'provider'    => 'payscribe',
                    'reference'   => $reference,
                    'endpoint'    => $endpoint,
                    'method'      => 'POST',
                    'payload'     => $payload,
                    'response'    => $data,
                    'duration_ms' => $duration,
                    'success'     => $success,
                ]);

                return [
                    'success'   => $success,
                    'reference' => $data['message']['ref'] ?? $reference,
                    'response'  => $data,
                ];
            }

            // Default / VTPass
            $vtpassRef = date('YmdHis') . Str::upper(Str::random(6));
            $baseUrl   = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
            $endpoint  = $baseUrl . '/api/pay';
            $payload   = [
                'request_id'     => $vtpassRef,
                'serviceID'      => $provider->slug,
                'variation_code' => $plan->vtpass_id,
                'billersCode'    => $smartcard,
                'amount'         => (int) $plan->amount,
                'phone'          => $phone,
            ];
            $apiKey    = config('services.vtpass.api_key') ?: AppSetting::get('vtpass_api_key') ?: AppSetting::get('vtpass_public_key');
            $secretKey = config('services.vtpass.secret_key') ?: AppSetting::get('vtpass_secret_key');
            $publicKey = config('services.vtpass.public_key') ?: AppSetting::get('vtpass_public_key') ?: $apiKey;

            $headers = ['api-key' => $apiKey, 'secret-key' => $secretKey];
            if ($publicKey) {
                $headers['public-key'] = $publicKey;
            }

            $res     = Http::withHeaders($headers)->timeout(30)->post($endpoint, $payload);
            $data    = $res->json() ?? [];
            $code    = $data['code'] ?? '';
            $success = in_array($code, ['000', '099']);
            $txn     = $data['content']['transactions'] ?? [];
            $duration= (int) ((hrtime(true) - $start) / 1e6);

            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'cable',
                'provider'    => 'vtpass',
                'reference'   => $reference,
                'endpoint'    => $endpoint,
                'method'      => 'POST',
                'payload'     => $payload,
                'response'    => $data,
                'duration_ms' => $duration,
                'success'     => $success,
            ]);

            return [
                'success'   => $success,
                'reference' => $txn['transactionId'] ?? $data['requestId'] ?? $vtpassRef,
                'response'  => $data,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function callExamPinGateway($examType, $quantity, $phone, $reference, $api): array
    {
        $start = hrtime(true);
        try {
            if ($api === 'easyaccess') {
                $service  = app(EasyaccessService::class);
                $res      = $service->payExamPin($examType->easyaccess_code ?? $examType->slug, $quantity, $phone, $reference);
                $duration = (int) ((hrtime(true) - $start) / 1e6);

                ApiLog::record([
                    'user_id'     => auth()->id(),
                    'service'     => 'epin',
                    'provider'    => 'easyaccess',
                    'reference'   => $reference,
                    'endpoint'    => config('services.easyaccess.base_url') . '/pay_pin',
                    'method'      => 'POST',
                    'payload'     => ['exam' => $examType->slug, 'quantity' => $quantity],
                    'response'    => $res,
                    'duration_ms' => $duration,
                    'success'     => $res['success'] ?? false,
                ]);

                return [
                    'success'   => $res['success'] ?? false,
                    'reference' => $res['reference'] ?? $reference,
                    'tokens'    => $res['tokens'] ?? [],
                    'response'  => $res,
                ];
            }

            // VTPass Exam Pin
            $vtpassRef = date('YmdHis') . Str::upper(Str::random(6));
            $baseUrl   = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
            $endpoint  = $baseUrl . '/api/pay';
            $payload   = [
                'request_id'     => $vtpassRef,
                'serviceID'      => $examType->slug,
                'variation_code' => $examType->vtpass_service_id,
                'phone'          => $phone,
                'quantity'       => $quantity,
            ];
            $apiKey    = config('services.vtpass.api_key') ?: AppSetting::get('vtpass_api_key') ?: AppSetting::get('vtpass_public_key');
            $secretKey = config('services.vtpass.secret_key') ?: AppSetting::get('vtpass_secret_key');
            $publicKey = config('services.vtpass.public_key') ?: AppSetting::get('vtpass_public_key') ?: $apiKey;

            $headers = ['api-key' => $apiKey, 'secret-key' => $secretKey];
            if ($publicKey) {
                $headers['public-key'] = $publicKey;
            }

            $res     = Http::withHeaders($headers)->timeout(30)->post($endpoint, $payload);
            $data    = $res->json() ?? [];
            $code    = $data['code'] ?? '';
            $success = in_array($code, ['000', '099']);
            $txn     = $data['content']['transactions'] ?? [];
            $pins    = [];

            if ($success) {
                if (!empty($data['cards']) && is_array($data['cards'])) {
                    foreach ($data['cards'] as $p) {
                        $pins[] = [
                            'pin'    => $p['pin'] ?? ($p['Pin'] ?? ''),
                            'serial' => $p['serialnumber'] ?? ($p['Serial'] ?? null),
                        ];
                    }
                } else {
                    for ($i = 1; $i <= $quantity; $i++) {
                        $key = $i === 1 ? 'token' : 'token' . $i;
                        $t   = $txn[$key] ?? null;
                        if ($t) {
                            $pins[] = ['pin' => $t, 'serial' => null];
                        }
                    }
                }
            }

            $duration = (int) ((hrtime(true) - $start) / 1e6);

            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'epin',
                'provider'    => 'vtpass',
                'reference'   => $reference,
                'endpoint'    => $endpoint,
                'method'      => 'POST',
                'payload'     => $payload,
                'response'    => $data,
                'duration_ms' => $duration,
                'success'     => $success,
            ]);

            return [
                'success'   => $success,
                'reference' => $txn['transactionId'] ?? $data['requestId'] ?? $vtpassRef,
                'tokens'    => $pins,
                'response'  => $data,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

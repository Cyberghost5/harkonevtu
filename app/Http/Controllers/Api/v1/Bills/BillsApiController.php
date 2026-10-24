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
use App\Services\ProviderAlertService;
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

            ServiceTransaction::create([
                'user_id'               => $user->id,
                'wallet_transaction_id' => $walletTx?->id,
                'service_type'          => 'electricity',
                'provider'              => $disco->short_code ?? $disco->slug,
                'recipient'             => $meterNumber,
                'amount'                => $amount,
                'status'                => 'refunded',
                'reference'             => $reference,
                'api_reference'         => $apiRes['reference'] ?? $reference,
                'api_response'          => array_merge(is_array($apiRes['response'] ?? null) ? $apiRes['response'] : ['error' => $apiRes['message'] ?? 'Electricity purchase failed.'], ['api_provider' => $api]),
            ]);

            $rawMsg = $apiRes['message'] ?? 'Electricity purchase failed.';
            $friendlyMsg = ProviderAlertService::processFailure($rawMsg, [
                'service'   => 'Electricity (' . $disco->name . ' - ' . ucfirst($meterType) . ')',
                'provider'  => $api,
                'amount'    => $amount,
                'user'      => $user,
                'recipient' => $meterNumber,
                'reference' => $reference,
            ], true);

            return response()->json([
                'status'   => false,
                'refunded' => true,
                'message'  => $friendlyMsg,
            ], 422);
        }

        $rawToken = $apiRes['token'] 
            ?? ($apiRes['purchased_code'] ?? null)
            ?? ($apiRes['response']['purchased_code'] ?? null)
            ?? ($apiRes['response']['token'] ?? null)
            ?? ($apiRes['response']['Token'] ?? null)
            ?? ($apiRes['response']['content']['transactions']['token'] ?? null)
            ?? ($apiRes['response']['message']['details']['token'] ?? null)
            ?? null;
        $token = null;
        if ($rawToken) {
            $cleaned = preg_replace('/^Token\s*[:\-]\s*/i', '', trim((string) $rawToken));
            if ($cleaned !== '' && strtoupper($cleaned) !== 'N/A') {
                $token = $cleaned;
            }
        }

        $rawUnits = $apiRes['units'] 
            ?? ($apiRes['response']['units'] ?? null)
            ?? ($apiRes['response']['meter_units'] ?? null)
            ?? ($apiRes['response']['RefundUnits'] ?? null)
            ?? ($apiRes['response']['content']['transactions']['units'] ?? null)
            ?? ($apiRes['response']['message']['details']['unit'] ?? null);
        $units = null;
        if ($rawUnits) {
            $cleanedUnits = trim((string) $rawUnits);
            if ($cleanedUnits !== '' && strtoupper($cleanedUnits) !== 'N/A') {
                $units = $cleanedUnits;
            }
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
            'api_response'          => array_merge(is_array($apiRes['response']) ? $apiRes['response'] : ['raw' => $apiRes['response']], [
                'api_provider'  => $api,
                'token'         => $token,
                'units'         => $units,
                'meter_number'  => $meterNumber,
                'customer_name' => $apiRes['customer_name'] ?? 'CUSTOMER',
            ]),
        ]);

        if ($walletTx) {
            $wMeta = $walletTx->metadata ?? [];
            if ($token) {
                $wMeta['token'] = $token;
            }
            if ($units) {
                $wMeta['units'] = $units;
            }
            $wMeta['meter_number'] = $meterNumber;
            $wMeta['customer_name'] = $apiRes['customer_name'] ?? 'CUSTOMER';
            $walletTx->metadata = $wMeta;
            $walletTx->save();
        }

        $freshWallet = $user->fresh()->wallet;

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

            ServiceTransaction::create([
                'user_id'               => $user->id,
                'wallet_transaction_id' => $walletTx?->id,
                'service_type'          => 'cable',
                'provider'              => $provider->slug,
                'recipient'             => $smartcard,
                'amount'                => $amount,
                'status'                => 'refunded',
                'reference'             => $reference,
                'api_reference'         => $apiRes['reference'] ?? $reference,
                'api_response'          => array_merge(is_array($apiRes['response'] ?? null) ? $apiRes['response'] : ['error' => $apiRes['message'] ?? 'Cable TV subscription failed.'], ['api_provider' => $api]),
            ]);

            $rawMsg = $apiRes['message'] ?? 'Cable TV subscription failed.';
            $friendlyMsg = ProviderAlertService::processFailure($rawMsg, [
                'service'   => 'Cable TV (' . $provider->name . ' - ' . $plan->name . ')',
                'provider'  => $api,
                'amount'    => $amount,
                'user'      => $user,
                'recipient' => $smartcard,
                'reference' => $reference,
            ], true);

            return response()->json([
                'status'   => false,
                'refunded' => true,
                'message'  => $friendlyMsg,
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

            ServiceTransaction::create([
                'user_id'               => $user->id,
                'wallet_transaction_id' => $walletTx?->id,
                'service_type'          => 'epin',
                'provider'              => $examType->slug,
                'recipient'             => $phone,
                'amount'                => $amount,
                'status'                => 'refunded',
                'reference'             => $reference,
                'api_reference'         => $apiRes['reference'] ?? $reference,
                'api_response'          => array_merge(is_array($apiRes['response'] ?? null) ? $apiRes['response'] : ['error' => $apiRes['message'] ?? 'Exam pin purchase failed.'], ['api_provider' => $api]),
            ]);

            $rawMsg = $apiRes['message'] ?? 'Exam pin purchase failed.';
            $friendlyMsg = ProviderAlertService::processFailure($rawMsg, [
                'service'   => 'Exam Pin (' . $examType->name . ' x' . $quantity . ')',
                'provider'  => $api,
                'amount'    => $amount,
                'user'      => $user,
                'recipient' => $phone,
                'reference' => $reference,
            ], true);

            return response()->json([
                'status'   => false,
                'refunded' => true,
                'message'  => $friendlyMsg,
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
                'api_reference' => $apiRes['reference'] ?? $reference,
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
        $start    = hrtime(true);
        $endpoint = config('services.easyaccess.base_url') . '/verify-electricity';
        $payload  = [
            'company'   => $disco->easyaccess_code ?? $disco->slug,
            'metertype' => $meterType === 'prepaid' ? '1' : '2',
            'meterno'   => $meterNumber,
        ];
        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.easyaccess.token'),
        ];
        $data    = [];
        $success = false;

        try {
            $service = app(EasyaccessService::class);
            $res     = $service->verifyElectricity($disco->easyaccess_code ?? $disco->slug, $meterNumber, $meterType);
            $data    = is_array($res) ? $res : ['raw' => $res];
            $success = $res['success'] ?? false;

            return [
                'success'       => $success,
                'customer_name' => $res['customer_name'] ?? 'VALIDATED CUSTOMER',
                'address'       => $res['address'] ?? null,
                'message'       => $res['message'] ?? null,
            ];
        } catch (\Throwable $e) {
            $data = ['error' => $e->getMessage()];
            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'         => auth()->id(),
                'service'         => 'electricity_validate',
                'provider'        => 'easyaccess',
                'reference'       => $meterNumber,
                'endpoint'        => $endpoint,
                'method'          => 'POST',
                'payload'         => $payload,
                'request_headers' => $requestHeaders,
                'response'        => $data,
                'duration_ms'     => $duration,
                'success'         => $success,
            ]);
        }
    }

    private function validateMeterPayscribe($disco, $meterType, $meterNumber): array
    {
        $apiKey = \App\Services\PayscribePaymentService::getApiKey();
        if (!$apiKey) {
            return ['success' => false, 'message' => 'Payscribe API key is not configured. Please enter your Public Key / API Token in Admin Settings -> API Keys.'];
        }

        $start    = hrtime(true);
        $endpoint = rtrim(config('services.payscribe.base_url', 'https://api.payscribe.ng/api/v1'), '/') . '/electricity/validate';
        $payload  = [
            'service'      => $disco->idForApi('payscribe'),
            'meter_number' => $meterNumber,
            'amount'       => '1000',
            'meter_type'   => $meterType,
        ];
        $rawBody        = json_encode($payload);
        $requestHeaders = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'text/plain',
        ];
        $data       = [];
        $httpStatus = null;
        $resHeaders = null;
        $success    = false;

        try {
            $httpResponse = Http::withHeaders($requestHeaders)->timeout(20)->withBody($rawBody, 'text/plain')->post($endpoint);
            $httpStatus   = $httpResponse->status();
            $resHeaders   = $httpResponse->headers();
            $data         = $httpResponse->json() ?? [];
            $rawStatus    = $data['status'] ?? false;
            $success      = $httpResponse->successful() && ($rawStatus === true || $rawStatus === 'true' || $rawStatus === 'success');

            if ($success) {
                $details = $data['message']['details'] ?? ($data['details'] ?? ($data['data'] ?? []));
                return [
                    'success'       => true,
                    'customer_name' => $details['customer_name'] ?? ($details['name'] ?? 'VALIDATED CUSTOMER'),
                    'address'       => $details['address'] ?? null,
                ];
            }
            $errMsg = is_array($data['message'] ?? null)
                ? ($data['message']['description'] ?? ($data['message']['message'] ?? ($data['description'] ?? 'Meter validation failed.')))
                : ($data['description'] ?? ($data['message'] ?? 'Meter validation failed.'));
            return ['success' => false, 'message' => $errMsg];
        } catch (\Throwable $e) {
            $data = ['error' => $e->getMessage()];
            return ['success' => false, 'message' => 'Unable to connect to Payscribe service provider.'];
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            $loggedHeaders = $requestHeaders;
            $loggedHeaders['Authorization'] = 'Bearer ***';
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'electricity_validate',
                'provider'         => 'payscribe',
                'reference'        => $meterNumber,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $loggedHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $resHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }
    }

    private function validateMeterVtpass($disco, $meterType, $meterNumber): array
    {
        $start    = hrtime(true);
        $baseUrl  = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
        $endpoint = $baseUrl . '/api/merchant-verify';
        $payload  = [
            'billersCode' => $meterNumber,
            'serviceID'   => $disco->slug,
            'type'        => $meterType,
        ];
        $apiKey    = config('services.vtpass.api_key') ?: AppSetting::get('vtpass_api_key') ?: AppSetting::get('vtpass_public_key');
        $secretKey = config('services.vtpass.secret_key') ?: AppSetting::get('vtpass_secret_key');
        $headers   = ['api-key' => $apiKey, 'secret-key' => $secretKey];
        $data       = [];
        $httpStatus = null;
        $resHeaders = null;
        $success    = false;

        try {
            $res        = Http::withHeaders($headers)->timeout(20)->post($endpoint, $payload);
            $httpStatus = $res->status();
            $resHeaders = $res->headers();
            $data       = $res->json() ?? [];
            $code       = $data['code'] ?? '';
            $content    = $data['content'] ?? [];

            $hasError          = !empty($content['error']);
            $isWrongBillerCode = isset($content['WrongBillersCode']) && ($content['WrongBillersCode'] === true || $content['WrongBillersCode'] === 'true');
            $customerName      = $content['Customer_Name'] ?? $content['name'] ?? $content['customer_name'] ?? null;

            if ($code === '000' && !$hasError && !$isWrongBillerCode && !empty($customerName)) {
                $success = true;
                return [
                    'success'       => true,
                    'customer_name' => $customerName,
                    'address'       => $content['Address'] ?? $content['address'] ?? null,
                ];
            }

            $errorMessage = $content['error'] ?? $data['response_description'] ?? 'Meter validation failed. Please verify the meter number.';
            return ['success' => false, 'message' => $errorMessage];
        } catch (\Throwable $e) {
            $data = ['error' => $e->getMessage()];
            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'electricity_validate',
                'provider'         => 'vtpass',
                'reference'        => $meterNumber,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $headers,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $resHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
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
                $apiKey = \App\Services\PayscribePaymentService::getApiKey();
                if (!$apiKey) {
                    return [
                        'success'   => false,
                        'reference' => $reference,
                        'token'     => 'N/A',
                        'units'     => null,
                        'message'   => 'Payscribe API key is not configured. Please enter your Public Key / API Token in Admin Settings -> API Keys.',
                        'response'  => ['message' => 'Payscribe API key is not configured.'],
                    ];
                }

                $endpoint = rtrim(config('services.payscribe.base_url', 'https://api.payscribe.ng/api/v1'), '/') . '/electricity/vend';
                $payload  = [
                    'service'       => $disco->idForApi('payscribe'),
                    'meter_number'  => $meterNumber,
                    'amount'        => (string) (int) $amount,
                    'phone'         => $phone,
                    'meter_type'    => $meterType,
                    'customer_name' => auth()->user()?->name ?? 'Customer',
                    'ref'           => $reference,
                ];
                $requestHeaders = [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ];
                $resHeaders = null;
                $res  = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
                $httpStatus = $res->status();
                $resHeaders = $res->headers();
                $data       = $res->json() ?? [];
                $rawStatus  = $data['status'] ?? null;
                $statusCode = $data['status_code'] ?? '';
                $success    = $res->successful() && (
                    $rawStatus === true ||
                    $rawStatus === 'true' ||
                    $rawStatus === 'success' ||
                    in_array($statusCode, ['200', '201', 200, 201]) ||
                    (($data['message']['status'] ?? null) === 'success')
                );
                $duration = (int) ((hrtime(true) - $start) / 1e6);

                $details = $data['message']['details'] ?? ($data['details'] ?? ($data['data'] ?? []));
                $token   = isset($details['token']) ? trim((string) $details['token']) : ($data['token'] ?? ($data['message']['token'] ?? 'N/A'));
                $units   = $details['unit'] ?? ($details['units'] ?? ($data['message']['units'] ?? ($data['units'] ?? null)));
                $apiRef  = $details['transid'] ?? ($details['transaction_id'] ?? ($data['reference'] ?? ($data['message']['ref'] ?? ($data['transid'] ?? $reference))));

                $errMsg = null;
                if (!$success) {
                    if (is_array($data['message'] ?? null)) {
                        $errMsg = $data['message']['description'] ?? ($data['message']['message'] ?? ($data['description'] ?? 'Payscribe electricity vending failed.'));
                    } else {
                        $errMsg = $data['description'] ?? ($data['message'] ?? 'Payscribe electricity vending failed.');
                    }
                }

                $loggedHeaders = $requestHeaders;
                $loggedHeaders['Authorization'] = 'Bearer ***';
                ApiLog::record([
                    'user_id'          => auth()->id(),
                    'service'          => 'electricity',
                    'provider'         => 'payscribe',
                    'reference'        => $reference,
                    'endpoint'         => $endpoint,
                    'method'           => 'POST',
                    'payload'          => $payload,
                    'request_headers'  => $loggedHeaders,
                    'response'         => $data,
                    'http_status'      => $httpStatus,
                    'response_headers' => $resHeaders,
                    'duration_ms'      => $duration,
                    'success'          => $success,
                ]);

                return [
                    'success'   => $success,
                    'reference' => $apiRef,
                    'token'     => $token,
                    'units'     => $units,
                    'message'   => $errMsg,
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
            // if ($publicKey) {
            //     $headers['public-key'] = $publicKey;
            // }

            $res     = Http::withHeaders($headers)->timeout(30)->post($endpoint, $payload);
            $data    = $res->json() ?? [];
            $code    = $data['code'] ?? '';
            $content = $data['content'] ?? [];
            $success = in_array($code, ['000', '099'])
                && empty($content['error'])
                && !(isset($content['WrongBillersCode']) && ($content['WrongBillersCode'] === true || $content['WrongBillersCode'] === 'true'));
            $txn      = $data['content']['transactions'] ?? [];
            $rawToken = $data['token'] ?? $data['purchased_code'] ?? ($txn['token'] ?? ($txn['purchased_code'] ?? ($data['Token'] ?? null)));
            $token    = null;
            if ($rawToken) {
                $cleaned = preg_replace('/^Token\s*[:\-]\s*/i', '', trim((string) $rawToken));
                if ($cleaned !== '' && strtoupper($cleaned) !== 'N/A') {
                    $token = $cleaned;
                }
            }
            $rawUnits = $data['units'] ?? ($txn['units'] ?? ($data['RefundUnits'] ?? ($data['FreeUnits'] ?? null)));
            $units    = null;
            if ($rawUnits) {
                $cleanedUnits = trim((string) $rawUnits);
                if ($cleanedUnits !== '' && strtoupper($cleanedUnits) !== 'N/A') {
                    $units = $cleanedUnits;
                }
            }
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
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'electricity',
                'provider'    => $api ?? 'electricity',
                'reference'   => $reference,
                'endpoint'    => $endpoint ?? 'N/A',
                'method'      => 'POST',
                'payload'     => ['disco' => $disco->slug, 'meter' => $meterNumber, 'amount' => $amount],
                'response'    => ['error' => $e->getMessage()],
                'duration_ms' => $duration,
                'success'     => false,
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function validateCardEasyaccess($provider, $smartcard): array
    {
        $start    = hrtime(true);
        $endpoint = config('services.easyaccess.base_url') . '/verify-tv';
        $payload  = [
            'company' => $provider->easyaccess_id ?? $provider->slug,
            'iucno'   => $smartcard,
        ];
        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.easyaccess.token'),
            'Cache-Control' => 'no-cache',
        ];
        $data    = [];
        $success = false;

        try {
            $service = app(EasyaccessService::class);
            $res     = $service->verifyCable($provider->easyaccess_id ?? $provider->slug, $smartcard);
            $data    = is_array($res) ? $res : ['raw' => $res];
            $success = $res['success'] ?? false;

            return [
                'success'       => $success,
                'customer_name' => $res['customer_name'] ?? 'VALIDATED SUBSCRIBER',
                'message'       => $res['message'] ?? null,
            ];
        } catch (\Throwable $e) {
            $data = ['error' => $e->getMessage()];
            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'         => auth()->id(),
                'service'         => 'cable_validate',
                'provider'        => 'easyaccess',
                'reference'       => $smartcard,
                'endpoint'        => $endpoint,
                'method'          => 'POST',
                'payload'         => $payload,
                'request_headers' => $requestHeaders,
                'response'        => $data,
                'duration_ms'     => $duration,
                'success'         => $success,
            ]);
        }
    }

    private function validateCardPayscribe($provider, $smartcard): array
    {
        $apiKey = \App\Services\PayscribePaymentService::getApiKey();
        if (!$apiKey) {
            return ['success' => false, 'message' => 'Payscribe API key is not configured. Please enter your Public Key / API Token in Admin Settings -> API Keys.'];
        }

        $start    = hrtime(true);
        $endpoint = rtrim(config('services.payscribe.base_url', 'https://api.payscribe.ng/api/v1'), '/') . '/multichoice/validate';
        $payload  = [
            'service' => $provider->idForApi('payscribe'),
            'account' => $smartcard,
        ];
        $rawBody        = json_encode($payload);
        $requestHeaders = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
        $data       = [];
        $httpStatus = null;
        $resHeaders = null;
        $success    = false;

        try {
            $res        = Http::withHeaders($requestHeaders)->timeout(20)->withBody($rawBody, 'application/json')->post($endpoint);
            $httpStatus = $res->status();
            $resHeaders = $res->headers();
            $data       = $res->json() ?? [];
            $rawStatus  = $data['status'] ?? false;
            $success    = $res->successful() && ($rawStatus === true || $rawStatus === 'true' || $rawStatus === 'success');

            if ($success) {
                $details = $data['message']['details'] ?? ($data['details'] ?? ($data['data'] ?? []));
                return [
                    'success'       => true,
                    'customer_name' => $details['customer_name'] ?? ($details['name'] ?? 'VALIDATED SUBSCRIBER'),
                ];
            }
            $errMsg = is_array($data['message'] ?? null)
                ? ($data['message']['description'] ?? ($data['message']['message'] ?? ($data['description'] ?? 'Smartcard validation failed.')))
                : ($data['description'] ?? ($data['message'] ?? 'Smartcard validation failed.'));
            return ['success' => false, 'message' => $errMsg];
        } catch (\Throwable $e) {
            $data = ['error' => $e->getMessage()];
            return ['success' => false, 'message' => 'Unable to connect to Payscribe service provider.'];
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            $loggedHeaders = $requestHeaders;
            $loggedHeaders['Authorization'] = 'Bearer ***';
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'cable_validate',
                'provider'         => 'payscribe',
                'reference'        => $smartcard,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $loggedHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $resHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }
    }

    private function validateCardVtpass($provider, $smartcard): array
    {
        $start    = hrtime(true);
        $baseUrl  = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
        $endpoint = $baseUrl . '/api/merchant-verify';
        $payload  = [
            'serviceID'   => $provider->slug,
            'billersCode' => $smartcard,
        ];
        $apiKey    = config('services.vtpass.api_key') ?: AppSetting::get('vtpass_api_key') ?: AppSetting::get('vtpass_public_key');
        $secretKey = config('services.vtpass.secret_key') ?: AppSetting::get('vtpass_secret_key');
        $headers   = ['api-key' => $apiKey, 'secret-key' => $secretKey];
        $data       = [];
        $httpStatus = null;
        $resHeaders = null;
        $success    = false;

        try {
            $res        = Http::withHeaders($headers)->timeout(20)->post($endpoint, $payload);
            $httpStatus = $res->status();
            $resHeaders = $res->headers();
            $data       = $res->json() ?? [];
            $code       = $data['code'] ?? '';
            $content    = $data['content'] ?? [];

            $hasError          = !empty($content['error']);
            $isWrongBillerCode = isset($content['WrongBillersCode']) && ($content['WrongBillersCode'] === true || $content['WrongBillersCode'] === 'true');
            $customerName      = $content['Customer_Name'] ?? $content['name'] ?? $content['customer_name'] ?? null;

            if ($code === '000' && !$hasError && !$isWrongBillerCode && !empty($customerName)) {
                $success = true;
                return [
                    'success'       => true,
                    'customer_name' => $customerName,
                ];
            }

            $errorMessage = $content['error'] ?? $data['response_description'] ?? 'Smartcard validation failed. Please check the IUC/Smartcard number.';
            return ['success' => false, 'message' => $errorMessage];
        } catch (\Throwable $e) {
            $data = ['error' => $e->getMessage()];
            return ['success' => false, 'message' => $e->getMessage()];
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'cable_validate',
                'provider'         => 'vtpass',
                'reference'        => $smartcard,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $headers,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $resHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
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
                $apiKey = \App\Services\PayscribePaymentService::getApiKey();
                if (!$apiKey) {
                    return [
                        'success'   => false,
                        'reference' => $reference,
                        'message'   => 'Payscribe API key is not configured. Please enter your Public Key / API Token in Admin Settings -> API Keys.',
                        'response'  => ['message' => 'Payscribe API key is not configured.'],
                    ];
                }

                $endpoint = rtrim(config('services.payscribe.base_url', 'https://api.payscribe.ng/api/v1'), '/') . '/multichoice/vend';
                $payload  = [
                    'service'       => $provider->idForApi('payscribe'),
                    'account'       => $smartcard,
                    'plan_id'       => $plan->idForApi('payscribe'),
                    'customer_name' => auth()->user()?->name ?? 'Customer',
                    'ref'           => $reference,
                ];
                $requestHeaders = [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ];
                $resHeaders = null;
                $res  = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
                $httpStatus = $res->status();
                $resHeaders = $res->headers();
                $data       = $res->json() ?? [];
                $rawStatus  = $data['status'] ?? null;
                $statusCode = $data['status_code'] ?? '';
                $success    = $res->successful() && (
                    $rawStatus === true ||
                    $rawStatus === 'true' ||
                    $rawStatus === 'success' ||
                    in_array($statusCode, ['200', '201', 200, 201]) ||
                    (($data['message']['status'] ?? null) === 'success')
                );
                $duration = (int) ((hrtime(true) - $start) / 1e6);

                $details = $data['message']['details'] ?? ($data['details'] ?? ($data['data'] ?? []));
                $apiRef  = $details['transid'] ?? ($details['transaction_id'] ?? ($data['reference'] ?? ($data['message']['ref'] ?? ($data['transid'] ?? $reference))));

                $errMsg = null;
                if (!$success) {
                    if (is_array($data['message'] ?? null)) {
                        $errMsg = $data['message']['description'] ?? ($data['message']['message'] ?? ($data['description'] ?? 'Payscribe cable subscription failed.'));
                    } else {
                        $errMsg = $data['description'] ?? ($data['message'] ?? 'Payscribe cable subscription failed.');
                    }
                }

                $loggedHeaders = $requestHeaders;
                $loggedHeaders['Authorization'] = 'Bearer ***';
                ApiLog::record([
                    'user_id'          => auth()->id(),
                    'service'          => 'cable',
                    'provider'         => 'payscribe',
                    'reference'        => $reference,
                    'endpoint'         => $endpoint,
                    'method'           => 'POST',
                    'payload'          => $payload,
                    'request_headers'  => $loggedHeaders,
                    'response'         => $data,
                    'http_status'      => $httpStatus,
                    'response_headers' => $resHeaders,
                    'duration_ms'      => $duration,
                    'success'          => $success,
                ]);

                return [
                    'success'   => $success,
                    'reference' => $apiRef,
                    'message'   => $errMsg,
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
            // if ($publicKey) {
            //     $headers['public-key'] = $publicKey;
            // }

            $res     = Http::withHeaders($headers)->timeout(30)->post($endpoint, $payload);
            $data    = $res->json() ?? [];
            $code    = $data['code'] ?? '';
            $content = $data['content'] ?? [];
            $success = in_array($code, ['000', '099'])
                && empty($content['error'])
                && !(isset($content['WrongBillersCode']) && ($content['WrongBillersCode'] === true || $content['WrongBillersCode'] === 'true'));
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
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'cable',
                'provider'    => $api ?? 'cable',
                'reference'   => $reference,
                'endpoint'    => $endpoint ?? 'N/A',
                'method'      => 'POST',
                'payload'     => ['provider' => $provider->slug, 'smartcard' => $smartcard, 'plan' => $plan->name],
                'response'    => ['error' => $e->getMessage()],
                'duration_ms' => $duration,
                'success'     => false,
            ]);

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

            if ($api === 'payscribe') {
                $apiKey = \App\Services\PayscribePaymentService::getApiKey();
                if (!$apiKey) {
                    return [
                        'success'   => false,
                        'reference' => $reference,
                        'tokens'    => [],
                        'pins'      => [],
                        'message'   => 'Payscribe API key is not configured. Please enter your Public Key / API Token in Admin Settings -> API Keys.',
                    ];
                }

                $serviceCode = method_exists($examType, 'idForApi') ? $examType->idForApi('payscribe') : ($examType->payscribe_service_id ?? $examType->slug);
                $endpoint    = rtrim(config('services.payscribe.base_url', 'https://api.payscribe.ng/api/v1'), '/') . '/education/vend';
                $payload     = [
                    'service'   => $serviceCode,
                    'quantity'  => $quantity,
                    'recipient' => $phone,
                    'phone'     => $phone,
                    'ref'       => $reference,
                ];

                $requestHeaders = [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ];

                $response        = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
                $httpStatus      = $response->status();
                $responseHeaders = $response->headers();
                $data            = $response->json() ?? [];
                $rawStatus       = $data['status'] ?? null;
                $success         = $response->successful() && (
                    $rawStatus === true ||
                    $rawStatus === 'true' ||
                    $rawStatus === 'success' ||
                    $rawStatus === 'process' ||
                    (($data['message']['status'] ?? null) === 'success')
                );

                $apiRef = $data['data']['trans_id']
                    ?? $data['data']['reference']
                    ?? $data['data']['transaction_id']
                    ?? $data['details']['transid']
                    ?? $data['details']['transaction_id']
                    ?? $data['message']['details']['transid']
                    ?? $data['message']['transaction_id']
                    ?? $data['transaction_id']
                    ?? $data['transid']
                    ?? $reference;

                $pins = [];
                if ($success) {
                    $pins = $this->extractPayscribeExamPins($data);
                } else {
                    $errMsg = is_array($data['message'] ?? null)
                        ? ($data['message']['description'] ?? $data['message']['message'] ?? $data['description'] ?? 'Payscribe exam pin purchase failed.')
                        : ($data['description'] ?? $data['message'] ?? 'Payscribe exam pin purchase failed.');
                    $data['message'] = $errMsg;
                }

                $duration = (int) ((hrtime(true) - $start) / 1e6);
                $loggedHeaders = $requestHeaders;
                $loggedHeaders['Authorization'] = 'Bearer ***';
                ApiLog::record([
                    'user_id'          => auth()->id(),
                    'service'          => 'epin',
                    'provider'         => 'payscribe',
                    'reference'        => $reference,
                    'endpoint'         => $endpoint,
                    'method'           => 'POST',
                    'payload'          => $payload,
                    'request_headers'  => $loggedHeaders,
                    'response'         => $data,
                    'http_status'      => $httpStatus,
                    'response_headers' => $responseHeaders,
                    'duration_ms'      => $duration,
                    'success'          => $success,
                ]);

                return [
                    'success'   => $success,
                    'reference' => $apiRef,
                    'tokens'    => $pins,
                    'pins'      => $pins,
                    'message'   => $data['message'] ?? ($success ? 'Success' : 'Exam pin purchase failed.'),
                    'response'  => $data,
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
            // if ($publicKey) {
            //     $headers['public-key'] = $publicKey;
            // }

            $res     = Http::withHeaders($headers)->timeout(30)->post($endpoint, $payload);
            $data    = $res->json() ?? [];
            $code    = $data['code'] ?? '';
            $content = $data['content'] ?? [];
            $success = in_array($code, ['000', '099'])
                && empty($content['error'])
                && !(isset($content['WrongBillersCode']) && ($content['WrongBillersCode'] === true || $content['WrongBillersCode'] === 'true'));
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
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'epin',
                'provider'    => $api ?? 'epin',
                'reference'   => $reference,
                'endpoint'    => $endpoint ?? 'N/A',
                'method'      => 'POST',
                'payload'     => ['exam_type' => $examType->code ?? $examType->name ?? 'epin', 'quantity' => $quantity, 'phone' => $phone],
                'response'    => ['error' => $e->getMessage()],
                'duration_ms' => $duration,
                'success'     => false,
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Extract PINs from Payscribe data structure.
     */
    private function extractPayscribeExamPins(array $data): array
    {
        $pins = [];
        $candidates = $data['cards']
            ?? $data['data']['cards']
            ?? $data['tokens']
            ?? $data['data']['tokens']
            ?? $data['pins']
            ?? $data['data']['pins']
            ?? $data['details']['cards']
            ?? $data['details']['tokens']
            ?? [];

        if (is_array($candidates) && !empty($candidates)) {
            foreach ($candidates as $item) {
                if (is_array($item)) {
                    $pin    = $item['pin'] ?? $item['Pin'] ?? $item['token'] ?? $item['code'] ?? '';
                    $serial = $item['serial'] ?? $item['Serial'] ?? $item['serialnumber'] ?? $item['serial_no'] ?? null;
                    if ($pin !== '') {
                        $pins[] = ['pin' => (string) $pin, 'serial' => $serial ? (string) $serial : null];
                    }
                } elseif (is_string($item) && trim($item) !== '') {
                    if (str_contains($item, '<=>')) {
                        [$p, $s] = explode('<=>', $item, 2);
                        $pins[] = ['pin' => trim($p), 'serial' => trim($s)];
                    } else {
                        $pins[] = ['pin' => trim($item), 'serial' => null];
                    }
                }
            }
        }

        if (empty($pins)) {
            $pool = is_array($data['data'] ?? null) ? array_merge($data, $data['data']) : $data;
            foreach ($pool as $k => $v) {
                if (preg_match('/^(pin|token)\d*$/i', (string) $k) && is_string($v) && trim($v) !== '') {
                    if (str_contains($v, '<=>')) {
                        [$p, $s] = explode('<=>', $v, 2);
                        $pins[] = ['pin' => trim($p), 'serial' => trim($s)];
                    } else {
                        $pins[] = ['pin' => trim($v), 'serial' => null];
                    }
                }
            }
        }

        return $pins;
    }
}

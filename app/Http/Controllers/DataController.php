<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\DataPlan;
use App\Models\NetworkAirtime;
use App\Models\ServiceTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DataController extends Controller
{
    // VTpass serviceID per network key
    private array $vtpassServiceIds = [
        'mtn'      => 'mtn-data',
        'glo'      => 'glo-data',
        'airtel'   => 'airtel-data',
        'etisalat' => 'etisalat-data',
    ];

    // Autopilot network IDs for data purchases
    private array $autopilotNetworkIds = [
        'mtn'      => 1,
        'glo'      => 3,
        'airtel'   => 2,
        'etisalat' => 4,
    ];

    // Human-readable type labels
    private array $typeLabels = [
        'cheap_data' => 'Cheap Data',
        'sme'        => 'SME',
        'gifting'    => 'Gifting',
        'cg'         => 'Corporate Gifting',
        'awoof'      => 'AWOOF',
    ];

    // ─── Pages ────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $user     = auth()->user();
        $networks = NetworkAirtime::active()->get();

        // Build enabled data types per network from settings
        $allTypes   = array_keys($this->typeLabels);
        $networkKeys = $networks->pluck('network_key');
        $dataTypes  = [];

        foreach ($networkKeys as $nk) {
            $dataTypes[$nk] = [];
            foreach ($allTypes as $type) {
                if (AppSetting::get("data_type_{$nk}_{$type}", '0') === '1') {
                    $dataTypes[$nk][] = $type;
                }
            }
        }

        $history = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'data')
            ->latest()
            ->paginate(10);

        $discounts = $this->getNetworkDiscounts($user);

        return view('services.data', compact(
            'user', 'networks', 'dataTypes', 'history', 'discounts'
        ));
    }

    // ─── AJAX: load plans ─────────────────────────────────────────────────────

    public function getPlans(Request $request): JsonResponse
    {
        $request->validate([
            'network_key' => ['required', 'string'],
            'data_type'   => ['required', 'string'],
        ]);

        $networkKey = $request->network_key;
        $dataType   = $request->data_type;
        $api        = AppSetting::get('data_api_' . $networkKey, 'autopilot');
        $user       = auth()->user();
        $isAgent    = $user->isAgent();

        $plans = DataPlan::active()
            ->forNetwork($networkKey)
            ->forType($dataType)
            ->forApi($api)
            ->get()
            ->map(fn (DataPlan $p) => [
                'id'       => $p->id,
                'name'     => $p->plan_name,
                'validity' => $p->validity,
                'amount'   => $p->priceFor($user),
            ]);

        return response()->json(['success' => true, 'plans' => $plans]);
    }

    // ─── Purchase ─────────────────────────────────────────────────────────────

    public function purchase(Request $request): JsonResponse
    {
        if (!AppSetting::get('service_data', '1')) {
            return response()->json(['success' => false, 'message' => 'Data service is temporarily unavailable.'], 503);
        }

        $validNetworkKeys = NetworkAirtime::where('enabled', true)->pluck('network_key')->implode(',');

        $request->validate([
            'network_key'     => ['required', 'string', 'in:' . $validNetworkKeys],
            'data_type'       => ['required', 'string'],
            'plan_id'         => ['required', 'integer', 'exists:data_plans,id'],
            'phone'           => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user    = auth()->user();
        $network = NetworkAirtime::where('network_key', $request->network_key)
            ->where('enabled', true)->firstOrFail();

        // Verify transaction PIN
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json([
                'success'   => false,
                'message'   => 'Incorrect transaction PIN. Please try again.',
                'pin_error' => true,
            ], 422);
        }

        $api = AppSetting::get('data_api_' . $request->network_key, 'autopilot');

        // Load plan (must belong to network/type, be active, and have an ID for the active API)
        $plan = DataPlan::active()
            ->forNetwork($request->network_key)
            ->forType($request->data_type)
            ->forApi($api)
            ->find($request->plan_id);

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Selected plan is not available.'], 422);
        }

        $phone       = preg_replace('/^\+234/', '0', $request->phone);
        $finalAmount = $this->calculateFinalAmount($plan->priceFor($user), $request->network_key, $user);

        // Daily limit check
        $dailyLimit = (float) AppSetting::get('data_daily_limit', 100000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'data')
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $finalAmount) > $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => 'Daily data spending limit of ₦' . number_format($dailyLimit, 0) . ' reached. Try again tomorrow.',
            ], 422);
        }

        // Wallet balance check
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($finalAmount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance. Please fund your wallet.',
            ], 422);
        }

        // Debit wallet
        $reference = 'DAT' . date('YmdHis') . Str::upper(Str::random(10));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($wallet, $finalAmount, $network, $plan, $phone, $reference) {
                return $wallet->debit(
                    $finalAmount,
                    $network->name . ' Data - ' . $plan->plan_name . ' - ' . $phone,
                    $reference,
                    ['service' => 'data', 'network' => $network->network_key, 'phone' => $phone, 'plan' => $plan->plan_name]
                );
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // Call API
        ['success' => $apiSuccess, 'reference' => $apiRef, 'response' => $apiResponse]
            = $this->callDataApi($network, $plan, $phone, $reference, $api);

        // Refund on failure - no transaction record saved
        if (!$apiSuccess) {
            try {
                DB::transaction(function () use ($wallet, $finalAmount, $reference, $network, $phone, $plan) {
                    $wallet->credit(
                        $finalAmount,
                        'Refund: ' . $network->name . ' Data failed - ' . $phone,
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Data refund failed', [
                    'user_id'   => $user->id,
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success'  => false,
                'refunded' => true,
                'message'  => $apiResponse['message'] ?? 'Data delivery failed.',
            ], 422);
        }

        // Save successful transaction only
        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'data',
            'provider'              => $network->network_key,
            'recipient'             => $phone,
            'amount'                => $finalAmount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRef,
            'api_response'          => array_merge(
                is_array($apiResponse) ? $apiResponse : ['raw' => $apiResponse],
                ['api_provider' => $api, 'plan' => $plan->plan_name, 'data_type' => $plan->data_type]
            ),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => $plan->plan_name . ' ' . $network->name . ' data sent to ' . $phone . ' successfully.',
            'balance'   => '₦' . number_format((float) $wallet->fresh()->balance, 2),
            'reference' => $reference,
        ]);
    }

    // ─── Pricing Helpers ──────────────────────────────────────────────────────

    private function getNetworkDiscounts(\App\Models\User $user): array
    {
        $isAgent = $user->isAgent();
        $prefix  = $isAgent ? 'data_agent_off_percentage_' : 'data_off_percentage_';

        $keys   = ['mtn', 'glo', 'airtel', 'etisalat'];
        $result = [];

        foreach ($keys as $nk) {
            $result[$nk] = (float) AppSetting::get($prefix . $nk, 0);
        }

        return $result;
    }

    private function calculateFinalAmount(float $amount, string $networkKey, \App\Models\User $user): float
    {
        $isAgent    = $user->isAgent();
        $prefix     = $isAgent ? 'data_agent_off_percentage_' : 'data_off_percentage_';
        $offPercent = (float) AppSetting::get($prefix . $networkKey, 0);

        return round($amount * (100 - $offPercent) / 100, 2);
    }

    // ─── API Dispatcher ───────────────────────────────────────────────────────

    private function callDataApi(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference, string $api): array
    {
        return match ($api) {
            'clubkonnect'  => $this->callClubkonnectData($network, $plan, $phone, $reference),
            'autopilot'    => $this->callAutopilotData($network, $plan, $phone, $reference),
            'merrybills'   => $this->callMerrybillsData($network, $plan, $phone, $reference),
            'easyaccess'   => $this->callEasyaccessData($network, $plan, $phone, $reference),
            'aabaxztech'   => $this->callAabaxyztechData($network, $plan, $phone, $reference),
            'legitdataway' => $this->callLegitdatawayData($network, $plan, $phone, $reference),
            'globacom'     => $this->callGlobacomData($network, $plan, $phone, $reference),
            default        => $this->callVtpassData($network, $plan, $phone, $reference),
        };
    }

    // ─── VTpass ───────────────────────────────────────────────────────────────

    private function callVtpassData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $vtpassRef  = date('YmdHis') . Str::upper(Str::random(6));
        $endpoint   = config('services.vtpass.base_url') . '/api/pay';
        $serviceId  = $this->vtpassServiceIds[$network->network_key] ?? ($network->network_key . '-data');
        $payload    = [
            'request_id'     => $vtpassRef,
            'serviceID'      => $serviceId,
            'billersCode'    => $phone,
            'variation_code' => $plan->vtpass_id,
            'amount'         => $plan->amount,
            'phone'          => $phone,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $vtpassRef;

        $requestHeaders = [
            'api-key'    => config('services.vtpass.api_key'),
            'public-key' => config('services.vtpass.public_key'),
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $raw        = $response->json();
            $data       = is_array($raw) ? $raw : ['message' => is_string($raw) ? $raw : 'Unknown VTpass response'];
            $code       = $data['code'] ?? '';
            $apiRef     = $data['content']['transactions']['transactionId'] ?? $data['requestId'] ?? $vtpassRef;
            $success    = in_array($code, ['000', '099']);
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('VTpass data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'data',
                'provider'         => 'vtpass',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    // ─── Clubkonnect ──────────────────────────────────────────────────────────

    private function callClubkonnectData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint   = 'https://www.nellobytesystems.com/APIDataV1.asp';
        $payload    = [
            'UserID'        => config('services.clubkonnect.user_id'),
            'APIKey'        => config('services.clubkonnect.api_key'),
            'MobileNetwork' => $network->clubkonnect_id,
            'DataPlan'      => $plan->clubkonnect_id,
            'MobileNumber'  => $phone,
            'RequestID'     => $reference,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders  = [];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response        = Http::timeout(30)->get($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data       = $response->json() ?? [];
            $status     = $data['status'] ?? '';
            $apiRef     = $data['orderid'] ?? $reference;
            $success    = in_array($status, ['ORDER_RECEIVED', 'ORDER_COMPLETED']);
            if (!$success) {
                $data['message'] = $data['statusremark'] ?? $data['orderremark'] ?? 'Transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Clubkonnect data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'data',
                'provider'         => 'clubkonnect',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'GET',
                'payload'          => $payload,
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    // ─── Autopilot ────────────────────────────────────────────────────────────

    private function callAutopilotData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint  = config('services.autopilot.base_url') . '/data';
        $networkId = (string) ($this->autopilotNetworkIds[$network->network_key] ?? 1);
        $payload   = [
            'networkId'   => $networkId,
            'dataType'  => $plan->data_type,
            'planId'    => $plan->autopilot_id,
            'phone'     => $phone,
            'reference'    => $reference,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.autopilot.api_key'),
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data       = $response->json() ?? [];
            $success    = ($data['status'] ?? false) === true && ($data['code'] ?? 0) === 200;
            $apiRef     = $data['data']['reference'] ?? $reference;
            if (!$success) {
                $data['message'] = $data['message'] ?? ($data['data']['message'] ?? 'Transaction failed.');
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Autopilot data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'data',
                'provider'         => 'autopilot',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    // ─── Merrybills ───────────────────────────────────────────────────────────

    private function callMerrybillsData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint  = config('services.merrybills.base_url') . '/data';
        $payload   = [
            'request_id' => $reference,
            'product_id' => $plan->merrybills_product_id,
            'val_id'     => $plan->merrybills_id,
            'phone'      => $phone,
            'pin'        => config('services.merrybills.pin'),
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.merrybills.token'),
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data       = $response->json() ?? [];
            $success    = ($data['status'] ?? false) === true;
            $apiRef     = $data['ref'] ?? $data['data']['ref'] ?? $reference;
            if (!$success) {
                $data['message'] = $data['message'] ?? 'Transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Merrybills data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'data',
                'provider'         => 'merrybills',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    // ─── Easyaccess ───────────────────────────────────────────────────────────

    private function callEasyaccessData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint  = config('services.easyaccess.base_url') . '/purchase-data';
        $payload   = [
            'network'          => $network->easyaccess_id,
            'dataplan'         => $plan->easyaccess_id,
            'mobileno'         => $phone,
            'client_reference' => $reference,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.easyaccess.token'),
            'Cache-Control' => 'no-cache',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->asForm()->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data       = $response->json() ?? [];
            $success    = isset($data['success']) && (string) $data['success'] === 'true';
            if (!$success) {
                $data['message'] = $data['message'] ?? $data['error'] ?? 'Easyaccess transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Easyaccess data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'data',
                'provider'         => 'easyaccess',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    // ─── Aabaxyztech ─────────────────────────────────────────────────────────

    private function callAabaxyztechData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint  = config('services.aabaxztech.base_url') . '/data';
        $payload   = [
            'network'    => (int) $network->aabaxztech_id,
            'phone'      => $phone,
            'data_plan'  => $plan->aabaxztech_id,
            'bypass'     => true,
            'request-id' => $reference,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => config('services.aabaxztech.token'),
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data       = $response->json() ?? [];
            $status     = $data['status'] ?? '';
            // 'process' means the order is queued but will be delivered - treat as success
            $success    = in_array($status, ['success', 'process'], true);
            if (!$success) {
                $errMsg = $data['message'] ?? 'Aabaxyztech transaction failed.';
                if (str_contains(strtolower((string) $errMsg), 'insufficient')) {
                    $errMsg = 'Insufficient balance on provider. Please try another network.';
                }
                $data['message'] = $errMsg;
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Aabaxyztech data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'data',
                'provider'         => 'aabaxztech',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    // ─── Legitdataway ─────────────────────────────────────────────────────────

    private function callLegitdatawayData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint  = config('services.legitdataway.base_url') . '/data';
        $payload   = [
            'network'    => (int) $network->legitdataway_id,
            'phone'      => $phone,
            'data_plan'  => $plan->legitdataway_id,
            'bypass'     => true,
            'request-id' => $reference,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => 'Token ' . config('services.legitdataway.token'),
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data       = $response->json() ?? [];
            $status     = $data['status'] ?? '';
            // 'process' means queued - treat as success (wallet already debited)
            $success    = in_array($status, ['success', 'process'], true);
            if (!$success) {
                $data['message'] = $data['message'] ?? 'Legitdataway transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Legitdataway data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'data',
                'provider'         => 'legitdataway',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    // ─── Globacom (Gift API) ──────────────────────────────────────────────────
    // Endpoint: https://gift-api.gloworld.com/
    // Auth: x-api-key header
    // Body: transId, msisdn, bucketId, planId, sponsorId, quantity, ignoresms
    // Success: status === 'ok'

    private function callGlobacomData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $trx_ref = "TRX".time();
        $endpoint  = rtrim(config('services.globacom.base_url'), '/') . '/';
        $payload   = [
            'transId'    => $trx_ref,
            'msisdn'     => preg_replace('/^0/', '234', $phone), // Globacom expects 234XXXXXXXXXX
            'bucketId'   => (int) config('services.globacom.bucket_id'),
            'planId'     => (int) $plan->idForApi('globacom'),
            'sponsorId'  => config('services.globacom.sponsor_id'),
            'quantity'   => 1,
            'ignoresms'  => false,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $trx_ref;

        $requestHeaders = [
            'x-api-key'    => config('services.globacom.x_api_key'),
            'Content-Type' => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data       = $response->json() ?? [];
            $status     = $data['status'] ?? '';
            $success    = $status === 'ok';
            $apiRef     = $data['egmstransId'] ?? $data['transId'] ?? $trx_ref;
            if (!$success) {
                $data['message'] = $data['message'] ?? 'Globacom transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Globacom data request failed', ['reference' => $trx_ref, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'data',
                'provider'         => 'globacom',
                'reference'        => $trx_ref,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }
}

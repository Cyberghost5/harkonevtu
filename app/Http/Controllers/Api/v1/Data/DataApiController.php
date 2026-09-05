<?php

namespace App\Http\Controllers\Api\v1\Data;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\DataPlan;
use App\Models\NetworkAirtime;
use App\Models\ServiceTransaction;
use App\Models\Wallet;
use App\Services\GloErsSoapService;
use App\Services\MtnErsSoapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataApiController extends Controller
{
    private array $vtpassServiceIds = [
        'mtn'      => 'mtn-data',
        'glo'      => 'glo-data',
        'airtel'   => 'airtel-data',
        'etisalat' => 'etisalat-data',
    ];

    private array $autopilotNetworkIds = [
        'mtn'      => 1,
        'glo'      => 3,
        'airtel'   => 2,
        'etisalat' => 4,
    ];

    private array $typeLabels = [
        'cheap_data' => 'Cheap Data',
        'sme'        => 'SME',
        'gifting'    => 'Gifting',
        'cg'         => 'Corporate Gifting',
        'awoof'      => 'Awoof',
    ];

    /**
     * List enabled data networks and available data types.
     */
    public function networks(Request $request): JsonResponse
    {
        if (AppSetting::get('service_data', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Data service is temporarily unavailable.',
            ], 503);
        }

        $user     = auth()->user();
        $networks = NetworkAirtime::active()->get();
        $allTypes = array_keys($this->typeLabels);

        $networkList = $networks->map(function (NetworkAirtime $net) use ($allTypes) {
            $key = $net->network_key;
            $enabledTypes = [];

            foreach ($allTypes as $type) {
                if (AppSetting::get("data_type_{$key}_{$type}", '1') === '1') {
                    $enabledTypes[] = [
                        'type_key' => $type,
                        'name'     => $this->typeLabels[$type] ?? strtoupper($type),
                    ];
                }
            }

            return [
                'id'                  => $net->id,
                'name'                => $net->name,
                'network_key'         => $key,
                'available_data_types' => $enabledTypes,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Data networks and types retrieved successfully.',
            'data'    => [
                'user_tier' => $user ? ($user->isAgent() ? 'Agent' : 'User') : 'User',
                'networks'  => $networkList,
            ],
        ]);
    }

    /**
     * Fetch active data plans for network and optional data_type, sorted lowest to highest price.
     */
    public function plans(Request $request): JsonResponse
    {
        if (AppSetting::get('service_data', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Data service is temporarily unavailable.',
            ], 503);
        }

        $validNetworkKeys = NetworkAirtime::where('enabled', true)->pluck('network_key')->toArray();

        $request->validate([
            'network'   => ['required', 'string', 'in:' . implode(',', $validNetworkKeys)],
            'data_type' => ['nullable', 'string'],
        ]);

        $networkKey = $request->network;
        $dataType   = $request->data_type;
        $api        = AppSetting::get('data_api_' . $networkKey, 'autopilot');

        if ($api === 'mtn_ers' && $networkKey !== 'mtn') {
            $api = 'autopilot';
        }

        $user = auth()->user();

        $query = DataPlan::active()
            ->forNetwork($networkKey)
            ->forApi($api);

        if (!empty($dataType)) {
            $query->forType($dataType);
        }

        $plans = $query->get()
            ->sortBy(fn (DataPlan $p) => $p->priceFor($user))
            ->values()
            ->map(fn (DataPlan $p) => [
                'id'             => $p->id,
                'plan_name'      => $p->plan_name,
                'network_key'    => $p->network_key,
                'data_type'      => $p->data_type,
                'type_label'     => $this->typeLabels[$p->data_type] ?? strtoupper($p->data_type),
                'size'           => $p->size,
                'validity'       => $p->validity,
                'price'          => (float) $p->priceFor($user),
                'regular_price'  => (float) $p->amount,
                'agent_price'    => (float) ($p->agent_amount ?? $p->amount),
            ]);

        return response()->json([
            'status'  => true,
            'message' => 'Data plans retrieved successfully.',
            'data'    => [
                'network'     => $networkKey,
                'data_type'   => $dataType,
                'total_plans' => count($plans),
                'plans'       => $plans,
            ],
        ]);
    }

    /**
     * Execute data bundle purchase transaction.
     */
    public function purchase(Request $request): JsonResponse
    {
        if (AppSetting::get('service_data', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Data service is temporarily unavailable.',
            ], 503);
        }

        $request->validate([
            'phone'           => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'plan_id'         => ['required', 'integer', 'exists:data_plans,id'],
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

        // 1. Verify transaction PIN
        if (!$user->verifyPin($providedPin)) {
            return response()->json([
                'status'  => false,
                'message' => 'Incorrect transaction PIN. Please try again.',
                'errors'  => ['pin' => ['Incorrect transaction PIN.']],
            ], 422);
        }

        $plan = DataPlan::where('id', $request->plan_id)->where('enabled', true)->first();
        if (!$plan) {
            return response()->json([
                'status'  => false,
                'message' => 'Selected data plan is unavailable or disabled.',
            ], 422);
        }

        $networkKey = $plan->network_key;
        $network    = NetworkAirtime::where('network_key', $networkKey)->where('enabled', true)->first();
        if (!$network) {
            return response()->json([
                'status'  => false,
                'message' => 'Network provider is disabled.',
            ], 422);
        }

        $phone       = preg_replace('/^\+234/', '0', $request->phone);
        $finalPrice  = (float) $plan->priceFor($user);
        $api         = AppSetting::get('data_api_' . $networkKey, 'autopilot');

        if ($api === 'mtn_ers' && $networkKey !== 'mtn') {
            $api = 'autopilot';
        }

        // 2. Daily spending limit check
        $dailyLimit = (float) AppSetting::get('data_daily_limit', 100000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'data')
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $finalPrice) > $dailyLimit) {
            return response()->json([
                'status'  => false,
                'message' => 'Daily data spending limit of ₦' . number_format($dailyLimit, 0) . ' reached. Try again tomorrow.',
            ], 422);
        }

        // 3. Wallet balance check
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($finalPrice)) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient wallet balance. You require ₦' . number_format($finalPrice, 2) . ' to complete this transaction.',
                'errors'  => ['balance' => ['Insufficient wallet balance.']],
            ], 422);
        }

        // 4. Debit wallet in DB transaction
        $reference = 'DAT' . date('YmdHis') . Str::upper(Str::random(8));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($user, $finalPrice, $network, $plan, $phone, $reference) {
                $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                if (!$w->hasSufficientBalance($finalPrice)) {
                    throw new \Exception('Insufficient wallet balance. Please fund your wallet.');
                }
                return $w->debit(
                    $finalPrice,
                    $network->name . ' Data - ' . $plan->plan_name . ' - ' . $phone,
                    $reference,
                    ['service' => 'data', 'network' => $network->network_key, 'phone' => $phone, 'plan' => $plan->plan_name]
                );
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        // 5. Dispatch API call to configured VTU gateway
        ['success' => $apiSuccess, 'reference' => $apiRef, 'response' => $apiResponse]
            = $this->callDataGateway($network, $plan, $phone, $reference, $api);

        // 6. Handle refund on VTU failure
        if (!$apiSuccess) {
            try {
                DB::transaction(function () use ($user, $finalPrice, $reference, $network, $phone) {
                    $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    $w->credit(
                        $finalPrice,
                        'Refund: ' . $network->name . ' Data failed - ' . $phone,
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Data API refund failed', [
                    'user_id'   => $user->id,
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }

            return response()->json([
                'status'   => false,
                'refunded' => true,
                'message'  => $apiResponse['message'] ?? 'Data bundle delivery failed. Your wallet balance has been refunded.',
            ], 422);
        }

        // 7. Record transaction log on clean success
        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'data',
            'provider'              => $network->network_key,
            'recipient'             => $phone,
            'amount'                => $finalPrice,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRef,
            'api_response'          => array_merge(is_array($apiResponse) ? $apiResponse : ['raw' => $apiResponse], ['api_provider' => $api]),
        ]);

        $freshWallet = $user->fresh()->wallet;

        return response()->json([
            'status'  => true,
            'message' => $network->name . ' ' . $plan->plan_name . ' sent to ' . $phone . ' successfully.',
            'data'    => [
                'reference'     => $reference,
                'api_reference' => $apiRef,
                'network'       => $network->name,
                'network_key'   => $networkKey,
                'recipient'     => $phone,
                'plan_name'     => $plan->plan_name,
                'validity'      => $plan->validity,
                'amount_paid'   => $finalPrice,
                'balance_after' => (float) ($freshWallet ? $freshWallet->balance : 0),
                'date'          => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Dispatch Data bundle call to gateway provider.
     */
    private function callDataGateway(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference, string $api): array
    {
        return match ($api) {
            'clubkonnect'  => $this->callClubkonnectData($network, $plan, $phone, $reference),
            'autopilot'    => $this->callAutopilotData($network, $plan, $phone, $reference),
            'merrybills'   => $this->callMerrybillsData($network, $plan, $phone, $reference),
            'easyaccess'   => $this->callEasyaccessData($network, $plan, $phone, $reference),
            'aabaxztech'   => $this->callAabaxyztechData($network, $plan, $phone, $reference),
            'legitdataway' => $this->callLegitdatawayData($network, $plan, $phone, $reference),
            'globacom'     => $this->callGlobacomData($network, $plan, $phone, $reference),
            'mtn_ers'      => $this->callMtnErsData($network, $plan, $phone, $reference),
            'glo_ers'      => $this->callGloErsData($network, $plan, $phone, $reference),
            default        => $this->callVtpassData($network, $plan, $phone, $reference),
        };
    }

    private function callGloErsData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $ersService = app(GloErsSoapService::class);
        $productId  = $plan->idForApi('glo_ers');

        $result = $ersService->vendData($phone, (float) $plan->amount, $productId, $reference);

        $success = $result['success'] ?? false;
        $data    = $result['response'] ?? ['message' => $result['message'] ?? 'Failed to communicate with Glo ERS SOAP Gateway'];
        $apiRef  = $result['reference'] ?? $reference;

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    private function callMtnErsData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $ersService = app(MtnErsSoapService::class);

        $formattedPhone = $phone;
        if (str_starts_with($phone, '234') && strlen($phone) === 13) {
            $formattedPhone = '0' . substr($phone, 3);
        }

        $tariffTypeId = $plan->mtn_ers_id;

        $result = $ersService->vend($formattedPhone, $plan->mtn_ers_cis_id, $tariffTypeId);

        $success = $result['status'] ?? false;
        $data    = $result['data'] ?? ['message' => $result['message'] ?? 'MTN ERS transaction failed'];
        $apiRef  = $data['txRefId'] ?? $reference;

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    private function callVtpassData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $vtpassRef  = date('YmdHis') . Str::upper(Str::random(6));
        $baseUrl    = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
        $endpoint   = $baseUrl . '/api/pay';
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

        $apiKey    = config('services.vtpass.api_key') ?: AppSetting::get('vtpass_api_key') ?: AppSetting::get('vtpass_public_key');
        $secretKey = config('services.vtpass.secret_key') ?: AppSetting::get('vtpass_secret_key');
        $publicKey = config('services.vtpass.public_key') ?: AppSetting::get('vtpass_public_key') ?: $apiKey;

        $requestHeaders = [
            'api-key'    => $apiKey,
            'secret-key' => $secretKey,
        ];
        if ($publicKey) {
            $requestHeaders['public-key'] = $publicKey;
        }

        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $raw             = $response->json();
            $data            = is_array($raw) ? $raw : ['message' => is_string($raw) ? $raw : 'Unknown VTpass response'];
            $code            = $data['code'] ?? '';
            $apiRef          = $data['content']['transactions']['transactionId'] ?? $data['requestId'] ?? $vtpassRef;
            $success         = in_array($code, ['000', '099']);
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('VTpass data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
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

    private function callClubkonnectData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint   = 'https://www.nellobytesystems.com/APIDataV1.asp';
        $payload    = [
            'UserID'        => config('services.clubkonnect.user_id') ?: AppSetting::get('clubkonnect_user_id'),
            'APIKey'        => config('services.clubkonnect.api_key') ?: AppSetting::get('clubkonnect_api_key'),
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
            $data            = $response->json() ?? [];
            $status          = $data['status'] ?? '';
            $apiRef          = $data['orderid'] ?? $reference;
            $success         = in_array($status, ['ORDER_RECEIVED', 'ORDER_COMPLETED']);
            if (!$success) {
                $data['message'] = $data['statusremark'] ?? $data['orderremark'] ?? 'Transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Clubkonnect data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
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

    private function callAutopilotData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint  = config('services.autopilot.base_url') . '/data';
        $networkId = (string) ($this->autopilotNetworkIds[$network->network_key] ?? 1);
        $dataType  = 'SME';
        if ($plan->data_type === 'cg') {
            $dataType = 'CORPORATE GIFTING';
        }
        if ($plan->data_type === 'gifting') {
            $dataType = 'GIFTING';
        }
        if ($plan->data_type === 'cg' && $network->network_key === 'airtel') {
            $dataType = 'DIRECT GIFTING';
        }
        if ($plan->data_type === 'awoof') {
            $dataType = 'CORPORATE GIFTING';
        }
        if ($plan->data_type === 'sme') {
            $dataType = 'SME';
        }
        $payload   = [
            'networkId' => $networkId,
            'dataType'  => $dataType,
            'planId'    => $plan->autopilot_id,
            'phone'     => $phone,
            'reference' => $reference,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . (config('services.autopilot.api_key') ?: AppSetting::get('autopilot_api_key')),
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];
            $success         = ($data['status'] ?? false) === true && ($data['code'] ?? 0) === 200;
            $apiRef          = $data['data']['reference'] ?? $reference;
            if (!$success) {
                $data['message'] = $data['message'] ?? ($data['data']['message'] ?? 'Transaction failed.');
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Autopilot data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
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

    private function callMerrybillsData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint  = config('services.merrybills.base_url') . '/data';
        $payload   = [
            'request_id' => $reference,
            'product_id' => $plan->merrybills_product_id,
            'val_id'     => $plan->merrybills_id,
            'phone'      => $phone,
            'pin'        => config('services.merrybills.pin') ?: AppSetting::get('merrybills_pin'),
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . (config('services.merrybills.token') ?: AppSetting::get('merrybills_token')),
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];
            $success         = ($data['status'] ?? false) === true;
            $apiRef          = $data['ref'] ?? $data['data']['ref'] ?? $reference;
            if (!$success) {
                $data['message'] = $data['message'] ?? 'Transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Merrybills data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
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
            'Authorization' => 'Bearer ' . (config('services.easyaccess.token') ?: AppSetting::get('easyaccess_api_key')),
            'Cache-Control' => 'no-cache',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->asForm()->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];
            $success         = ($data['code'] ?? '') === '200' || ($data['message'] ?? '') === 'Data purchase was successful' || ($data['status'] ?? '') === 'success';
            if (!$success) {
                $data['message'] = $data['message'] ?? $data['error'] ?? 'Easyaccess transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Easyaccess data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
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
            'Authorization' => config('services.aabaxztech.token') ?: AppSetting::get('aabaxztech_api_key'),
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];
            $status          = $data['status'] ?? '';
            $success         = in_array($status, ['success', 'process'], true);
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
                'user_id'          => auth()->id(),
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
            'Authorization' => 'Token ' . (config('services.legitdataway.token') ?: AppSetting::get('legitdataway_api_key')),
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];
            $status          = $data['status'] ?? '';
            $success         = in_array($status, ['success', 'process'], true);
            if (!$success) {
                $data['message'] = $data['message'] ?? 'Legitdataway transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Legitdataway data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
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

    private function callGlobacomData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $trx_ref  = "TRX" . time();
        $endpoint = rtrim(config('services.globacom.base_url'), '/') . '/';
        $payload  = [
            'transId'   => $trx_ref,
            'msisdn'    => preg_replace('/^0/', '234', $phone),
            'bucketId'  => (string) config('services.globacom.bucket_id'),
            'planId'    => (string) $plan->idForApi('globacom'),
            'sponsorId' => (string) config('services.globacom.sponsor_id'),
            'quantity'  => 1,
            'ignoresms' => false,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $trx_ref;

        $requestHeaders = [
            'x-api-key'    => config('services.globacom.x_api_key') ?: AppSetting::get('globacom_xapi_key'),
            'Content-Type' => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];
            $status          = $data['status'] ?? '';
            $success         = $status === 'ok';
            $apiRef          = $data['egmstransId'] ?? $data['transId'] ?? $trx_ref;
            if (!$success) {
                $data['message'] = $data['message'] ?? 'Globacom transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Globacom data request failed', ['reference' => $trx_ref, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
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

    /**
     * Get paginated data purchase transactions for authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $transactions = ServiceTransaction::where('user_id', $request->user()->id)
            ->where('service_type', 'data')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'status'  => true,
            'message' => 'Data transaction history retrieved successfully.',
            'data'    => $transactions,
        ]);
    }
}

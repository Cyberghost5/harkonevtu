<?php

namespace App\Http\Controllers\Api\v1\Data;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\DataPlan;
use App\Models\NetworkAirtime;
use App\Models\ServiceTransaction;
use App\Models\Wallet;
use App\Services\MtnErsSoapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataApiController extends Controller
{
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
            'mtn_ers'     => $this->callMtnErsData($network, $plan, $phone, $reference),
            'autopilot'   => $this->callAutopilotData($network, $plan, $phone, $reference),
            'easyaccess'  => $this->callEasyaccessData($network, $plan, $phone, $reference),
            'vtpass'      => $this->callVtpassData($network, $plan, $phone, $reference),
            'clubkonnect' => $this->callClubkonnectData($network, $plan, $phone, $reference),
            default       => $this->callAutopilotData($network, $plan, $phone, $reference),
        };
    }

    private function callMtnErsData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        try {
            $service = app(MtnErsSoapService::class);
            $amount = (float) ($plan->user_price ?? $plan->amount ?? 0);
            $res = $service->vend($phone, $amount, $plan->api_plan_id ?: 1);
            return [
                'success'   => $res['status'] ?? false,
                'reference' => $res['data']['txRefId'] ?? $reference,
                'response'  => $res,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }

    private function callAutopilotData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint = config('services.autopilot.base_url', 'https://autopilot.com.ng/api/v1') . '/data';
        $netIds = ['mtn' => 1, 'glo' => 3, 'airtel' => 2, 'etisalat' => 4];
        $payload = [
            'network'   => $netIds[$network->network_key] ?? 1,
            'plan'      => $plan->api_plan_id,
            'phone'     => $phone,
            'reference' => $reference,
        ];
        $start = hrtime(true);
        try {
            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . AppSetting::get('autopilot_api_key'),
            ])->post($endpoint, $payload);

            $body = $res->json() ?? [];
            $success = ($body['status'] ?? false) === true || ($body['code'] ?? '') === '00';
            $duration = (int) ((hrtime(true) - $start) / 1e6);

            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'data',
                'provider'    => 'autopilot',
                'reference'   => $reference,
                'endpoint'    => $endpoint,
                'method'      => 'POST',
                'payload'     => $payload,
                'response'    => $body,
                'http_status' => $res->status(),
                'duration_ms' => $duration,
                'success'     => $success,
            ]);

            return [
                'success'   => $success,
                'reference' => $body['reference'] ?? $reference,
                'response'  => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }

    private function callEasyaccessData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint = 'https://easyaccess.com.ng/api/data.php';
        $netIds = ['mtn' => 01, 'glo' => 02, 'airtel' => 03, 'etisalat' => 04];
        $payload = [
            'network'          => $netIds[$network->network_key] ?? 01,
            'data_plan'        => $plan->api_plan_id,
            'mobileno'         => $phone,
            'client_reference' => $reference,
        ];
        $start = hrtime(true);
        try {
            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . AppSetting::get('easyaccess_api_key'),
            ])->post($endpoint, $payload);

            $body = $res->json() ?? [];
            $success = str_contains(strtolower($body['message'] ?? ''), 'successful') || ($body['success'] ?? false) === true;
            $duration = (int) ((hrtime(true) - $start) / 1e6);

            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'data',
                'provider'    => 'easyaccess',
                'reference'   => $reference,
                'endpoint'    => $endpoint,
                'method'      => 'POST',
                'payload'     => $payload,
                'response'    => $body,
                'http_status' => $res->status(),
                'duration_ms' => $duration,
                'success'     => $success,
            ]);

            return [
                'success'   => $success,
                'reference' => $body['reference'] ?? $reference,
                'response'  => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }

    private function callVtpassData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $baseUrl  = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
        $endpoint = $baseUrl . '/api/pay';
        $vtpassServiceIds = ['mtn' => 'mtn-data', 'glo' => 'glo-data', 'airtel' => 'airtel-data', 'etisalat' => 'etisalat-data'];
        $payload = [
            'request_id'     => $reference,
            'serviceID'      => $vtpassServiceIds[$network->network_key] ?? 'mtn-data',
            'billersCode'    => $phone,
            'variation_code' => $plan->api_plan_id,
            'phone'          => $phone,
        ];

        $apiKey    = config('services.vtpass.api_key') ?: AppSetting::get('vtpass_api_key') ?: AppSetting::get('vtpass_public_key');
        $secretKey = config('services.vtpass.secret_key') ?: AppSetting::get('vtpass_secret_key');
        $publicKey = config('services.vtpass.public_key') ?: AppSetting::get('vtpass_public_key') ?: $apiKey;

        $start = hrtime(true);
        try {
            $headers = [
                'api-key'    => $apiKey,
                'secret-key' => $secretKey,
            ];
            if ($publicKey) {
                $headers['public-key'] = $publicKey;
            }

            $res = Http::withHeaders($headers)->post($endpoint, $payload);

            $body = $res->json() ?? [];
            $success = (($body['code'] ?? '') === '000');
            $duration = (int) ((hrtime(true) - $start) / 1e6);

            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'data',
                'provider'    => 'vtpass',
                'reference'   => $reference,
                'endpoint'    => $endpoint,
                'method'      => 'POST',
                'payload'     => $payload,
                'response'    => $body,
                'http_status' => $res->status(),
                'duration_ms' => $duration,
                'success'     => $success,
            ]);

            return [
                'success'   => $success,
                'reference' => $body['requestId'] ?? $reference,
                'response'  => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }

    private function callClubkonnectData(NetworkAirtime $network, DataPlan $plan, string $phone, string $reference): array
    {
        $endpoint = 'https://www.nellobytesystems.com/APIDataV1.asp';
        $netCodes = ['mtn' => '01', 'glo' => '02', 'airtel' => '03', 'etisalat' => '04'];
        $payload = [
            'UserID'        => AppSetting::get('clubkonnect_user_id'),
            'APIKey'        => AppSetting::get('clubkonnect_api_key'),
            'MobileNetwork' => $netCodes[$network->network_key] ?? '01',
            'DataPlan'      => $plan->api_plan_id,
            'MobileNo'      => $phone,
            'RequestID'     => $reference,
        ];
        $start = hrtime(true);
        try {
            $res = Http::get($endpoint, $payload);

            $body = $res->json() ?? [];
            $status = $body['status'] ?? '';
            $success = str_contains(strtolower($status), 'order_received') || str_contains(strtolower($status), 'success');
            $duration = (int) ((hrtime(true) - $start) / 1e6);

            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'     => 'data',
                'provider'    => 'clubkonnect',
                'reference'   => $reference,
                'endpoint'    => $endpoint,
                'method'      => 'GET',
                'payload'     => $payload,
                'response'    => $body,
                'http_status' => $res->status(),
                'duration_ms' => $duration,
                'success'     => $success,
            ]);

            return [
                'success'   => $success,
                'reference' => $body['orderid'] ?? $reference,
                'response'  => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
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

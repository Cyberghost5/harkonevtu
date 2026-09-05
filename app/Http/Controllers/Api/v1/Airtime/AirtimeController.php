<?php

namespace App\Http\Controllers\Api\v1\Airtime;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\AppSetting;
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

class AirtimeController extends Controller
{
    /**
     * List all active airtime networks with tier discount rates.
     */
    public function networks(Request $request): JsonResponse
    {
        if (AppSetting::get('service_airtime', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Airtime service is temporarily unavailable.',
            ], 503);
        }

        $user    = auth()->user();
        $isAgent = $user ? $user->isAgent() : false;
        $prefix  = $isAgent ? 'airtime_agent_off_percentage_' : 'airtime_off_percentage_';

        $networks = NetworkAirtime::active()->get()->map(function (NetworkAirtime $net) use ($prefix) {
            $discount = (float) AppSetting::get($prefix . $net->network_key, 0);
            return [
                'id'                  => $net->id,
                'name'                => $net->name,
                'network_key'         => $net->network_key,
                'discount_percentage' => $discount,
                'enabled'             => (bool) $net->enabled,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Airtime networks retrieved successfully.',
            'data'    => [
                'user_tier' => $isAgent ? 'Agent' : 'User',
                'networks'  => $networks,
            ],
        ]);
    }

    /**
     * Auto-detect network key from recipient phone number prefix.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:14'],
        ]);

        $phone = preg_replace('/^\+234/', '0', preg_replace('/[^0-9]/', '', $request->phone));
        if (strlen($phone) === 10 && !str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        $prefix = substr($phone, 0, 4);
        $networkKey = match (true) {
            in_array($prefix, ['0803', '0806', '0810', '0813', '0814', '0816', '0703', '0704', '0706', '0903', '0906', '0913', '0916']) => 'mtn',
            in_array($prefix, ['0805', '0807', '0811', '0815', '0705', '0905', '0915']) => 'glo',
            in_array($prefix, ['0802', '0808', '0812', '0701', '0708', '0901', '0902', '0904', '0907', '0912']) => 'airtel',
            in_array($prefix, ['0809', '0817', '0818', '0908', '0909']) => 'etisalat',
            default => null,
        };

        if (!$networkKey) {
            return response()->json([
                'status'  => false,
                'message' => 'Could not auto-detect network for this phone number. Please select network manually.',
            ], 422);
        }

        $network = NetworkAirtime::where('network_key', $networkKey)->first();

        return response()->json([
            'status'  => true,
            'message' => 'Network detected successfully.',
            'data'    => [
                'phone'       => $phone,
                'prefix'      => $prefix,
                'network_key' => $networkKey,
                'name'        => $network ? $network->name : strtoupper($networkKey),
            ],
        ]);
    }

    /**
     * Execute airtime disbursement transaction.
     */
    public function purchase(Request $request): JsonResponse
    {
        if (AppSetting::get('service_airtime', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Airtime service is temporarily unavailable.',
            ], 503);
        }

        $validNetworkKeys = NetworkAirtime::where('enabled', true)->pluck('network_key')->implode(',');

        $request->validate([
            'network' => ['required', 'string', 'in:' . $validNetworkKeys],
            'phone'   => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'amount'  => ['required', 'numeric', 'min:1', 'max:50000'],
            'pin'     => ['nullable', 'digits:4'],
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

        $networkKey = $request->network;
        $network    = NetworkAirtime::where('network_key', $networkKey)->where('enabled', true)->firstOrFail();
        $phone      = preg_replace('/^\+234/', '0', $request->phone);
        $faceAmount = (float) $request->amount;
        $chargedAmount = $this->calculateChargedAmount($faceAmount, $networkKey, $user);

        // 2. Daily spending limit check
        $dailyLimit = (float) AppSetting::get('airtime_daily_limit', 100000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'airtime')
            ->whereIn('status', ['success'])
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $chargedAmount) > $dailyLimit) {
            return response()->json([
                'status'  => false,
                'message' => 'Daily airtime spending limit of ₦' . number_format($dailyLimit, 0) . ' reached. Try again tomorrow.',
            ], 422);
        }

        // 3. Wallet balance check
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($chargedAmount)) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient wallet balance. You require ₦' . number_format($chargedAmount, 2) . ' to complete this transaction.',
                'errors'  => ['balance' => ['Insufficient wallet balance.']],
            ], 422);
        }

        // 4. Debit wallet in DB transaction
        $reference = 'AIR' . date('YmdHis') . Str::upper(Str::random(8));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($user, $chargedAmount, $networkKey, $phone, $reference, $network) {
                $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                if (!$w->hasSufficientBalance($chargedAmount)) {
                    throw new \Exception('Insufficient wallet balance. Please fund your wallet.');
                }
                return $w->debit(
                    $chargedAmount,
                    $network->name . ' Airtime - ' . $phone,
                    $reference,
                    ['service' => 'airtime', 'network' => $networkKey, 'phone' => $phone]
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
            = $this->callAirtimeGateway($network, $faceAmount, $phone, $reference);

        // 6. Handle refund on VTU failure
        if (!$apiSuccess) {
            try {
                DB::transaction(function () use ($user, $chargedAmount, $reference, $network, $phone) {
                    $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    $w->credit(
                        $chargedAmount,
                        'Refund: ' . $network->name . ' Airtime failed - ' . $phone,
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Airtime API refund failed', [
                    'user_id'   => $user->id,
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }

            return response()->json([
                'status'   => false,
                'refunded' => true,
                'message'  => $apiResponse['message'] ?? 'Airtime delivery failed. Your wallet balance has been refunded.',
            ], 422);
        }

        // 7. Record transaction log on clean success
        $api = AppSetting::get('airtime_api', 'vtpass');

        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'airtime',
            'provider'              => $networkKey,
            'recipient'             => $phone,
            'amount'                => $chargedAmount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRef,
            'api_response'          => array_merge(is_array($apiResponse) ? $apiResponse : ['raw' => $apiResponse], ['api_provider' => $api]),
        ]);

        $freshWallet = $user->fresh()->wallet;

        return response()->json([
            'status'  => true,
            'message' => '₦' . number_format($faceAmount, 0) . ' ' . $network->name . ' airtime sent to ' . $phone . ' successfully.',
            'data'    => [
                'reference'        => $reference,
                'api_reference'    => $apiRef,
                'network'          => $network->name,
                'network_key'      => $networkKey,
                'recipient'        => $phone,
                'face_amount'      => $faceAmount,
                'charged_amount'   => $chargedAmount,
                'discount_applied' => round($faceAmount - $chargedAmount, 2),
                'balance_after'    => (float) ($freshWallet ? $freshWallet->balance : 0),
                'date'             => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Calculate wallet debit amount after tier discount.
     */
    private function calculateChargedAmount(float $faceAmount, string $networkKey, $user): float
    {
        $isAgent = $user->isAgent();
        $prefix  = $isAgent ? 'airtime_agent_off_percentage_' : 'airtime_off_percentage_';
        $percent = (float) AppSetting::get($prefix . $networkKey, 0);

        if ($percent <= 0) {
            return $faceAmount;
        }

        $discount = ($faceAmount * $percent) / 100;
        return max(0, $faceAmount - $discount);
    }

    /**
     * Dispatch VTU airtime call to active gateway provider.
     */
    private function callAirtimeGateway(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $networkKey = $network->network_key;
        
        $api = AppSetting::get('airtime_net_' . $networkKey);
        if (empty($api) || in_array($api, ['Enable', 'Disable'])) {
            $api = AppSetting::get('airtime_api', 'vtpass');
        }

        if ($api === 'mtn_ers' && $networkKey !== 'mtn') {
            $api = 'vtpass';
        }

        if ($api === 'glo_ers' && $networkKey !== 'glo') {
            $api = 'vtpass';
        }

        return match ($api) {
            'clubkonnect'  => $this->callClubkonnect($network, $amount, $phone, $reference),
            'autopilot'    => $this->callAutopilot($network, $amount, $phone, $reference),
            'easyaccess'   => $this->callEasyaccess($network, $amount, $phone, $reference),
            'legitdataway' => $this->callLegitdataway($network, $amount, $phone, $reference),
            'merrybills'   => $this->callMerrybills($network, $amount, $phone, $reference),
            'payscribe'    => $this->callPayscribe($network, $amount, $phone, $reference),
            'mtn_ers'      => $this->callMtnErs($network, $amount, $phone, $reference),
            'glo_ers'      => $this->callGloErs($network, $amount, $phone, $reference),
            default        => $this->callVtpass($network, $amount, $phone, $reference),
        };
    }

    private function callGloErs(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $ersService = app(GloErsSoapService::class);
        $result = $ersService->vendAirtime($phone, $amount, $reference);

        $success = $result['success'] ?? false;
        $data = $result['response'] ?? ['message' => $result['message'] ?? 'Failed to communicate with Glo ERS SOAP Gateway'];
        $apiRef = $result['reference'] ?? $reference;

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    private function callMtnErs(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $ersService = app(MtnErsSoapService::class);

        $formattedPhone = $phone;
        $result = $ersService->vend($formattedPhone, $amount, 1);

        $success = $result['status'] ?? false;
        $data = $result['data'] ?? ['message' => $result['message'] ?? 'MTN ERS transaction failed'];
        $apiRef = $data['txRefId'] ?? $reference;

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    private function callVtpass(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $vtpassRef  = date('YmdHis') . Str::upper(Str::random(6));
        $baseUrl    = rtrim(config('services.vtpass.base_url') ?: AppSetting::get('vtpass_base_url', 'https://vtpass.com'), '/');
        $endpoint   = $baseUrl . '/api/pay';
        $payload    = [
            'request_id' => $vtpassRef,
            'serviceID'  => $network->vtpass_id ?? $network->network_key,
            'amount'     => $amount,
            'phone'      => $phone,
        ];
        $data        = [];
        $httpStatus  = null;
        $success     = false;
        $apiRef      = $vtpassRef;

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
            Log::error('VTpass airtime request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'airtime',
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

    private function callClubkonnect(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint   = 'https://www.nellobytesystems.com/APIAirtimeV1.asp';
        $payload    = [
            'UserID'        => config('services.clubkonnect.user_id') ?: AppSetting::get('clubkonnect_user_id'),
            'APIKey'        => config('services.clubkonnect.api_key') ?: AppSetting::get('clubkonnect_api_key'),
            'MobileNetwork' => $network->clubkonnect_id,
            'Amount'        => (int) $amount,
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
            Log::error('Clubkonnect airtime request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'airtime',
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

    private function callAutopilot(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint   = config('services.autopilot.base_url') . '/airtime';
        $payload    = [
            'networkId'   => (string) $network->id,
            'amount'      => (string) $amount,
            'phone'       => $phone,
            'airtimeType' => 'VTU',
            'reference'   => $reference,
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
            $response        = Http::withHeaders($requestHeaders)->timeout(60)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];
            $success         = ($data['status'] ?? false) === true && ($data['code'] ?? 0) === 200;
            $apiRef          = $data['data']['reference'] ?? $reference;
            if (!$success) {
                $data['message'] = $data['data']['message'] ?? 'Transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Autopilot airtime request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'airtime',
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

    private function callEasyaccess(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint = config('services.easyaccess.base_url') . '/airtime';
        $payload  = [
            'network'          => $network->easyaccess_id,
            'amount'           => (int) $amount,
            'mobileno'         => $phone,
            'airtimetype'      => 'VTU',
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
            $success         = ($data['code'] ?? '') === '200' || str_contains(strtolower($data['message'] ?? ''), 'successful') || ($data['status'] ?? '') === 'success';
            if (!$success) {
                $data['message'] = $data['message'] ?? $data['error'] ?? 'Easyaccess airtime transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Easyaccess airtime request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'airtime',
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

    private function callPayscribe(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint = config('services.payscribe.base_url') . '/airtime';
        $payload  = [
            'network'   => strtolower($network->name),
            'amount'    => (int) $amount,
            'recipient' => $phone,
            'ref'       => $reference,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . (config('services.payscribe.public_key') ?: AppSetting::get('payscribe_public_key')),
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
                $data['message'] = $data['message'] ?? 'Payscribe transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Payscribe airtime request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'airtime',
                'provider'         => 'payscribe',
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

    private function callLegitdataway(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint = config('services.legitdataway.base_url') . '/topup';
        $payload  = [
            'network'    => $network->legitdataway_id,
            'phone'      => $phone,
            'plan_type'  => 'VTU',
            'amount'     => (int) $amount,
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
            Log::error('Legitdataway airtime request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'airtime',
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

    private function callMerrybills(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint = config('services.merrybills.base_url') . '/airtime';
        $payload  = [
            'request_id' => $reference,
            'phone'      => $phone,
            'product_id' => $network->merrybills_id,
            'amount'     => $amount,
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
                $data['message'] = $data['message'] ?? 'Merrybills transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Merrybills airtime request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'airtime',
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

    /**
     * Get paginated airtime purchase transactions for authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $transactions = ServiceTransaction::where('user_id', $request->user()->id)
            ->where('service_type', 'airtime')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'status'  => true,
            'message' => 'Airtime transaction history retrieved successfully.',
            'data'    => $transactions,
        ]);
    }
}

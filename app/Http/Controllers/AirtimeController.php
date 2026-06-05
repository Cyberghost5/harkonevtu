<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\NetworkAirtime;
use App\Models\ServiceTransaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AirtimeController extends Controller
{
    public function index(): View
    {
        $user     = auth()->user();
        $networks = NetworkAirtime::active()->get();
        $history  = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'airtime')
            ->latest()
            ->paginate(10);

        $discounts = $this->getNetworkDiscounts($user);

        return view('services.airtime', compact('user', 'history', 'networks', 'discounts'));
    }

    public function purchase(Request $request): JsonResponse
    {
        // Check service is enabled
        if (!AppSetting::get('service_airtime', '1')) {
            return response()->json(['success' => false, 'message' => 'Airtime service is temporarily unavailable.'], 503);
        }

        $validNetworkKeys = NetworkAirtime::where('enabled', true)->pluck('network_key')->implode(',');

        $request->validate([
            'network'         => ['required', 'string', 'in:' . $validNetworkKeys],
            'phone'           => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'amount'          => ['required', 'numeric', 'min:50', 'max:50000'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user = auth()->user();

        // ── 0. Verify transaction PIN ────────────────────────────────────────
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json([
                'success'   => false,
                'message'   => 'Incorrect transaction PIN. Please try again.',
                'pin_error' => true,
            ], 422);
        }

        $networkKey = $request->network;
        $network    = NetworkAirtime::where('network_key', $networkKey)->where('enabled', true)->firstOrFail();
        $phone      = preg_replace('/^\+234/', '0', $request->phone);
        $amount     = (float) $request->amount;                      // face value sent to API
        $finalAmount = $this->calculateFinalAmount($amount, $networkKey, $user); // wallet debit

        // ── 1. Daily spending limit ──────────────────────────────────────────
        $dailyLimit = (float) AppSetting::get('airtime_daily_limit', 100000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'airtime')
            ->whereIn('status', ['success'])
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $finalAmount) > $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => 'Daily airtime spending limit of ₦' . number_format($dailyLimit, 0) . ' reached. Try again tomorrow.',
            ], 422);
        }

        // ── 2. Wallet balance check ──────────────────────────────────────────
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($finalAmount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance. Please fund your wallet.',
            ], 422);
        }

        // ── 3. Debit wallet ──────────────────────────────────────────────────
        $reference = 'AIR' . date('YmdHis') . Str::upper(Str::random(10));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($wallet, $finalAmount, $networkKey, $phone, $reference, $network) {
                return $wallet->debit(
                    $finalAmount,
                    $network->name . ' Airtime - ' . $phone,
                    $reference,
                    ['service' => 'airtime', 'network' => $networkKey, 'phone' => $phone]
                );
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // ── 4. Call active API ───────────────────────────────────────────────
        ['success' => $apiSuccess, 'reference' => $apiRef, 'response' => $apiResponse]
            = $this->callAirtimeApi($network, $amount, $phone, $reference);

        // ── 5. Refund on failure - do NOT record failed transactions ─────────
        if (!$apiSuccess) {
            try {
                DB::transaction(function () use ($wallet, $finalAmount, $reference, $network, $phone) {
                    $wallet->credit(
                        $finalAmount,
                        'Refund: ' . $network->name . ' Airtime failed - ' . $phone,
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Airtime refund failed', [
                    'user_id'   => $user->id,
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success'  => false,
                'refunded' => true,
                'message'  => $apiResponse['message'] ?? 'Airtime delivery failed.',
            ], 422);
        }

        // ── 6. Record successful transaction only ────────────────────────────
        $api = AppSetting::get('airtime_api', 'vtpass');

        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'airtime',
            'provider'              => $networkKey,
            'recipient'             => $phone,
            'amount'                => $finalAmount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRef,
            'api_response'          => array_merge(is_array($apiResponse) ? $apiResponse : ['raw' => $apiResponse], ['api_provider' => $api]),
        ]);

        return response()->json([
            'success' => true,
            'message' => '₦' . number_format($amount, 0) . ' ' . $network->name . ' airtime sent to ' . $phone . ' successfully.',
            'balance' => '₦' . number_format((float) $wallet->fresh()->balance, 2),
            'reference' => $reference,
        ]);
    }

    // ─── Pricing Helpers ──────────────────────────────────────────────────────

    private function getNetworkDiscounts(\App\Models\User $user): array
    {
        $isAgent = $user->isAgent();
        $prefix  = $isAgent ? 'airtime_agent_off_percentage_' : 'airtime_off_percentage_';

        $keys = NetworkAirtime::all()
            ->map(fn ($n) => $prefix . $n->network_key)
            ->toArray();

        $raw = AppSetting::getMany($keys);

        // Re-key to network_key → discount value
        $result = [];
        foreach ($raw as $key => $value) {
            $networkKey = str_replace($prefix, '', $key);
            $result[$networkKey] = (float) $value;
        }

        return $result;
    }

    private function calculateFinalAmount(float $amount, string $networkKey, \App\Models\User $user): float
    {
        $isAgent    = $user->isAgent();
        $prefix     = $isAgent ? 'airtime_agent_off_percentage_' : 'airtime_off_percentage_';
        $offPercent = (float) AppSetting::get($prefix . $networkKey, 0);

        return round($amount * (100 - $offPercent) / 100, 2);
    }

    // ─── API Dispatcher ───────────────────────────────────────────────────────

    private function callAirtimeApi(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $api = AppSetting::get('airtime_api', 'vtpass');

        return match ($api) {
            'clubkonnect' => $this->callClubkonnect($network, $amount, $phone, $reference),
            'autopilot'   => $this->callAutopilot($network, $amount, $phone, $reference),
            'legitdataway'=> $this->callLegitdataway($network, $amount, $phone, $reference),
            'merrybills'  => $this->callMerrybills($network, $amount, $phone, $reference),
            'easyaccess'   => $this->callEasyairtime($network, $amount, $phone, $reference),
            default       => $this->callVtpass($network, $amount, $phone, $reference),
        };
    }

    // ─── VTpass ───────────────────────────────────────────────────────────────

    private function callVtpass(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $vtpassRef  = date('YmdHis') . Str::upper(Str::random(6));
        $endpoint   = config('services.vtpass.base_url') . '/api/pay';
        $payload    = [
            'request_id' => $vtpassRef,
            'serviceID'  => $network->vtpass_id,
            'amount'     => $amount,
            'phone'      => $phone,
        ];
        $data        = [];
        $httpStatus  = null;
        $success     = false;
        $apiRef      = $vtpassRef;

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
            Log::error('VTpass request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
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

    // ─── Clubkonnect ──────────────────────────────────────────────────────────

    private function callClubkonnect(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint   = 'https://www.nellobytesystems.com/APIAirtimeV1.asp';
        $payload    = [
            'UserID'        => config('services.clubkonnect.user_id'),
            'APIKey'        => config('services.clubkonnect.api_key'),
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
            $data       = $response->json() ?? [];
            $status     = $data['status'] ?? '';
            $apiRef     = $data['orderid'] ?? $reference;
            $success    = in_array($status, ['ORDER_RECEIVED', 'ORDER_COMPLETED']);
            if (!$success) {
                $data['message'] = $data['statusremark'] ?? $data['orderremark'] ?? 'Transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Clubkonnect request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
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

    // ─── Autopilot ────────────────────────────────────────────────────────────

    private function callAutopilot(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint   = config('services.autopilot.base_url') . '/airtime';
        $payload    = [
            'networkId' => $network->autopilot_id,
            'amount' => (string) $amount,
            'phone' => $phone,
            'airtimeType' => 'VTU',
            'reference' => $reference,
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
            $response   = Http::withHeaders($requestHeaders)->timeout(60)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data       = $response->json() ?? [];
            $success    = ($data['status'] ?? false) === true && ($data['code'] ?? 0) === 200;
            $apiRef     = $data['data']['reference'] ?? $reference;
            if (!$success) {
                $data['message'] = $data['data']['message'] ?? 'Transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Autopilot request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
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

    // ─── Easyairtime ─────────────────────────────────────────────────────────

    private function callEasyairtime(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint   = config('services.easyairtime.base_url') . '/topup';
        $payload    = [
            'network' => $network->easyaccess_id,
            'mobileno' => $phone,
            'airtimetype' => 'VTU',
            'amount' => (int) $amount,
            'client_reference' => $reference,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.easyairtime.token'),
            'Cache-Control' => 'no-cache',
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
                $data['message'] = $data['message'] ?? 'Easyairtime transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Easyairtime request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'airtime',
                'provider'         => 'easyairtime',
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

    // ─── Legitdataway ────────────────────────────────────────────────────────────

    private function callLegitdataway(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint   = config('services.legitdataway.base_url') . '/topup';
        $payload    = [
            'network' => $network->legitdataway_id,
            'phone' => $phone,
            'plan_type' => 'VTU',
            'amount' => (int) $amount,
            'bypass' => true,
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
            Log::error('Legitdataway request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
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

    // ─── Merrybills ───────────────────────────────────────────────────────────

    private function callMerrybills(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $endpoint   = config('services.merrybills.base_url') . '/airtime';
        $payload    = [
            'request_id' => $reference,
            'phone'      => $phone,
            'product_id' => $network->merrybills_id,
            'amount'     => $amount,
            'pin'        => config('services.merrybills.pin'),
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $apiRef     = $reference;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.merrybills.token'),
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data       = $response->json() ?? [];
            $success    = ($data['status'] ?? '') === 'success' || ($data['Status'] ?? '') === '200';
            $apiRef     = $data['reference'] ?? $data['transid'] ?? $reference;
            if (!$success) {
                $data['message'] = $data['message'] ?? $data['Message'] ?? 'Transaction failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Merrybills request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
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
}

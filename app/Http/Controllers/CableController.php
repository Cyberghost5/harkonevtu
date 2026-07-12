<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\CablePlan;
use App\Models\CableProvider;
use App\Models\ServiceTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CableController extends Controller
{
    // ─── Page ─────────────────────────────────────────────────────────────────

    public function index(): mixed
    {
        if (AppSetting::get('service_cable', '1') !== '1') {
            return redirect()->route('dashboard')->with('error', 'Cable TV service is temporarily unavailable.');
        }

        $user      = auth()->user();
        $providers = CableProvider::active()->with('plans')->get();

        $history = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'cable')
            ->latest()
            ->paginate(10);

        return view('services.cable', compact('user', 'providers', 'history'));
    }

    // ─── AJAX: Load plans for a provider ─────────────────────────────────────

    public function getPlans(Request $request): JsonResponse
    {
        if (AppSetting::get('service_cable', '1') !== '1') {
            return response()->json(['success' => false, 'message' => 'Cable TV service is temporarily unavailable.'], 503);
        }

        $request->validate([
            'provider_id' => ['required', 'integer', 'exists:cable_providers,id'],
        ]);

        $plans = CablePlan::where('cable_provider_id', $request->provider_id)
            ->active()
            ->get(['id', 'name', 'amount']);

        return response()->json(['plans' => $plans]);
    }

    // ─── AJAX: Validate smartcard number ─────────────────────────────────────

    public function validateCard(Request $request): JsonResponse
    {
        if (AppSetting::get('service_cable', '1') !== '1') {
            return response()->json(['success' => false, 'message' => 'Cable TV service is temporarily unavailable.'], 503);
        }

        $request->validate([
            'provider_id' => ['required', 'integer', 'exists:cable_providers,id'],
            'plan_id'     => ['required', 'integer', 'exists:cable_plans,id'],
            'smartcard'   => ['required', 'string', 'min:5', 'max:20'],
        ]);

        $provider  = CableProvider::findOrFail($request->provider_id);
        $plan      = CablePlan::findOrFail($request->plan_id);
        $smartcard = trim($request->smartcard);

        $api = AppSetting::get('cable_api', 'vtpass');

        return match ($api) {
            'easyaccess' => $this->validateCardEasyaccess($provider, $smartcard),
            'payscribe'  => $this->validateCardPayscribe($provider, $plan, $smartcard),
            'vtpass'     => $this->validateCardVtpass($provider, $smartcard),
        };
    }

    // ─── Purchase ─────────────────────────────────────────────────────────────

    public function purchase(Request $request): JsonResponse
    {
        if (AppSetting::get('service_cable', '1') !== '1') {
            return response()->json(['success' => false, 'message' => 'Cable TV service is temporarily unavailable.'], 503);
        }

        $request->validate([
            'provider_id'     => ['required', 'integer', 'exists:cable_providers,id'],
            'plan_id'         => ['required', 'integer', 'exists:cable_plans,id'],
            'smartcard'       => ['required', 'string', 'min:5', 'max:20'],
            'phone'           => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user      = auth()->user();
        $provider  = CableProvider::where('id', $request->provider_id)->where('enabled', true)->firstOrFail();
        $plan      = CablePlan::where('id', $request->plan_id)
            ->where('cable_provider_id', $provider->id)
            ->where('enabled', true)
            ->firstOrFail();
        $smartcard = trim($request->smartcard);
        $amount    = $plan->amount;
        $phone     = preg_replace('/^\+234/', '0', $request->phone);

        // ── 0. Verify PIN ────────────────────────────────────────────────────
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json([
                'success'   => false,
                'message'   => 'Incorrect transaction PIN. Please try again.',
                'pin_error' => true,
            ], 422);
        }

        // ── 1. Daily spending limit ──────────────────────────────────────────
        $dailyLimit = (float) AppSetting::get('cable_daily_limit', 100000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'cable')
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $amount) > $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => 'Daily cable TV spending limit of ₦' . number_format($dailyLimit, 0) . ' reached.',
            ], 422);
        }

        // ── 2. Wallet balance check ──────────────────────────────────────────
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance. Please fund your wallet.',
            ], 422);
        }

        // ── 3. Debit wallet ──────────────────────────────────────────────────
        $reference = 'CBL' . date('YmdHis') . Str::upper(Str::random(6));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($user, $amount, $provider, $plan, $smartcard, $reference) {
                $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                if (!$wallet->hasSufficientBalance($amount)) {
                    throw new \Exception('Insufficient wallet balance. Please fund your wallet.');
                }
                return $wallet->debit(
                    $amount,
                    $provider->name . ' – ' . $plan->name . ' – ' . $smartcard,
                    $reference,
                    ['service' => 'cable', 'provider' => $provider->slug, 'plan' => $plan->vtpass_id, 'smartcard' => $smartcard]
                );
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // ── 4. Call API ──────────────────────────────────────────────────────
        [
            'success'       => $apiSuccess,
            'reference'     => $apiRef,
            'customer_name' => $customerName,
            'response'      => $apiResponse,
        ] = $this->callCableApi($provider, $plan, $smartcard, $phone, $reference);

        // ── 5. Refund on failure ─────────────────────────────────────────────
        if (!$apiSuccess) {
            try {
                DB::transaction(function () use ($user, $amount, $reference, $provider, $plan, $smartcard) {
                    $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    $wallet->credit(
                        $amount,
                        'Refund: ' . $provider->name . ' ' . $plan->name . ' failed – ' . $smartcard,
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Cable TV refund failed', [
                    'user_id'   => $user->id,
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success'  => false,
                'refunded' => true,
                'message'  => $apiResponse['message'] ?? 'Cable subscription failed.',
            ], 422);
        }

        // ── 6. Record successful transaction ─────────────────────────────────
        $api = AppSetting::get('cable_api', 'vtpass');

        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'cable',
            'provider'              => $provider->slug,
            'recipient'             => $smartcard,
            'amount'                => $amount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRef,
            'api_response'          => array_merge(is_array($apiResponse) ? $apiResponse : [], [
                'api_provider'  => $api,
                'provider'      => $provider->name,
                'plan'          => $plan->name,
                'smartcard'     => $smartcard,
                'customer_name' => $customerName,
            ]),
        ]);

        return response()->json([
            'success'       => true,
            'message'       => $provider->name . ' ' . $plan->name . ' subscribed successfully for ' . $smartcard . '.',
            'customer_name' => $customerName,
            'balance'       => '₦' . number_format((float) $wallet->fresh()->balance, 2),
            'reference'     => $reference,
        ]);
    }

    // ─── API Dispatcher ───────────────────────────────────────────────────────

    private function callCableApi(
        CableProvider $provider,
        CablePlan $plan,
        string $smartcard,
        string $phone,
        string $reference
    ): array {
        $api = AppSetting::get('cable_api', 'vtpass');

        return match ($api) {
            'easyaccess' => $this->callEasyaccessCable($provider, $plan, $smartcard, $reference),
            'payscribe'  => $this->callPayscribeCable($provider, $plan, $smartcard, $reference),
            'vtpass'     => $this->callVtpassCable($provider, $plan, $smartcard, $phone, $reference),
        };
    }

    // ─── VTPass: validate ─────────────────────────────────────────────────────

    private function validateCardVtpass(CableProvider $provider, string $smartcard): JsonResponse
    {
        $endpoint   = config('services.vtpass.base_url') . '/api/merchant-verify';
        $payload    = [
            'serviceID'   => $provider->slug,
            'billersCode' => $smartcard,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $result     = null;

        $requestHeaders = [
            'api-key'    => config('services.vtpass.api_key'),
            'secret-key' => config('services.vtpass.secret_key'),
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $httpResponse = Http::withHeaders($requestHeaders)->timeout(20)->post($endpoint, $payload);

            $httpStatus      = $httpResponse->status();
            $responseHeaders = $httpResponse->headers();
            $data            = $httpResponse->json() ?? [];
            $code       = $data['code'] ?? '';

            if ($code === '000') {
                $content = $data['content'] ?? [];
                $success = true;
                $result  = response()->json([
                    'success'       => true,
                    'customer_name' => $content['Customer_Name'] ?? $content['name'] ?? null,
                    'smartcard'     => $smartcard,
                ]);
            } else {
                $result = response()->json([
                    'success' => false,
                    'message' => $data['response_description'] ?? 'Invalid smartcard number.',
                ], 422);
            }
        } catch (\Exception $e) {
            $data   = ['error' => $e->getMessage()];
            Log::error('VTPass cable validation failed', ['smartcard' => $smartcard, 'error' => $e->getMessage()]);
            $result = response()->json(['success' => false, 'message' => 'Validation unavailable. Please try again.'], 503);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'cable_validate',
                'provider'         => 'vtpass',
                'reference'        => $smartcard,
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

        return $result;
    }

    // ─── EasyAccess: validate ─────────────────────────────────────────────────

    private function validateCardEasyaccess(CableProvider $provider, string $smartcard): JsonResponse
    {
        $endpoint = config('services.easyaccess.base_url') . '/verify-tv';
        $payload    = [
            'company'     => $provider->idForApi('easyaccess'),
            'iucno' => $smartcard,
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $result     = null;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.easyaccess.token'),
            'Cache-Control' => 'no-cache',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $httpResponse = Http::withHeaders($requestHeaders)->timeout(20)->post($endpoint, $payload);

            $httpStatus      = $httpResponse->status();
            $responseHeaders = $httpResponse->headers();
            $data            = $httpResponse->json() ?? [];
            $status          = isset($data['status']) ? ($data['status'] === 'success') : ($data['code'] === 200);

            if ($status) {
                $content = $data['message']['content'] ?? $data['message'] ?? [];
                $name    = is_array($content) ? ($content['Customer_Name'] ?? $content['name'] ?? $content['customer_name'] ?? $data['customer_name']) : null;
                $success = true;
                $result  = response()->json([
                    'success'       => true,
                    'customer_name' => $name,
                    'smartcard'     => $smartcard,
                ]);
            } else {
                $errMsg = $data['message'] ?? 'Invalid smartcard number.';
                $result = response()->json([
                    'success' => false,
                    'message' => is_string($errMsg) ? $errMsg : 'Invalid smartcard number.',
                ], 422);
            }
        } catch (\Exception $e) {
            $data   = ['error' => $e->getMessage()];
            Log::error('EasyAccess cable validation failed', ['smartcard' => $smartcard, 'error' => $e->getMessage()]);
            $result = response()->json(['success' => false, 'message' => 'Validation unavailable. Please try again.'], 503);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'cable_validate',
                'provider'         => 'easyaccess',
                'reference'        => $smartcard,
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

        return $result;
    }

    // ─── Payscribe: validate ──────────────────────────────────────────────────

    private function validateCardPayscribe(CableProvider $provider, CablePlan $plan, string $smartcard): JsonResponse
    {
        // Payscribe cable validate requires the plan_id too
        $payscribePlanId = $plan->idForApi('payscribe');

        if (!$payscribePlanId || $payscribePlanId === $plan->vtpass_id) {
            // Plan has no Payscribe ID - fall back to VTPass validate
            return $this->validateCardVtpass($provider, $smartcard);
        }

        $endpoint = config('services.payscribe.base_url') . '/multichoice/validate';
        $rawBody  = json_encode([
            'service' => $provider->idForApi('payscribe'),
            'account' => $smartcard,
            'plan_id' => $payscribePlanId,
        ]);
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $result     = null;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.payscribe.secret_key'),
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $httpResponse = Http::withHeaders($requestHeaders)->timeout(20)->withBody($rawBody, 'application/json')->post($endpoint);

            $httpStatus      = $httpResponse->status();
            $responseHeaders = $httpResponse->headers();
            $data            = $httpResponse->json() ?? [];
            $status        = $data['status']      ?? false;
            $statusMessage = $data['description'] ?? 'Invalid smartcard number.';

            if ($status) {
                $details      = $data['message']['details'] ?? [];
                $customerName = $details['customer_name']   ?? null;

                if ($customerName === null) {
                    $result = response()->json([
                        'success' => false,
                        'message' => 'Could not find customer details for this smartcard number.',
                    ], 422);
                } else {
                    $success = true;
                    $result  = response()->json([
                        'success'       => true,
                        'customer_name' => $customerName,
                        'smartcard'     => $smartcard,
                    ]);
                }
            } else {
                $result = response()->json(['success' => false, 'message' => $statusMessage], 422);
            }
        } catch (\Exception $e) {
            $data   = ['error' => $e->getMessage()];
            Log::error('Payscribe cable validation failed', ['smartcard' => $smartcard, 'error' => $e->getMessage()]);
            $result = response()->json(['success' => false, 'message' => 'Validation unavailable. Please try again.'], 503);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'cable_validate',
                'provider'         => 'payscribe',
                'reference'        => $smartcard,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => json_decode($rawBody, true),
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return $result;
    }

    // ─── VTPass: purchase ─────────────────────────────────────────────────────

    private function callVtpassCable(
        CableProvider $provider,
        CablePlan $plan,
        string $smartcard,
        string $phone,
        string $reference
    ): array {
        $vtpassRef = date('YmdHis') . Str::upper(Str::random(6));
        $endpoint  = config('services.vtpass.base_url') . '/api/pay';
        $payload   = [
            'request_id'     => $vtpassRef,
            'serviceID'      => $provider->slug,
            'variation_code' => $plan->vtpass_id,
            'billersCode'    => $smartcard,
            'amount'         => (int) $plan->amount,
            'phone'          => $phone,
        ];
        $data         = [];
        $httpStatus   = null;
        $success      = false;
        $apiRef       = $vtpassRef;
        $customerName = null;

        $requestHeaders = [
            'api-key'    => config('services.vtpass.api_key'),
            'secret-key' => config('services.vtpass.secret_key'),
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $raw        = $response->json();
            $data       = is_array($raw) ? $raw : ['message' => 'Unknown VTPass response'];
            $code       = $data['code'] ?? '';
            $success    = in_array($code, ['000', '099']);
            $apiRef     = $data['content']['transactions']['transactionId'] ?? $data['requestId'] ?? $vtpassRef;

            if ($success) {
                $txn          = $data['content']['transactions'] ?? [];
                $customerName = $txn['customerName'] ?? $data['customerName'] ?? null;
            } else {
                $data['message'] = $data['response_description'] ?? 'Cable TV subscription failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('VTPass cable request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'cable',
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

        return ['success' => $success, 'reference' => $apiRef, 'customer_name' => $customerName, 'response' => $data];
    }

    // ─── EasyAccess: purchase ─────────────────────────────────────────────────

    private function callEasyaccessCable(
        CableProvider $provider,
        CablePlan $plan,
        string $smartcard,
        string $reference
    ): array {
        $endpoint = config('services.easyaccess.base_url') . '/pay-tv';
        $payload  = [
            'company'         => $provider->idForApi('easyaccess'),
            'iucno' => $smartcard,
            'package'         => $plan->idForApi('easyaccess'),
        ];
        $data         = [];
        $httpStatus   = null;
        $success      = false;
        $apiRef       = $reference;
        $customerName = null;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.easyaccess.token'),
            'Cache-Control'       => 'no-cache',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->asForm()->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $raw        = $response->json();
            $data       = is_array($raw) ? $raw : ['message' => 'Unknown EasyAccess response'];
            $statusCode = $data['success'] ?? 'false';
            $success    = ($statusCode === 'true');

            if (!$success) {
                $errMsg          = $data['message'] ?? 'EasyAccess cable subscription failed.';
                $data['message'] = is_array($errMsg) ? json_encode($errMsg) : $errMsg;
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('EasyAccess cable request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'cable',
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

        return ['success' => $success, 'reference' => $apiRef, 'customer_name' => $customerName, 'response' => $data];
    }

    // ─── Payscribe: purchase ──────────────────────────────────────────────────

    private function callPayscribeCable(
        CableProvider $provider,
        CablePlan $plan,
        string $smartcard,
        string $reference
    ): array {
        $endpoint = config('services.payscribe.base_url') . '/multichoice/vend';
        $payload  = [
            'plan_id'   => $plan->idForApi('payscribe'),
            'customer_name' => auth()->user()->name,
            'account'   => $smartcard,
            'service'   => $provider->idForApi('payscribe'),
            'ref'       => $reference,
        ];
        $data         = [];
        $httpStatus   = null;
        $success      = false;
        $apiRef       = $reference;
        $customerName = null;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.payscribe.secret_key'),
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $raw        = $response->json();
            $data       = is_array($raw) ? $raw : ['message' => 'Unknown Payscribe response'];
            $success    = (bool) ($data['status'] ?? false);
            $apiRef     = $data['reference'] ?? $reference;

            if ($success) {
                $details      = $data['message']['details'] ?? [];
                $customerName = $details['customer_name'] ?? null;
            } else {
                $data['message'] = $data['description'] ?? 'Payscribe cable subscription failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Payscribe cable request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'cable',
                'provider'         => 'payscribe',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,                'request_headers'  => $requestHeaders,                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'customer_name' => $customerName, 'response' => $data];
    }
}

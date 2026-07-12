<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\ElectricityDisco;
use App\Models\ServiceTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ElectricityController extends Controller
{
    // ─── Pages ────────────────────────────────────────────────────────────────

    public function index(): mixed
    {
        if (AppSetting::get('service_electricity', '1') !== '1') {
            return redirect()->route('dashboard')->with('error', 'Electricity service is temporarily unavailable.');
        }

        $user   = auth()->user();
        $discos = ElectricityDisco::active()->get();

        $history = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'electricity')
            ->latest()
            ->paginate(10);

        return view('services.electricity', compact('user', 'discos', 'history'));
    }

    // ─── AJAX: Validate meter number ──────────────────────────────────────────

    public function validateMeter(Request $request): JsonResponse
    {
        if (AppSetting::get('service_electricity', '1') !== '1') {
            return response()->json(['success' => false, 'message' => 'Electricity service is temporarily unavailable.'], 503);
        }

        $request->validate([
            'disco_id'   => ['required', 'integer', 'exists:electricity_discos,id'],
            'meter_type' => ['required', 'in:prepaid,postpaid'],
            'meter_number' => ['required', 'string', 'min:5', 'max:30'],
        ]);

        $disco       = ElectricityDisco::findOrFail($request->disco_id);
        $meterType   = $request->meter_type;
        $meterNumber = $request->meter_number;

        $api = AppSetting::get('electricity_api', 'easyaccess');

        return match ($api) {
            'easyaccess' => $this->validateMeterEasyaccess($disco, $meterType, $meterNumber),
            'payscribe'  => $this->validateMeterPayscribe($disco, $meterType, $meterNumber),
            'vtpass'      => $this->validateMeterVtpass($disco, $meterType, $meterNumber),
        };
    }

    // ─── Purchase ─────────────────────────────────────────────────────────────

    public function purchase(Request $request): JsonResponse
    {
        // ── Service enabled check ────────────────────────────────────────────
        if (AppSetting::get('service_electricity', '1') !== '1') {
            return response()->json(['success' => false, 'message' => 'Electricity service is temporarily unavailable.'], 503);
        }

        $request->validate([
            'disco_id'       => ['required', 'integer', 'exists:electricity_discos,id'],
            'meter_type'     => ['required', 'in:prepaid,postpaid'],
            'meter_number'   => ['required', 'string', 'min:5', 'max:30'],
            'amount'         => ['required', 'numeric', 'min:1000', 'max:500000'],
            'phone'          => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user        = auth()->user();
        $disco       = ElectricityDisco::where('id', $request->disco_id)->where('enabled', true)->firstOrFail();
        $meterType   = $request->meter_type;
        $meterNumber = trim($request->meter_number);
        $amount      = (float) $request->amount;
        $phone       = preg_replace('/^\+234/', '0', $request->phone);

        // ── 0. Verify PIN ────────────────────────────────────────────────────
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json([
                'success'   => false,
                'message'   => 'Incorrect transaction PIN. Please try again.',
                'pin_error' => true,
            ], 422);
        }

        // ── 1. Min amount ────────────────────────────────────────────────────
        $minAmount = (float) AppSetting::get('electricity_min_amount', 1000);
        if ($amount < $minAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum electricity purchase amount is ₦' . number_format($minAmount, 0) . '.',
            ], 422);
        }

        // ── 2. Daily spending limit ──────────────────────────────────────────
        $dailyLimit = (float) AppSetting::get('electricity_daily_limit', 100000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'electricity')
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $amount) > $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => 'Daily electricity spending limit of ₦' . number_format($dailyLimit, 0) . ' reached. Try again tomorrow.',
            ], 422);
        }

        // ── 3. Wallet balance check ──────────────────────────────────────────
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance. Please fund your wallet.',
            ], 422);
        }

        // ── 4. Debit wallet ──────────────────────────────────────────────────
        $reference = 'ELC' . date('YmdHis') . Str::upper(Str::random(6));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($user, $amount, $disco, $meterNumber, $meterType, $reference) {
                $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                if (!$wallet->hasSufficientBalance($amount)) {
                    throw new \Exception('Insufficient wallet balance. Please fund your wallet.');
                }
                return $wallet->debit(
                    $amount,
                    $disco->name . ' (' . ucfirst($meterType) . ') – ' . $meterNumber,
                    $reference,
                    ['service' => 'electricity', 'disco' => $disco->slug, 'meter' => $meterNumber, 'meter_type' => $meterType]
                );
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // ── 5. Call API ──────────────────────────────────────────────────────
        [
            'success'       => $apiSuccess,
            'reference'     => $apiRef,
            'token'         => $token,
            'units'         => $units,
            'customer_name' => $customerName,
            'response'      => $apiResponse,
        ] = $this->callElectricityApi($disco, $meterType, $meterNumber, $amount, $phone, $reference);

        // ── 6. Refund on failure ─────────────────────────────────────────────
        if (!$apiSuccess) {
            try {
                DB::transaction(function () use ($user, $amount, $reference, $disco, $meterNumber) {
                    $wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    $wallet->credit(
                        $amount,
                        'Refund: ' . $disco->name . ' electricity failed – ' . $meterNumber,
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Electricity refund failed', [
                    'user_id'   => $user->id,
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success'  => false,
                'refunded' => true,
                'message'  => $apiResponse['message'] ?? 'Electricity vending failed.',
            ], 422);
        }

        // ── 7. Record successful transaction ─────────────────────────────────
        $api = AppSetting::get('electricity_api', 'vtpass');

        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'electricity',
            'provider'              => $disco->slug,
            'recipient'             => $meterNumber,
            'amount'                => $amount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRef,
            'api_response'          => array_merge(is_array($apiResponse) ? $apiResponse : [], [
                'api_provider'    => $api,
                'disco'           => $disco->name,
                'meter_type'      => $meterType,
                'meter_number'    => $meterNumber,
                'token'           => $token,
                'units'           => $units,
                'customer_name'   => $customerName,
            ]),
        ]);

        $successMsg = 'Electricity token purchased successfully!';
        if ($token) {
            $successMsg .= ' Token: <strong>' . e($token) . '</strong>';
            if ($units) {
                $successMsg .= ' (' . e($units) . ')';
            }
        }

        return response()->json([
            'success'       => true,
            'message'       => '₦' . number_format($amount, 0) . ' ' . $disco->name . ' electricity purchased successfully.',
            'token'         => $token,
            'units'         => $units,
            'customer_name' => $customerName,
            'balance'       => '₦' . number_format((float) $wallet->fresh()->balance, 2),
            'reference'     => $reference,
        ]);
    }

    // ─── API Dispatcher ───────────────────────────────────────────────────────

    private function callElectricityApi(
        ElectricityDisco $disco,
        string $meterType,
        string $meterNumber,
        float $amount,
        string $phone,
        string $reference
    ): array {
        $api = AppSetting::get('electricity_api', 'vtpass');

        return match ($api) {
            'easyaccess' => $this->callEasyaccessElectricity($disco, $meterType, $meterNumber, $amount, $reference),
            'payscribe'  => $this->callPayscribeElectricity($disco, $meterType, $meterNumber, $amount, $reference),
            'vtpass'      => $this->callVtpassElectricity($disco, $meterType, $meterNumber, $amount, $phone, $reference),
        };
    }

    // ─── VTPass: validate meter ───────────────────────────────────────────────

    private function validateMeterVtpass(ElectricityDisco $disco, string $meterType, string $meterNumber): JsonResponse
    {
        $endpoint   = config('services.vtpass.base_url') . '/api/merchant-verify';
        $payload    = [
            'billersCode' => $meterNumber,
            'serviceID'   => $disco->slug,
            'type'        => $meterType,
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
                    'success'          => true,
                    'customer_name'    => $content['Customer_Name']    ?? $content['name']    ?? null,
                    'customer_address' => $content['Address']          ?? $content['address'] ?? null,
                    'meter_number'     => $content['Meter_Number']     ?? $meterNumber,
                ]);
            } else {
                $result = response()->json([
                    'success' => false,
                    'message' => $data['response_description'] ?? 'Invalid meter number or details not found.',
                ], 422);
            }
        } catch (\Exception $e) {
            $data   = ['error' => $e->getMessage()];
            Log::error('VTPass meter validation failed', ['meter' => $meterNumber, 'error' => $e->getMessage()]);
            $result = response()->json(['success' => false, 'message' => 'Meter validation unavailable. Please try again.'], 503);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'electricity_validate',
                'provider'         => 'vtpass',
                'reference'        => $meterNumber,
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

    // ─── Payscribe: validate meter ────────────────────────────────────────────

    private function validateMeterPayscribe(ElectricityDisco $disco, string $meterType, string $meterNumber): JsonResponse
    {
        $endpoint = config('services.payscribe.base_url') . '/electricity/validate';

        // Payscribe requires a raw JSON string body with Content-Type: text/plain
        $rawBody = json_encode([
            'service'      => $disco->idForApi('payscribe'),
            'meter_number' => $meterNumber,
            'amount'       => '1000',
            'meter_type'   => $meterType,
        ]);
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $result     = null;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.payscribe.secret_key'),
            'Content-Type'  => 'text/plain',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $httpResponse  = Http::withHeaders($requestHeaders)->timeout(20)->withBody($rawBody, 'text/plain')->post($endpoint);

            $httpStatus      = $httpResponse->status();
            $responseHeaders = $httpResponse->headers();
            $data            = $httpResponse->json() ?? [];
            $status        = $data['status']      ?? false;
            $statusMessage = $data['description'] ?? 'Invalid meter number or details not found.';

            if ($status) {
                $details      = $data['message']['details'] ?? [];
                $customerName = $details['customer_name']   ?? null;

                if ($customerName === null) {
                    $result = response()->json([
                        'success' => false,
                        'message' => 'Could not find customer details for this meter number.',
                    ], 422);
                } else {
                    $success = true;
                    $result  = response()->json([
                        'success'          => true,
                        'customer_name'    => $customerName,
                        'customer_address' => $details['address'] ?? null,
                        'meter_number'     => $meterNumber,
                    ]);
                }
            } else {
                $result = response()->json(['success' => false, 'message' => $statusMessage], 422);
            }
        } catch (\Exception $e) {
            $data   = ['error' => $e->getMessage()];
            Log::error('Payscribe meter validation failed', ['meter' => $meterNumber, 'error' => $e->getMessage()]);
            $result = response()->json(['success' => false, 'message' => 'Meter validation unavailable. Please try again.'], 503);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'electricity_validate',
                'provider'         => 'payscribe',
                'reference'        => $meterNumber,
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

    // ─── EasyAccess: validate meter ───────────────────────────────────────────

    private function validateMeterEasyaccess(ElectricityDisco $disco, string $meterType, string $meterNumber): JsonResponse
    {
        $endpoint   = config('services.easyaccess.base_url') . '/verify-electricity';
        if($meterType === 'prepaid') {
            $meterTypeRequest = '1';
        } else {
            $meterTypeRequest = '2';
        }
        $payload    = [
            'company'   => $disco->idForApi('easyaccess'),
            'metertype' => $meterTypeRequest,
            'meterno'   => $meterNumber,
            'amount' => '1000',
        ];
        $data       = [];
        $httpStatus = null;
        $success    = false;
        $result     = null;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.easyaccess.token'),
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $httpResponse = Http::withHeaders($requestHeaders)->timeout(20)->post($endpoint, $payload);

            $httpStatus      = $httpResponse->status();
            $responseHeaders = $httpResponse->headers();
            $data            = $httpResponse->json() ?? [];
            $status          = isset($data['status']) ? ($data['status'] === 'success') : ($data['code'] === 100);

            if ($status) {
                $success = true;
                $result  = response()->json([
                    'success'          => true,
                    'customer_name'    => $data['customer_name'] ?? null,
                    'customer_address' => $data['customer_address'] ?? null,
                    'meter_number'     => $meterNumber,
                ]);
            } else {
                $errMsg = $data['message'] ?? 'Invalid meter number.';
                $result = response()->json([
                    'success' => false,
                    'message' => is_array($errMsg) ? json_encode($errMsg) : $errMsg,
                ], 422);
            }
        } catch (\Exception $e) {
            $data   = ['error' => $e->getMessage()];
            Log::error('EasyAccess meter validation failed', ['meter' => $meterNumber, 'error' => $e->getMessage()]);
            $result = response()->json(['success' => false, 'message' => 'Meter validation unavailable. Please try again.'], 503);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'electricity_validate',
                'provider'         => 'easyaccess',
                'reference'        => $meterNumber,
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

    // ─── VTPass: purchase ─────────────────────────────────────────────────────

    private function callVtpassElectricity(
        ElectricityDisco $disco,
        string $meterType,
        string $meterNumber,
        float $amount,
        string $phone,
        string $reference
    ): array {
        $vtpassRef = date('YmdHis') . Str::upper(Str::random(6));
        $endpoint  = config('services.vtpass.base_url') . '/api/pay';
        $payload   = [
            'request_id'     => $vtpassRef,
            'serviceID'      => $disco->slug,
            'variation_code' => $meterType,
            'billersCode'    => $meterNumber,
            'amount'         => (int) $amount,
            'phone'          => $phone,
        ];
        $data        = [];
        $httpStatus  = null;
        $success     = false;
        $apiRef      = $vtpassRef;
        $token       = null;
        $units       = null;
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
                $rawToken     = $txn['token'] ?? $data['purchased_code'] ?? $data['Token'] ?? null;
                $token        = $rawToken ? preg_replace('/^Token\s*:\s*/i', '', trim((string) $rawToken)) : null;
                $units        = $txn['units'] ?? $data['RefundUnits'] ?? $data['FreeUnits'] ?? null;
                $customerName = $txn['customerName'] ?? $data['customerName'] ?? $data['Name'] ?? null;
            } else {
                $data['message'] = $data['response_description'] ?? 'Electricity vending failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('VTPass electricity request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'electricity',
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

        return [
            'success'       => $success,
            'reference'     => $apiRef,
            'token'         => $token,
            'units'         => $units,
            'customer_name' => $customerName,
            'response'      => $data,
        ];
    }

    // ─── Payscribe: purchase ──────────────────────────────────────────────────

    private function callPayscribeElectricity(
        ElectricityDisco $disco,
        string $meterType,
        string $meterNumber,
        float $amount,
        string $reference
    ): array {
        $endpoint = config('services.payscribe.base_url') . '/electricity/vend';
        $payload  = [
            'service'       => $disco->idForApi('payscribe'),
            'meter_number'  => $meterNumber,
            'meter_type'    => $meterType,
            'amount'        => (string) (int) $amount,
            'customer_name' => auth()->user()->name,
            'ref'           => $reference,
        ];
        $data         = [];
        $httpStatus   = null;
        $success      = false;
        $apiRef       = $reference;
        $token        = null;
        $units        = null;
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
            $statusCode = $data['status_code'] ?? '';
            $success    = in_array($statusCode, ['200', '201']);

            if ($success) {
                $details      = $data['message']['details'] ?? [];
                $token        = isset($details['token']) ? trim((string) $details['token']) : null;
                $units        = $details['unit'] ?? null;
                $customerName = $details['customer_name'] ?? null;
                $apiRef       = $data['reference'] ?? $reference;
            } else {
                $data['message'] = $data['description'] ?? 'Payscribe electricity vending failed.';
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Payscribe electricity request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'electricity',
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

        return [
            'success'       => $success,
            'reference'     => $apiRef,
            'token'         => $token,
            'units'         => $units,
            'customer_name' => $customerName,
            'response'      => $data,
        ];
    }

    // ─── EasyAccess: purchase ─────────────────────────────────────────────────

    private function callEasyaccessElectricity(
        ElectricityDisco $disco,
        string $meterType,
        string $meterNumber,
        float $amount,
        string $reference
    ): array {
        $endpoint = config('services.easyaccess.base_url') . '/pay-electricity';
        if($meterType === 'prepaid') {
            $meterTypeRequest = '1';
        } else {
            $meterTypeRequest = '2';
        }
        $payload  = [
            'company'   => $disco->idForApi('easyaccess'),
            'metertype' => $meterTypeRequest,
            'meterno'   => $meterNumber,
            'amount'    => (int) $amount,
        ];
        $data        = [];
        $httpStatus  = null;
        $success     = false;
        $apiRef      = $reference;
        $token       = null;
        $units       = null;
        $customerName = null;

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('services.easyaccess.token'),
            'Cache-Control' => 'no-cache',
        ];
        $responseHeaders = null;
        $start = hrtime(true);
        try {
            $response   = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $raw        = $response->json();
            $data       = is_array($raw) ? $raw : ['message' => 'Unknown EasyAccess response'];
            $statusCode = $data['status'] ?? 'false';
            $msgCode    = $data['code']  ?? '';
            $success    = ($statusCode === 'success') && ($msgCode === '200');

            if ($msgCode === '200') {
                $msg  = $data['message'] ?? [];
                $rawToken = $data['token'] ?? $msg['mainToken'] ?? $msg['token'] ?? $msg['Token'] ?? null;
                $token    = $rawToken ? preg_replace('/^Token\s*:\s*/i', '', trim((string) $rawToken)) : null;
                $units    = $data['meter_units'] ?? $msg['mainTokenUnit'] ?? $msg['units'] ?? null;
                // Fetch customer name via a follow-up verify call (best-effort)
                $customerName = $data['customer_name'];
            } else {
                $errorMsg = $data['message'] ?? 'EasyAccess electricity vending failed.';
                $data['message'] = is_array($errorMsg) ? ($errorMsg['content'] ?? json_encode($errorMsg)) : $errorMsg;
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('EasyAccess electricity request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'     => auth()->id(),
                'service'          => 'electricity',
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

        return [
            'success'       => $success,
            'reference'     => $apiRef,
            'token'         => $token,
            'units'         => $units,
            'customer_name' => $customerName,
            'response'      => $data,
        ];
    }
}

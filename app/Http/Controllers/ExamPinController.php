<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\ExamPinType;
use App\Models\ServiceTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamPinController extends Controller
{
    // ─── Page ─────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $user      = auth()->user();
        $examTypes = ExamPinType::active()->orderBy('amount', 'desc')->get();

        $history = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'epin')
            ->latest()
            ->paginate(10);

        return view('services.epins', compact('user', 'examTypes', 'history'));
    }

    // ─── Purchase ─────────────────────────────────────────────────────────────

    public function purchase(Request $request): JsonResponse
    {
        if (AppSetting::get('service_epins', '1') !== '1') {
            return response()->json(['success' => false, 'message' => 'Exam Pins service is temporarily unavailable.'], 503);
        }

        $request->validate([
            'exam_type_id'    => ['required', 'integer', 'exists:exam_pin_types,id'],
            'quantity'        => ['required', 'integer', 'min:1', 'max:10'],
            'phone'           => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user     = auth()->user();
        $examType = ExamPinType::where('id', $request->exam_type_id)->where('is_active', true)->firstOrFail();
        $quantity = (int) $request->quantity;
        $amount   = $examType->amount * $quantity;
        $phone    = preg_replace('/^\+234/', '0', $request->phone);

        // ── 0. Verify PIN ────────────────────────────────────────────────────
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json([
                'success'   => false,
                'message'   => 'Incorrect transaction PIN. Please try again.',
                'pin_error' => true,
            ], 422);
        }

        // ── 1. Daily spending limit ──────────────────────────────────────────
        $dailyLimit = (float) AppSetting::get('epins_daily_limit', 50000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'epin')
            ->where('status', 'success')
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $amount) > $dailyLimit) {
            return response()->json([
                'success' => false,
                'message' => 'Daily exam pins spending limit of ₦' . number_format($dailyLimit, 0) . ' reached.',
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
        $reference = 'EPN' . date('YmdHis') . Str::upper(Str::random(11));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($wallet, $amount, $examType, $quantity, $reference) {
                return $wallet->debit(
                    $amount,
                    $examType->name . ' × ' . $quantity,
                    $reference,
                    ['service' => 'epin', 'exam_type' => $examType->slug, 'quantity' => $quantity]
                );
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // ── 4. Call API ──────────────────────────────────────────────────────
        $result = $this->callExamPinApi($examType, $quantity, $phone, $reference);

        $apiSuccess  = $result['success'];
        $apiRef      = $result['apiRef'];
        $pins        = $result['pins'];
        $apiResponse = $result['response'];

        // ── 5. Refund on failure ─────────────────────────────────────────────
        if (!$apiSuccess) {
            try {
                DB::transaction(function () use ($wallet, $amount, $reference, $examType, $quantity) {
                    $wallet->credit(
                        $amount,
                        'Refund: ' . $examType->name . ' × ' . $quantity . ' failed',
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Exam pin refund failed', [
                    'user_id'   => $user->id,
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success'  => false,
                'refunded' => true,
                'message'  => $apiResponse['message'] ?? 'Exam pin purchase failed.',
            ], 422);
        }

        // ── 6. Record successful transaction ─────────────────────────────────
        $api = AppSetting::get('epins_api', 'vtpass');

        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'epin',
            'provider'              => $examType->slug,
            'recipient'             => $phone,
            'amount'                => $amount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRef,
            'api_response'          => array_merge(is_array($apiResponse) ? $apiResponse : [], [
                'api_provider' => $api,
                'exam_type'    => $examType->name,
                'quantity'     => $quantity,
                'pins'         => $pins,
            ]),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => $examType->name . ' × ' . $quantity . ' purchased successfully.',
            'pins'      => $pins,
            'balance'   => '₦' . number_format((float) $wallet->fresh()->balance, 2),
            'reference' => $reference,
            'exam_type' => $examType->name,
            'quantity'  => $quantity,
        ]);
    }

    // ─── API Dispatcher ───────────────────────────────────────────────────────

    private function callExamPinApi(ExamPinType $examType, int $quantity, string $phone, string $reference): array
    {
        $api = AppSetting::get('epins_api', 'vtpass');

        return match ($api) {
            'easyaccess'  => $this->callEasyaccessExamPin($examType, $quantity, $reference),
            'primebiller' => $this->callPrimebillerExamPin($examType, $quantity, $reference),
            default       => $this->callVtpassExamPin($examType, $quantity, $phone, $reference),
        };
    }

    // ─── VTPass: purchase ─────────────────────────────────────────────────────

    private function callVtpassExamPin(
        ExamPinType $examType,
        int $quantity,
        string $phone,
        string $reference
    ): array {
        $vtpassRef = date('YmdHis') . Str::upper(Str::random(6));
        $endpoint  = config('services.vtpass.base_url') . '/api/pay';
        $payload   = [
            'request_id'   => $vtpassRef,
            'serviceID'    => $examType->vtpass_service_id,
            'billersCode'  => $phone,
            'amount'       => (int) $examType->amount,
            'phone'        => $phone,
            'quantity'     => $quantity,
        ];
        $data            = [];
        $httpStatus      = null;
        $success         = false;
        $apiRef          = $vtpassRef;
        $pins            = [];
        $responseHeaders = null;

        $requestHeaders = [
            'api-key'    => config('services.vtpass.api_key'),
            'public-key' => config('services.vtpass.public_key'),
        ];
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $raw             = $response->json();
            $data            = is_array($raw) ? $raw : ['message' => 'Unknown VTPass response'];
            $code            = $data['code'] ?? '';
            $success         = in_array($code, ['000', '099']);
            $txn             = $data['content']['transactions'] ?? [];
            $apiRef          = $txn['transactionId'] ?? ($data['requestId'] ?? $vtpassRef);

            if ($success) {
                // VTPass may return pins as an array under 'pins', or as token/token2/…
                if (!empty($txn['pins']) && is_array($txn['pins'])) {
                    foreach ($txn['pins'] as $p) {
                        $pins[] = [
                            'pin'    => $p['pin']          ?? ($p['token']  ?? ''),
                            'serial' => $p['serialnumber'] ?? ($p['serial'] ?? null),
                        ];
                    }
                } else {
                    // Fallback: look for token, token2, token3…
                    for ($i = 1; $i <= $quantity; $i++) {
                        $key = $i === 1 ? 'token' : 'token' . $i;
                        $t   = $txn[$key] ?? null;
                        if ($t) {
                            $pins[] = ['pin' => $t, 'serial' => null];
                        }
                    }
                }
            } else {
                $data['message'] = $data['response_description'] ?? 'Exam pin purchase failed.';
            }
        } catch (\Exception $e) {
            $data    = ['error' => $e->getMessage(), 'message' => 'Request failed. Please try again.'];
            $success = false;
            Log::error('VTPass exam pin purchase failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'epin',
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

        return compact('success', 'apiRef', 'pins') + ['response' => $data];
    }

    // ─── EasyAccess: purchase ─────────────────────────────────────────────────

    private function callEasyaccessExamPin(ExamPinType $examType, int $quantity, string $reference): array
    {
        $baseUrl  = config('services.easyaccess.base_url');
        $endpoint = $baseUrl . $examType->easyaccess_endpoint;
        $payload  = ['no_of_pins' => $quantity];

        $data            = [];
        $httpStatus      = null;
        $success         = false;
        $apiRef          = $reference;
        $pins            = [];
        $responseHeaders = null;

        $requestHeaders = [
            'AuthorizationToken' => config('services.easyaccess.token'),
            'cache-control'      => 'no-cache',
        ];
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->asForm()->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $raw             = $response->json();
            $data            = is_array($raw) ? $raw : ['message' => 'Unknown EasyAccess response'];
            $rawSuccess      = $data['success'] ?? 'false';
            $success         = ($rawSuccess === 'true' || $rawSuccess === true);
            $apiRef          = $data['reference_no'] ?? $reference;

            if ($success) {
                $pins = $this->parsePins($data);
            } else {
                $data['message'] = $data['message'] ?? 'Exam pin purchase failed.';
            }
        } catch (\Exception $e) {
            $data    = ['error' => $e->getMessage(), 'message' => 'Request failed. Please try again.'];
            $success = false;
            Log::error('EasyAccess exam pin purchase failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'epin',
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

        return compact('success', 'apiRef', 'pins') + ['response' => $data];
    }

    // ─── PrimeBiller: purchase ────────────────────────────────────────────────

    private function callPrimebillerExamPin(ExamPinType $examType, int $quantity, string $reference): array
    {
        $endpoint = config('services.primebiller.base_url') . '/exam/';
        $payload  = [
            'provider' => $examType->primebiller_provider_id,
            'quantity' => $quantity,
            'ref'      => $reference,
        ];

        $data            = [];
        $httpStatus      = null;
        $success         = false;
        $apiRef          = $reference;
        $pins            = [];
        $responseHeaders = null;

        $requestHeaders = [
            'Authorization' => 'Token ' . config('services.primebiller.token'),
            'cache-control' => 'no-cache',
        ];
        $start = hrtime(true);
        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(30)->asForm()->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $raw             = $response->json();
            $data            = is_array($raw) ? $raw : ['message' => 'Unknown PrimeBiller response'];
            $success         = (($data['status'] ?? '') === 'success');
            $apiRef          = $data['reference'] ?? $reference;

            if ($success) {
                $pins = $this->parsePins($data);
            } else {
                $data['message'] = $data['message'] ?? 'Exam pin purchase failed.';
            }
        } catch (\Exception $e) {
            $data    = ['error' => $e->getMessage(), 'message' => 'Request failed. Please try again.'];
            $success = false;
            Log::error('PrimeBiller exam pin purchase failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'epin',
                'provider'         => 'primebiller',
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

        return compact('success', 'apiRef', 'pins') + ['response' => $data];
    }

    // ─── PIN Parser ───────────────────────────────────────────────────────────

    /**
     * Extract pins from an API response array.
     * Keys "pin", "pin2", "pin3"… each may be "PINVALUE<=>SERIAL" or just "PINVALUE".
     */
    private function parsePins(array $response): array
    {
        $pins = [];
        foreach ($response as $key => $value) {
            if (!str_starts_with((string) $key, 'pin') || !is_string($value) || $value === '') {
                continue;
            }
            if (str_contains($value, '<=>')) {
                [$pin, $serial] = explode('<=>', $value, 2);
                $pins[] = ['pin' => trim($pin), 'serial' => trim($serial)];
            } else {
                $pins[] = ['pin' => trim($value), 'serial' => null];
            }
        }
        return $pins;
    }
}

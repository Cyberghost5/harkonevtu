<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PrintedVoucher;
use App\Models\NetworkAirtime;
use App\Models\ApiLog;
use App\Services\ProviderAlertService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Str;

use Illuminate\Routing\Controllers\HasMiddleware;

class VoucherPrintingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                if (\App\Models\AppSetting::get('service_recharge_card_printing', '1') !== '1') {
                    if ($request->expectsJson()) {
                        return response()->json(['success' => false, 'message' => 'Recharge Card printing is currently disabled.'], 503);
                    }
                    return redirect()->route('dashboard')->with('error', 'Recharge Card printing is currently disabled.');
                }
                return $next($request);
            }
        ];
    }
    public function index(Request $request): View
    {
        $user = auth()->user();
        $type = $request->query('type', 'airtime');
        if (!in_array($type, ['airtime', 'data'])) {
            $type = 'airtime';
        }

        $vouchers = PrintedVoucher::where('user_id', $user->id)
            ->where('type', $type)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('services.print-pins', compact('user', 'type', 'vouchers'));
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'type'            => ['required', 'string', 'in:airtime,data'],
            'network'         => ['required', 'string', 'in:mtn,airtel,glo,9mobile'],
            'value'           => ['required', 'integer', $request->type === 'airtime' ? 'in:100,200,500,1000' : 'min:50'],
            'quantity'        => ['required', 'integer', 'min:1', 'max:50'],
            'name_on_card'    => ['nullable', 'string', 'max:50'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user = auth()->user();
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json([
                'success'   => false,
                'message'   => 'Incorrect transaction PIN. Please try again.',
                'pin_error' => true,
            ], 422);
        }

        $type = $request->type;
        $network = $request->network;
        $value = (float) $request->value;
        $quantity = (int) $request->quantity;
        $nameOnCard = $request->name_on_card;

        $totalCost = $value * $quantity;

        if (!$user->wallet || !$user->wallet->hasSufficientBalance($totalCost)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance for this voucher generation.',
            ], 422);
        }

        $ref = 'VCH-' . strtoupper(Str::random(12));

        $netKey = ($network === '9mobile') ? 'etisalat' : $network;
        $netApi = AppSetting::get('airtime_pin_net_' . $netKey);
        
        if (empty($netApi)) {
            $netApi = AppSetting::get('airtime_pin_api');
        }

        if ($netApi === 'Disable') {
            return response()->json([
                'success' => false,
                'message' => 'Voucher printing for ' . strtoupper($network) . ' is currently disabled.'
            ], 422);
        }

        $useClubkonnect = ($netApi === 'clubkonnect');
        $useErs = ($network === 'mtn' && $netApi === 'mtn_ers');
        $useGloErs = ($network === 'glo' && $netApi === 'glo_ers');
        $ersService = app(\App\Services\MtnErsSoapService::class);
        $ersOriginator = $ersService->formatMsisdn($ersService->getOriginatorMsisdn());

        try {
            // 1. Debit wallet first in a short-lived transaction
            DB::transaction(function () use ($user, $totalCost, $network, $type, $quantity, $value, $ref) {
                $user->wallet->debit(
                    $totalCost,
                    "Voucher generation: {$quantity}x {$network} " . ucfirst($type) . " ₦" . number_format($value, 2),
                    $ref,
                    ['source' => 'voucher_printing']
                );
            });

            $pins = [];
            // 2. Perform external API HTTP requests outside the database transaction
            if ($useClubkonnect) {
                $networkCodeMap = [
                    'mtn'      => '01',
                    'glo'      => '02',
                    '9mobile'  => '03',
                    'etisalat' => '03',
                    'airtel'   => '04',
                ];
                $networkModel = NetworkAirtime::where('network_key', $netKey)->first();
                $networkCode = $networkModel?->clubkonnect_id ?? ($networkCodeMap[$network] ?? '01');

                $result = $this->callClubkonnectEpin($networkCode, (int) $value, $quantity, $ref);

                if (!$result['success'] || empty($result['pins'])) {
                    // Refund the full amount on failure
                    $user->wallet->credit(
                        $totalCost,
                        "Refund for failed voucher generation: {$quantity}x {$network} " . ucfirst($type),
                        $ref . '-REFUND',
                        ['source' => 'voucher_printing']
                    );
                    throw new \Exception('Clubkonnect EPIN generation failed: ' . ($result['message'] ?? 'No PINs returned'));
                }

                $pins = $result['pins'];

                // Refund for partial fulfillment if provider returned fewer pins than requested
                if (count($pins) < $quantity) {
                    $missingCount = $quantity - count($pins);
                    $refundAmount = $missingCount * $value;
                    if ($refundAmount > 0) {
                        $user->wallet->credit(
                            $refundAmount,
                            "Refund for partial voucher generation: {$missingCount}x {$network} " . ucfirst($type),
                            $ref . '-PARTIAL-REFUND',
                            ['source' => 'voucher_printing']
                        );
                    }
                }
            } else {
                for ($i = 0; $i < $quantity; $i++) {
                    try {
                        if ($useErs) {
                            // Request voucher from ERS SOAP API
                            $result = $ersService->vend($ersOriginator, $value, 7); // 7 = Voucher
                            if (!$result['status']) {
                                throw new \Exception('MTN ERS Voucher generation failed: ' . ($result['message'] ?? 'Unknown Error'));
                            }
                            $pin = $result['data']['voucherPIN'] ?? null;
                            $serial = $result['data']['voucherSerial'] ?? null;
                            
                            if (empty($pin) || empty($serial)) {
                                throw new \Exception('MTN ERS response is missing voucher PIN or Serial.');
                            }
                        } elseif ($useGloErs) {
                            // Request voucher from Glo ERS SOAP API (using requestPurchase with VOT)
                            $gloErsService = app(\App\Services\GloErsSoapService::class);
                            $result = $gloErsService->purchaseVoucher($user->phone ?: '2348050000000', $value, 'VOT', $ref . '-' . $i);
                            
                            if (!$result['success']) {
                                throw new \Exception('Glo ERS Voucher generation failed: ' . ($result['response']['message'] ?? $result['message'] ?? 'Unknown Error'));
                            }

                            $pin = $result['pin'] ?? null;
                            $serial = $result['serial'] ?? null;

                            if (empty($pin) || empty($serial)) {
                                throw new \Exception('Glo ERS response is missing voucher PIN or Serial. Response description: ' . ($result['response']['resultDescription'] ?? 'None'));
                            }
                        } else {
                            // Pin is a random 15-digit number
                            $pin = str_pad((string) random_int(100000000000000, 999999999999999), 15, '0', STR_PAD_LEFT);
                            // Serial is a random 10-digit number
                            $serial = str_pad((string) random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
                        }

                        $pins[] = [
                            'pin' => $pin,
                            'serial' => $serial,
                        ];
                    } catch (\Exception $e) {
                        // Refund the wallet for the failed and remaining unsent quantity of pins
                        $remainingCount = $quantity - count($pins);
                        $refundAmount = $remainingCount * $value;
                        if ($refundAmount > 0) {
                            $user->wallet->credit(
                                $refundAmount,
                                "Refund for failed voucher generation: {$remainingCount}x {$network} " . ucfirst($type),
                                $ref . '-REFUND',
                                ['source' => 'voucher_printing']
                            );
                        }
                        throw $e;
                    }
                }
            }

            // 3. Save successfully generated vouchers to the database
            foreach ($pins as $p) {
                PrintedVoucher::create([
                    'user_id'       => $user->id,
                    'type'          => $type,
                    'network'       => $network,
                    'name_on_card'  => $nameOnCard,
                    'value'         => $value,
                    'pin'           => $p['pin'],
                    'serial_number' => $p['serial'],
                    'status'        => 'unused',
                ]);
            }
        } catch (\Exception $e) {
            $rawMsg = $e->getMessage();
            $friendlyMsg = ProviderAlertService::processFailure($rawMsg, [
                'service'   => 'Voucher Printing (' . strtoupper($network ?? '') . ' ' . ucfirst($type ?? '') . ')',
                'provider'  => $netApi ?? 'ers',
                'amount'    => $totalCost ?? 0,
                'user'      => $user,
                'recipient' => $user->phone ?? 'Vouchers',
                'reference' => $ref ?? 'VOUCHER',
            ], true);

            return response()->json([
                'success' => false,
                'message' => $friendlyMsg,
            ], 422);
        }

        $generatedCount = count($pins);
        $walletBalance = '₦' . number_format($user->wallet->fresh()->balance, 2);
        return response()->json([
            'success' => true,
            'message' => "Successfully generated {$generatedCount} vouchers!",
            'balance' => $walletBalance,
        ]);
    }

    public function printVouchers(Request $request): View|RedirectResponse
    {
        $user = auth()->user();
        $ids = $request->input('ids');

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'Please select at least one voucher to print.');
        }

        $vouchers = PrintedVoucher::where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->get();

        if ($vouchers->isEmpty()) {
            return back()->with('error', 'No valid vouchers found.');
        }

        return view('services.print-pins-layout', compact('vouchers'));
    }

    /**
     * Call Clubkonnect (Nellobyte Systems) API to purchase Airtime Recharge Card PINs (EPIN).
     *
     * @param string $networkCode  '01' (MTN), '02' (Glo), '03' (9mobile), '04' (Airtel)
     * @param int $value           Denomination (100, 200, 500, 1000)
     * @param int $quantity        Number of pins requested
     * @param string $reference    Unique transaction reference
     * @return array
     */
    private function callClubkonnectEpin(string $networkCode, int $value, int $quantity, string $reference): array
    {
        $endpoint = 'https://www.nellobytesystems.com/APIEPINV1.asp';
        $userId   = config('services.clubkonnect.user_id') ?: AppSetting::get('clubkonnect_user_id');
        $apiKey   = config('services.clubkonnect.api_key') ?: AppSetting::get('clubkonnect_api_key');

        if (empty($userId) || empty($apiKey)) {
            Log::warning('Clubkonnect EPIN: Missing UserID or APIKey.');
            return [
                'success' => false,
                'pins'    => [],
                'message' => 'Clubkonnect credentials (UserID or APIKey) are not configured in system settings.',
            ];
        }

        $payload = [
            'UserID'        => $userId,
            'APIKey'        => $apiKey,
            'MobileNetwork' => $networkCode,
            'Value'         => $value,
            'Quantity'      => $quantity,
            'RequestID'     => $reference,
        ];

        $data            = [];
        $httpStatus      = null;
        $success         = false;
        $pins            = [];
        $requestHeaders  = [];
        $responseHeaders = null;
        $start           = hrtime(true);

        try {
            $response        = Http::timeout(45)->get($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];

            // Primary format: {"ORDER_ID": "...", "TXN_EPIN": [ {"pin": "...", "sno": "..."}, ... ]}
            if (isset($data['TXN_EPIN']) && is_array($data['TXN_EPIN']) && !empty($data['TXN_EPIN'])) {
                foreach ($data['TXN_EPIN'] as $item) {
                    $pin = $item['pin'] ?? null;
                    $serial = $item['sno'] ?? $item['serial'] ?? $item['batchno'] ?? null;
                    if (!empty($pin)) {
                        $pins[] = [
                            'pin'    => (string) $pin,
                            'serial' => (string) ($serial ?? ''),
                        ];
                    }
                }
                $success = count($pins) > 0;
            } elseif (isset($data['status']) && in_array(strtoupper($data['status']), ['ORDER_RECEIVED', 'ORDER_COMPLETED']) && isset($data['pin'])) {
                $pins[] = [
                    'pin'    => (string) $data['pin'],
                    'serial' => (string) ($data['sno'] ?? $data['serial'] ?? ''),
                ];
                $success = true;
            }

            if (!$success) {
                $errorMsg = $data['statusremark'] ?? $data['orderremark'] ?? $data['message'] ?? $data['error'] ?? 'EPIN generation failed from provider.';
                $data['message'] = $errorMsg;
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('Clubkonnect EPIN request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'voucher',
                'provider'         => 'clubkonnect',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'GET',
                'payload'          => [
                    'UserID'        => $userId ? substr($userId, 0, 3) . '***' : null,
                    'MobileNetwork' => $networkCode,
                    'Value'         => $value,
                    'Quantity'      => $quantity,
                    'RequestID'     => $reference,
                ],
                'request_headers'  => $requestHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return [
            'success'   => $success,
            'pins'      => $pins,
            'order_id'  => $data['ORDER_ID'] ?? $data['orderid'] ?? $reference,
            'message'   => $data['message'] ?? ($success ? 'Success' : 'EPIN generation failed.'),
            'raw'       => $data,
        ];
    }
}

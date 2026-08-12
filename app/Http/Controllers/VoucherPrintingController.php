<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PrintedVoucher;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
            'value'           => ['required', 'numeric', 'min:50'],
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
            for ($i = 0; $i < $quantity; $i++) {
                try {
                    if ($useErs) {
                        // Request voucher from ERS SOAP API
                        $result = $ersService->vend($ersOriginator, $value, 7); // 7 = Voucher
                        if (!$result['status']) {
                            \App\Models\ApiLog::record([
                                'user_id'          => $user->id,
                                'service'          => 'voucher',
                                'provider'         => 'mtn_ers',
                                'reference'        => $ref . '-' . $i,
                                'endpoint'         => AppSetting::get('mtn_ers_endpoint', 'https://ers.seamless.se/services/ERSExchange3GPort'),
                                'method'           => 'POST',
                                'payload'          => ['value' => $value, 'tariffTypeId' => 7],
                                'request_headers'  => ['SoapAction' => 'urn:Vend'],
                                'response'         => $result,
                                'http_status'      => 500,
                                'response_headers' => null,
                                'duration_ms'      => 0,
                                'success'          => false,
                            ]);
                            throw new \Exception('MTN ERS Voucher generation failed: ' . ($result['message'] ?? 'Unknown Error'));
                        }
                        $pin = $result['data']['voucherPIN'] ?? null;
                        $serial = $result['data']['voucherSerial'] ?? null;
                        
                        if (empty($pin) || empty($serial)) {
                            throw new \Exception('MTN ERS response is missing voucher PIN or Serial.');
                        }

                        \App\Models\ApiLog::record([
                            'user_id'          => $user->id,
                            'service'          => 'voucher',
                            'provider'         => 'mtn_ers',
                            'reference'        => $ref . '-' . $i,
                            'endpoint'         => AppSetting::get('mtn_ers_endpoint', 'https://ers.seamless.se/services/ERSExchange3GPort'),
                            'method'           => 'POST',
                            'payload'          => ['value' => $value, 'tariffTypeId' => 7],
                            'request_headers'  => ['SoapAction' => 'urn:Vend'],
                            'response'         => $result['data'] ?? $result,
                            'http_status'      => 200,
                            'response_headers' => null,
                            'duration_ms'      => 0,
                            'success'          => true,
                        ]);
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $walletBalance = '₦' . number_format($user->wallet->balance, 2);
        return response()->json([
            'success' => true,
            'message' => "Successfully generated {$quantity} vouchers!",
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
}

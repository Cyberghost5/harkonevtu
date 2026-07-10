<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PrintedVoucher;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Str;

class VoucherPrintingController extends Controller
{
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

    public function generate(Request $request): RedirectResponse
    {
        $request->validate([
            'type'         => ['required', 'string', 'in:airtime,data'],
            'network'      => ['required', 'string', 'in:mtn,airtel,glo,9mobile'],
            'value'        => ['required', 'numeric', 'min:50'],
            'quantity'     => ['required', 'integer', 'min:1', 'max:50'],
            'name_on_card' => ['nullable', 'string', 'max:50'],
        ]);

        $user = auth()->user();
        $type = $request->type;
        $network = $request->network;
        $value = (float) $request->value;
        $quantity = (int) $request->quantity;
        $nameOnCard = $request->name_on_card;

        $totalCost = $value * $quantity;

        if (!$user->wallet || !$user->wallet->hasSufficientBalance($totalCost)) {
            return back()->with('error', 'Insufficient wallet balance for this voucher generation.');
        }

        $ref = 'VCH-' . strtoupper(Str::random(12));

        $useErs = ($network === 'mtn' && AppSetting::get('epins_api') === 'mtn_ers');
        $ersService = app(\App\Services\MtnErsSoapService::class);
        $ersOriginator = $ersService->formatMsisdn($ersService->getOriginatorMsisdn());

        try {
            DB::transaction(function () use ($user, $type, $network, $value, $quantity, $nameOnCard, $totalCost, $ref, $useErs, $ersService, $ersOriginator) {
                // Debit wallet
                $user->wallet->debit(
                    $totalCost,
                    "Voucher generation: {$quantity}x {$network} " . ucfirst($type) . " ₦" . number_format($value, 2),
                    $ref,
                    ['source' => 'voucher_printing']
                );

                // Generate pins & serial numbers
                for ($i = 0; $i < $quantity; $i++) {
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
                    } else {
                        // Pin is a random 15-digit number
                        $pin = str_pad((string) random_int(100000000000000, 999999999999999), 15, '0', STR_PAD_LEFT);
                        // Serial is a random 10-digit number
                        $serial = str_pad((string) random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
                    }

                    PrintedVoucher::create([
                        'user_id'       => $user->id,
                        'type'          => $type,
                        'network'       => $network,
                        'name_on_card'  => $nameOnCard,
                        'value'         => $value,
                        'pin'           => $pin,
                        'serial_number' => $serial,
                        'status'        => 'unused',
                    ]);
                }
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('services.print-pins', ['type' => $type])
            ->with('success', "Successfully generated {$quantity} vouchers!");
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

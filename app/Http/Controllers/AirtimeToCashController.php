<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\AirtimeToCashRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class AirtimeToCashController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        
        $settings = AppSetting::getMany([
            'airtime2cash_phone',
            'airtime2cash_tx_charge',
            'airtime2cash_min_per_payment',
            'airtime2cash_max_per_payment',
        ]);

        $requests = AirtimeToCashRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('services.airtime-to-cash', compact('user', 'settings', 'requests'));
    }

    public function submit(Request $request): RedirectResponse
    {
        $min = (float) AppSetting::get('airtime2cash_min_per_payment', 500);
        $max = (float) AppSetting::get('airtime2cash_max_per_payment', 50000);

        $request->validate([
            'network'     => ['required', 'string', 'in:mtn,airtel,glo,9mobile'],
            'phone'       => ['required', 'string', 'digits:11'],
            'amount'      => ['required', 'numeric', "min:{$min}", "max:{$max}"],
            'screenshot'  => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:3072'], // 3MB
        ]);

        $amount = (float) $request->amount;
        $chargePercent = (float) AppSetting::get('airtime2cash_tx_charge', 20); // default 20%
        $charge = ($amount * $chargePercent) / 100;
        $receiveAmount = $amount - $charge;

        $path = $request->file('screenshot')->store('airtime-proofs', 'public');

        AirtimeToCashRequest::create([
            'user_id'        => auth()->id(),
            'network'        => $request->network,
            'phone'          => $request->phone,
            'amount'         => $amount,
            'charge'         => $charge,
            'receive_amount' => $receiveAmount,
            'screenshot'     => $path,
            'status'         => 'pending',
        ]);

        return redirect()->route('services.airtime-to-cash')
            ->with('success', 'Your airtime conversion request has been submitted and is awaiting verification.');
    }
}

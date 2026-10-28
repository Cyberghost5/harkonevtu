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
    public function index(): View|RedirectResponse
    {
        if (AppSetting::get('service_airtime_to_cash', '1') !== '1') {
            return redirect()->route('dashboard')->with('error', 'Airtime to Cash service is currently disabled.');
        }

        $user = auth()->user();
        
        $settings = AppSetting::getMany([
            'airtime2cash_phone',
            'airtime2cash_phone_mtn',
            'airtime2cash_phone_airtel',
            'airtime2cash_phone_glo',
            'airtime2cash_phone_9mobile',
            'airtime2cash_tx_charge',
            'airtime2cash_charge_mtn',
            'airtime2cash_charge_airtel',
            'airtime2cash_charge_glo',
            'airtime2cash_charge_9mobile',
            'airtime2cash_min_per_payment',
            'airtime2cash_max_per_payment',
        ]);

        $defaultPhone  = $settings['airtime2cash_phone'] ?? '';
        $defaultCharge = (float) ($settings['airtime2cash_tx_charge'] ?? 20);

        $networks = [
            'mtn' => [
                'name'          => 'MTN',
                'full_name'     => 'MTN Nigeria',
                'code'          => 'mtn',
                'phone'         => !empty($settings['airtime2cash_phone_mtn']) ? $settings['airtime2cash_phone_mtn'] : $defaultPhone,
                'charge'        => (float) (!empty($settings['airtime2cash_charge_mtn']) ? $settings['airtime2cash_charge_mtn'] : $defaultCharge),
                'ussd_template' => '*321*{phone}*{amount}*{pin}#',
                'default_pin'   => '0000',
                'pin_help'      => 'Change PIN via SMS: Send "defaultPIN NewPIN NewPIN" to 321 (e.g. 0000 1234 1234)',
                'ussd_short'    => '*321#',
                'brand_color'   => '#FFCC00',
                'protocol'      => "Share 'N' Sell",
            ],
            'airtel' => [
                'name'          => 'Airtel',
                'full_name'     => 'Airtel Nigeria',
                'code'          => 'airtel',
                'phone'         => !empty($settings['airtime2cash_phone_airtel']) ? $settings['airtime2cash_phone_airtel'] : $defaultPhone,
                'charge'        => (float) (!empty($settings['airtime2cash_charge_airtel']) ? $settings['airtime2cash_charge_airtel'] : $defaultCharge),
                'ussd_template' => '*432*1*{phone}*{amount}*{pin}#',
                'default_pin'   => '1234',
                'pin_help'      => 'Change PIN via SMS: Send "PIN OldPIN NewPIN" to 432 (e.g. PIN 1234 5678)',
                'ussd_short'    => '*432#',
                'brand_color'   => '#E60000',
                'protocol'      => 'Airtel Me2U',
            ],
            'glo' => [
                'name'          => 'Glo',
                'full_name'     => 'Globacom Nigeria',
                'code'          => 'glo',
                'phone'         => !empty($settings['airtime2cash_phone_glo']) ? $settings['airtime2cash_phone_glo'] : $defaultPhone,
                'charge'        => (float) (!empty($settings['airtime2cash_charge_glo']) ? $settings['airtime2cash_charge_glo'] : $defaultCharge),
                'ussd_template' => '*131*{phone}*{amount}*{pin}#',
                'default_pin'   => '00000',
                'pin_help'      => 'Change PIN: Dial *132*OldPIN*NewPIN*NewPIN# (e.g. *132*00000*12345*12345#)',
                'ussd_short'    => '*131#',
                'brand_color'   => '#009B00',
                'protocol'      => 'Glo EasyShare',
            ],
            '9mobile' => [
                'name'          => '9mobile',
                'full_name'     => '9mobile (Etisalat)',
                'code'          => '9mobile',
                'phone'         => !empty($settings['airtime2cash_phone_9mobile']) ? $settings['airtime2cash_phone_9mobile'] : $defaultPhone,
                'charge'        => (float) (!empty($settings['airtime2cash_charge_9mobile']) ? $settings['airtime2cash_charge_9mobile'] : $defaultCharge),
                'ussd_template' => '*223*{pin}*{amount}*{phone}#',
                'default_pin'   => '0000',
                'pin_help'      => 'Change PIN: Dial *247*OldPIN*NewPIN# (e.g. *247*0000*1234#)',
                'ussd_short'    => '*223#',
                'brand_color'   => '#006655',
                'protocol'      => 'Airtime Transfer',
            ],
        ];

        $requests = AirtimeToCashRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('services.airtime-to-cash', compact('user', 'settings', 'requests', 'networks'));
    }

    public function submit(Request $request): RedirectResponse
    {
        if (AppSetting::get('service_airtime_to_cash', '1') !== '1') {
            return redirect()->route('dashboard')->with('error', 'Airtime to Cash service is currently disabled.');
        }

        $min = (float) AppSetting::get('airtime2cash_min_per_payment', 500);
        $max = (float) AppSetting::get('airtime2cash_max_per_payment', 50000);

        $request->validate([
            'network'     => ['required', 'string', 'in:mtn,airtel,glo,9mobile'],
            'phone'       => ['required', 'string', 'digits:11'],
            'amount'      => ['required', 'numeric', "min:{$min}", "max:{$max}"],
            'screenshot'  => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:3072'], // 3MB
        ]);

        $network = strtolower($request->network);
        $amount = (float) $request->amount;

        $defaultCharge = (float) AppSetting::get('airtime2cash_tx_charge', 20);
        $chargePercent = (float) AppSetting::get('airtime2cash_charge_' . $network, $defaultCharge);
        if ($chargePercent <= 0) {
            $chargePercent = $defaultCharge;
        }

        $charge = ($amount * $chargePercent) / 100;
        $receiveAmount = $amount - $charge;

        $path = $request->file('screenshot')->store('airtime-proofs', 'public');

        AirtimeToCashRequest::create([
            'user_id'        => auth()->id(),
            'network'        => $network,
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

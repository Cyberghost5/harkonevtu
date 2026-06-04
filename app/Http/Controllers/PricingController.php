<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CableProvider;
use App\Models\DataPlan;
use App\Models\ElectricityDisco;
use App\Models\ExamPinType;
use App\Models\NetworkAirtime;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        // ── Airtime ───────────────────────────────────────────────────────────
        $networks  = NetworkAirtime::active()->get();
        $isAgent   = $user->isAgent();
        $airtimePrefix = $isAgent ? 'airtime_agent_off_percentage_' : 'airtime_off_percentage_';
        $discountKeys  = $networks->map(fn ($n) => $airtimePrefix . $n->network_key)->toArray();
        $discountRaw   = AppSetting::getMany($discountKeys);
        $airtimeDiscounts = [];
        foreach ($discountRaw as $key => $val) {
            $airtimeDiscounts[str_replace($airtimePrefix, '', $key)] = (float) $val;
        }

        // ── Data Plans (active, grouped network → data_type → plans) ─────────
        $dataPlans = DataPlan::active()
            ->where('enabled', true)
            ->orderBy('network_key')
            ->orderBy('data_type')
            ->orderBy('amount')
            ->get()
            ->groupBy('network_key')
            ->map(fn ($byNetwork) => $byNetwork->groupBy('data_type'));

        // ── Cable TV ──────────────────────────────────────────────────────────
        $cableProviders = CableProvider::active()->with('plans')->get();

        // ── Electricity ───────────────────────────────────────────────────────
        $discos = ElectricityDisco::active()->get();

        // ── Exam Pins ─────────────────────────────────────────────────────────
        $examPins = ExamPinType::active()->orderBy('name')->get();

        // ── Service charge ────────────────────────────────────────────────────
        $txChargeType  = AppSetting::get('transaction_charge_type', 'flat');
        $txChargeValue = (float) AppSetting::get('transaction_charge_value', 0);

        return view('pricing.index', compact(
            'networks',
            'airtimeDiscounts',
            'dataPlans',
            'cableProviders',
            'discos',
            'examPins',
            'txChargeType',
            'txChargeValue',
            'isAgent',
        ));
    }
}

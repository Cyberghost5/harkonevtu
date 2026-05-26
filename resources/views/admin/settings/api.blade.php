@extends('layouts.admin')

@section('title', 'API Settings')
@section('heading', 'API Settings')
@section('subheading', 'Edit everything technical pertaining to ' . \App\Models\AppSetting::get('site_name', 'PayPulse'))

@section('content')

@php
    $providers = ['','vtpass','easyaccess','primebiller','payscribe','merrybills','clubkonnect','autopilot','aabaxztech','legitdataway'];
    $net_opts  = ['','Enable','Disable'];
@endphp

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

    {{-- Card header --}}
    <div class="px-6 pt-5 pb-4 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-800">{{ \App\Models\AppSetting::get('site_name', 'PayPulse') }} API Settings</h3>
        <p class="text-xs text-slate-400 mt-0.5">{{ \App\Models\AppSetting::get('site_name', 'PayPulse') }} API Settings</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.api.update') }}">
        @csrf

        <div class="divide-y divide-slate-100">

            {{-- ── Data API ─────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-5">Data API</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['data_api_mtn'=>'API for MTN Data','data_api_airtel'=>'API for Airtel Data','data_api_glo'=>'API for Glo Data','data_api_etisalat'=>'API for 9Mobile Data'] as $key=>$label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <select name="{{ $key }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                            @foreach($providers as $p)
                            <option value="{{ $p }}" {{ ($s[$key] ?? '') === $p ? 'selected' : '' }}>{{ $p ?: '— Select Provider —' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Data Network Settings ────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-5">Data Network Settings</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                        $netFields = [
                            'mtn_sme'               => ['label' => 'MTN SME',               'color' => ''],
                            'mtn_gifting'            => ['label' => 'MTN Gifting',            'color' => ''],
                            'mtn_sme2'               => ['label' => 'MTN SME2',               'color' => ''],
                            'mtn_awoof'              => ['label' => 'MTN AWOOF',              'color' => 'text-orange-500'],
                            'mtn_corporate_gifting'  => ['label' => 'MTN Corporate Gifting',  'color' => ''],
                            'airtel_gifting'         => ['label' => 'Airtel Gifting',         'color' => 'text-orange-500'],
                            'airtel_corporate_gifting' => ['label' => 'Airtel Corporate Gifting', 'color' => ''],
                            'airtel_awoof'           => ['label' => 'Airtel AWOOF',           'color' => 'text-orange-500'],
                            'glo_awoof'              => ['label' => 'Glo AWOOF',              'color' => 'text-green-600'],
                            'glo_corporate_gifting'  => ['label' => 'Glo Corporate Gifting',  'color' => ''],
                            'etisalat_sme'           => ['label' => '9Mobile SME',            'color' => ''],
                            'etisalat_gifting'       => ['label' => '9Mobile Gifting',        'color' => ''],
                        ];
                    @endphp
                    @foreach($netFields as $key => $def)
                    <div>
                        <label class="block text-xs font-medium mb-1 {{ $def['color'] ?: 'text-slate-500' }}">{{ $def['label'] }}</label>
                        <select name="{{ $key }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                            @foreach($net_opts as $o)
                            <option value="{{ $o }}" {{ ($s[$key] ?? '') === $o ? 'selected' : '' }}>{{ $o ?: '— Select —' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Airtime Network Settings ─────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-5">Airtime Network Settings</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['airtime_net_mtn'=>'MTN','airtime_net_airtel'=>'Airtel','airtime_net_glo'=>'Glo','airtime_net_etisalat'=>'9Mobile'] as $key=>$label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <select name="{{ $key }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                            @foreach($net_opts as $o)
                            <option value="{{ $o }}" {{ ($s[$key] ?? '') === $o ? 'selected' : '' }}>{{ $o ?: '— Select —' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Other API ────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-5">Other API</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach([
                        'airtime_api'     => 'API for Airtime Services',
                        'datacard_api'    => 'API for DataCard Services',
                        'airtime_pin_api' => 'API for Airtime PIN Services',
                        'electricity_api' => 'API for Electricity Services',
                        'cable_api'       => 'API for Cable Sub Services',
                        'betting_api'     => 'API for Betting Services',
                        'epins_api'       => 'API for E-Pin Services',
                    ] as $key => $label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <select name="{{ $key }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                            @foreach($providers as $p)
                            <option value="{{ $p }}" {{ ($s[$key] ?? '') === $p ? 'selected' : '' }}>{{ $p ?: '— Select Provider —' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Dealing charge [Flat rate]</label>
                        <input type="text" name="dealing_charge" value="{{ $s['dealing_charge'] ?? '100' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                    </div>
                </div>
            </div>

            {{-- ── Normal Users — Airtime % ─────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Normal Users</h4>
                <p class="text-xs text-slate-400 mb-5">Airtime % charge off per network</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['normal_airtime_mtn'=>'Airtime % charge off [MTN]','normal_airtime_airtel'=>'Airtime % charge off [Airtel]','normal_airtime_glo'=>'Airtime % charge off [Glo]','normal_airtime_etisalat'=>'Airtime % charge off [9Mobile]'] as $key=>$label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <input type="text" name="{{ $key }}" value="{{ $s[$key] ?? '0' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Agent Users — Airtime % ──────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Agent Users</h4>
                <p class="text-xs text-slate-400 mb-5">Airtime % charge off per network</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['agent_airtime_mtn'=>'Airtime % charge off [MTN]','agent_airtime_airtel'=>'Airtime % charge off [Airtel]','agent_airtime_glo'=>'Airtime % charge off [Glo]','agent_airtime_etisalat'=>'Airtime % charge off [9Mobile]'] as $key=>$label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <input type="text" name="{{ $key }}" value="{{ $s[$key] ?? '0' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Normal Users — Airtime PIN % ────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Normal Users</h4>
                <p class="text-xs text-slate-400 mb-5">Airtime PIN % charge off per network</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['normal_pin_mtn'=>'Airtime PIN % charge off [MTN]','normal_pin_airtel'=>'Airtime PIN % charge off [Airtel]','normal_pin_glo'=>'Airtime PIN % charge off [Glo]','normal_pin_etisalat'=>'Airtime PIN % charge off [9Mobile]'] as $key=>$label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <input type="text" name="{{ $key }}" value="{{ $s[$key] ?? '0' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Agent Users — Airtime PIN % ─────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Agent Users</h4>
                <p class="text-xs text-slate-400 mb-5">Airtime PIN % charge off per network</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['agent_pin_mtn'=>'Airtime PIN % charge off [MTN]','agent_pin_airtel'=>'Airtime PIN % charge off [Airtel]','agent_pin_glo'=>'Airtime PIN % charge off [Glo]','agent_pin_etisalat'=>'Airtime PIN % charge off [9Mobile]'] as $key=>$label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <input type="text" name="{{ $key }}" value="{{ $s[$key] ?? '0' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Save ─────────────────────────────────────────────────── --}}
            <div class="px-6 py-5">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90"
                        style="background:#4CAF50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Save
                </button>
            </div>

        </div>{{-- /divide-y --}}
    </form>

</div>

@endsection

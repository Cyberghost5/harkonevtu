@extends('layouts.admin')

@section('title', 'API Settings')
@section('heading', 'API Settings')
@section('subheading', 'Edit everything technical pertaining to ' . \App\Models\AppSetting::get('site_name', 'PayPulse'))

@section('content')

@php
    // $availableProviders is computed by the controller from configured API keys
    $providerOpts = array_merge([''], $availableProviders);
    $net_opts     = ['', 'Enable', 'Disable'];
@endphp

@if(empty($availableProviders))
<div class="mb-5 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 flex items-start gap-3">
    <svg class="h-5 w-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <p class="text-sm text-amber-700">
        No VTU API providers are configured yet. Go to
        <a href="{{ route('admin.settings.api-keys') }}" class="font-semibold underline">API Keys Settings</a>
        and enter credentials for at least one provider first.
    </p>
</div>
@endif

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

    {{-- Card header --}}
    <div class="px-6 pt-5 pb-4 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-800">{{ \App\Models\AppSetting::get('site_name', 'PayPulse') }} API Settings</h3>
        <p class="text-xs text-slate-400 mt-0.5">Configure service status, provider routing and pricing rules</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.api.update') }}">
        @csrf

        <div class="divide-y divide-slate-100">

            {{-- ── Service Status ──────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Service Status</h4>
                <p class="text-xs text-slate-400 mb-5">Enable or disable each service platform-wide</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach([
                        'service_data'               => ['label' => 'Data',        'icon' => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0'],
                        'service_airtime'            => ['label' => 'Airtime',     'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                        'service_electricity'        => ['label' => 'Electricity', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                        'service_cable'              => ['label' => 'Cable TV',    'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                        'service_epins'              => ['label' => 'Exam Pins',   'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        'service_betting'            => ['label' => 'Betting',     'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z'],
                        'service_recharge_card_printing' => ['label' => 'Recharge Card (PINs)', 'icon' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14'],
                        'service_funding_gateway'    => ['label' => 'Card Payment / Gateway', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        'service_funding_auto_bank'  => ['label' => 'Auto Bank Transfer (DVA)', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        'service_funding_manual'     => ['label' => 'Manual Bank Funding', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                        'service_funding_coupon'     => ['label' => 'Coupon Funding', 'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
                    ] as $key => $def)
                    @php $val = $s[$key] ?? '1'; @endphp
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center gap-2.5 mb-3">
                            <div class="h-8 w-8 rounded-lg flex items-center justify-center" style="background:{{ $themeColor }}20">
                                <svg class="h-4 w-4" style="color:{{ $themeColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $def['icon'] }}"/>
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">{{ $def['label'] }}</span>
                            <span class="ml-auto text-xs font-bold px-2 py-0.5 rounded-full {{ $val === '1' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                                {{ $val === '1' ? 'ON' : 'OFF' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="{{ $key }}" value="1" {{ $val === '1' ? 'checked' : '' }}
                                       class="w-3.5 h-3.5" style="accent-color:{{ $themeColor }}">
                                <span class="text-xs text-slate-600">Enabled</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="{{ $key }}" value="0" {{ $val !== '1' ? 'checked' : '' }}
                                       class="w-3.5 h-3.5" style="accent-color:{{ $themeColor }}">
                                <span class="text-xs text-slate-600">Disabled</span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Data API ─────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Data API</h4>
                <p class="text-xs text-slate-400 mb-5">Select the provider to route each mobile network's data purchases</p>
                @if(empty($availableProviders))
                <p class="text-sm text-slate-400 italic">No providers available - configure API keys first.</p>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['data_api_mtn'=>'MTN Data','data_api_airtel'=>'Airtel Data','data_api_glo'=>'Glo Data','data_api_etisalat'=>'9Mobile Data'] as $key=>$label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <select name="{{ $key }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            <option value="">- Select Provider -</option>
                            @foreach($dataProviders as $p)
                            <option value="{{ $p }}" {{ ($s[$key] ?? '') === $p ? 'selected' : '' }}>
                                {{ ucfirst($p) }}
                            </option>
                            @endforeach
                        </select>
                        @if(empty($dataProviders))
                            <p class="mt-1 text-xs text-amber-500">No data-compatible providers have credentials configured.</p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- ── Data Network Settings ────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Data Network Settings</h4>
                <p class="text-xs text-slate-400 mb-5">Enable or disable specific data plan types per network</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                        $netFields = [
                            'mtn_sme'                  => 'MTN SME',
                            'mtn_gifting'              => 'MTN Gifting',
                            'mtn_sme2'                 => 'MTN SME2',
                            'mtn_awoof'                => 'MTN AWOOF',
                            'mtn_corporate_gifting'    => 'MTN Corporate Gifting',
                            'airtel_gifting'           => 'Airtel Gifting',
                            'airtel_corporate_gifting' => 'Airtel Corporate Gifting',
                            'airtel_awoof'             => 'Airtel AWOOF',
                            'glo_awoof'                => 'Glo AWOOF',
                            'glo_corporate_gifting'    => 'Glo Corporate Gifting',
                            'etisalat_sme'             => '9Mobile SME',
                            'etisalat_gifting'         => '9Mobile Gifting',
                        ];
                    @endphp
                    @foreach($netFields as $key => $label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <select name="{{ $key }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            @foreach($net_opts as $o)
                            <option value="{{ $o }}" {{ ($s[$key] ?? '') === $o ? 'selected' : '' }}>{{ $o ?: '- Select -' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Airtime Network Settings ─────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Airtime Network Settings</h4>
                <p class="text-xs text-slate-400 mb-5">Enable or disable airtime per network</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['airtime_net_mtn'=>'MTN','airtime_net_airtel'=>'Airtel','airtime_net_glo'=>'Glo','airtime_net_etisalat'=>'9Mobile'] as $key=>$label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $label }}</label>
                        <select name="{{ $key }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            @foreach($net_opts as $o)
                            <option value="{{ $o }}" {{ ($s[$key] ?? '') === $o ? 'selected' : '' }}>{{ $o ?: '- Select -' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Other Service APIs ───────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Service API Routing</h4>
                <p class="text-xs text-slate-400 mb-5">Assign a provider for each service type and set the dealing charge</p>
                @if(empty($availableProviders))
                <p class="text-sm text-slate-400 italic">No providers available - configure API keys first.</p>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Airtime — only shows its own integrated providers --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API for Airtime</label>
                        <select name="airtime_api" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            <option value="">- Select Provider -</option>
                            @foreach($airtimeProviders as $p)
                            <option value="{{ $p }}" {{ ($s['airtime_api'] ?? '') === $p ? 'selected' : '' }}>
                                {{ ucfirst($p) }}
                            </option>
                            @endforeach
                        </select>
                        @if(empty($airtimeProviders))
                            <p class="mt-1 text-xs text-amber-500">No airtime-compatible providers have credentials configured.</p>
                        @endif
                    </div>

                    {{-- Other services still use the full available-providers list --}}
                    @foreach([
                        'datacard_api'    => 'DataCard',
                        'airtime_pin_api' => 'Airtime PIN',
                        'betting_api'     => 'Betting',
                    ] as $key => $label)
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API for {{ $label }}</label>
                        <select name="{{ $key }}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            @foreach($providerOpts as $p)
                            <option value="{{ $p }}" {{ ($s[$key] ?? '') === $p ? 'selected' : '' }}>
                                {{ $p ? ucfirst($p) : '- Select Provider -' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach

                    {{-- Electricity — only shows its own integrated providers --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API for Electricity</label>
                        <select name="electricity_api" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            <option value="">- Select Provider -</option>
                            @foreach($electricityProviders as $p)
                            <option value="{{ $p }}" {{ ($s['electricity_api'] ?? '') === $p ? 'selected' : '' }}>
                                {{ ucfirst($p) }}
                            </option>
                            @endforeach
                        </select>
                        @if(empty($electricityProviders))
                            <p class="mt-1 text-xs text-amber-500">No electricity-compatible providers have credentials configured.</p>
                        @endif
                    </div>

                    {{-- Cable TV — only shows its own integrated providers --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API for Cable TV</label>
                        <select name="cable_api" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            <option value="">- Select Provider -</option>
                            @foreach($cableProviders as $p)
                            <option value="{{ $p }}" {{ ($s['cable_api'] ?? '') === $p ? 'selected' : '' }}>
                                {{ ucfirst($p) }}
                            </option>
                            @endforeach
                        </select>
                        @if(empty($cableProviders))
                            <p class="mt-1 text-xs text-amber-500">No cable-compatible providers have credentials configured.</p>
                        @endif
                    </div>

                    {{-- Exam Pins — only shows its own integrated providers --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API for Exam Pins (ePins)</label>
                        <select name="epins_api" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            <option value="">- Select Provider -</option>
                            @foreach($epinsProviders as $p)
                            <option value="{{ $p }}" {{ ($s['epins_api'] ?? '') === $p ? 'selected' : '' }}>
                                {{ ucfirst($p) }}
                            </option>
                            @endforeach
                        </select>
                        @if(empty($epinsProviders))
                            <p class="mt-1 text-xs text-amber-500">No epin-compatible providers have credentials configured.</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Dealing Charge <span class="text-slate-400">(flat fee, ₦)</span>
                        </label>
                        <input type="text" name="dealing_charge" value="{{ $s['dealing_charge'] ?? '100' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Betting Convenience Charge <span class="text-slate-400">(flat fee, ₦)</span>
                        </label>
                        <input type="text" name="betting_charge" value="{{ $s['betting_charge'] ?? '50' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Betting Minimum Amount <span class="text-slate-400">(₦)</span>
                        </label>
                        <input type="text" name="betting_min_amount" value="{{ $s['betting_min_amount'] ?? '100' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Betting Daily Limit <span class="text-slate-400">(₦)</span>
                        </label>
                        <input type="text" name="betting_daily_limit" value="{{ $s['betting_daily_limit'] ?? '30000' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
                @endif
            </div>

            {{-- ── Airtime Pricing ──────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Airtime Pricing</h4>
                <p class="text-xs text-slate-400 mb-5">% charged off airtime per network, per user type</p>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-3">Normal Users</p>
                        <div class="space-y-3">
                            @foreach(['airtime_off_percentage_mtn'=>'MTN','airtime_off_percentage_airtel'=>'Airtel','airtime_off_percentage_glo'=>'Glo','airtime_off_percentage_etisalat'=>'9Mobile'] as $key=>$net)
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 w-16 flex-shrink-0">{{ $net }}</span>
                                <div class="relative flex-1">
                                    <input type="text" name="{{ $key }}" value="{{ $s[$key] ?? '0' }}"
                                           class="w-full pl-3 pr-8 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">%</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-3">Agent Users</p>
                        <div class="space-y-3">
                            @foreach(['airtime_agent_off_percentage_mtn'=>'MTN','airtime_agent_off_percentage_airtel'=>'Airtel','airtime_agent_off_percentage_glo'=>'Glo','airtime_agent_off_percentage_etisalat'=>'9Mobile'] as $key=>$net)
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 w-16 flex-shrink-0">{{ $net }}</span>
                                <div class="relative flex-1">
                                    <input type="text" name="{{ $key }}" value="{{ $s[$key] ?? '0' }}"
                                           class="w-full pl-3 pr-8 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">%</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Airtime PIN Pricing ───────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-xl font-bold text-slate-800 mb-1">Airtime PIN Pricing</h4>
                <p class="text-xs text-slate-400 mb-5">% charged off airtime PIN per network, per user type</p>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-3">Normal Users</p>
                        <div class="space-y-3">
                            @foreach(['normal_pin_mtn'=>'MTN','normal_pin_airtel'=>'Airtel','normal_pin_glo'=>'Glo','normal_pin_etisalat'=>'9Mobile'] as $key=>$net)
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 w-16 flex-shrink-0">{{ $net }}</span>
                                <div class="relative flex-1">
                                    <input type="text" name="{{ $key }}" value="{{ $s[$key] ?? '0' }}"
                                           class="w-full pl-3 pr-8 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">%</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-3">Agent Users</p>
                        <div class="space-y-3">
                            @foreach(['agent_pin_mtn'=>'MTN','agent_pin_airtel'=>'Airtel','agent_pin_glo'=>'Glo','agent_pin_etisalat'=>'9Mobile'] as $key=>$net)
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500 w-16 flex-shrink-0">{{ $net }}</span>
                                <div class="relative flex-1">
                                    <input type="text" name="{{ $key }}" value="{{ $s[$key] ?? '0' }}"
                                           class="w-full pl-3 pr-8 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">%</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Save ─────────────────────────────────────────────────── --}}
            <div class="px-6 py-5">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90"
                        style="background:{{ $themeColor }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Save API Settings
                </button>
            </div>

        </div>{{-- /divide-y --}}
    </form>

</div>

@endsection

@section('scripts')
<script>
    // Live badge update when radio changes
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const card = this.closest('.rounded-xl');
            if (!card) return;
            const badge = card.querySelector('span.ml-auto');
            if (!badge) return;
            if (this.value === '1') {
                badge.textContent = 'ON';
                badge.className = 'ml-auto text-xs font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700';
            } else {
                badge.textContent = 'OFF';
                badge.className = 'ml-auto text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-600';
            }
        });
    });
</script>
@endsection

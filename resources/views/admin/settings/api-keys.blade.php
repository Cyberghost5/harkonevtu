@extends('layouts.admin')

@section('title', 'API Keys Settings')
@section('heading', 'API Keys Settings')
@section('subheading', 'Edit everything pertaining to ' . \App\Models\AppSetting::get('site_name', 'PayPulse'))

@section('content')

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

    {{-- Card header --}}
    <div class="px-6 pt-5 pb-4 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-800">{{ \App\Models\AppSetting::get('site_name', 'PayPulse') }} API Keys Settings</h3>
        <p class="text-xs text-slate-400 mt-0.5">{{ \App\Models\AppSetting::get('site_name', 'PayPulse') }} API Keys Settings</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.api-keys.update') }}">
        @csrf

        <div class="divide-y divide-slate-100">

            {{-- ── Flutterwave ──────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    Flutterwave
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://dashboard.flutterwave.com" target="_blank" class="text-blue-500 hover:underline">Flutterwave</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Public Key</label>
                        <input type="text" name="flutterwave_public_key" value="{{ $s['flutterwave_public_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                        <input type="text" name="flutterwave_secret_key" value="{{ $s['flutterwave_secret_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Encryption Key</label>
                        <input type="text" name="flutterwave_encryption_key" value="{{ $s['flutterwave_encryption_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">BVN</label>
                        <input type="text" name="flutterwave_bvn" value="{{ $s['flutterwave_bvn'] ?? '' }}"
                               placeholder="Enter BVN"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── Paystack ─────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    Paystack
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://dashboard.paystack.com" target="_blank" class="text-blue-500 hover:underline">Paystack</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Public Key</label>
                        <input type="text" name="paystack_public_key" value="{{ $s['paystack_public_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                        <input type="text" name="paystack_secret_key" value="{{ $s['paystack_secret_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── Monnify ──────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    Monnify
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://dashboard.monnify.com" target="_blank" class="text-blue-500 hover:underline">Monnify</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="monnify_api_key" value="{{ $s['monnify_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                        <input type="text" name="monnify_secret_key" value="{{ $s['monnify_secret_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Contract Code</label>
                        <input type="text" name="monnify_contract_no" value="{{ $s['monnify_contract_no'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Mode</label>
                        <select name="monnify_mode" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            <option value="sandbox" {{ ($s['monnify_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ ($s['monnify_mode'] ?? 'sandbox') === 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ── Payscribe ─────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    Payscribe
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://payscribe.ng" target="_blank" class="text-blue-500 hover:underline">Payscribe</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                        <input type="text" name="payscribe_secret_key" value="{{ $s['payscribe_secret_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Public Key</label>
                        <input type="text" name="payscribe_public_key" value="{{ $s['payscribe_public_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── Other Payment Settings ───────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">Other Payment Settings</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Transaction charge in % per payment [VM Funding]</label>
                        <input type="text" name="tx_charge_m2m" value="{{ $s['tx_charge_m2m'] ?? '0' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Transaction charge in % per payment [with Bank Transfer]</label>
                        <input type="text" name="tx_charge_bank" value="{{ $s['tx_charge_bank'] ?? '0' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── VTU API: VTPass ──────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    VTU API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://vtpass.com" target="_blank" class="text-blue-500 hover:underline">VTPass</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Username / Email</label>
                        <input type="text" name="vtpass_username" value="{{ $s['vtpass_username'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                        <input type="password" name="vtpass_password" value="{{ $s['vtpass_password'] ?? '' }}"
                               placeholder="Leave blank to keep current"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="vtpass_api_key" value="{{ $s['vtpass_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Primebiller ─────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    VTU API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://primebiller.com" target="_blank" class="text-blue-500 hover:underline">Primebiller</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="primebiller_api_key" value="{{ $s['primebiller_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div class="flex items-end gap-3">
                        @php $ps = $s['primebiller_status'] ?? ''; @endphp
                        @if($ps)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $ps === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            API Status: {{ ucfirst($ps) }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Aabaxztech ──────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    VTU API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://aabaxztech.com" target="_blank" class="text-blue-500 hover:underline">Aabaxztech</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Username</label>
                        <input type="text" name="aabaxztech_username" value="{{ $s['aabaxztech_username'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                        <input type="password" name="aabaxztech_password" value="{{ $s['aabaxztech_password'] ?? '' }}"
                               placeholder="Leave blank to keep current"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="aabaxztech_api_key" value="{{ $s['aabaxztech_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── VTU API: AutoPilot ───────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    VTU API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://autopilot.com.ng" target="_blank" class="text-blue-500 hover:underline">AutoPilot</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Email</label>
                        <input type="email" name="autopilot_email" value="{{ $s['autopilot_email'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="autopilot_api_key" value="{{ $s['autopilot_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Easyaccess ──────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    VTU API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://easyaccess.ng" target="_blank" class="text-blue-500 hover:underline">Easyaccess</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="easyaccess_api_key" value="{{ $s['easyaccess_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── VTU API: LegitDataway ────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    VTU API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://legitdataway.com" target="_blank" class="text-blue-500 hover:underline">LegitDataway</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Username</label>
                        <input type="text" name="legitdataway_username" value="{{ $s['legitdataway_username'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                        <input type="password" name="legitdataway_password" value="{{ $s['legitdataway_password'] ?? '' }}"
                               placeholder="Leave blank to keep current"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="legitdataway_api_key" value="{{ $s['legitdataway_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Merrybills ──────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    VTU API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://merrybills.com" target="_blank" class="text-blue-500 hover:underline">Merrybills</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Username</label>
                        <input type="text" name="merrybills_username" value="{{ $s['merrybills_username'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                        <input type="password" name="merrybills_password" value="{{ $s['merrybills_password'] ?? '' }}"
                               placeholder="Leave blank to keep current"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Pin</label>
                        <input type="text" name="merrybills_pin" value="{{ $s['merrybills_pin'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Token</label>
                        <input type="text" name="merrybills_token" value="{{ $s['merrybills_token'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Clubkonnect ─────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    VTU API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://clubkonnect.com" target="_blank" class="text-blue-500 hover:underline">Clubkonnect</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">User ID</label>
                        <input type="text" name="clubkonnect_user_id" value="{{ $s['clubkonnect_user_id'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="clubkonnect_api_key" value="{{ $s['clubkonnect_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Globacom ────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    VTU API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://globacom.com" target="_blank" class="text-blue-500 hover:underline">Globacom</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">X-API Key</label>
                        <input type="text" name="globacom_xapi_key" value="{{ $s['globacom_xapi_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Sponsor ID</label>
                        <input type="text" name="globacom_sponsor_id" value="{{ $s['globacom_sponsor_id'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>


            {{-- ── SMS API: Termii ──────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    SMS API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://termii.com" target="_blank" class="text-blue-500 hover:underline">Termii</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="termii_api_key" value="{{ $s['termii_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── SMS API: BulkSMSNigeria ──────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    SMS API
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://bulksmsnigeria.com" target="_blank" class="text-blue-500 hover:underline">BulkSMSNigeria</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Sender ID</label>
                        <input type="text" name="bulksms_sender" value="{{ $s['bulksms_sender'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="bulksms_api_key" value="{{ $s['bulksms_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Amount per unit</label>
                        <input type="text" name="bulksms_amount_per_unit" value="{{ $s['bulksms_amount_per_unit'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>


            {{-- ── Onesignal ────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    Onesignal
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://onesignal.com" target="_blank" class="text-blue-500 hover:underline">Onesignal</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">App ID</label>
                        <input type="text" name="onesignal_app_id" value="{{ $s['onesignal_app_id'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                        <input type="text" name="onesignal_api_key" value="{{ $s['onesignal_api_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── QoreID (KYC Verification) ────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    QoreID (KYC Verification)
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <a href="https://qoreid.com" target="_blank" class="text-blue-500 hover:underline">QoreID</a>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Client Key</label>
                        <input type="text" name="qoreid_client_key" value="{{ $s['qoreid_client_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                        <input type="text" name="qoreid_secret_key" value="{{ $s['qoreid_secret_key'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Mode</label>
                        <select name="qoreid_mode" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            <option value="sandbox" {{ ($s['qoreid_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ ($s['qoreid_mode'] ?? 'sandbox') === 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ── MTN ERS (SOAP API Gateway) ───────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">
                    MTN ERS (SOAP API Gateway)
                    <span class="text-sm font-normal text-slate-400 ml-1">from
                        <span class="text-indigo-500">Seamless Distribution Systems (SDS)</span>
                    </span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Username (Trade Partner ID)</label>
                        <input type="text" name="mtn_ers_username" value="{{ $s['mtn_ers_username'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">PIN / Password</label>
                        <input type="password" name="mtn_ers_pin" value="{{ $s['mtn_ers_pin'] ?? '' }}"
                               placeholder="Leave blank to keep current"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">SOAP Gateway Endpoint Url</label>
                        <input type="text" name="mtn_ers_endpoint" value="{{ $s['mtn_ers_endpoint'] ?? '' }}"
                               placeholder="e.g. https://ers.seamless.se/services/ERSExchange3GPort"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Mode</label>
                        <select name="mtn_ers_mode" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            <option value="sandbox" {{ ($s['mtn_ers_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox / Mock</option>
                            <option value="production" {{ ($s['mtn_ers_mode'] ?? 'sandbox') === 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ── Airtime2Cash Parameters ──────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">Airtime2Cash Parameters</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Phone Number to receive airtime</label>
                        <input type="text" name="airtime2cash_phone" value="{{ $s['airtime2cash_phone'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Transaction charge in % per payment</label>
                        <input type="text" name="airtime2cash_tx_charge" value="{{ $s['airtime2cash_tx_charge'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Max. Amount per payment</label>
                        <input type="text" name="airtime2cash_max_per_payment" value="{{ $s['airtime2cash_max_per_payment'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Min. Amount per payment</label>
                        <input type="text" name="airtime2cash_min_per_payment" value="{{ $s['airtime2cash_min_per_payment'] ?? '' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── Referral Commission ──────────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">Referral Commission</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Referral Commission (%)</label>
                        <input type="text" name="referral_commission" value="{{ $s['referral_commission'] ?? '0' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Referral Min. Withdrawal Amount (₦)</label>
                        <input type="text" name="referral_min_withdrawal" value="{{ $s['referral_min_withdrawal'] ?? '0' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Referral Min. Total Spent (₦)</label>
                        <input type="text" name="referral_min_total_spent" value="{{ $s['referral_min_total_spent'] ?? '0' }}"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>
            </div>

            {{-- ── Save ─────────────────────────────────────────────────── --}}
            <div class="px-6 py-5">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90"
                        style="background:{{ $themeColor }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Save
                </button>
            </div>

        </div>{{-- /divide-y --}}
    </form>

</div>

@endsection

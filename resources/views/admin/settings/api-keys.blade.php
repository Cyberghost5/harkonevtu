@extends('layouts.admin')

@section('title', 'API Keys Settings')
@section('heading', 'API Keys Settings')
@section('subheading', 'Edit everything pertaining to ' . \App\Models\AppSetting::get('site_name', 'PayPulse'))

@section('content')

<style>
    /* Dark mode styling overrides for settings cards and form inputs */
    .dark .admin-settings-card {
        background-color: #0f172a !important; /* bg-slate-900 */
        border-color: #1e293b !important; /* border-slate-800 */
    }
    .dark .admin-settings-card h3,
    .dark .admin-settings-card h4 {
        color: #ffffff !important;
    }
    .dark .admin-settings-card p,
    .dark .admin-settings-card span.text-slate-400,
    .dark .admin-settings-card span.text-slate-500,
    .dark .admin-settings-card p.text-slate-400 {
        color: #64748b !important; /* text-slate-500 */
    }
    .dark .admin-settings-card label {
        color: #94a3b8 !important; /* text-slate-400 */
    }
    .dark .admin-settings-card select,
    .dark .admin-settings-card input {
        background-color: #0f172a !important; /* bg-slate-900 */
        border-color: #334155 !important; /* border-slate-700 */
        color: #f1f5f9 !important; /* text-slate-100 */
    }
    .dark .admin-settings-card div.border-b,
    .dark .admin-settings-card div.border-slate-100,
    .dark .admin-settings-card div.divide-y > div {
        border-color: #1e293b !important; /* border-slate-800 */
    }
</style>

<div class="admin-settings-card bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

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
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        Flutterwave
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://dashboard.flutterwave.com" target="_blank" class="text-blue-500 hover:underline">Flutterwave</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="flutterwave_status" value="0">
                        <input type="checkbox" name="flutterwave_status" value="1" {{ ($s['flutterwave_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-flutterwave">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['flutterwave_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['flutterwave_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-flutterwave" class="provider-content transition-all duration-300 {{ ($s['flutterwave_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Public Key</label>
                            <input type="text" name="flutterwave_public_key" value="{{ $s['flutterwave_public_key'] ?? '' }}"
                            placeholder="Enter Public Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                            <input type="text" name="flutterwave_secret_key" value="{{ $s['flutterwave_secret_key'] ?? '' }}"
                            placeholder="Enter Secret Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Encryption Key</label>
                            <input type="text" name="flutterwave_encryption_key" value="{{ $s['flutterwave_encryption_key'] ?? '' }}"
                            placeholder="Enter Encryption Key"
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
            </div>

            {{-- ── Paystack ─────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        Paystack
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://dashboard.paystack.com" target="_blank" class="text-blue-500 hover:underline">Paystack</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="paystack_status" value="0">
                        <input type="checkbox" name="paystack_status" value="1" {{ ($s['paystack_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-paystack">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['paystack_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['paystack_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-paystack" class="provider-content transition-all duration-300 {{ ($s['paystack_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Public Key</label>
                            <input type="text" name="paystack_public_key" value="{{ $s['paystack_public_key'] ?? '' }}"
                                   placeholder="Enter Public Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                            <input type="text" name="paystack_secret_key" value="{{ $s['paystack_secret_key'] ?? '' }}"
                                   placeholder="Enter Secret Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Monnify ──────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        Monnify
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://dashboard.monnify.com" target="_blank" class="text-blue-500 hover:underline">Monnify</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="monnify_status" value="0">
                        <input type="checkbox" name="monnify_status" value="1" {{ ($s['monnify_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-monnify">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['monnify_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['monnify_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-monnify" class="provider-content transition-all duration-300 {{ ($s['monnify_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="monnify_api_key" value="{{ $s['monnify_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                            <input type="text" name="monnify_secret_key" value="{{ $s['monnify_secret_key'] ?? '' }}"
                                   placeholder="Enter Secret Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Contract Code</label>
                            <input type="text" name="monnify_contract_no" value="{{ $s['monnify_contract_no'] ?? '' }}"
                                   placeholder="Enter Contract Code"
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
            </div>

            {{-- ── Payscribe ─────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        Payscribe
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://payscribe.ng" target="_blank" class="text-blue-500 hover:underline">Payscribe</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="payscribe_status" value="0">
                        <input type="checkbox" name="payscribe_status" value="1" {{ ($s['payscribe_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-payscribe">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['payscribe_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['payscribe_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-payscribe" class="provider-content transition-all duration-300 {{ ($s['payscribe_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                            <input type="text" name="payscribe_secret_key" value="{{ $s['payscribe_secret_key'] ?? '' }}"
                                   placeholder="Enter Secret Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Public Key</label>
                            <input type="text" name="payscribe_public_key" value="{{ $s['payscribe_public_key'] ?? '' }}"
                                   placeholder="Enter Public Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Other Payment Settings ───────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">Other Payment Settings</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Transaction charge in % per payment [VM Funding]</label>
                        <input type="text" name="tx_charge_m2m" value="{{ $s['tx_charge_m2m'] ?? '0' }}"
                               placeholder="Enter Transaction Charge"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Transaction charge in % per payment [with Bank Transfer]</label>
                        <input type="text" name="tx_charge_bank" value="{{ $s['tx_charge_bank'] ?? '0' }}"
                               placeholder="Enter Transaction Charge"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Active Card/ATM Payment Gateway</label>
                        <select name="active_gateway" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                            <option value="paystack" {{ ($s['active_gateway'] ?? 'paystack') === 'paystack' ? 'selected' : '' }}>Paystack</option>
                            <option value="flutterwave" {{ ($s['active_gateway'] ?? 'paystack') === 'flutterwave' ? 'selected' : '' }}>Flutterwave</option>
                            <option value="monnify" {{ ($s['active_gateway'] ?? 'paystack') === 'monnify' ? 'selected' : '' }}>Monnify</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- ── VTU API: VTPass ──────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        VTU API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://vtpass.com" target="_blank" class="text-blue-500 hover:underline">VTPass</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="vtpass_status" value="0">
                        <input type="checkbox" name="vtpass_status" value="1" {{ ($s['vtpass_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-vtpass">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['vtpass_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['vtpass_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-vtpass" class="provider-content transition-all duration-300 {{ ($s['vtpass_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Username / Email</label>
                            <input type="text" name="vtpass_username" value="{{ $s['vtpass_username'] ?? '' }}"
                                   placeholder="Enter Username / Email"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                            <input type="password" name="vtpass_password" value="{{ $s['vtpass_password'] ?? '' }}"
                                   placeholder="Enter Password"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key / Public Key</label>
                            <input type="text" name="vtpass_api_key" value="{{ $s['vtpass_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                            <input type="password" name="vtpass_secret_key" value="{{ $s['vtpass_secret_key'] ?? '' }}"
                                   placeholder="Enter Secret Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Base URL (Default: https://vtpass.com)</label>
                            <input type="text" name="vtpass_base_url" value="{{ $s['vtpass_base_url'] ?? '' }}"
                                   placeholder="https://vtpass.com (or https://sandbox.vtpass.com for testing)"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Primebiller ─────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        VTU API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://primebiller.com" target="_blank" class="text-blue-500 hover:underline">Primebiller</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="primebiller_status" value="0">
                        <input type="checkbox" name="primebiller_status" value="1" {{ ($s['primebiller_status'] ?? '1') !== '0' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-primebiller">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['primebiller_status'] ?? '1') !== '0' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['primebiller_status'] ?? '1') !== '0' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-primebiller" class="provider-content transition-all duration-300 {{ ($s['primebiller_status'] ?? '1') !== '0' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="primebiller_api_key" value="{{ $s['primebiller_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Aabaxztech ──────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        VTU API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://aabaxztech.com" target="_blank" class="text-blue-500 hover:underline">Aabaxztech</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="aabaxztech_status" value="0">
                        <input type="checkbox" name="aabaxztech_status" value="1" {{ ($s['aabaxztech_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-aabaxztech">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['aabaxztech_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['aabaxztech_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-aabaxztech" class="provider-content transition-all duration-300 {{ ($s['aabaxztech_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Username</label>
                            <input type="text" name="aabaxztech_username" value="{{ $s['aabaxztech_username'] ?? '' }}"
                                   placeholder="Enter Username"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                            <input type="password" name="aabaxztech_password" value="{{ $s['aabaxztech_password'] ?? '' }}"
                                   placeholder="Enter Password"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="aabaxztech_api_key" value="{{ $s['aabaxztech_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── VTU API: AutoPilot ───────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        VTU API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://autopilot.com.ng" target="_blank" class="text-blue-500 hover:underline">AutoPilot</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="autopilot_status" value="0">
                        <input type="checkbox" name="autopilot_status" value="1" {{ ($s['autopilot_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-autopilot">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['autopilot_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['autopilot_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-autopilot" class="provider-content transition-all duration-300 {{ ($s['autopilot_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Email</label>
                            <input type="email" name="autopilot_email" value="{{ $s['autopilot_email'] ?? '' }}"
                                   placeholder="Enter Email"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="autopilot_api_key" value="{{ $s['autopilot_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Easyaccess ──────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        VTU API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://easyaccess.ng" target="_blank" class="text-blue-500 hover:underline">Easyaccess</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="easyaccess_status" value="0">
                        <input type="checkbox" name="easyaccess_status" value="1" {{ ($s['easyaccess_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-easyaccess">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['easyaccess_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['easyaccess_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-easyaccess" class="provider-content transition-all duration-300 {{ ($s['easyaccess_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="easyaccess_api_key" value="{{ $s['easyaccess_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── VTU API: LegitDataway ────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        VTU API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://legitdataway.com" target="_blank" class="text-blue-500 hover:underline">LegitDataway</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="legitdataway_status" value="0">
                        <input type="checkbox" name="legitdataway_status" value="1" {{ ($s['legitdataway_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-legitdataway">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['legitdataway_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['legitdataway_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-legitdataway" class="provider-content transition-all duration-300 {{ ($s['legitdataway_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Username</label>
                            <input type="text" name="legitdataway_username" value="{{ $s['legitdataway_username'] ?? '' }}"
                                   placeholder="Enter Username"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                            <input type="password" name="legitdataway_password" value="{{ $s['legitdataway_password'] ?? '' }}"
                                   placeholder="Enter Password"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="legitdataway_api_key" value="{{ $s['legitdataway_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Merrybills ──────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        VTU API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://merrybills.com" target="_blank" class="text-blue-500 hover:underline">Merrybills</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="merrybills_status" value="0">
                        <input type="checkbox" name="merrybills_status" value="1" {{ ($s['merrybills_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-merrybills">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['merrybills_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['merrybills_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-merrybills" class="provider-content transition-all duration-300 {{ ($s['merrybills_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Username</label>
                            <input type="text" name="merrybills_username" value="{{ $s['merrybills_username'] ?? '' }}"
                                   placeholder="Enter Username"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                            <input type="password" name="merrybills_password" value="{{ $s['merrybills_password'] ?? '' }}"
                                   placeholder="Enter Password"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Pin</label>
                            <input type="text" name="merrybills_pin" value="{{ $s['merrybills_pin'] ?? '' }}"
                                   placeholder="Enter Pin"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Token</label>
                            <input type="text" name="merrybills_token" value="{{ $s['merrybills_token'] ?? '' }}"
                                   placeholder="Enter Token"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Clubkonnect ─────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        VTU API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://clubkonnect.com" target="_blank" class="text-blue-500 hover:underline">Clubkonnect</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="clubkonnect_status" value="0">
                        <input type="checkbox" name="clubkonnect_status" value="1" {{ ($s['clubkonnect_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-clubkonnect">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['clubkonnect_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['clubkonnect_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-clubkonnect" class="provider-content transition-all duration-300 {{ ($s['clubkonnect_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">User ID</label>
                            <input type="text" name="clubkonnect_user_id" value="{{ $s['clubkonnect_user_id'] ?? '' }}"
                                   placeholder="Enter User ID"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="clubkonnect_api_key" value="{{ $s['clubkonnect_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── VTU API: Globacom ────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        VTU API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://globacom.com" target="_blank" class="text-blue-500 hover:underline">Globacom</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="globacom_status" value="0">
                        <input type="checkbox" name="globacom_status" value="1" {{ ($s['globacom_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-globacom">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['globacom_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['globacom_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-globacom" class="provider-content transition-all duration-300 {{ ($s['globacom_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="globacom_xapi_key" value="{{ $s['globacom_xapi_key'] ?? '' }}"
                                   placeholder="Enter Consumer API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Account Email</label>
                            <input type="email" name="globacom_email" value="{{ $s['globacom_email'] ?? '' }}"
                                   placeholder="Enter Consumer Email"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Rating / Bucket ID</label>
                            <input type="text" name="globacom_bucket_id" value="{{ $s['globacom_bucket_id'] ?? '' }}"
                                   placeholder="Enter Rating ID"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Sponsor ID (Optional)</label>
                            <input type="text" name="globacom_sponsor_id" value="{{ $s['globacom_sponsor_id'] ?? '' }}"
                                   placeholder="Enter Sponsor ID"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── MTN ERS (SOAP API Gateway) ───────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        MTN ERS (SOAP API Gateway)
                        <span class="text-sm font-normal text-slate-400">from
                            <span class="text-indigo-500">Seamless Distribution Systems (SDS)</span>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="mtn_ers_status" value="0">
                        <input type="checkbox" name="mtn_ers_status" value="1" {{ ($s['mtn_ers_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-mtn-ers">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['mtn_ers_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['mtn_ers_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-mtn-ers" class="provider-content transition-all duration-300 {{ ($s['mtn_ers_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Originator MSISDN (Merchant Phone)</label>
                            <input type="text" name="mtn_ers_originator_msisdn" value="{{ $s['mtn_ers_originator_msisdn'] ?? '' }}"
                                   placeholder="e.g. 09062058470"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div class="sm:col-span-3">
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
            </div>

            {{-- ── Glo ERS (SOAP API Gateway) ───────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        Glo ERS (SOAP API Gateway)
                        <span class="text-sm font-normal text-slate-400">from
                            <span class="text-indigo-500">Seamless Distribution Systems (SDS)</span>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="glo_ers_status" value="0">
                        <input type="checkbox" name="glo_ers_status" value="1" {{ ($s['glo_ers_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-glo-ers">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['glo_ers_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['glo_ers_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-glo-ers" class="provider-content transition-all duration-300 {{ ($s['glo_ers_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Username (Trade Partner ID)</label>
                            <input type="text" name="glo_ers_username" value="{{ $s['glo_ers_username'] ?? '' }}"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Password / PIN</label>
                            <input type="password" name="glo_ers_password" value="{{ $s['glo_ers_password'] ?? '' }}"
                                   placeholder="Leave blank to keep current"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Client ID</label>
                            <input type="text" name="glo_ers_client_id" value="{{ $s['glo_ers_client_id'] ?? 'ERS' }}"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Distributor ID</label>
                            <input type="text" name="glo_ers_distributor_id" value="{{ $s['glo_ers_distributor_id'] ?? '' }}"
                                   placeholder="e.g. DIST1"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Distributor Sub-User ID (UserId)</label>
                            <input type="text" name="glo_ers_distributor_userid" value="{{ $s['glo_ers_distributor_userid'] ?? '' }}"
                                   placeholder="e.g. 9900"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Mode</label>
                            <select name="glo_ers_mode" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                                <option value="sandbox" {{ ($s['glo_ers_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox / Mock</option>
                                <option value="production" {{ ($s['glo_ers_mode'] ?? 'sandbox') === 'production' ? 'selected' : '' }}>Production</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-500 mb-1">SOAP Gateway Endpoint Url</label>
                            <input type="text" name="glo_ers_endpoint" value="{{ $s['glo_ers_endpoint'] ?? '' }}"
                                   placeholder="e.g. http://10.10.3.42:8914/topupservice/service"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── SMS API: Termii ──────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        SMS API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://termii.com" target="_blank" class="text-blue-500 hover:underline">Termii</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="termii_status" value="0">
                        <input type="checkbox" name="termii_status" value="1" {{ ($s['termii_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-termii">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['termii_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['termii_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-termii" class="provider-content transition-all duration-300 {{ ($s['termii_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="termii_api_key" value="{{ $s['termii_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── SMS API: BulkSMSNigeria ──────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        SMS API
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://bulksmsnigeria.com" target="_blank" class="text-blue-500 hover:underline">BulkSMSNigeria</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="bulksms_status" value="0">
                        <input type="checkbox" name="bulksms_status" value="1" {{ ($s['bulksms_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-bulksms">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['bulksms_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['bulksms_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-bulksms" class="provider-content transition-all duration-300 {{ ($s['bulksms_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Sender ID</label>
                            <input type="text" name="bulksms_sender" value="{{ $s['bulksms_sender'] ?? '' }}"
                                   placeholder="Enter Sender ID"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="bulksms_api_key" value="{{ $s['bulksms_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Amount per unit</label>
                            <input type="text" name="bulksms_amount_per_unit" value="{{ $s['bulksms_amount_per_unit'] ?? '' }}"
                                   placeholder="Enter Amount per unit"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Onesignal ────────────────────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        Onesignal
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://onesignal.com" target="_blank" class="text-blue-500 hover:underline">Onesignal</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="onesignal_status" value="0">
                        <input type="checkbox" name="onesignal_status" value="1" {{ ($s['onesignal_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-onesignal">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['onesignal_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['onesignal_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-onesignal" class="provider-content transition-all duration-300 {{ ($s['onesignal_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">App ID</label>
                            <input type="text" name="onesignal_app_id" value="{{ $s['onesignal_app_id'] ?? '' }}"
                                   placeholder="Enter App ID"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">API Key</label>
                            <input type="text" name="onesignal_api_key" value="{{ $s['onesignal_api_key'] ?? '' }}"
                                   placeholder="Enter API Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── QoreID (KYC Verification) ────────────────────────────── --}}
            <div class="px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        QoreID (KYC Verification)
                        <span class="text-sm font-normal text-slate-400">from
                            <a href="https://qoreid.com" target="_blank" class="text-blue-500 hover:underline">QoreID</a>
                        </span>
                    </h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="qoreid_status" value="0">
                        <input type="checkbox" name="qoreid_status" value="1" {{ ($s['qoreid_status'] ?? '1') === '1' ? 'checked' : '' }}
                               class="sr-only peer provider-toggle" data-target="section-qoreid">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-vtu-primary"></div>
                        <span class="ml-2.5 text-xs font-bold status-label uppercase tracking-wider {{ ($s['qoreid_status'] ?? '1') === '1' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500' }}">
                            {{ ($s['qoreid_status'] ?? '1') === '1' ? 'Enabled' : 'Disabled' }}
                        </span>
                    </label>
                </div>
                <div id="section-qoreid" class="provider-content transition-all duration-300 {{ ($s['qoreid_status'] ?? '1') === '1' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Client Key</label>
                            <input type="text" name="qoreid_client_key" value="{{ $s['qoreid_client_key'] ?? '' }}"
                                   placeholder="Enter Client Key"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Secret Key</label>
                            <input type="text" name="qoreid_secret_key" value="{{ $s['qoreid_secret_key'] ?? '' }}"
                                   placeholder="Enter Secret Key"
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
            </div>

            {{-- ── Airtime2Cash Parameters ──────────────────────────────── --}}
            <div class="px-6 py-6">
                <h4 class="text-lg font-bold text-slate-800 mb-4">Airtime2Cash Parameters</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Phone Number to receive airtime</label>
                        <input type="text" name="airtime2cash_phone" value="{{ $s['airtime2cash_phone'] ?? '' }}"
                               placeholder="Enter Phone Number"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Transaction charge in % per payment</label>
                        <input type="text" name="airtime2cash_tx_charge" value="{{ $s['airtime2cash_tx_charge'] ?? '' }}"
                               placeholder="Enter Transaction Charge"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Max. Amount per payment</label>
                        <input type="text" name="airtime2cash_max_per_payment" value="{{ $s['airtime2cash_max_per_payment'] ?? '' }}"
                               placeholder="Enter Maximum Amount"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Min. Amount per payment</label>
                        <input type="text" name="airtime2cash_min_per_payment" value="{{ $s['airtime2cash_min_per_payment'] ?? '' }}"
                               placeholder="Enter Minimum Amount"
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
                               placeholder="Enter Referral Commission"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Referral Min. Withdrawal Amount (₦)</label>
                        <input type="text" name="referral_min_withdrawal" value="{{ $s['referral_min_withdrawal'] ?? '0' }}"
                               placeholder="Enter Minimum Withdrawal Amount"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Referral Min. Total Spent (₦)</label>
                        <input type="text" name="referral_min_total_spent" value="{{ $s['referral_min_total_spent'] ?? '0' }}"
                               placeholder="Enter Minimum Total Spent"
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.provider-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const targetId = this.getAttribute('data-target');
            const targetContent = document.getElementById(targetId);
            const label = this.closest('label').querySelector('.status-label');
            
            if (this.checked) {
                if (targetContent) targetContent.classList.remove('hidden');
                if (label) {
                    label.textContent = 'Enabled';
                    label.className = 'ml-2.5 text-xs font-bold status-label uppercase tracking-wider text-emerald-600 dark:text-emerald-400';
                }
            } else {
                if (targetContent) targetContent.classList.add('hidden');
                if (label) {
                    label.textContent = 'Disabled';
                    label.className = 'ml-2.5 text-xs font-bold status-label uppercase tracking-wider text-slate-400 dark:text-slate-500';
                }
            }
        });
    });
});
</script>

@endsection

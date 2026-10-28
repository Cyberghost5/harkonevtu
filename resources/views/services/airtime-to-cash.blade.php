@extends('layouts.dashboard')

@section('title', 'Convert Airtime to Cash')

@section('content')
<div class="max-w-6xl mx-auto space-y-6 pb-12" x-data="{
    networks: @js($networks),
    selectedNetwork: '{{ old('network', 'mtn') }}',
    amount: '{{ old('amount', '') }}',
    senderPhone: '{{ old('phone', $user->phone ?? '') }}',
    customPin: '',
    copiedPhone: false,
    copiedUssd: false,
    imagePreview: null,
    showPinGuide: false,
    minAmount: {{ (float) ($settings['airtime2cash_min_per_payment'] ?? 500) }},
    maxAmount: {{ (float) ($settings['airtime2cash_max_per_payment'] ?? 50000) }},

    init() {
        // If initial network is disabled, select first enabled network
        if (!this.networks[this.selectedNetwork] || !this.networks[this.selectedNetwork].enabled) {
            let active = Object.keys(this.networks).find(k => this.networks[k].enabled);
            if (active) {
                this.selectedNetwork = active;
            }
        }
    },

    get currentNetwork() {
        return this.networks[this.selectedNetwork] || this.networks['mtn'];
    },
    get isCurrentNetworkEnabled() {
        return this.currentNetwork ? Boolean(this.currentNetwork.enabled) : false;
    },
    get hasAnyActiveNetwork() {
        return Object.values(this.networks).some(n => Boolean(n.enabled));
    },
    get currentCharge() {
        return this.currentNetwork ? parseFloat(this.currentNetwork.charge) : 20;
    },
    get payoutPercent() {
        return 100 - this.currentCharge;
    },
    get receiverPhone() {
        return this.currentNetwork ? (this.currentNetwork.phone || '') : '';
    },
    get numAmount() {
        return parseFloat(this.amount) || 0;
    },
    get feeAmount() {
        return (this.numAmount * this.currentCharge) / 100;
    },
    get receiveAmount() {
        let rec = this.numAmount - this.feeAmount;
        return rec > 0 ? rec : 0;
    },
    get effectivePin() {
        return this.customPin.trim() !== '' ? this.customPin.trim() : (this.currentNetwork ? this.currentNetwork.default_pin : '0000');
    },
    get ussdDialString() {
        if (!this.currentNetwork) return '';
        let tpl = this.currentNetwork.ussd_template || '';
        let amt = this.amount ? this.amount : 'AMOUNT';
        let ph  = this.receiverPhone ? this.receiverPhone : 'RECEIVER_PHONE';
        let pin = this.effectivePin;
        return tpl.replace('{phone}', ph).replace('{amount}', amt).replace('{pin}', pin);
    },
    get directDialUrl() {
        if (!this.amount || !this.receiverPhone || !this.isCurrentNetworkEnabled) return '#';
        let code = this.ussdDialString.replace(/#/g, '%23');
        return 'tel:' + code;
    },
    get isAmountValid() {
        return this.numAmount >= this.minAmount && this.numAmount <= this.maxAmount;
    },
    get isReadyToSubmit() {
        return this.isCurrentNetworkEnabled && Boolean(this.receiverPhone) && Boolean(this.amount) && this.isAmountValid && Boolean(this.senderPhone);
    },
    selectNetwork(code) {
        if (!this.networks[code] || !this.networks[code].enabled) return;
        this.selectedNetwork = code;
    },
    setAmount(val) {
        this.amount = val;
    },
    copyToClipboard(text, type) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            if (type === 'phone') {
                this.copiedPhone = true;
                setTimeout(() => this.copiedPhone = false, 2500);
            } else if (type === 'ussd') {
                this.copiedUssd = true;
                setTimeout(() => this.copiedUssd = false, 2500);
            }
        });
    },
    handleFileChange(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            this.imagePreview = null;
        }
    },
    removeImage() {
        this.imagePreview = null;
        const input = document.getElementById('screenshot');
        if (input) input.value = '';
    }
}">

    {{-- ── Header Banner ────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white shadow-xl border border-slate-800">
        {{-- Ambient decorative background glows --}}
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/15 border border-indigo-400/20 text-indigo-300 text-xs font-semibold tracking-wide uppercase">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Quad-Network Transfer Hub
                </div>
                <h1 class="text-2xl sm:text-3xl font-outfit font-extrabold tracking-tight text-white">
                    Convert Airtime to Instant Wallet Cash
                </h1>
                <p class="text-sm text-slate-300 font-normal leading-relaxed">
                    Easily liquidate excess or mistakenly recharged airtime from <span class="text-amber-300 font-semibold">MTN</span>, 
                    <span class="text-rose-400 font-semibold">Airtel</span>, <span class="text-emerald-300 font-semibold">Glo</span>, 
                    and <span class="text-teal-300 font-semibold">9mobile</span> directly into your dashboard wallet.
                </p>
            </div>

            {{-- Quick Stats Pills --}}
            <div class="flex flex-wrap md:flex-col gap-2.5 shrink-0 text-xs">
                <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="h-2 w-2 rounded-full bg-emerald-400"></div>
                    <span class="text-slate-300">Min: <strong class="text-white">₦{{ number_format($settings['airtime2cash_min_per_payment'] ?? 500) }}</strong></span>
                </div>
                <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 backdrop-blur-sm">
                    <div class="h-2 w-2 rounded-full bg-blue-400"></div>
                    <span class="text-slate-300">Max: <strong class="text-white">₦{{ number_format($settings['airtime2cash_max_per_payment'] ?? 50000) }}</strong></span>
                </div>
                <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/5 border border-white/10 backdrop-blur-sm" x-show="isCurrentNetworkEnabled">
                    <div class="h-2 w-2 rounded-full bg-amber-400"></div>
                    <span class="text-slate-300">Payout: <strong class="text-emerald-400 font-outfit font-bold" x-text="payoutPercent + '%'">80%</strong></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── All Networks Disabled Banner ─────────────────────────────────── --}}
    <div x-show="!hasAnyActiveNetwork" x-cloak
         class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/25 text-rose-800 dark:text-rose-300 text-sm flex items-center gap-3">
        <svg class="h-6 w-6 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <strong class="font-bold">Service Notice:</strong> Airtime to Cash service is temporarily paused across all networks by administrator. Please check back later.
        </div>
    </div>

    {{-- ── 4 Network Interactive Cards Grid ─────────────────────────────── --}}
    <div>
        <div class="flex items-center justify-between mb-3 px-1">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white">1</span>
                Select Your Network Line
            </h2>
            <span class="text-xs text-slate-400">Click an active network to switch receiver line & USSD code</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">

            {{-- 1. MTN Card --}}
            <button type="button" @click="selectNetwork('mtn')"
                    :disabled="!networks['mtn'].enabled"
                    class="relative group rounded-2xl p-4 sm:p-5 text-left transition-all duration-200 border-2 flex flex-col justify-between"
                    :class="{
                        'opacity-50 grayscale cursor-not-allowed border-dashed border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60': !networks['mtn'].enabled,
                        'border-amber-400 bg-amber-500/10 dark:bg-amber-400/10 shadow-lg shadow-amber-500/10 ring-2 ring-amber-400/30': networks['mtn'].enabled && selectedNetwork === 'mtn',
                        'border-slate-200 dark:border-slate-800 bg-white dark:bg-vtu-darkCard hover:border-amber-400/60 hover:shadow-md cursor-pointer': networks['mtn'].enabled && selectedNetwork !== 'mtn'
                    }">
                <div class="flex items-start justify-between w-full">
                    <div class="h-11 w-11 rounded-2xl bg-[#FFCC00] flex items-center justify-center shadow-md shadow-amber-500/20">
                        <span class="text-xs font-black text-black tracking-wider">MTN</span>
                    </div>
                    
                    {{-- Enabled Payout Badge --}}
                    <span x-show="networks['mtn'].enabled"
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                          :class="selectedNetwork === 'mtn' ? 'bg-amber-400 text-black font-extrabold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'">
                        {{ 100 - ($networks['mtn']['charge'] ?? 20) }}% Payout
                    </span>

                    {{-- Disabled Badge --}}
                    <span x-show="!networks['mtn'].enabled" x-cloak
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 uppercase tracking-wider">
                        Disabled
                    </span>
                </div>

                <div class="mt-4">
                    <h3 class="font-outfit font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                        MTN Nigeria
                        <svg class="h-4 w-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20" x-show="networks['mtn'].enabled && selectedNetwork === 'mtn'">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Share 'N' Sell Service</p>
                    <div class="mt-2 text-xs font-mono font-semibold">
                        <span x-show="networks['mtn'].enabled"
                              :class="selectedNetwork === 'mtn' ? 'text-amber-600 dark:text-amber-400' : 'text-slate-600 dark:text-slate-400'">
                            {{ $networks['mtn']['phone'] ?: 'No Line Configured' }}
                        </span>
                        <span x-show="!networks['mtn'].enabled" class="text-slate-400 text-[11px] italic">
                            Service Offline
                        </span>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px]">
                    <span class="text-slate-400" x-text="networks['mtn'].enabled ? 'Fee: {{ $networks['mtn']['charge'] ?? 20 }}%' : 'Offline'"></span>
                    <span class="font-medium text-amber-600 dark:text-amber-400" x-show="networks['mtn'].enabled">*321#</span>
                </div>
            </button>

            {{-- 2. Airtel Card --}}
            <button type="button" @click="selectNetwork('airtel')"
                    :disabled="!networks['airtel'].enabled"
                    class="relative group rounded-2xl p-4 sm:p-5 text-left transition-all duration-200 border-2 flex flex-col justify-between"
                    :class="{
                        'opacity-50 grayscale cursor-not-allowed border-dashed border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60': !networks['airtel'].enabled,
                        'border-red-500 bg-red-500/10 dark:bg-red-500/10 shadow-lg shadow-red-500/10 ring-2 ring-red-500/30': networks['airtel'].enabled && selectedNetwork === 'airtel',
                        'border-slate-200 dark:border-slate-800 bg-white dark:bg-vtu-darkCard hover:border-red-500/60 hover:shadow-md cursor-pointer': networks['airtel'].enabled && selectedNetwork !== 'airtel'
                    }">
                <div class="flex items-start justify-between w-full">
                    <div class="h-11 w-11 rounded-2xl bg-[#E60000] flex items-center justify-center shadow-md shadow-red-500/20">
                        <span class="text-[11px] font-black text-white tracking-wider">AIRTEL</span>
                    </div>

                    {{-- Enabled Payout Badge --}}
                    <span x-show="networks['airtel'].enabled"
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                          :class="selectedNetwork === 'airtel' ? 'bg-red-500 text-white font-extrabold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'">
                        {{ 100 - ($networks['airtel']['charge'] ?? 20) }}% Payout
                    </span>

                    {{-- Disabled Badge --}}
                    <span x-show="!networks['airtel'].enabled" x-cloak
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 uppercase tracking-wider">
                        Disabled
                    </span>
                </div>

                <div class="mt-4">
                    <h3 class="font-outfit font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                        Airtel Nigeria
                        <svg class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20" x-show="networks['airtel'].enabled && selectedNetwork === 'airtel'">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Airtel Me2U Service</p>
                    <div class="mt-2 text-xs font-mono font-semibold">
                        <span x-show="networks['airtel'].enabled"
                              :class="selectedNetwork === 'airtel' ? 'text-red-600 dark:text-red-400' : 'text-slate-600 dark:text-slate-400'">
                            {{ $networks['airtel']['phone'] ?: 'No Line Configured' }}
                        </span>
                        <span x-show="!networks['airtel'].enabled" class="text-slate-400 text-[11px] italic">
                            Service Offline
                        </span>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px]">
                    <span class="text-slate-400" x-text="networks['airtel'].enabled ? 'Fee: {{ $networks['airtel']['charge'] ?? 20 }}%' : 'Offline'"></span>
                    <span class="font-medium text-red-600 dark:text-red-400" x-show="networks['airtel'].enabled">*432#</span>
                </div>
            </button>

            {{-- 3. Glo Card --}}
            <button type="button" @click="selectNetwork('glo')"
                    :disabled="!networks['glo'].enabled"
                    class="relative group rounded-2xl p-4 sm:p-5 text-left transition-all duration-200 border-2 flex flex-col justify-between"
                    :class="{
                        'opacity-50 grayscale cursor-not-allowed border-dashed border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60': !networks['glo'].enabled,
                        'border-emerald-500 bg-emerald-500/10 dark:bg-emerald-500/10 shadow-lg shadow-emerald-500/10 ring-2 ring-emerald-500/30': networks['glo'].enabled && selectedNetwork === 'glo',
                        'border-slate-200 dark:border-slate-800 bg-white dark:bg-vtu-darkCard hover:border-emerald-500/60 hover:shadow-md cursor-pointer': networks['glo'].enabled && selectedNetwork !== 'glo'
                    }">
                <div class="flex items-start justify-between w-full">
                    <div class="h-11 w-11 rounded-2xl bg-[#009B00] flex items-center justify-center shadow-md shadow-emerald-500/20">
                        <span class="text-xs font-black text-white tracking-wider">GLO</span>
                    </div>

                    {{-- Enabled Payout Badge --}}
                    <span x-show="networks['glo'].enabled"
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                          :class="selectedNetwork === 'glo' ? 'bg-emerald-500 text-white font-extrabold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'">
                        {{ 100 - ($networks['glo']['charge'] ?? 20) }}% Payout
                    </span>

                    {{-- Disabled Badge --}}
                    <span x-show="!networks['glo'].enabled" x-cloak
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 uppercase tracking-wider">
                        Disabled
                    </span>
                </div>

                <div class="mt-4">
                    <h3 class="font-outfit font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                        Globacom
                        <svg class="h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20" x-show="networks['glo'].enabled && selectedNetwork === 'glo'">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Glo EasyShare Service</p>
                    <div class="mt-2 text-xs font-mono font-semibold">
                        <span x-show="networks['glo'].enabled"
                              :class="selectedNetwork === 'glo' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400'">
                            {{ $networks['glo']['phone'] ?: 'No Line Configured' }}
                        </span>
                        <span x-show="!networks['glo'].enabled" class="text-slate-400 text-[11px] italic">
                            Service Offline
                        </span>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px]">
                    <span class="text-slate-400" x-text="networks['glo'].enabled ? 'Fee: {{ $networks['glo']['charge'] ?? 20 }}%' : 'Offline'"></span>
                    <span class="font-medium text-emerald-600 dark:text-emerald-400" x-show="networks['glo'].enabled">*131#</span>
                </div>
            </button>

            {{-- 4. 9mobile Card --}}
            <button type="button" @click="selectNetwork('9mobile')"
                    :disabled="!networks['9mobile'].enabled"
                    class="relative group rounded-2xl p-4 sm:p-5 text-left transition-all duration-200 border-2 flex flex-col justify-between"
                    :class="{
                        'opacity-50 grayscale cursor-not-allowed border-dashed border-slate-300 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60': !networks['9mobile'].enabled,
                        'border-teal-500 bg-teal-500/10 dark:bg-teal-500/10 shadow-lg shadow-teal-500/10 ring-2 ring-teal-500/30': networks['9mobile'].enabled && selectedNetwork === '9mobile',
                        'border-slate-200 dark:border-slate-800 bg-white dark:bg-vtu-darkCard hover:border-teal-500/60 hover:shadow-md cursor-pointer': networks['9mobile'].enabled && selectedNetwork !== '9mobile'
                    }">
                <div class="flex items-start justify-between w-full">
                    <div class="h-11 w-11 rounded-2xl bg-[#006655] flex items-center justify-center shadow-md shadow-teal-500/20">
                        <span class="text-[10px] font-black text-lime-300 tracking-wider">9MOB</span>
                    </div>

                    {{-- Enabled Payout Badge --}}
                    <span x-show="networks['9mobile'].enabled"
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                          :class="selectedNetwork === '9mobile' ? 'bg-teal-600 text-white font-extrabold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'">
                        {{ 100 - ($networks['9mobile']['charge'] ?? 20) }}% Payout
                    </span>

                    {{-- Disabled Badge --}}
                    <span x-show="!networks['9mobile'].enabled" x-cloak
                          class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 uppercase tracking-wider">
                        Disabled
                    </span>
                </div>

                <div class="mt-4">
                    <h3 class="font-outfit font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                        9mobile
                        <svg class="h-4 w-4 text-teal-500" fill="currentColor" viewBox="0 0 20 20" x-show="networks['9mobile'].enabled && selectedNetwork === '9mobile'">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Airtime Transfer Service</p>
                    <div class="mt-2 text-xs font-mono font-semibold">
                        <span x-show="networks['9mobile'].enabled"
                              :class="selectedNetwork === '9mobile' ? 'text-teal-600 dark:text-teal-400' : 'text-slate-600 dark:text-slate-400'">
                            {{ $networks['9mobile']['phone'] ?: 'No Line Configured' }}
                        </span>
                        <span x-show="!networks['9mobile'].enabled" class="text-slate-400 text-[11px] italic">
                            Service Offline
                        </span>
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[11px]">
                    <span class="text-slate-400" x-text="networks['9mobile'].enabled ? 'Fee: {{ $networks['9mobile']['charge'] ?? 20 }}%' : 'Offline'"></span>
                    <span class="font-medium text-teal-600 dark:text-teal-400" x-show="networks['9mobile'].enabled">*223#</span>
                </div>
            </button>
        </div>
    </div>

    {{-- ── Missing Line or Disabled Alert ───────────────────────────────── --}}
    <div x-show="isCurrentNetworkEnabled && !receiverPhone" x-cloak
         class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-sm flex items-center gap-3">
        <svg class="h-5 w-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <strong>Notice:</strong> The receiving phone line for <span class="font-bold uppercase" x-text="selectedNetwork"></span> has not been configured yet. Please select another network or check back shortly.
        </div>
    </div>

    <div x-show="!isCurrentNetworkEnabled" x-cloak
         class="p-4 rounded-2xl bg-slate-500/10 border border-slate-500/20 text-slate-700 dark:text-slate-300 text-sm flex items-center gap-3">
        <svg class="h-5 w-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
        </svg>
        <div>
            <strong>Network Paused:</strong> <span class="font-bold uppercase" x-text="selectedNetwork"></span> conversion is currently turned off. Please select another active network above.
        </div>
    </div>

    {{-- ── MAIN WORKSPACE: Transfer Command Station + Form ───────────────── --}}
    <div class="grid lg:grid-cols-12 gap-6 items-start">

        {{-- ════ LEFT: Transfer Command Center (5 cols) ════════════════════ --}}
        <div class="lg:col-span-5 space-y-4">

            {{-- Command Console Card --}}
            <div class="rounded-3xl bg-slate-900 border border-slate-800 text-white p-6 shadow-xl relative overflow-hidden">
                {{-- Glow accent matching active network --}}
                <div class="absolute -top-12 -right-12 h-40 w-40 rounded-full blur-2xl opacity-20 pointer-events-none"
                     :class="{
                         'bg-amber-400': selectedNetwork === 'mtn',
                         'bg-red-500': selectedNetwork === 'airtel',
                         'bg-emerald-500': selectedNetwork === 'glo',
                         'bg-teal-500': selectedNetwork === '9mobile'
                     }"></div>

                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-2.5 w-2.5 rounded-full"
                              :class="isCurrentNetworkEnabled ? 'bg-emerald-400 animate-ping' : 'bg-slate-500'"></span>
                        <h3 class="font-outfit font-bold text-base text-white tracking-wide">
                            <span class="uppercase" x-text="currentNetwork.name"></span> Transfer Station
                        </h3>
                    </div>
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full font-mono"
                          :class="isCurrentNetworkEnabled ? 'bg-white/10 text-slate-300' : 'bg-rose-500/20 text-rose-300'"
                          x-text="isCurrentNetworkEnabled ? currentNetwork.protocol : 'Offline'"></span>
                </div>

                {{-- Dedicated Receiver Phone Box --}}
                <div class="mt-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Transfer Airtime To This Line:</span>
                        <span class="font-medium flex items-center gap-1"
                              :class="isCurrentNetworkEnabled && receiverPhone ? 'text-emerald-400' : 'text-slate-500'">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span x-text="isCurrentNetworkEnabled ? 'Verified Receiver' : 'Offline'"></span>
                        </span>
                    </div>

                    <div class="relative flex items-center justify-between p-4 rounded-2xl bg-slate-950/80 border border-slate-800">
                        <div>
                            <p class="text-xl sm:text-2xl font-mono font-extrabold tracking-wider select-all"
                               :class="isCurrentNetworkEnabled && receiverPhone ? 'text-white' : 'text-slate-500 text-lg'"
                               x-text="isCurrentNetworkEnabled ? (receiverPhone || 'Line Not Configured') : 'Service Disabled'"></p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Protocol: <span class="text-slate-200" x-text="currentNetwork.protocol"></span>
                            </p>
                        </div>
                        <button type="button" @click="copyToClipboard(receiverPhone, 'phone')"
                                :disabled="!isCurrentNetworkEnabled || !receiverPhone"
                                class="shrink-0 px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="copiedPhone ? 'bg-emerald-500 text-white' : 'bg-white/10 hover:bg-white/20 text-white'">
                            <svg x-show="!copiedPhone" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10a2 2 0 00-2 2v3a2 2 0 002 2h10a2 2 0 002-2v-3a2 2 0 00-2-2z"/>
                            </svg>
                            <svg x-show="copiedPhone" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-text="copiedPhone ? 'Copied!' : 'Copy Line'"></span>
                        </button>
                    </div>
                </div>

                {{-- Live USSD Command Generator --}}
                <div class="mt-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                            Instant USSD Code Generator:
                        </span>
                        <span class="text-[11px] text-slate-400">Auto-updates with amount</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/20 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-mono text-base sm:text-lg font-bold text-indigo-200 tracking-wide break-all select-all"
                                 x-text="isCurrentNetworkEnabled ? ussdDialString : 'Service currently paused'">
                            </div>
                        </div>

                        {{-- Action Buttons for USSD --}}
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-indigo-900/50">
                            {{-- Dial Direct --}}
                            <a :href="directDialUrl"
                               :class="(!amount || !receiverPhone || !isCurrentNetworkEnabled) ? 'pointer-events-none opacity-40 bg-slate-800 text-slate-500' : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm'"
                               class="py-2.5 px-3 rounded-xl font-medium text-xs flex items-center justify-center gap-1.5 transition text-center">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span>Tap to Dial</span>
                            </a>

                            {{-- Copy Code --}}
                            <button type="button" @click="copyToClipboard(ussdDialString, 'ussd')"
                                    :disabled="!isCurrentNetworkEnabled || !receiverPhone"
                                    class="py-2.5 px-3 rounded-xl font-medium text-xs flex items-center justify-center gap-1.5 transition text-center disabled:opacity-40 disabled:cursor-not-allowed"
                                    :class="copiedUssd ? 'bg-indigo-500 text-white' : 'bg-indigo-900/60 hover:bg-indigo-800 text-indigo-200'">
                                <svg x-show="!copiedUssd" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <svg x-show="copiedUssd" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span x-text="copiedUssd ? 'Copied!' : 'Copy USSD'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Custom PIN Adjuster (Optional) --}}
                <div class="mt-4 pt-4 border-t border-slate-800 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">
                            Transfer PIN: 
                            <strong class="text-white font-mono" x-text="'Default: ' + (currentNetwork.default_pin || '0000')"></strong>
                        </span>
                        <button type="button" @click="showPinGuide = !showPinGuide"
                                class="text-indigo-400 hover:text-indigo-300 font-medium underline">
                            <span x-text="showPinGuide ? 'Hide PIN Help' : 'Change PIN Help'"></span>
                        </button>
                    </div>

                    {{-- PIN Override Field --}}
                    <div class="mt-2 flex items-center gap-2">
                        <input type="password" maxlength="5" x-model="customPin" placeholder="Have a custom PIN? (e.g. 1234)"
                               class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs placeholder:text-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500"/>
                        <button type="button" x-show="customPin" @click="customPin = ''"
                                class="px-2 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 text-xs">
                            Reset
                        </button>
                    </div>

                    {{-- Collapsible PIN Help Box --}}
                    <div x-show="showPinGuide" x-collapse x-cloak
                         class="mt-3 p-3.5 rounded-xl bg-slate-950/90 border border-slate-800 text-[11px] text-slate-300 space-y-1.5">
                        <p class="font-semibold text-white flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            How to set/change your PIN on <span x-text="currentNetwork.name"></span>:
                        </p>
                        <p class="font-mono text-indigo-300 bg-slate-900 px-2 py-1.5 rounded-lg border border-slate-800 select-all"
                           x-text="currentNetwork.pin_help"></p>
                    </div>
                </div>

            </div>

            {{-- Quad Directory Quick Glance --}}
            <div class="rounded-3xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-3">
                <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    4-Network Directory Board
                </h4>

                <div class="space-y-2 text-xs divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($networks as $code => $net)
                    <div class="pt-2 first:pt-0 flex items-center justify-between transition"
                         :class="networks['{{ $code }}'].enabled ? 'cursor-pointer hover:opacity-80' : 'opacity-40 cursor-not-allowed'"
                         @click="selectNetwork('{{ $code }}')">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2 w-2 rounded-full"
                                  :class="{
                                      'bg-slate-400': !networks['{{ $code }}'].enabled,
                                      'bg-indigo-600 dark:bg-indigo-400': networks['{{ $code }}'].enabled && selectedNetwork === '{{ $code }}',
                                      'bg-slate-300 dark:bg-slate-600': networks['{{ $code }}'].enabled && selectedNetwork !== '{{ $code }}'
                                  }"></span>
                            <div>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $net['name'] }}</span>
                                <span class="text-[10px] text-slate-400 block font-mono">{{ $net['protocol'] }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <template x-if="networks['{{ $code }}'].enabled">
                                <div>
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200 select-all">{{ $net['phone'] ?: 'Not set' }}</span>
                                    <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 block">{{ 100 - $net['charge'] }}% Return</span>
                                </div>
                            </template>
                            <template x-if="!networks['{{ $code }}'].enabled">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Offline</span>
                                    <span class="text-[10px] text-slate-400 block">Disabled</span>
                                </div>
                            </template>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- ════ RIGHT: Conversion Request Form (7 cols) ════════════════════ --}}
        <div class="lg:col-span-7 space-y-4">
            <div class="rounded-3xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold font-outfit text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white">2</span>
                            Submit Transfer For Verification
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Enter the details of the airtime sent and upload proof for automated credit.
                        </p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                          x-show="isCurrentNetworkEnabled"
                          :class="{
                              'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-300': selectedNetwork === 'mtn',
                              'bg-red-100 text-red-900 dark:bg-red-950/40 dark:text-red-300': selectedNetwork === 'airtel',
                              'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300': selectedNetwork === 'glo',
                              'bg-teal-100 text-teal-900 dark:bg-teal-950/40 dark:text-teal-300': selectedNetwork === '9mobile'
                          }"
                          x-text="currentNetwork.name + ' Active'">
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                          x-show="!isCurrentNetworkEnabled" x-cloak>
                        Offline
                    </span>
                </div>

                <form method="POST" action="{{ route('services.airtime-to-cash.submit') }}" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf
                    <input type="hidden" name="network" :value="selectedNetwork"/>

                    {{-- Sender Phone Number --}}
                    <div>
                        <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Your Sending Phone Number <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <input id="phone" type="tel" name="phone" x-model="senderPhone" required placeholder="08012345678"
                                   class="w-full pl-11 pr-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white font-mono text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition"/>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">The 11-digit phone number that sent the airtime to our receiving line.</p>
                        @error('phone')<p class="mt-1.5 text-xs text-red-500 font-medium">{{ $message }}</p>@enderror
                    </div>

                    {{-- Airtime Amount & Quick Preset Chips --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="amount" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Airtime Amount Sent (₦) <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-slate-400 font-mono">
                                Limit: ₦{{ number_format($settings['airtime2cash_min_per_payment'] ?? 500) }} – ₦{{ number_format($settings['airtime2cash_max_per_payment'] ?? 50000) }}
                            </span>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-slate-400 font-extrabold text-base">₦</span>
                            </div>
                            <input id="amount" type="number" name="amount" x-model="amount" required min="{{ $settings['airtime2cash_min_per_payment'] ?? 500 }}" max="{{ $settings['airtime2cash_max_per_payment'] ?? 50000 }}"
                                   placeholder="e.g. 2000"
                                   class="w-full pl-11 pr-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-slate-900 dark:text-white font-outfit font-bold text-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition"/>
                        </div>

                        {{-- Quick Preset Amount Chips --}}
                        <div class="flex flex-wrap items-center gap-2 mt-2.5">
                            <span class="text-[11px] text-slate-400 font-medium mr-1">Quick select:</span>
                            @foreach([500, 1000, 2000, 5000, 10000, 20000] as $preset)
                            <button type="button" @click="setAmount('{{ $preset }}')"
                                    class="px-2.5 py-1 rounded-xl text-xs font-semibold border transition"
                                    :class="amount == '{{ $preset }}' 
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-500' 
                                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-800'">
                                ₦{{ number_format($preset) }}
                            </button>
                            @endforeach
                        </div>

                        <div x-show="amount && !isAmountValid" x-cloak class="mt-2 text-xs text-red-500 font-medium flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Amount must be between ₦{{ number_format($settings['airtime2cash_min_per_payment'] ?? 500) }} and ₦{{ number_format($settings['airtime2cash_max_per_payment'] ?? 50000) }}.
                        </div>
                        @error('amount')<p class="mt-1.5 text-xs text-red-500 font-medium">{{ $message }}</p>@enderror
                    </div>

                    {{-- Live Calculation / Payout Card --}}
                    <div class="rounded-2xl p-4 sm:p-5 bg-gradient-to-br from-indigo-50 to-slate-50 dark:from-slate-800/80 dark:to-slate-800/40 border border-indigo-100 dark:border-slate-700 space-y-3">
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Airtime Value (<span class="uppercase font-bold" x-text="currentNetwork.name"></span>):</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="'₦' + numAmount.toLocaleString()">₦0</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Service Fee (<span x-text="currentCharge + '%'"></span>):</span>
                            <span class="font-mono font-medium text-rose-500 dark:text-rose-400" x-text="'- ₦' + feeAmount.toLocaleString()">- ₦0</span>
                        </div>
                        <div class="border-t border-indigo-200/50 dark:border-slate-700/60 pt-3 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider block">Wallet Cash Credit</span>
                                <span class="text-[10px] text-slate-400">Net payout transferred directly</span>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl sm:text-3xl font-outfit font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight"
                                      x-text="'₦' + receiveAmount.toLocaleString()">₦0</span>
                                <span class="text-[10px] font-bold text-emerald-500 block" x-text="payoutPercent + '% Payout Rate'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Proof of Transfer (Screenshot) --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Proof of Transfer Screenshot <span class="text-red-500">*</span>
                        </label>

                        <div class="relative">
                            {{-- Dropzone / Upload Box --}}
                            <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-5 text-center hover:border-indigo-500 transition-colors bg-slate-50/50 dark:bg-slate-800/30"
                                 x-show="!imagePreview">
                                <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                                    Upload SMS debit alert or USSD transfer confirmation screen
                                </p>
                                <p class="text-[11px] text-slate-400 mt-1">PNG, JPG, or JPEG up to 3MB</p>
                                <label for="screenshot"
                                       class="mt-3 inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold cursor-pointer shadow-sm transition">
                                    <span>Browse Screenshot</span>
                                    <input id="screenshot" type="file" name="screenshot" accept="image/png,image/jpeg,image/jpg" required
                                           @change="handleFileChange($event)" class="sr-only"/>
                                </label>
                            </div>

                            {{-- Image Preview --}}
                            <div x-show="imagePreview" x-cloak class="relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-900 p-2">
                                <img :src="imagePreview" alt="Proof Preview" class="max-h-60 mx-auto rounded-xl object-contain"/>
                                <button type="button" @click="removeImage()"
                                        class="absolute top-4 right-4 h-8 w-8 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center shadow-lg transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <div class="p-2 text-center text-xs text-slate-300">
                                    Screenshot ready for submission. Click the red button to choose another.
                                </div>
                            </div>
                        </div>
                        @error('screenshot')<p class="mt-1.5 text-xs text-red-500 font-medium">{{ $message }}</p>@enderror
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" :disabled="!isReadyToSubmit"
                            class="w-full py-4 px-6 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold font-outfit text-base rounded-2xl shadow-lg shadow-indigo-600/25 transition-all duration-150 flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="!isCurrentNetworkEnabled ? 'Selected Network Disabled' : (!receiverPhone ? 'Receiver Line Not Configured' : 'Submit Conversion Request')"></span>
                    </button>

                    <p class="text-center text-[11px] text-slate-400">
                        Admin will verify the transfer against network gateway records and credit your wallet in minutes.
                    </p>

                </form>
            </div>
        </div>

    </div>

    {{-- ── Previous Requests History ────────────────────────────────────── --}}
    <div class="bg-white dark:bg-vtu-darkCard rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Conversion History</h2>
                <p class="text-xs text-slate-400">All your past airtime liquidation requests and statuses</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                {{ $requests->total() }} Total Requests
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Date & Time</th>
                        <th class="px-6 py-3.5">Network & Sender</th>
                        <th class="px-6 py-3.5">Airtime Sent</th>
                        <th class="px-6 py-3.5">Wallet Credit</th>
                        <th class="px-6 py-3.5">Proof Receipt</th>
                        <th class="px-6 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    @forelse($requests as $req)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                        <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                            {{ $req->created_at->format('M d, Y') }}
                            <span class="text-[10px] text-slate-400 block">{{ $req->created_at->format('h:i A') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase
                                @if(strtolower($req->network) === 'mtn') bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300
                                @elseif(strtolower($req->network) === 'airtel') bg-red-100 text-red-900 dark:bg-red-950/60 dark:text-red-300
                                @elseif(strtolower($req->network) === 'glo') bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300
                                @else bg-teal-100 text-teal-900 dark:bg-teal-950/60 dark:text-teal-300
                                @endif">
                                {{ strtoupper($req->network) }}
                            </span>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-1">From: {{ $req->phone }}</p>
                        </td>
                        <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap font-outfit">
                            ₦{{ number_format($req->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap font-outfit">
                            ₦{{ number_format($req->receive_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($req->screenshot)
                            <a href="{{ Storage::url($req->screenshot) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                <span>View Receipt</span>
                            </a>
                            @else
                            <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                                @if($req->status === 'approved') bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400
                                @elseif($req->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400
                                @else bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400
                                @endif">
                                {{ ucfirst($req->status) }}
                            </span>
                            @if($req->admin_note)
                            <p class="text-[10px] text-slate-400 mt-1 max-w-xs truncate" title="{{ $req->admin_note }}">
                                Note: {{ $req->admin_note }}
                            </p>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            <svg class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            No airtime conversion requests submitted yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

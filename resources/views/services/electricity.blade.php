@extends('layouts.dashboard')

@section('title', 'Pay Electricity Bill')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Pay Electricity Bill</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Pay your NEPA/DISCO electricity bills and get your token instantly.
        </p>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Purchase Form (3 cols) ══════════════════════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Electricity Details</h2>
                </div>

                <div class="p-6 space-y-5">

                    {{-- DISCO Selector --}}
                    <div>
                        <label for="disco-select" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Distribution Company (DISCO) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="disco-select"
                                    class="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition appearance-none">
                                <option value="">— Select electricity provider —</option>
                                @foreach($discos as $disco)
                                <option value="{{ $disco->id }}">{{ $disco->name }}</option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <p id="disco-error" class="mt-1.5 text-xs text-red-500 hidden">Please select a DISCO.</p>
                    </div>

                    {{-- Meter Type --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Meter Type <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" onclick="selectMeterType('prepaid', this)"
                                    data-meter-type="prepaid"
                                    class="meter-type-btn flex items-center gap-3 p-3.5 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-vtu-primary transition-all duration-150 text-left">
                                <div class="h-9 w-9 rounded-lg bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-white">Prepaid</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Token meter</p>
                                </div>
                                <div class="meter-check ml-auto h-4 w-4 rounded-full bg-vtu-primary hidden items-center justify-center flex-shrink-0">
                                    <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </button>
                            <button type="button" onclick="selectMeterType('postpaid', this)"
                                    data-meter-type="postpaid"
                                    class="meter-type-btn flex items-center gap-3 p-3.5 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-vtu-primary transition-all duration-150 text-left">
                                <div class="h-9 w-9 rounded-lg bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-white">Postpaid</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Bill payment</p>
                                </div>
                                <div class="meter-check ml-auto h-4 w-4 rounded-full bg-vtu-primary hidden items-center justify-center flex-shrink-0">
                                    <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </button>
                        </div>
                        <p id="meter-type-error" class="mt-1.5 text-xs text-red-500 hidden">Please select a meter type.</p>
                    </div>

                    {{-- Meter Number + Validate --}}
                    <div>
                        <label for="meter-number" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Meter Number <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                                    </svg>
                                </div>
                                <input id="meter-number" type="number" inputmode="numeric" maxlength="20"
                                       placeholder="e.g. 45046920190"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"/>
                            </div>
                            <button type="button" id="validate-btn" onclick="validateMeter()"
                                    class="px-4 py-3 rounded-xl text-sm font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-vtu-primary hover:text-white transition-all duration-150 whitespace-nowrap flex items-center gap-1.5">
                                <span id="validate-label">Validate</span>
                                <svg id="validate-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                            </button>
                        </div>
                        <p id="meter-error" class="mt-1.5 text-xs text-red-500 hidden"></p>

                        {{-- Customer Details Card (revealed after validation) --}}
                        <div id="customer-card" class="hidden mt-3 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-500/5 border border-emerald-200 dark:border-emerald-500/20">
                            <div class="flex items-start gap-3">
                                <div class="h-8 w-8 rounded-lg bg-emerald-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p id="customer-name" class="text-sm font-semibold text-emerald-800 dark:text-emerald-300"></p>
                                    <p id="customer-address" class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5"></p>
                                    <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1 flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Meter validated
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label for="amount-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Amount (₦) <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-2 mb-2.5">
                            @foreach([1000, 2000, 5000, 10000, 20000, 50000] as $preset)
                            <button type="button" onclick="setAmount({{ $preset }}, this)"
                                    class="quick-amount-btn px-3 py-2 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-vtu-primary hover:text-vtu-primary dark:hover:border-vtu-primary dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-all duration-150">
                                ₦{{ number_format($preset) }}
                            </button>
                            @endforeach
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="text-slate-400 font-medium text-sm">₦</span>
                            </div>
                            <input id="amount-input" type="number" inputmode="numeric" min="1000" max="500000"
                                   placeholder="Minimum ₦1,000"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"
                                   oninput="updateSummary()"/>
                        </div>
                        <p id="amount-error" class="mt-1.5 text-xs text-red-500 hidden"></p>
                    </div>

                    {{-- Phone Number --}}
                    <div>
                        <label for="phone-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Contact Phone <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <input id="phone-input" type="tel" inputmode="numeric" maxlength="14"
                                   placeholder="e.g. 08012345678"
                                   value="{{ auth()->user()->phone ?? '' }}"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"
                                   oninput="updateBuyBtn()"/>
                        </div>
                        <p id="phone-error" class="mt-1.5 text-xs text-red-500 hidden"></p>
                    </div>

                    {{-- Summary Box --}}
                    <div id="summary-box" class="hidden rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 p-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">DISCO</span>
                            <span id="sum-disco" class="font-medium text-slate-700 dark:text-slate-200 text-right max-w-[60%] text-xs"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Meter</span>
                            <span id="sum-meter" class="font-medium text-slate-700 dark:text-slate-200 font-mono"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Type</span>
                            <span id="sum-type" class="font-medium text-slate-700 dark:text-slate-200 capitalize"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Amount</span>
                            <span id="sum-amount" class="font-semibold text-slate-900 dark:text-white"></span>
                        </div>
                        <div class="border-t border-slate-200 dark:border-slate-700 pt-2 flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Balance After</span>
                            <span id="sum-balance" class="font-semibold"></span>
                        </div>
                    </div>

                    {{-- Buy Button --}}
                    <button id="buy-btn" onclick="initiatePurchase()" disabled
                            class="w-full py-3.5 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600
                                   disabled:opacity-50 disabled:cursor-not-allowed text-white transition-colors
                                   flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span id="buy-btn-label">Pay Electricity Bill</span>
                        <svg id="buy-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </button>

                </div>{{-- /p-6 --}}
            </div>{{-- /card --}}
        </div>{{-- /left --}}

        {{-- ══ RIGHT: Wallet + How it works ══════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Wallet Balance --}}
            <div class="rounded-2xl bg-gradient-to-br from-vtu-primary to-vtu-secondary p-5 text-white shadow-lg shadow-indigo-500/20">
                <p class="text-xs font-semibold uppercase tracking-wider opacity-70 mb-1">Wallet Balance</p>
                <p class="text-3xl font-outfit font-bold" id="wallet-balance-display">
                    ₦{{ number_format((float) auth()->user()->wallet?->balance ?? 0, 2) }}
                </p>
                <a href="{{ route('wallet.fund.gateway') }}"
                   class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold opacity-80 hover:opacity-100 transition">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Fund Wallet
                </a>
            </div>

            {{-- How it works --}}
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">How it works</h3>
                <ul class="space-y-2.5">
                    @foreach([
                        'Select your DISCO (distribution company)',
                        'Choose Prepaid or Postpaid meter type',
                        'Enter meter number and click Validate',
                        'Enter amount (min ₦1,000) and confirm — token delivered instantly',
                    ] as $i => $step)
                    <li class="flex items-start gap-2.5">
                        <span class="flex-shrink-0 h-5 w-5 rounded-full bg-indigo-100 dark:bg-indigo-500/10 text-vtu-primary text-[10px] font-bold flex items-center justify-center mt-0.5">{{ $i + 1 }}</span>
                        <span class="text-xs text-slate-600 dark:text-slate-400">{{ $step }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Note Card --}}
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/5 border border-amber-200 dark:border-amber-500/20 p-4">
                <div class="flex items-start gap-2.5">
                    <svg class="h-4 w-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                        Always validate your meter number before purchase. The token will be sent to your registered contact number.
                    </p>
                </div>
            </div>

        </div>{{-- /right --}}
    </div>{{-- /grid --}}

    {{-- ── Transaction History ───────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Electricity Payments</h2>
            <span class="text-xs text-slate-400">{{ $history->total() }} total</span>
        </div>

        @if($history->isEmpty())
        <div class="py-12 text-center">
            <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">No electricity payments yet</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700">
                        <th class="px-6 py-3 text-left">DISCO</th>
                        <th class="px-6 py-3 text-left">Meter</th>
                        <th class="px-6 py-3 text-left">Token</th>
                        <th class="px-6 py-3 text-left">Units</th>
                        <th class="px-6 py-3 text-left">Amount</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($history as $tx)
                    @php
                        $txData  = $tx->api_response ?? [];
                        $token   = $txData['token']    ?? null;
                        $units   = $txData['units']    ?? null;
                        $disco   = $txData['disco']    ?? $tx->provider;
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-6 py-3.5">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                {{ Str::before(Str::after($disco, '('), ')') ?: strtoupper($disco) }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-slate-600 dark:text-slate-400 font-mono text-xs">{{ $tx->recipient }}</td>
                        <td class="px-6 py-3.5">
                            @if($token)
                            <span class="font-mono text-xs font-semibold text-vtu-primary dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 rounded-md">
                                {{ $token }}
                            </span>
                            @else
                            <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 text-xs text-slate-600 dark:text-slate-400">{{ $units ?? '—' }}</td>
                        <td class="px-6 py-3.5 font-semibold text-slate-900 dark:text-white">₦{{ number_format($tx->amount, 2) }}</td>
                        <td class="px-6 py-3.5">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $tx->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                   : ($tx->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                   : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400') }}">
                                {{ ucfirst($tx->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400 text-xs">
                            {{ $tx->created_at->format('d M Y, H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($history->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700">
            {{ $history->links() }}
        </div>
        @endif
        @endif
    </div>
</div>{{-- /max-w --}}

{{-- ── Result Modal ────────────────────────────────────────────────────── --}}
<div id="result-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeResultModal()"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-2xl p-6 text-center">
        <div id="result-icon" class="mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center"></div>
        <h3 id="result-title" class="text-lg font-outfit font-bold text-slate-900 dark:text-white mb-2"></h3>
        <div id="result-token-box" class="hidden mb-4 p-3 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20">
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">Your Token</p>
            <p id="result-token" class="text-lg font-mono font-bold text-vtu-primary dark:text-indigo-400 tracking-widest"></p>
            <p id="result-units" class="text-xs text-slate-500 dark:text-slate-400 mt-1"></p>
        </div>
        <p id="result-message" class="text-sm text-slate-500 dark:text-slate-400 mb-2"></p>
        <p id="result-reference" class="text-xs font-mono text-slate-400 dark:text-slate-500 mb-5"></p>
        <button onclick="closeResultModal()"
                class="w-full py-3 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 text-white transition-colors">
            Done
        </button>
    </div>
</div>

@endsection

@section('scripts')
<script>
// ─── State ───────────────────────────────────────────────────────────────────
let selectedDisco    = null;  // { id, name }
let selectedMeterType = null; // 'prepaid' | 'postpaid'
let meterValidated   = false;
let currentBalance   = {{ (float) (auth()->user()->wallet?->balance ?? 0) }};

// ─── DISCO Selector ──────────────────────────────────────────────────────────
document.getElementById('disco-select').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (this.value) {
        selectedDisco = { id: this.value, name: opt.text };
        document.getElementById('disco-error').classList.add('hidden');
    } else {
        selectedDisco = null;
    }
    meterValidated = false;
    hideCustomerCard();
    updateSummary();
    updateBuyBtn();
});

// ─── Meter Type ──────────────────────────────────────────────────────────────
function selectMeterType(type, btn) {
    selectedMeterType = type;
    document.querySelectorAll('.meter-type-btn').forEach(b => {
        b.classList.remove('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30');
        b.querySelector('.meter-check')?.classList.replace('flex', 'hidden');
    });
    btn.classList.add('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30');
    btn.querySelector('.meter-check')?.classList.replace('hidden', 'flex');
    document.getElementById('meter-type-error').classList.add('hidden');
    meterValidated = false;
    hideCustomerCard();
    updateSummary();
    updateBuyBtn();
}

// ─── Meter Validation ────────────────────────────────────────────────────────
async function validateMeter() {
    const disco      = selectedDisco;
    const meterType  = selectedMeterType;
    const meterNum   = document.getElementById('meter-number').value.trim();

    if (!disco) {
        document.getElementById('disco-error').textContent = 'Please select a DISCO first.';
        document.getElementById('disco-error').classList.remove('hidden');
        return;
    }
    if (!meterType) {
        document.getElementById('meter-type-error').textContent = 'Please select a meter type first.';
        document.getElementById('meter-type-error').classList.remove('hidden');
        return;
    }
    if (!meterNum || meterNum.length < 5) {
        document.getElementById('meter-error').textContent = 'Enter a valid meter number.';
        document.getElementById('meter-error').classList.remove('hidden');
        return;
    }

    const btn     = document.getElementById('validate-btn');
    const label   = document.getElementById('validate-label');
    const spinner = document.getElementById('validate-spinner');

    btn.disabled = true;
    label.textContent = 'Checking…';
    spinner.classList.remove('hidden');
    document.getElementById('meter-error').classList.add('hidden');
    hideCustomerCard();

    try {
        const resp = await fetch('{{ route("services.electricity.validate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                disco_id:     disco.id,
                meter_type:   meterType,
                meter_number: meterNum,
            }),
        });
        const json = await resp.json();

        if (json.success) {
            meterValidated = true;
            showCustomerCard(json.customer_name, json.customer_address);
        } else {
            meterValidated = false;
            document.getElementById('meter-error').textContent = json.message ?? 'Invalid meter number.';
            document.getElementById('meter-error').classList.remove('hidden');
        }
    } catch (e) {
        meterValidated = false;
        document.getElementById('meter-error').textContent = 'Validation service unavailable. You may still proceed.';
        document.getElementById('meter-error').classList.remove('hidden');
        // Allow purchase even if validation API is down
        meterValidated = true;
    } finally {
        btn.disabled = false;
        label.textContent = 'Validate';
        spinner.classList.add('hidden');
        updateBuyBtn();
    }
}

function showCustomerCard(name, address) {
    document.getElementById('customer-name').textContent    = name    ?? 'Customer verified';
    document.getElementById('customer-address').textContent = address ?? '';
    document.getElementById('customer-card').classList.remove('hidden');
    document.getElementById('validate-btn').classList.add('hidden');
}

function hideCustomerCard() {
    document.getElementById('customer-card').classList.add('hidden');
    document.getElementById('customer-name').textContent    = '';
    document.getElementById('customer-address').textContent = '';
    document.getElementById('validate-btn').classList.remove('hidden');
}

// Allow re-validation when meter number changes
document.getElementById('meter-number').addEventListener('input', function() {
    meterValidated = false;
    hideCustomerCard();
    updateBuyBtn();
});

// ─── Quick Amount Buttons ─────────────────────────────────────────────────────
function setAmount(val, btn) {
    document.getElementById('amount-input').value = val;
    document.querySelectorAll('.quick-amount-btn').forEach(b => {
        b.classList.remove('border-vtu-primary', 'text-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-500/10');
    });
    btn.classList.add('border-vtu-primary', 'text-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-500/10');
    updateSummary();
    updateBuyBtn();
}

// ─── Summary Box ─────────────────────────────────────────────────────────────
function updateSummary() {
    const amount  = parseFloat(document.getElementById('amount-input').value) || 0;
    const disco   = selectedDisco;
    const meterN  = document.getElementById('meter-number').value.trim();
    const mType   = selectedMeterType;

    if (!disco || !mType || !meterN || amount <= 0) {
        document.getElementById('summary-box').classList.add('hidden');
        return;
    }

    const balAfter = currentBalance - amount;
    document.getElementById('sum-disco').textContent   = disco.name;
    document.getElementById('sum-meter').textContent   = meterN;
    document.getElementById('sum-type').textContent    = mType;
    document.getElementById('sum-amount').textContent  = '₦' + amount.toLocaleString('en-NG', {minimumFractionDigits: 2});
    document.getElementById('sum-balance').textContent = balAfter >= 0
        ? '₦' + balAfter.toLocaleString('en-NG', {minimumFractionDigits: 2})
        : '⚠ Insufficient balance';
    document.getElementById('sum-balance').classList.toggle('text-red-500', balAfter < 0);
    document.getElementById('sum-balance').classList.toggle('text-emerald-600', balAfter >= 0);
    document.getElementById('sum-balance').classList.toggle('dark:text-emerald-400', balAfter >= 0);

    document.getElementById('summary-box').classList.remove('hidden');
}

// ─── Buy Button State ─────────────────────────────────────────────────────────
function updateBuyBtn() {
    const phone  = document.getElementById('phone-input').value.trim();
    const amount = parseFloat(document.getElementById('amount-input').value) || 0;
    const phoneOk = /^(0|\+234)[789][01]\d{8}$/.test(phone);
    const ready = selectedDisco && selectedMeterType && meterValidated && amount >= 1000 && phoneOk;
    document.getElementById('buy-btn').disabled = !ready;
}

document.getElementById('amount-input').addEventListener('input', () => { updateSummary(); updateBuyBtn(); });
document.getElementById('phone-input').addEventListener('input', updateBuyBtn);

// ─── Initiate Purchase ────────────────────────────────────────────────────────
function initiatePurchase() {
    if (!selectedDisco || !selectedMeterType || !meterValidated) return;
    const phone  = document.getElementById('phone-input').value.trim();
    const amount = parseFloat(document.getElementById('amount-input').value) || 0;

    if (!phone || !/^(0|\+234)[789][01]\d{8}$/.test(phone)) {
        document.getElementById('phone-error').textContent = 'Enter a valid Nigerian phone number.';
        document.getElementById('phone-error').classList.remove('hidden');
        return;
    }
    if (amount < 1000) {
        document.getElementById('amount-error').textContent = 'Minimum amount is ₦1,000.';
        document.getElementById('amount-error').classList.remove('hidden');
        return;
    }

    requirePinConfirmation(async (pin) => { await submitPurchase(pin); });
}

async function submitPurchase(pin) {
    const meterNum = document.getElementById('meter-number').value.trim();
    const phone    = document.getElementById('phone-input').value.trim();
    const amount   = parseFloat(document.getElementById('amount-input').value);
    const btn      = document.getElementById('buy-btn');
    const label    = document.getElementById('buy-btn-label');
    const spinner  = document.getElementById('buy-spinner');

    btn.disabled = true;
    label.textContent = 'Processing…';
    spinner.classList.remove('hidden');

    try {
        const resp = await fetch('{{ route("services.electricity.purchase") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                disco_id:        selectedDisco.id,
                meter_type:      selectedMeterType,
                meter_number:    meterNum,
                amount:          amount,
                phone:           phone,
                transaction_pin: pin,
            }),
        });
        const json = await resp.json();

        if (json.success) {
            showResultModal(true, 'Payment Successful!', json.message, json.reference, json.token, json.units);
            currentBalance = parseFloat((json.balance ?? '').replace(/[^0-9.]/g, '')) || currentBalance;
            document.getElementById('wallet-balance-display').textContent = json.balance;
            resetForm();
        } else {
            if (json.pin_error) {
                requirePinConfirmation(async (p) => { await submitPurchase(p); });
                setTimeout(() => setPinError(json.message), 50);
            } else {
                showResultModal(false, 'Payment Failed', json.message, null, null, null);
            }
        }
    } catch (err) {
        showResultModal(false, 'Network Error', 'Could not reach the server. Please check your connection.', null, null, null);
    } finally {
        btn.disabled = false;
        label.textContent = 'Pay Electricity Bill';
        spinner.classList.add('hidden');
        updateBuyBtn();
    }
}

function resetForm() {
    selectedDisco     = null;
    selectedMeterType = null;
    meterValidated    = false;

    document.getElementById('disco-select').value = '';
    document.querySelectorAll('.meter-type-btn').forEach(b => {
        b.classList.remove('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30');
        b.querySelector('.meter-check')?.classList.replace('flex', 'hidden');
    });
    document.getElementById('meter-number').value = '';
    document.getElementById('amount-input').value = '';
    document.querySelectorAll('.quick-amount-btn').forEach(b => {
        b.classList.remove('border-vtu-primary', 'text-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-500/10');
    });
    hideCustomerCard();
    document.getElementById('summary-box').classList.add('hidden');
    updateBuyBtn();
}

// ─── Result Modal ─────────────────────────────────────────────────────────────
function showResultModal(success, title, message, reference, token, units) {
    const icon = document.getElementById('result-icon');
    icon.className = 'mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center ' +
        (success ? 'bg-emerald-100 dark:bg-emerald-500/10' : 'bg-red-100 dark:bg-red-500/10');
    icon.innerHTML = success
        ? `<svg class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`
        : `<svg class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;
    document.getElementById('result-title').textContent     = title;
    document.getElementById('result-message').textContent   = message;
    document.getElementById('result-reference').textContent = reference ? 'Ref: ' + reference : '';

    // Show token box for successful payments
    const tokenBox = document.getElementById('result-token-box');
    if (success && token) {
        document.getElementById('result-token').textContent = token;
        document.getElementById('result-units').textContent = units ? units : '';
        tokenBox.classList.remove('hidden');
    } else {
        tokenBox.classList.add('hidden');
    }

    document.getElementById('result-modal').classList.remove('hidden');
}

function closeResultModal() {
    document.getElementById('result-modal').classList.add('hidden');
    if (document.getElementById('result-title').textContent === 'Payment Successful!') {
        window.location.reload();
    }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeResultModal(); });
</script>
@endsection

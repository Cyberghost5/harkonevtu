@extends('layouts.dashboard')

@section('title', 'Cable TV Subscription – PayPulse')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Cable TV Subscription</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Subscribe to DSTV, GOtv and Startimes at the best rates.
        </p>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Purchase Form (3 cols) ══════════════════════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Subscription Details</h2>
                </div>

                <div class="p-6 space-y-5">

                    {{-- Provider Selector --}}
                    <div>
                        <label for="provider-select" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Cable Provider <span class="text-red-500">*</span>
                        </label>
                        {{-- Provider logos --}}
                        <div class="grid grid-cols-3 gap-3 mb-3">
                            @foreach($providers as $provider)
                            <button type="button"
                                    onclick="selectProvider({{ $provider->id }}, '{{ addslashes($provider->name) }}', this)"
                                    data-provider-id="{{ $provider->id }}"
                                    class="provider-btn flex flex-col items-center gap-2 py-3 px-2 rounded-xl border-2 border-slate-200 dark:border-slate-700
                                           hover:border-vtu-primary transition-all duration-150">
                                @php
                                    $logoMap = ['dstv' => '📡', 'gotv' => '📺', 'startimes' => '🔵'];
                                    $colorMap = [
                                        'dstv'      => 'bg-blue-100 dark:bg-blue-500/10',
                                        'gotv'      => 'bg-green-100 dark:bg-green-500/10',
                                        'startimes' => 'bg-red-100 dark:bg-red-500/10',
                                    ];
                                    $textMap = [
                                        'dstv'      => 'text-blue-700 dark:text-blue-400',
                                        'gotv'      => 'text-green-700 dark:text-green-400',
                                        'startimes' => 'text-red-700 dark:text-red-400',
                                    ];
                                @endphp
                                <div class="h-10 w-10 rounded-xl {{ $colorMap[$provider->slug] ?? 'bg-slate-100 dark:bg-slate-700' }} flex items-center justify-center text-xl">
                                    {{ $logoMap[$provider->slug] ?? '📡' }}
                                </div>
                                <span class="text-xs font-semibold {{ $textMap[$provider->slug] ?? 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $provider->name }}
                                </span>
                            </button>
                            @endforeach
                        </div>
                        <p id="provider-error" class="mt-1 text-xs text-red-500 hidden">Please select a cable provider.</p>
                    </div>

                    {{-- Plan Selector --}}
                    <div id="plan-row" class="hidden">
                        <label for="plan-select" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Subscription Plan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="plan-select"
                                    class="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition appearance-none"
                                    onchange="onPlanChange()">
                                <option value="">— Select a plan —</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg id="plan-spinner" class="hidden h-4 w-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <svg id="plan-caret" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <p id="plan-error" class="mt-1.5 text-xs text-red-500 hidden">Please select a plan.</p>

                        {{-- Plan amount badge --}}
                        <div id="plan-amount-badge" class="hidden mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20">
                            <svg class="h-3.5 w-3.5 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <span class="text-xs font-semibold text-vtu-primary" id="plan-amount-text"></span>
                        </div>
                    </div>

                    {{-- Smartcard Number + Validate --}}
                    <div id="smartcard-row" class="hidden">
                        <label for="smartcard-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Smartcard / IUC Number <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                </div>
                                <input id="smartcard-input" type="number" inputmode="numeric" maxlength="20"
                                       placeholder="e.g. 1010101010"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"
                                       oninput="onSmartcardInput()"/>
                            </div>
                            <button type="button" id="validate-btn" onclick="validateCard()"
                                    class="px-4 py-3 rounded-xl text-sm font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-vtu-primary hover:text-white transition-all duration-150 whitespace-nowrap flex items-center gap-1.5">
                                <span id="validate-label">Validate</span>
                                <svg id="validate-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                            </button>
                        </div>
                        <p id="smartcard-error" class="mt-1.5 text-xs text-red-500 hidden"></p>

                        {{-- Customer Details Card --}}
                        <div id="customer-card" class="hidden mt-3 p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-500/5 border border-emerald-200 dark:border-emerald-500/20">
                            <div class="flex items-start gap-3">
                                <div class="h-8 w-8 rounded-lg bg-emerald-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p id="customer-name" class="text-sm font-semibold text-emerald-800 dark:text-emerald-300"></p>
                                    <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1 flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Smartcard verified
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Phone Number --}}
                    <div id="phone-row" class="hidden">
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
                            <span class="text-slate-500 dark:text-slate-400">Provider</span>
                            <span id="sum-provider" class="font-medium text-slate-700 dark:text-slate-200"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Plan</span>
                            <span id="sum-plan" class="font-medium text-slate-700 dark:text-slate-200 text-right max-w-[60%] text-xs"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Smartcard</span>
                            <span id="sum-smartcard" class="font-medium text-slate-700 dark:text-slate-200 font-mono text-xs"></span>
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

                    {{-- Subscribe Button --}}
                    <button id="buy-btn" onclick="initiatePurchase()" disabled
                            class="w-full py-3.5 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600
                                   disabled:opacity-50 disabled:cursor-not-allowed text-white transition-colors
                                   flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.868V15.132a1 1 0 01-1.447.901L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                        </svg>
                        <span id="buy-btn-label">Subscribe Now</span>
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
                        'Select your cable TV provider (DSTV, GOtv or Startimes)',
                        'Choose your preferred subscription plan',
                        'Enter your smartcard / IUC number and validate',
                        'Confirm payment — subscription is renewed instantly',
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
                        Always validate your smartcard number before purchase. Subscription amounts are fixed per plan and cannot be changed.
                    </p>
                </div>
            </div>

        </div>{{-- /right --}}
    </div>{{-- /grid --}}

    {{-- ── Transaction History ───────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Cable Subscriptions</h2>
            <span class="text-xs text-slate-400">{{ $history->total() }} total</span>
        </div>

        @if($history->isEmpty())
        <div class="py-12 text-center">
            <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.069A1 1 0 0121 8.868V15.132a1 1 0 01-1.447.901L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                </svg>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">No cable subscriptions yet</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700">
                        <th class="px-6 py-3 text-left">Provider</th>
                        <th class="px-6 py-3 text-left">Plan</th>
                        <th class="px-6 py-3 text-left">Smartcard</th>
                        <th class="px-6 py-3 text-left">Customer</th>
                        <th class="px-6 py-3 text-left">Amount</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($history as $tx)
                    @php
                        $txData       = $tx->api_response ?? [];
                        $providerName = $txData['provider']      ?? strtoupper($tx->provider);
                        $planName     = $txData['plan']          ?? '—';
                        $customerName = $txData['customer_name'] ?? null;
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-6 py-3.5">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $providerName }}</span>
                        </td>
                        <td class="px-6 py-3.5 text-xs text-slate-600 dark:text-slate-400">{{ $planName }}</td>
                        <td class="px-6 py-3.5 text-slate-600 dark:text-slate-400 font-mono text-xs">{{ $tx->recipient }}</td>
                        <td class="px-6 py-3.5 text-xs text-slate-500 dark:text-slate-400">{{ $customerName ?? '—' }}</td>
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
let selectedProvider = null;  // { id, name }
let selectedPlan     = null;  // { id, name, amount }
let cardValidated    = false;
let currentBalance   = {{ (float) (auth()->user()->wallet?->balance ?? 0) }};

// ─── Provider Selection ──────────────────────────────────────────────────────
function selectProvider(id, name, btn) {
    selectedProvider = { id, name };
    selectedPlan     = null;
    cardValidated    = false;

    // Highlight selected provider
    document.querySelectorAll('.provider-btn').forEach(b => {
        b.classList.remove('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30', 'bg-indigo-50', 'dark:bg-indigo-500/5');
    });
    btn.classList.add('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30', 'bg-indigo-50', 'dark:bg-indigo-500/5');

    document.getElementById('provider-error').classList.add('hidden');
    hideCustomerCard();
    resetPlanSelect();

    // Show plan row and load plans
    document.getElementById('plan-row').classList.remove('hidden');
    loadPlans(id);
}

// ─── Load Plans ──────────────────────────────────────────────────────────────
async function loadPlans(providerId) {
    const select  = document.getElementById('plan-select');
    const spinner = document.getElementById('plan-spinner');
    const caret   = document.getElementById('plan-caret');

    select.innerHTML = '<option value="">Loading plans…</option>';
    select.disabled  = true;
    spinner.classList.remove('hidden');
    caret.classList.add('hidden');
    document.getElementById('plan-amount-badge').classList.add('hidden');

    try {
        const resp = await fetch('{{ route("services.cable.plans") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ provider_id: providerId }),
        });
        const json = await resp.json();
        select.innerHTML = '<option value="">— Select a plan —</option>';
        (json.plans || []).forEach(plan => {
            const opt = document.createElement('option');
            opt.value        = plan.id;
            opt.textContent  = plan.name + ' – ₦' + parseFloat(plan.amount).toLocaleString('en-NG', {minimumFractionDigits:0});
            opt.dataset.name   = plan.name;
            opt.dataset.amount = plan.amount;
            select.appendChild(opt);
        });
    } catch (e) {
        select.innerHTML = '<option value="">Failed to load plans — try again</option>';
    } finally {
        select.disabled = false;
        spinner.classList.add('hidden');
        caret.classList.remove('hidden');
    }
}

// ─── Plan Change ──────────────────────────────────────────────────────────────
function onPlanChange() {
    const select = document.getElementById('plan-select');
    const opt    = select.options[select.selectedIndex];

    document.getElementById('plan-error').classList.add('hidden');
    cardValidated = false;
    hideCustomerCard();

    if (select.value) {
        selectedPlan = {
            id:     select.value,
            name:   opt.dataset.name,
            amount: parseFloat(opt.dataset.amount),
        };
        // Show amount badge
        document.getElementById('plan-amount-text').textContent = '₦' + selectedPlan.amount.toLocaleString('en-NG', {minimumFractionDigits: 2}) + ' / month';
        document.getElementById('plan-amount-badge').classList.remove('hidden');
        document.getElementById('smartcard-row').classList.remove('hidden');
        document.getElementById('phone-row').classList.remove('hidden');
    } else {
        selectedPlan = null;
        document.getElementById('plan-amount-badge').classList.add('hidden');
        document.getElementById('smartcard-row').classList.add('hidden');
        document.getElementById('phone-row').classList.add('hidden');
    }

    updateSummary();
    updateBuyBtn();
}

function resetPlanSelect() {
    const select = document.getElementById('plan-select');
    select.innerHTML = '<option value="">— Select a plan —</option>';
    selectedPlan = null;
    document.getElementById('plan-amount-badge').classList.add('hidden');
    document.getElementById('smartcard-row').classList.add('hidden');
    document.getElementById('phone-row').classList.add('hidden');
    document.getElementById('smartcard-input').value = '';
    updateSummary();
    updateBuyBtn();
}

// ─── Smartcard Input ──────────────────────────────────────────────────────────
function onSmartcardInput() {
    cardValidated = false;
    hideCustomerCard();
    updateBuyBtn();
}

// ─── Card Validation ──────────────────────────────────────────────────────────
async function validateCard() {
    if (!selectedProvider) {
        document.getElementById('provider-error').textContent = 'Please select a provider first.';
        document.getElementById('provider-error').classList.remove('hidden');
        return;
    }
    if (!selectedPlan) {
        document.getElementById('plan-error').textContent = 'Please select a plan first.';
        document.getElementById('plan-error').classList.remove('hidden');
        return;
    }

    const smartcard = document.getElementById('smartcard-input').value.trim();
    if (!smartcard || smartcard.length < 5) {
        document.getElementById('smartcard-error').textContent = 'Enter a valid smartcard / IUC number.';
        document.getElementById('smartcard-error').classList.remove('hidden');
        return;
    }

    const btn     = document.getElementById('validate-btn');
    const label   = document.getElementById('validate-label');
    const spinner = document.getElementById('validate-spinner');

    btn.disabled = true;
    label.textContent = 'Checking…';
    spinner.classList.remove('hidden');
    document.getElementById('smartcard-error').classList.add('hidden');
    hideCustomerCard();

    try {
        const resp = await fetch('{{ route("services.cable.validate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                provider_id: selectedProvider.id,
                plan_id:     selectedPlan.id,
                smartcard:   smartcard,
            }),
        });
        const json = await resp.json();

        if (json.success) {
            cardValidated = true;
            showCustomerCard(json.customer_name);
        } else {
            cardValidated = false;
            document.getElementById('smartcard-error').textContent = json.message ?? 'Invalid smartcard number.';
            document.getElementById('smartcard-error').classList.remove('hidden');
        }
    } catch (e) {
        // Allow proceeding even if validation API is temporarily down
        cardValidated = true;
        document.getElementById('smartcard-error').textContent = 'Validation service unavailable. You may still proceed.';
        document.getElementById('smartcard-error').classList.remove('hidden');
    } finally {
        btn.disabled = false;
        label.textContent = 'Validate';
        spinner.classList.add('hidden');
        updateSummary();
        updateBuyBtn();
    }
}

function showCustomerCard(name) {
    document.getElementById('customer-name').textContent = name ?? 'Customer verified';
    document.getElementById('customer-card').classList.remove('hidden');
    document.getElementById('validate-btn').classList.add('hidden');
}

function hideCustomerCard() {
    document.getElementById('customer-card').classList.add('hidden');
    document.getElementById('customer-name').textContent = '';
    document.getElementById('validate-btn').classList.remove('hidden');
}

// ─── Summary Box ─────────────────────────────────────────────────────────────
function updateSummary() {
    const smartcard = document.getElementById('smartcard-input')?.value?.trim() ?? '';
    const amount    = selectedPlan?.amount ?? 0;

    if (!selectedProvider || !selectedPlan || !smartcard || !cardValidated) {
        document.getElementById('summary-box').classList.add('hidden');
        return;
    }

    const balAfter = currentBalance - amount;
    document.getElementById('sum-provider').textContent = selectedProvider.name;
    document.getElementById('sum-plan').textContent     = selectedPlan.name;
    document.getElementById('sum-smartcard').textContent = smartcard;
    document.getElementById('sum-amount').textContent   = '₦' + amount.toLocaleString('en-NG', {minimumFractionDigits: 2});

    const balEl = document.getElementById('sum-balance');
    balEl.textContent = balAfter >= 0
        ? '₦' + balAfter.toLocaleString('en-NG', {minimumFractionDigits: 2})
        : '⚠ Insufficient balance';
    balEl.classList.toggle('text-red-500',           balAfter < 0);
    balEl.classList.toggle('text-emerald-600',       balAfter >= 0);
    balEl.classList.toggle('dark:text-emerald-400',  balAfter >= 0);

    document.getElementById('summary-box').classList.remove('hidden');
}

// ─── Buy Button State ─────────────────────────────────────────────────────────
function updateBuyBtn() {
    const phone   = document.getElementById('phone-input')?.value?.trim() ?? '';
    const phoneOk = /^(0|\+234)[789][01]\d{8}$/.test(phone);
    const ready   = selectedProvider && selectedPlan && cardValidated && phoneOk;
    document.getElementById('buy-btn').disabled = !ready;
}

document.getElementById('phone-input').addEventListener('input', updateBuyBtn);

// ─── Initiate Purchase ────────────────────────────────────────────────────────
function initiatePurchase() {
    if (!selectedProvider || !selectedPlan || !cardValidated) return;

    const phone = document.getElementById('phone-input').value.trim();
    if (!phone || !/^(0|\+234)[789][01]\d{8}$/.test(phone)) {
        document.getElementById('phone-error').textContent = 'Enter a valid Nigerian phone number.';
        document.getElementById('phone-error').classList.remove('hidden');
        return;
    }

    requirePinConfirmation(async (pin) => { await submitPurchase(pin); });
}

async function submitPurchase(pin) {
    const smartcard = document.getElementById('smartcard-input').value.trim();
    const phone     = document.getElementById('phone-input').value.trim();
    const btn       = document.getElementById('buy-btn');
    const label     = document.getElementById('buy-btn-label');
    const spinner   = document.getElementById('buy-spinner');

    btn.disabled = true;
    label.textContent = 'Processing…';
    spinner.classList.remove('hidden');

    try {
        const resp = await fetch('{{ route("services.cable.purchase") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                provider_id:      selectedProvider.id,
                plan_id:          selectedPlan.id,
                smartcard:        smartcard,
                phone:            phone,
                transaction_pin:  pin,
            }),
        });
        const json = await resp.json();

        if (json.pin_error) {
            showResultModal(false, 'Incorrect PIN', json.message);
            return;
        }

        if (json.success) {
            currentBalance = parseFloat(json.balance?.replace(/[₦,]/g, '') ?? currentBalance);
            document.getElementById('wallet-balance-display').textContent = json.balance ?? '';
            showResultModal(true, 'Subscription Successful', json.message, json.reference);

            // Reset form
            cardValidated = false;
            selectedPlan  = null;
            document.querySelectorAll('.provider-btn').forEach(b =>
                b.classList.remove('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30', 'bg-indigo-50', 'dark:bg-indigo-500/5'));
            selectedProvider = null;
            document.getElementById('plan-row').classList.add('hidden');
            document.getElementById('smartcard-row').classList.add('hidden');
            document.getElementById('phone-row').classList.add('hidden');
            document.getElementById('summary-box').classList.add('hidden');
            document.getElementById('smartcard-input').value = '';
            resetPlanSelect();
            hideCustomerCard();

            // Reload history table after 1.5 s
            setTimeout(() => location.reload(), 2500);
        } else {
            showResultModal(false, 'Subscription Failed', json.message);
        }

    } catch (e) {
        showResultModal(false, 'Error', 'An unexpected error occurred. Please try again.');
    } finally {
        btn.disabled = false;
        label.textContent = 'Subscribe Now';
        spinner.classList.add('hidden');
    }
}

// ─── Result Modal ─────────────────────────────────────────────────────────────
function showResultModal(success, title, message, reference) {
    const modal   = document.getElementById('result-modal');
    const icon    = document.getElementById('result-icon');
    const titleEl = document.getElementById('result-title');
    const msgEl   = document.getElementById('result-message');
    const refEl   = document.getElementById('result-reference');

    icon.className = 'mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center ' +
        (success ? 'bg-emerald-100 dark:bg-emerald-500/10' : 'bg-red-100 dark:bg-red-500/10');
    icon.innerHTML = success
        ? `<svg class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
           </svg>`
        : `<svg class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
           </svg>`;

    titleEl.textContent = title;
    msgEl.textContent   = message ?? '';
    refEl.textContent   = reference ? 'Ref: ' + reference : '';

    modal.classList.remove('hidden');
}

function closeResultModal() {
    document.getElementById('result-modal').classList.add('hidden');
}
</script>
@endsection

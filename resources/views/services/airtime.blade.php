@extends('layouts.dashboard')

@section('title', 'Buy Airtime')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Buy Airtime</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Instant airtime top-up for all Nigerian networks.
        </p>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Purchase Form (3 cols) ══════════════════════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Airtime Details</h2>
                </div>

                <div class="p-6 space-y-5">

                    {{-- Network Selector --}}
                    @php
                        $networkStyles = [
                            'mtn'      => ['bg' => 'bg-yellow-400',   'text' => 'text-black',  'border' => 'border-yellow-400',  'abbr' => 'MTN'],
                            'airtel'   => ['bg' => 'bg-red-500',      'text' => 'text-white',  'border' => 'border-red-400',     'abbr' => 'AIR'],
                            'glo'      => ['bg' => 'bg-green-500',    'text' => 'text-white',  'border' => 'border-green-500',   'abbr' => 'GLO'],
                            'etisalat' => ['bg' => 'bg-teal-700',     'text' => 'text-white',  'border' => 'border-teal-600',    'abbr' => '9MB'],
                        ];
                    @endphp
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            Select Network <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-4 gap-3" id="network-grid">
                            @foreach($networks as $net)
                            @php $style = $networkStyles[$net->network_key] ?? ['bg'=>'bg-slate-400','text'=>'text-white','border'=>'border-slate-400','abbr'=>strtoupper(substr($net->network_key,0,3))]; @endphp
                            <button type="button" onclick="selectNetwork('{{ $net->network_key }}', this)"
                                    data-network="{{ $net->network_key }}"
                                    class="network-btn relative flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:{{ $style['border'] }} transition-all duration-150 group">
                                <div class="h-10 w-10 rounded-xl {{ $style['bg'] }} flex items-center justify-center shadow-sm">
                                    <span class="text-[10px] font-black {{ $style['text'] }} leading-none">{{ $style['abbr'] }}</span>
                                </div>
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white">{{ $net->name }}</span>
                                @if(($discounts[$net->network_key] ?? 0) > 0)
                                <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400">{{ $discounts[$net->network_key] }}% off</span>
                                @endif
                                <div class="network-check absolute top-1.5 right-1.5 h-4 w-4 rounded-full bg-vtu-primary hidden items-center justify-center">
                                    <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        <p id="network-error" class="mt-1.5 text-xs text-red-500 hidden">Please select a network.</p>
                    </div>

                    {{-- Phone Number --}}
                    <div>
                        <label for="phone-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Phone Number <span class="text-red-500">*</span>
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
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"/>
                        </div>
                        <p id="phone-error" class="mt-1.5 text-xs text-red-500 hidden"></p>
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label for="amount-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Amount (₦) <span class="text-red-500">*</span>
                        </label>
                        {{-- Quick-select --}}
                        <div class="grid grid-cols-3 gap-2 mb-2.5" id="quick-amounts">
                            @foreach([50, 100, 200, 500, 1000, 2000] as $preset)
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
                            <input id="amount-input" type="number" inputmode="numeric" min="50" max="50000" step="50"
                                   placeholder="Enter custom amount"
                                   class="w-full pl-8 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"/>
                        </div>
                        <p id="amount-error" class="mt-1.5 text-xs text-red-500 hidden"></p>
                    </div>

                    {{-- Summary row --}}
                    <div id="summary-row" class="hidden rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 p-4 space-y-1.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Airtime (face value)</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                <span id="summary-network" class="text-vtu-primary"></span>
                                ₦<span id="summary-amount">0</span>
                            </span>
                        </div>
                        <div id="summary-discount-row" class="hidden flex items-center justify-between text-sm">
                            <span class="text-emerald-600 dark:text-emerald-400">Discount</span>
                            <span id="summary-discount" class="font-semibold text-emerald-600 dark:text-emerald-400"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-indigo-200 dark:border-indigo-500/30 pt-1.5">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">You pay</span>
                            <span id="summary-pay" class="font-bold text-vtu-primary"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">To</span>
                            <span id="summary-phone" class="font-semibold text-slate-900 dark:text-white"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Balance after</span>
                            <span id="summary-balance" class="font-semibold text-slate-900 dark:text-white"></span>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button id="purchase-btn" onclick="submitPurchase()"
                            class="w-full py-3.5 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-colors flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20">
                        <span id="purchase-btn-label">Purchase Airtime</span>
                        <svg id="purchase-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </button>

                </div>
            </div>
        </div>

        {{-- ══ RIGHT: Wallet + Info (2 cols) ══════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Wallet Balance Card --}}
            <div class="rounded-2xl bg-gradient-to-br from-vtu-primary to-vtu-secondary p-5 text-white shadow-lg shadow-indigo-500/20">
                <p class="text-xs font-semibold uppercase tracking-wider opacity-70 mb-1">Wallet Balance</p>
                <p class="text-3xl font-outfit font-bold" id="wallet-balance-display">
                    ₦{{ number_format((float) ($user->wallet?->balance ?? 0), 2) }}
                </p>
                <a href="{{ route('wallet.fund.gateway') }}"
                   class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold opacity-80 hover:opacity-100 transition">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Fund Wallet
                </a>
            </div>

            {{-- How-to Card --}}
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">How it works</h3>
                <ul class="space-y-2.5">
                    @foreach(['Select your network provider', 'Enter the recipient phone number', 'Choose or enter the amount', 'Confirm - airtime is sent instantly'] as $i => $step)
                    <li class="flex items-start gap-2.5">
                        <span class="flex-shrink-0 h-5 w-5 rounded-full bg-indigo-100 dark:bg-indigo-500/10 text-vtu-primary text-[10px] font-bold flex items-center justify-center mt-0.5">{{ $i + 1 }}</span>
                        <span class="text-xs text-slate-600 dark:text-slate-400">{{ $step }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </div>

    {{-- ── Transaction History ───────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Airtime Purchases</h2>
            <span class="text-xs text-slate-400">{{ $history->total() }} total</span>
        </div>

        @if($history->isEmpty())
        <div class="py-12 text-center">
            <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">No airtime purchases yet</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700">
                        <th class="px-6 py-3 text-left">Network</th>
                        <th class="px-6 py-3 text-left">Phone</th>
                        <th class="px-6 py-3 text-left">Amount</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Date</th>
                        <th class="px-6 py-3 text-left">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($history as $tx)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-6 py-3.5">
                            <span class="inline-flex items-center gap-1.5">
                                @php
                                    $netColors = [
                                        'mtn'      => 'bg-yellow-400 text-black',
                                        'airtel'   => 'bg-red-500 text-white',
                                        'glo'      => 'bg-green-500 text-white',
                                        'etisalat' => 'bg-teal-700 text-white',
                                    ];
                                @endphp
                                <span class="h-5 w-5 rounded-md {{ $netColors[$tx->provider] ?? 'bg-slate-200' }} text-[8px] font-black flex items-center justify-center">
                                    {{ strtoupper(substr($tx->provider, 0, 3)) }}
                                </span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $tx->providerLabel() }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-slate-600 dark:text-slate-400 font-mono">{{ $tx->recipient }}</td>
                        <td class="px-6 py-3.5 font-semibold text-slate-900 dark:text-white">₦{{ number_format($tx->amount, 2) }}</td>
                        <td class="px-6 py-3.5">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $tx->statusBadgeClass() }}">
                                {{ ucfirst($tx->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400 text-xs">
                            {{ $tx->created_at->format('d M Y, H:i') }}
                        </td>
                        <td class="px-6 py-3.5 font-mono text-xs text-slate-400 dark:text-slate-500">{{ $tx->reference }}</td>
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

</div>

{{-- ── Result Modal ───────────────────────────────────────────────────────── --}}
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
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    const PURCHASE = @json(route('services.airtime.purchase'));
    const BALANCE  = {{ (float) ($user->wallet?->balance ?? 0) }};

    // Network data from server
    @php
        $networkLabels = $networks->pluck('name', 'network_key')->toArray();
        $borderMap = ['mtn'=>'border-yellow-400','airtel'=>'border-red-400','glo'=>'border-green-500','etisalat'=>'border-teal-600'];
        $networkBorders = [];
        foreach ($networks as $_n) {
            $networkBorders[$_n->network_key] = $borderMap[$_n->network_key] ?? 'border-indigo-400';
        }
    @endphp
    const NETWORK_LABELS = @json($networkLabels);
    const NETWORK_BORDER = @json($networkBorders);
    const DISCOUNTS = @json($discounts); // { mtn: 0, airtel: 2, ... }

    let selectedNetwork = null;
    let currentBalance  = BALANCE;

    // ── Network selection ────────────────────────────────────────────────────
    function selectNetwork(network, btn) {
        selectedNetwork = network;

        document.querySelectorAll('.network-btn').forEach(b => {
            b.classList.remove('border-yellow-400', 'border-red-400', 'border-green-500', 'border-teal-600', 'border-indigo-400');
            b.querySelector('.network-check').classList.add('hidden');
            b.querySelector('.network-check').classList.remove('flex');
        });

        btn.classList.add(NETWORK_BORDER[network] || 'border-indigo-400');
        btn.querySelector('.network-check').classList.remove('hidden');
        btn.querySelector('.network-check').classList.add('flex');

        document.getElementById('network-error').classList.add('hidden');
        updateSummary();
    }

    // ── Quick amount select ──────────────────────────────────────────────────
    function setAmount(val, btn) {
        document.getElementById('amount-input').value = val;
        document.querySelectorAll('.quick-amount-btn').forEach(b => {
            b.classList.remove('border-vtu-primary', 'text-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-500/10', 'dark:text-indigo-400');
        });
        btn.classList.add('border-vtu-primary', 'text-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-500/10');
        document.getElementById('amount-error').classList.add('hidden');
        updateSummary();
    }

    // Clear quick-select highlight when custom amount typed
    document.getElementById('amount-input').addEventListener('input', function () {
        document.querySelectorAll('.quick-amount-btn').forEach(b => {
            b.classList.remove('border-vtu-primary', 'text-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-500/10', 'dark:text-indigo-400');
        });
        document.getElementById('amount-error').classList.add('hidden');
        updateSummary();
    });

    document.getElementById('phone-input').addEventListener('input', function () {
        document.getElementById('phone-error').classList.add('hidden');
        updateSummary();
    });

    // ── Summary update ────────────────────────────────────────────────────────
    function calcFinal(amount, network) {
        const disc = DISCOUNTS[network] || 0;
        return Math.round(amount * (100 - disc) / 100 * 100) / 100;
    }

    function updateSummary() {
        const phone  = document.getElementById('phone-input').value.trim();
        const amount = parseFloat(document.getElementById('amount-input').value) || 0;
        const row    = document.getElementById('summary-row');

        if (selectedNetwork && phone.length >= 11 && amount >= 50) {
            const disc        = DISCOUNTS[selectedNetwork] || 0;
            const finalAmount = calcFinal(amount, selectedNetwork);

            document.getElementById('summary-network').textContent = (NETWORK_LABELS[selectedNetwork] || selectedNetwork) + ' ';
            document.getElementById('summary-amount').textContent  = amount.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            document.getElementById('summary-phone').textContent   = phone;
            document.getElementById('summary-pay').textContent     = '₦' + finalAmount.toLocaleString('en-NG', { minimumFractionDigits: 2 });

            const discRow = document.getElementById('summary-discount-row');
            if (disc > 0) {
                document.getElementById('summary-discount').textContent = disc + '% → save ₦' + (amount - finalAmount).toLocaleString('en-NG', { minimumFractionDigits: 2 });
                discRow.classList.remove('hidden');
                discRow.classList.add('flex');
            } else {
                discRow.classList.add('hidden');
                discRow.classList.remove('flex');
            }

            const after = currentBalance - finalAmount;
            document.getElementById('summary-balance').textContent =
                '₦' + after.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            document.getElementById('summary-balance').className =
                'font-semibold ' + (after < 0 ? 'text-red-500' : 'text-slate-900 dark:text-white');

            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    }

    // ── Submit ────────────────────────────────────────────────────────────────
    function submitPurchase() {
        const phone  = document.getElementById('phone-input').value.trim();
        const amount = parseFloat(document.getElementById('amount-input').value);

        // Client-side validation
        let valid = true;

        if (!selectedNetwork) {
            document.getElementById('network-error').classList.remove('hidden');
            valid = false;
        }

        if (!phone || !/^(0|\+234)[789][01]\d{8}$/.test(phone)) {
            const el = document.getElementById('phone-error');
            el.textContent = 'Enter a valid Nigerian phone number (e.g. 08012345678).';
            el.classList.remove('hidden');
            valid = false;
        }

        if (!amount || amount < 50 || amount > 50000) {
            const el = document.getElementById('amount-error');
            el.textContent = 'Amount must be between ₦50 and ₦50,000.';
            el.classList.remove('hidden');
            valid = false;
        }

        const finalAmount = selectedNetwork ? calcFinal(amount, selectedNetwork) : amount;
        if (finalAmount > currentBalance) {
            const el = document.getElementById('amount-error');
            el.textContent = 'Insufficient wallet balance.';
            el.classList.remove('hidden');
            valid = false;
        }

        if (!valid) return;

        // Ask for PIN before proceeding
        requirePinConfirmation(function (pin) {
            doAirtimePurchase(phone, amount, pin);
        });
    }

    async function doAirtimePurchase(phone, amount, pin) {
        setBtnLoading(true);

        try {
            const res  = await fetch(PURCHASE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ network: selectedNetwork, phone, amount, transaction_pin: pin }),
            });

            const data = await res.json();

            if (data.success) {
                // Update displayed balance
                currentBalance = parseFloat(data.balance.replace(/[₦,]/g, ''));
                document.getElementById('wallet-balance-display').textContent = data.balance;
                document.querySelectorAll('.balance-display').forEach(el => {
                    el.textContent = data.balance;
                    el.dataset.amount = data.balance;
                });

                showResultModal(true, 'Airtime Sent!', data.message, data.reference);

                // Reset form
                document.getElementById('phone-input').value  = '';
                document.getElementById('amount-input').value = '';
                selectedNetwork = null;
                document.querySelectorAll('.network-btn').forEach(b => {
                    b.classList.remove('border-yellow-400', 'border-red-400', 'border-green-500', 'border-teal-600');
                    b.querySelector('.network-check').classList.add('hidden');
                    b.querySelector('.network-check').classList.remove('flex');
                });
                document.querySelectorAll('.quick-amount-btn').forEach(b => {
                    b.classList.remove('border-vtu-primary', 'text-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-500/10');
                });
                document.getElementById('summary-row').classList.add('hidden');

            } else {
                const msg = data.refunded
                    ? (data.message)
                    : (data.message || 'Purchase failed. Please try again.');
                showResultModal(false, data.refunded ? 'Purchase Failed' : 'Error', msg, data.reference ?? null);
            }

        } catch (err) {
            showResultModal(false, 'Network Error', 'Could not reach the server. Please check your connection.', null);
        }

        setBtnLoading(false);
    }

    function setBtnLoading(loading) {
        const btn   = document.getElementById('purchase-btn');
        const label = document.getElementById('purchase-btn-label');
        const spin  = document.getElementById('purchase-spinner');
        btn.disabled = loading;
        label.textContent = loading ? 'Processing…' : 'Purchase Airtime';
        spin.classList.toggle('hidden', !loading);
    }

    // ── Result Modal ─────────────────────────────────────────────────────────
    function showResultModal(success, title, message, reference) {
        const icon = document.getElementById('result-icon');
        icon.className = 'mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center ' +
            (success ? 'bg-emerald-100 dark:bg-emerald-500/10' : 'bg-red-100 dark:bg-red-500/10');
        icon.innerHTML = success
            ? `<svg class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`
            : `<svg class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;

        document.getElementById('result-title').textContent   = title;
        document.getElementById('result-message').textContent = message;
        document.getElementById('result-reference').textContent = reference ? 'Ref: ' + reference : '';
        document.getElementById('result-modal').classList.remove('hidden');
    }

    function closeResultModal() {
        document.getElementById('result-modal').classList.add('hidden');
        if (document.getElementById('result-title').textContent === 'Airtime Sent!') {
            window.location.reload(); // refresh to update history
        }
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeResultModal(); });
</script>
@endsection

@extends('layouts.dashboard')

@section('title', 'Fund Betting Wallet')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Fund Betting Wallet</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Fund your sports betting account instantly. Validate your account details before proceeding.
        </p>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Purchase Form (3 cols) ══════════════════════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Betting Details</h2>
                </div>

                <div class="p-6 space-y-5">

                    {{-- Platform Selector --}}
                    <div>
                        <label for="platform" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Betting Platform <span class="text-red-500">*</span>
                        </label>
                        <select id="platform" name="platform" onchange="resetValidation()"
                                class="w-full px-3 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition">
                            <option value="">-- Choose Platform --</option>
                            @foreach($platforms as $p)
                                <option value="{{ $p->slug }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <p id="platform-error" class="mt-1.5 text-xs text-red-500 hidden">Please select a platform.</p>
                    </div>

                    {{-- Customer ID --}}
                    <div>
                        <label for="customer_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Customer ID / User ID <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input id="customer_id" type="text" name="customer_id" oninput="resetValidation()" placeholder="e.g. 1234567"
                                   class="flex-1 px-3.5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"/>
                            
                            <button type="button" id="validate-btn" onclick="validateCustomer()"
                                    class="px-5 py-3 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 text-indigo-650 dark:text-indigo-400 font-semibold rounded-xl text-sm transition flex items-center justify-center gap-1.5">
                                <span id="validate-btn-label">Validate</span>
                                <svg id="validate-spinner" class="hidden h-4 w-4 animate-spin text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </div>
                        <p id="customer-id-error" class="mt-1.5 text-xs text-red-500 hidden"></p>
                    </div>

                    {{-- Customer Name (Validated) --}}
                    <div id="validated-box" class="hidden p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/25 rounded-xl">
                        <label class="block text-[10px] uppercase font-bold text-emerald-650 dark:text-emerald-400 tracking-wider">Account Owner Name</label>
                        <p id="validated-name" class="mt-0.5 text-sm font-bold text-emerald-800 dark:text-emerald-300"></p>
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label for="amount-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Amount (₦) <span class="text-red-500">*</span>
                        </label>
                        {{-- Quick presets --}}
                        <div class="grid grid-cols-5 gap-2 mb-2.5">
                            @foreach([200, 500, 1000, 2000, 5000] as $preset)
                            <button type="button" onclick="setPresetAmount({{ $preset }}, this)"
                                    class="quick-amount-btn px-2 py-2 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-vtu-primary hover:text-vtu-primary dark:hover:border-vtu-primary dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-all duration-150">
                                ₦{{ number_format($preset) }}
                            </button>
                            @endforeach
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="text-slate-400 font-medium text-sm">₦</span>
                            </div>
                            <input id="amount-input" type="number" inputmode="numeric" min="{{ $minAmount }}"
                                   placeholder="Enter custom amount" oninput="updateSummary()"
                                   class="w-full pl-8 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"/>
                        </div>
                        <p id="amount-error" class="mt-1.5 text-xs text-red-500 hidden"></p>
                    </div>

                    {{-- Summary row --}}
                    <div id="summary-row" class="hidden rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 p-4 space-y-1.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Funding Amount</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                ₦<span id="summary-amount">0</span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Convenience Charge</span>
                            <span class="font-semibold text-slate-900 dark:text-white">
                                ₦{{ number_format($charge, 2) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-indigo-200 dark:border-indigo-500/30 pt-1.5">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">You pay</span>
                            <span id="summary-pay" class="font-bold text-vtu-primary"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Account Owner</span>
                            <span id="summary-owner" class="font-semibold text-slate-900 dark:text-white"></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Balance after</span>
                            <span id="summary-balance" class="font-semibold text-slate-900 dark:text-white"></span>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button id="purchase-btn" onclick="submitPurchase()"
                            class="w-full py-3.5 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-colors flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20">
                        <span id="purchase-btn-label">Fund Betting Wallet</span>
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
                    @foreach([
                        'Select your sports betting platform',
                        'Enter your customer/user account ID',
                        'Click Validate to confirm your account owner name',
                        'Enter amount and click Fund Wallet to complete instantly'
                    ] as $i => $step)
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
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Betting Logs</h2>
            <span class="text-xs text-slate-400">{{ $history->total() }} total</span>
        </div>

        @if($history->isEmpty())
        <div class="py-12 text-center">
            <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                </svg>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">No betting transactions yet</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700">
                        <th class="px-6 py-3 text-left">Platform</th>
                        <th class="px-6 py-3 text-left">Account ID</th>
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
                            <span class="font-bold text-slate-700 dark:text-slate-300 uppercase">{{ $tx->provider }}</span>
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
    const VALIDATE = @json(route('services.betting.validate'));
    const PURCHASE = @json(route('services.betting.purchase'));
    const BALANCE  = {{ (float) ($user->wallet?->balance ?? 0) }};
    const CHARGE   = {{ (float) $charge }};
    const MIN_AMT  = {{ (float) $minAmount }};

    let isValidated  = false;
    let customerName = '';
    let currentBalance = BALANCE;

    function resetValidation() {
        isValidated = false;
        customerName = '';
        document.getElementById('validated-box').classList.add('hidden');
        document.getElementById('customer-id-error').classList.add('hidden');
        updateSummary();
    }

    // ── Customer ID Verification ──────────────────────────────────────────────
    async function validateCustomer() {
        const platform   = document.getElementById('platform').value;
        const customerId = document.getElementById('customer_id').value.trim();

        if (!platform) {
            document.getElementById('platform-error').classList.remove('hidden');
            return;
        }
        document.getElementById('platform-error').classList.add('hidden');

        if (!customerId) {
            const errEl = document.getElementById('customer-id-error');
            errEl.textContent = 'Please enter your Customer ID.';
            errEl.classList.remove('hidden');
            return;
        }
        document.getElementById('customer-id-error').classList.add('hidden');

        setValidateLoading(true);

        try {
            const res = await fetch(VALIDATE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
                body: JSON.stringify({ platform, customer_id: customerId })
            });

            const data = await res.json();
            setValidateLoading(false);

            if (res.ok && data.customer_name) {
                customerName = data.customer_name;
                isValidated  = true;
                
                document.getElementById('validated-name').textContent = customerName;
                document.getElementById('validated-box').classList.remove('hidden');
                updateSummary();
            } else {
                const errMsg = data.error || 'Validation failed. Please verify details.';
                const errEl = document.getElementById('customer-id-error');
                errEl.textContent = errMsg;
                errEl.classList.remove('hidden');
            }
        } catch (err) {
            setValidateLoading(false);
            const errEl = document.getElementById('customer-id-error');
            errEl.textContent = 'Network error occurred. Please try again.';
            errEl.classList.remove('hidden');
        }
    }

    function setValidateLoading(loading) {
        const btn   = document.getElementById('validate-btn');
        const label = document.getElementById('validate-btn-label');
        const spin  = document.getElementById('validate-spinner');
        btn.disabled = loading;
        label.textContent = loading ? 'Checking…' : 'Validate';
        spin.classList.toggle('hidden', !loading);
    }

    // ── Presets amount select ──────────────────────────────────────────────────
    function setPresetAmount(val, btn) {
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

    // ── Summary Row calculation ───────────────────────────────────────────────
    function updateSummary() {
        const amount = parseFloat(document.getElementById('amount-input').value) || 0;
        const row    = document.getElementById('summary-row');

        if (isValidated && amount >= MIN_AMT) {
            const total = amount + CHARGE;
            document.getElementById('summary-amount').textContent = amount.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            document.getElementById('summary-pay').textContent    = '₦' + total.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            document.getElementById('summary-owner').textContent  = customerName;

            const after = currentBalance - total;
            document.getElementById('summary-balance').textContent = '₦' + after.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            document.getElementById('summary-balance').className =
                'font-semibold ' + (after < 0 ? 'text-red-500' : 'text-slate-900 dark:text-white');

            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    }

    // ── Purchase Submission ────────────────────────────────────────────────────
    function submitPurchase() {
        const platform   = document.getElementById('platform').value;
        const customerId = document.getElementById('customer_id').value.trim();
        const amount     = parseFloat(document.getElementById('amount-input').value);

        let valid = true;

        if (!platform) {
            document.getElementById('platform-error').classList.remove('hidden');
            valid = false;
        }

        if (!customerId || !isValidated) {
            const errEl = document.getElementById('customer-id-error');
            errEl.textContent = 'Please validate the customer account ID before proceeding.';
            errEl.classList.remove('hidden');
            valid = false;
        }

        if (!amount || amount < MIN_AMT) {
            const errEl = document.getElementById('amount-error');
            errEl.textContent = 'Amount must be at least ₦' + MIN_AMT.toLocaleString() + '.';
            errEl.classList.remove('hidden');
            valid = false;
        }

        const totalCost = amount + CHARGE;
        if (totalCost > currentBalance) {
            const errEl = document.getElementById('amount-error');
            errEl.textContent = 'Insufficient wallet balance.';
            errEl.classList.remove('hidden');
            valid = false;
        }

        if (!valid) return;

        // Ask for transaction PIN confirmation using the global modal helper
        requirePinConfirmation(function (pin) {
            doBettingPurchase(platform, customerId, customerName, amount, pin);
        });
    }

    async function doBettingPurchase(platform, customerId, name, amount, pin) {
        setPurchaseLoading(true);

        try {
            const res = await fetch(PURCHASE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
                body: JSON.stringify({
                    platform: platform,
                    customer_id: customerId,
                    customer_name: name,
                    amount: amount,
                    transaction_pin: pin
                })
            });

            const data = await res.json();

            if (data.success) {
                // Update balance displays
                currentBalance = parseFloat(data.balance.replace(/[₦,]/g, ''));
                document.getElementById('wallet-balance-display').textContent = data.balance;
                document.querySelectorAll('.balance-display').forEach(el => {
                    el.textContent = data.balance;
                    el.dataset.amount = data.balance;
                });

                showResultModal(true, 'Transaction Successful', data.message, data.reference);

                // Clear input form
                document.getElementById('platform').value = '';
                document.getElementById('customer_id').value = '';
                document.getElementById('amount-input').value = '';
                resetValidation();
            } else {
                showResultModal(false, 'Transaction Failed', data.message || 'Betting wallet funding failed. Please try again.', data.reference ?? null);
            }
        } catch (err) {
            showResultModal(false, 'Network Error', 'Could not communicate with the server. Please check your internet connection.', null);
        }

        setPurchaseLoading(false);
    }

    function setPurchaseLoading(loading) {
        const btn   = document.getElementById('purchase-btn');
        const label = document.getElementById('purchase-btn-label');
        const spin  = document.getElementById('purchase-spinner');
        btn.disabled = loading;
        label.textContent = loading ? 'Processing…' : 'Fund Betting Wallet';
        spin.classList.toggle('hidden', !loading);
    }

    // ── Result Modal controls ─────────────────────────────────────────────────
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
        if (document.getElementById('result-title').textContent === 'Transaction Successful') {
            window.location.reload(); // refresh to reload page logs
        }
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeResultModal(); });
</script>
@endsection

@extends('layouts.dashboard')

@section('title', 'Fund Wallet – PayPulse')

@section('content')

<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold font-outfit text-slate-900 dark:text-white">Fund Wallet</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
        Top up your wallet via
        {{ $activeGateway === 'flutterwave' ? 'Flutterwave' : 'Paystack' }}
        - card, bank transfer, or USSD.
    </p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

    {{-- ── Left: Funding Form ──────────────────────────────────────────────── --}}
    <div class="xl:col-span-3">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

            {{-- Card header --}}
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Card / Bank Funding</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Min: ₦500 &nbsp;·&nbsp; Max: ₦1,000,000</p>
            </div>

            <div class="p-6 space-y-5">

                {{-- Amount --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Amount (₦)</label>
                    <input id="fund-amount"
                           type="number"
                           min="500"
                           max="1000000"
                           step="1"
                           placeholder="Enter amount e.g. 5000"
                           oninput="computeCharge()"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 focus:border-vtu-primary transition-all">
                    <p id="amount-error" class="mt-1.5 text-xs text-rose-500 hidden"></p>
                </div>

                {{-- Transaction Charge (read-only) --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Transaction Charge</label>
                    <div class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 text-sm select-none">
                        <span id="charge-display">-</span>
                        @if($chargeType === 'percentage' && $chargeValue > 0)
                        <span class="ml-2 text-xs text-slate-400">({{ $chargeValue }}%)</span>
                        @endif
                    </div>
                </div>

                {{-- Total --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Total Payable</label>
                    <div class="w-full px-4 py-3 rounded-xl border border-indigo-200 dark:border-indigo-700/40 bg-indigo-50 dark:bg-indigo-500/10 text-vtu-primary dark:text-indigo-400 text-sm font-semibold select-none">
                        <span id="total-display">-</span>
                    </div>
                </div>

                {{-- Submit button --}}
                <button id="fund-btn"
                        onclick="initiateFunding()"
                        class="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-vtu-primary to-indigo-700 text-white font-semibold text-sm hover:from-indigo-700 hover:to-vtu-primary transition-all duration-200 shadow-lg shadow-indigo-500/20 disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg id="fund-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Continue to Funding
                </button>

                {{-- Gateway badge --}}
                <div class="flex flex-col items-center gap-3 pt-2">
                    @if($activeGateway === 'flutterwave')
                    <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Secured by <span class="font-bold text-orange-500">Flutterwave</span></p>
                    @else
                    <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Secured by <span class="font-bold text-emerald-600">Paystack</span></p>
                    @endif

                    {{-- Generic card type icons --}}
                    <div class="flex items-center gap-3">
                        {{-- Mastercard --}}
                        <div class="h-8 w-12 rounded-md bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden" title="Mastercard">
                            <div class="flex">
                                <div class="h-5 w-5 rounded-full bg-red-500 opacity-90"></div>
                                <div class="h-5 w-5 rounded-full bg-yellow-400 opacity-90 -ml-2.5"></div>
                            </div>
                        </div>
                        {{-- Visa --}}
                        <div class="h-8 w-12 rounded-md bg-slate-800 dark:bg-slate-700 flex items-center justify-center" title="Visa">
                            <span class="text-white text-xs font-bold italic tracking-tight">VISA</span>
                        </div>
                        {{-- Verve --}}
                        <div class="h-8 w-12 rounded-md bg-orange-600 flex items-center justify-center" title="Verve">
                            <span class="text-white text-[10px] font-bold tracking-tight">VERVE</span>
                        </div>
                        {{-- Bank Transfer --}}
                        <div class="h-8 w-12 rounded-md bg-slate-100 dark:bg-slate-800 flex items-center justify-center" title="Bank Transfer">
                            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Right: Previous Transactions ────────────────────────────────────── --}}
    <div class="xl:col-span-2">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Previous Transactions</h2>
                <span class="text-xs text-slate-400">{{ $previousTx->total() }} total</span>
            </div>

            @if ($previousTx->isEmpty())
                <div class="flex flex-col items-center justify-center py-14 px-6 text-center">
                    <div class="h-12 w-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">No gateway transactions yet</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($previousTx as $tx)
                    <li class="px-5 py-3.5 flex items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <a href="#"
                               class="text-xs font-mono font-semibold text-vtu-primary truncate block hover:underline"
                               title="{{ $tx->reference }}">
                                {{ $tx->reference }}
                            </a>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $tx->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">₦{{ number_format((float) $tx->amount, 0) }}</p>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded
                                {{ $tx->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400'
                                    : ($tx->status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400') }}">
                                {{ ucfirst($tx->status) }}
                            </span>
                        </div>
                    </li>
                    @endforeach
                </ul>

                @if ($previousTx->hasPages())
                <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $previousTx->links('pagination::simple-tailwind') }}
                </div>
                @endif
            @endif
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
    // ── Config (from PHP) ────────────────────────────────────────────────────
    const GATEWAY       = @json($activeGateway);
    const PUBLIC_KEY    = @json($publicKey ?? '');
    const CHARGE_TYPE   = @json($chargeType);
    const CHARGE_VALUE  = {{ $chargeValue }};
    const USER_EMAIL    = @json(auth()->user()->email);
    const USER_NAME     = @json(auth()->user()->name);
    const CSRF          = document.querySelector('meta[name="csrf-token"]').content;

    // ── Charge calculation ───────────────────────────────────────────────────
    function computeCharge() {
        const raw = parseFloat(document.getElementById('fund-amount').value);
        const errEl = document.getElementById('amount-error');

        if (isNaN(raw) || raw <= 0) {
            document.getElementById('charge-display').textContent = '-';
            document.getElementById('total-display').textContent  = '-';
            errEl.classList.add('hidden');
            return;
        }
        if (raw < 500) {
            errEl.textContent = 'Minimum funding amount is ₦500.';
            errEl.classList.remove('hidden');
        } else if (raw > 1000000) {
            errEl.textContent = 'Maximum funding amount is ₦1,000,000.';
            errEl.classList.remove('hidden');
        } else {
            errEl.classList.add('hidden');
        }

        const charge = CHARGE_TYPE === 'percentage'
            ? Math.round(raw * CHARGE_VALUE / 100 * 100) / 100
            : CHARGE_VALUE;
        const total = raw + charge;

        document.getElementById('charge-display').textContent = '₦' + charge.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total-display').textContent  = '₦' + total.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // ── Initiate ─────────────────────────────────────────────────────────────
    async function initiateFunding() {
        const amount = parseFloat(document.getElementById('fund-amount').value);

        if (isNaN(amount) || amount < 500 || amount > 1000000) {
            document.getElementById('amount-error').textContent = 'Please enter a valid amount between ₦500 and ₦1,000,000.';
            document.getElementById('amount-error').classList.remove('hidden');
            return;
        }

        setBtnLoading(true);

        try {
            const res = await fetch('/wallet/fund/gateway/initiate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ amount })
            });
            const data = await res.json();

            if (!res.ok) {
                showError(data.message || 'Could not initiate payment.');
                setBtnLoading(false);
                return;
            }

            openGateway(data);

        } catch (e) {
            showError('Network error. Please try again.');
            setBtnLoading(false);
        }
    }

    // ── Open Paystack or Flutterwave popup ───────────────────────────────────
    function openGateway(data) {
        if (GATEWAY === 'flutterwave') {
            FlutterwaveCheckout({
                public_key:      PUBLIC_KEY,
                tx_ref:          data.reference,
                amount:          data.total,
                currency:        'NGN',
                payment_options: 'card,banktransfer,ussd',
                // Fallback for bank-transfer redirect flows - server verifies and redirects to dashboard
                redirect_url:    '{{ route("wallet.fund.gateway.flutterwave.callback") }}',
                customer: { email: USER_EMAIL, name: USER_NAME },
                customizations: { title: 'PayPulse Wallet', description: 'Wallet top-up' },
                callback: function(response) {
                    // Flutterwave uses 'transaction_id' in docs but 'id' in some payment flows
                    const txId = response.transaction_id || response.id;
                    // Don't close the modal before the AJAX completes - redirect will handle it
                    verifyPayment('flutterwave', data.reference, txId);
                },
                onclose: function() { setBtnLoading(false); }
            });
        } else {
            const handler = PaystackPop.setup({
                key:      PUBLIC_KEY,
                email:    USER_EMAIL,
                amount:   Math.round(data.total * 100), // kobo
                currency: 'NGN',
                ref:      data.reference,
                onClose:  function() { setBtnLoading(false); },
                callback: function(response) {
                    verifyPayment('paystack', response.reference, null);
                }
            });
            handler.openIframe();
        }
    }

    // ── Verify with our server ───────────────────────────────────────────────
    async function verifyPayment(gateway, reference, transactionId) {
        try {
            const body = { reference };
            if (gateway === 'flutterwave') body.transaction_id = transactionId;

            const endpoint = gateway === 'flutterwave'
                ? '/wallet/fund/gateway/verify/flutterwave'
                : '/wallet/fund/gateway/verify/paystack';

            const res  = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(body)
            });
            const data = await res.json();

            setBtnLoading(false);

            if (data.success) {
                window.location.href = data.redirect || '/dashboard';
            } else {
                showError(data.message || 'Payment verification failed.');
            }
        } catch (e) {
            setBtnLoading(false);
            showError('Could not verify payment. Please contact support.');
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function setBtnLoading(loading) {
        const btn     = document.getElementById('fund-btn');
        const spinner = document.getElementById('fund-spinner');
        btn.disabled = loading;
        spinner.classList.toggle('hidden', !loading);
    }
    function showError(msg) {
        const el = document.getElementById('amount-error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }
</script>

{{-- Load the correct gateway SDK --}}
@if(($activeGateway ?? 'paystack') === 'flutterwave')
    <script src="https://checkout.flutterwave.com/v3.js"></script>
@else
    <script src="https://js.paystack.co/v1/inline.js"></script>
@endif
@endsection

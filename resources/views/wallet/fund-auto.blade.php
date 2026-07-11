@extends('layouts.dashboard')

@section('title', 'Auto Bank Transfer')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ── Page Header ───────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Auto Bank Transfer</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Fund your wallet instantly by transferring to your personal virtual bank account.
        </p>
    </div>

    {{-- ── Info Banner ───────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 p-4 flex gap-3">
        <div class="flex-shrink-0 mt-0.5">
            <svg class="h-5 w-5 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="text-sm text-indigo-700 dark:text-indigo-300 space-y-1">
            <p class="font-semibold">How it works</p>
            <ul class="list-disc list-inside space-y-1 text-indigo-600 dark:text-indigo-400">
                <li>Generate your unique virtual bank accounts using your BVN.</li>
                <li>Transfer any amount from any bank to the account number shown.</li>
                <li>Your wallet is credited automatically - usually within seconds.</li>
                <li>Your BVN is only sent securely to our payment partners (Paystack &amp; Flutterwave) and is <strong>never</strong> stored on our servers.</li>
            </ul>
        </div>
    </div>

    {{-- ── Existing Accounts / Empty State ──────────────────────────── --}}
    @if($accounts->isNotEmpty())

        <div class="space-y-4">
            <h2 class="text-base font-semibold text-slate-700 dark:text-slate-300">Your Virtual Accounts</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($accounts as $account)
                <div class="relative rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 p-5 shadow-sm">

                    {{-- Provider badge --}}
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full px-2.5 py-1
                            {{ $account->provider === 'paystack' ? 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400' }}">
                            @if($account->provider === 'paystack')
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                            @else
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><polygon points="12,2 22,20 2,20"/></svg>
                            @endif
                            {{ $account->providerLabel() }}
                        </span>
                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ $account->bank_name }}</span>
                    </div>

                    {{-- Account Number --}}
                    <div class="mb-1">
                        <p class="text-xs text-slate-400 dark:text-slate-500 mb-0.5">Account Number</p>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-outfit font-bold tracking-widest text-slate-900 dark:text-white" id="acct-{{ $account->id }}">
                                {{ $account->account_number }}
                            </span>
                            <button onclick="copyText('{{ $account->account_number }}', this)"
                                    class="flex-shrink-0 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-vtu-primary transition-colors"
                                    title="Copy account number">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Account Name --}}
                    <div>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mb-0.5">Account Name</p>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $account->account_name }}</p>
                    </div>

                    {{-- Bank name pill --}}
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $account->bank_name }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Charges note --}}
            <p class="text-xs text-slate-400 dark:text-slate-500 text-center">
                Bank transfer charges apply depending on your sending bank. Wallet is credited for the net amount received.
            </p>
        </div>

        {{-- Option to generate more (if any configured gateway accounts are missing) --}}
        @php
            $hasPaystack = $accounts->where('provider', 'paystack')->isNotEmpty();
            $hasFlutterwave = $accounts->where('provider', 'flutterwave')->isNotEmpty();
            $hasMonnify = $accounts->where('provider', 'monnify')->isNotEmpty();
            
            $paystackEnabled = !empty(\App\Models\AppSetting::get('paystack_secret_key'));
            $flutterwaveEnabled = !empty(\App\Models\AppSetting::get('flutterwave_secret_key'));
            $monnifyEnabled = !empty(\App\Models\AppSetting::get('monnify_api_key'));
            
            $canGenerateMore = ($paystackEnabled && !$hasPaystack) ||
                               ($flutterwaveEnabled && !$hasFlutterwave) ||
                               ($monnifyEnabled && !$hasMonnify);
        @endphp

        @if($canGenerateMore)
        <div class="flex justify-center">
            <button onclick="openBvnModal()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold border-2 border-dashed border-indigo-300 dark:border-indigo-500/40 text-vtu-primary hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Generate More Accounts
            </button>
        </div>
        @endif

    @else

        {{-- ── Empty State ──────────────────────────────────────────── --}}
        <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 p-10 text-center shadow-sm">
            <div class="mx-auto mb-5 h-16 w-16 rounded-full bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                <svg class="h-8 w-8 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-2">No virtual accounts yet</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-sm mx-auto">
                Generate your dedicated virtual bank accounts. Funds transferred to these accounts will automatically credit your wallet.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button onclick="openBvnModal()"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 text-white transition-colors shadow-md shadow-indigo-500/20">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Generate Virtual Accounts
                </button>
            </div>
        </div>

    @endif

</div>

{{-- ── BVN Modal ──────────────────────────────────────────────────────── --}}
<div id="bvn-modal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden"
     role="dialog" aria-modal="true" aria-labelledby="bvn-modal-title">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeBvnModal()"></div>

    {{-- Panel --}}
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-2xl p-6">

        {{-- Close --}}
        <button onclick="closeBvnModal()"
                class="absolute top-4 right-4 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Icon --}}
        <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center mb-4">
            <svg class="h-6 w-6 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>

        <h2 id="bvn-modal-title" class="text-lg font-outfit font-bold text-slate-900 dark:text-white mb-1">
            Enter Your BVN
        </h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">
            Your BVN is required to generate static Wema, Titan, and Moniepoint/Sterling bank accounts as directed by the Central Bank of Nigeria (CBN).
            <strong class="text-slate-700 dark:text-slate-300">{{ $siteName }} will never store your BVN</strong> - it is sent directly and securely to our payment partners.
        </p>

        {{-- BVN input --}}
        <div class="mb-5">
            <label for="bvn-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Bank Verification Number (BVN)
            </label>
            <input id="bvn-input"
                   type="tel"
                   inputmode="numeric"
                   maxlength="11"
                   placeholder="Enter your 11-digit BVN"
                   autocomplete="off"
                   class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition text-lg font-mono tracking-widest"/>
            <p id="bvn-error" class="mt-1.5 text-xs text-red-500 hidden"></p>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button onclick="closeBvnModal()"
                    class="flex-1 px-4 py-3 rounded-xl text-sm font-semibold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                Close
            </button>
            <button id="bvn-submit-btn"
                    onclick="submitBvn()"
                    class="flex-1 px-4 py-3 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 text-white transition-colors flex items-center justify-center gap-2">
                <span id="bvn-btn-label">Generate Accounts</span>
                <svg id="bvn-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </button>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    #bvn-input { letter-spacing: 0.15em; }
</style>
@endsection

@section('scripts')
<script>
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    const GENERATE = @json(route('wallet.fund.auto.generate'));

    // ── Modal helpers ──────────────────────────────────────────────────────
    function openBvnModal() {
        document.getElementById('bvn-modal').classList.remove('hidden');
        document.getElementById('bvn-input').value = '';
        clearBvnError();
        setTimeout(() => document.getElementById('bvn-input').focus(), 50);
    }

    function closeBvnModal() {
        document.getElementById('bvn-modal').classList.add('hidden');
    }

    function clearBvnError() {
        const el = document.getElementById('bvn-error');
        el.textContent = '';
        el.classList.add('hidden');
    }

    function showBvnError(msg) {
        const el = document.getElementById('bvn-error');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function setBtnLoading(loading) {
        const btn    = document.getElementById('bvn-submit-btn');
        const label  = document.getElementById('bvn-btn-label');
        const spin   = document.getElementById('bvn-spinner');
        btn.disabled = loading;
        label.textContent = loading ? 'Generating…' : 'Generate Accounts';
        spin.classList.toggle('hidden', !loading);
    }

    // ── Submit BVN ────────────────────────────────────────────────────────
    async function submitBvn() {
        clearBvnError();

        const bvn = document.getElementById('bvn-input').value.trim();
        if (!/^\d{11}$/.test(bvn)) {
            showBvnError('Please enter a valid 11-digit BVN.');
            return;
        }

        setBtnLoading(true);

        try {
            const res = await fetch(GENERATE, {
                method: 'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'Accept':        'application/json',
                    'X-CSRF-TOKEN':  CSRF,
                },
                body: JSON.stringify({ bvn }),
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                showBvnError(data.message || 'Something went wrong. Please try again.');
                setBtnLoading(false);
                return;
            }

            // Success - reload the page to show generated accounts
            closeBvnModal();
            window.location.reload();

        } catch (err) {
            showBvnError('Network error. Please check your connection and try again.');
            setBtnLoading(false);
        }
    }

    // Close modal on Escape key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeBvnModal();
    });

    // Only allow digits in BVN field
    document.getElementById('bvn-input').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
        clearBvnError();
    });

    // ── Copy account number ───────────────────────────────────────────────
    function copyText(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = `<svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>`;
            setTimeout(() => { btn.innerHTML = orig; }, 1800);
        }).catch(() => {
            // Fallback
            const el = document.createElement('textarea');
            el.value = text;
            el.style.position = 'fixed';
            el.style.opacity  = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        });
    }
</script>
@endsection

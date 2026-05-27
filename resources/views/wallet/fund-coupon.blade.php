@extends('layouts.dashboard')

@section('title', 'Coupon Funding')

@section('content')

<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold font-outfit text-slate-900 dark:text-white">Coupon / Voucher</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
        Redeem a coupon code to instantly credit your wallet.
    </p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

    {{-- ── Left: Coupon Form ───────────────────────────────────────────────── --}}
    <div class="xl:col-span-3">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Enter Coupon Code</h2>
            </div>

            <div class="p-6 space-y-5">

                {{-- Coupon icon --}}
                <div class="flex justify-center py-4">
                    <div class="relative">
                        <div class="h-24 w-24 rounded-3xl bg-gradient-to-br from-vtu-primary to-vtu-secondary flex items-center justify-center shadow-xl shadow-indigo-500/20">
                            <svg class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                        </div>
                        <div class="absolute -top-1 -right-1 h-6 w-6 rounded-full bg-vtu-accent flex items-center justify-center shadow-md">
                            <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Code input --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Coupon Code</label>
                    <input id="coupon-input"
                           type="text"
                           placeholder="e.g. WELCOME500 or BONUS2026"
                           maxlength="50"
                           oninput="this.value = this.value.toUpperCase()"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-mono tracking-widest uppercase focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 focus:border-vtu-primary transition-all">
                    <p id="coupon-error" class="mt-1.5 text-xs text-rose-500 hidden"></p>
                </div>

                <button id="redeem-btn"
                        onclick="redeemCoupon()"
                        class="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-vtu-primary to-indigo-700 text-white font-semibold text-sm hover:from-indigo-700 hover:to-vtu-primary transition-all duration-200 shadow-lg shadow-indigo-500/20 disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg id="redeem-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Redeem Coupon
                </button>

                {{-- How it works --}}
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800 p-4 space-y-2.5">
                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">How it works</p>
                    <div class="flex items-start gap-2.5">
                        <div class="h-5 w-5 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-[10px] font-bold text-vtu-primary">1</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Enter the coupon code you received above.</p>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="h-5 w-5 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-[10px] font-bold text-vtu-primary">2</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Click "Redeem Coupon" - we'll validate it instantly.</p>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="h-5 w-5 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <span class="text-[10px] font-bold text-vtu-primary">3</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Your wallet is credited immediately upon success. Each code can only be redeemed once per user.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Right: Redemption History ───────────────────────────────────────── --}}
    <div class="xl:col-span-2">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Redemption History</h2>
                <span class="text-xs text-slate-400">{{ $previousRedemptions->total() }} total</span>
            </div>

            @if ($previousRedemptions->isEmpty())
                <div class="flex flex-col items-center justify-center py-14 px-6 text-center">
                    <div class="h-12 w-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">No coupons redeemed yet</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($previousRedemptions as $redemption)
                    <li class="px-5 py-3.5 flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $redemption->coupon->code }}</p>
                            <p class="text-xs text-slate-400">{{ $redemption->created_at->format('d M Y, h:ia') }}</p>
                        </div>
                        <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 flex-shrink-0">
                            +₦{{ number_format((float) $redemption->coupon->amount, 2) }}
                        </p>
                    </li>
                    @endforeach
                </ul>

                @if ($previousRedemptions->hasPages())
                <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $previousRedemptions->links('pagination::simple-tailwind') }}
                </div>
                @endif
            @endif
        </div>
    </div>

</div>

{{-- ── Success modal ──────────────────────────────────────────────────────── --}}
<div id="coupon-success-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm p-8 text-center">
        <div class="h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center mx-auto mb-4">
            <svg class="h-8 w-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold font-outfit text-slate-900 dark:text-white mb-2">Coupon Redeemed!</h3>
        <p id="coupon-success-msg" class="text-sm text-slate-500 dark:text-slate-400 mb-1"></p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-6">New balance: <span id="coupon-balance" class="font-semibold text-vtu-primary"></span></p>
        <button onclick="location.reload()"
                class="w-full px-4 py-2.5 rounded-xl bg-vtu-primary text-white text-sm font-semibold hover:bg-indigo-700 transition-colors">
            Done
        </button>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    async function redeemCoupon() {
        const code = document.getElementById('coupon-input').value.trim();
        const errEl = document.getElementById('coupon-error');

        if (!code) {
            errEl.textContent = 'Please enter a coupon code.';
            errEl.classList.remove('hidden');
            return;
        }

        errEl.classList.add('hidden');
        setBtnLoading(true);

        try {
            const res  = await fetch('/wallet/fund/coupon/redeem', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body:    JSON.stringify({ code })
            });
            const data = await res.json();

            setBtnLoading(false);

            if (data.success) {
                document.getElementById('coupon-success-msg').textContent     = data.message;
                document.getElementById('coupon-balance').textContent         = data.balance;
                document.getElementById('coupon-success-modal').classList.remove('hidden');
            } else {
                errEl.textContent = data.message || 'Invalid coupon code.';
                errEl.classList.remove('hidden');
            }
        } catch (e) {
            setBtnLoading(false);
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.remove('hidden');
        }
    }

    function setBtnLoading(loading) {
        document.getElementById('redeem-btn').disabled = loading;
        document.getElementById('redeem-spinner').classList.toggle('hidden', !loading);
    }

    // Allow Enter key to submit
    document.getElementById('coupon-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') redeemCoupon();
    });
</script>
@endsection

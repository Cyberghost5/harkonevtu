@extends('layouts.dashboard')

@section('title', 'Referral Program')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Referral Program</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Invite friends and earn ₦{{ number_format((float)($settings['referral_commission'] ?? 0), 2) }}
            for every referral that spends up to
            ₦{{ number_format((float)($settings['referral_min_total_spent'] ?? 0), 2) }}.
        </p>
    </div>

    @if (session('success'))
        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-sm text-emerald-700 dark:text-emerald-400">
            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-3 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-sm text-red-700 dark:text-red-400">
            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Stats Row ────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Referral Balance --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Referral Balance</span>
            </div>
            <p class="text-2xl font-bold font-outfit text-slate-900 dark:text-white">
                ₦{{ number_format($referralBalance, 2) }}
            </p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                Min. withdrawal: ₦{{ number_format($minWithdrawal, 2) }}
            </p>
        </div>

        {{-- Total Referrals --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 rounded-xl bg-cyan-50 dark:bg-cyan-500/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Referrals</span>
            </div>
            <p class="text-2xl font-bold font-outfit text-slate-900 dark:text-white">{{ $referrals->count() }}</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">People who used your link</p>
        </div>

        {{-- Commission Per Referral --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Earn Per Referral</span>
            </div>
            <p class="text-2xl font-bold font-outfit text-slate-900 dark:text-white">
                ₦{{ number_format((float)($settings['referral_commission'] ?? 0), 2) }}
            </p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                When they spend ₦{{ number_format((float)($settings['referral_min_total_spent'] ?? 0), 2) }}
            </p>
        </div>

    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Link + Withdraw ══════════════════════════════════════════ --}}
        <div class="lg:col-span-3 space-y-5">

            {{-- Referral Link --}}
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Your Referral Link</h2>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Share this link. When someone registers and spends up to the qualifying amount, you earn ₦{{ number_format((float)($settings['referral_commission'] ?? 0), 2) }} automatically.
                    </p>
                    <div class="flex items-center gap-2">
                        <input id="refLink" type="text" readonly value="{{ $referralLink }}"
                               class="flex-1 px-3 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-300 font-mono focus:outline-none cursor-pointer truncate">
                        <button onclick="copyRef()" id="copyBtn"
                                class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90"
                                style="background: {{ $themeColor }}">
                            <svg id="copyIcon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span id="copyText">Copy</span>
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Your referral code: <span class="font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ $user->referral_code }}</span>
                    </p>
                </div>
            </div>

            {{-- Withdraw --}}
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Withdraw to Wallet</h2>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Transfer your entire referral balance to your main wallet instantly.
                        Minimum withdrawal is <strong class="text-slate-700 dark:text-slate-300">₦{{ number_format($minWithdrawal, 2) }}</strong>.
                    </p>

                    @if ($referralBalance > 0)
                        <div class="flex items-center justify-between p-3.5 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/30">
                            <div>
                                <p class="text-xs text-indigo-500 dark:text-indigo-400 font-medium">Available to withdraw</p>
                                <p class="text-lg font-bold text-indigo-600 dark:text-indigo-300">₦{{ number_format($referralBalance, 2) }}</p>
                            </div>
                            @if ($canWithdraw)
                                <form method="POST" action="{{ route('referral.withdraw') }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90"
                                            style="background: {{ $themeColor }}"
                                            onclick="return confirm('Transfer ₦{{ number_format($referralBalance, 2) }} to your main wallet?')">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                        </svg>
                                        Withdraw
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    Need ₦{{ number_format($minWithdrawal - $referralBalance, 2) }} more
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-6 text-slate-400 dark:text-slate-500">
                            <svg class="h-10 w-10 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm">No referral earnings yet.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ══ RIGHT: How It Works ══════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">How It Works</h2>
                </div>
                <div class="p-6 space-y-5">
                    @foreach ([
                        ['1', 'Share your link', 'Send your referral link to friends and family.'],
                        ['2', 'They register', 'They sign up using your referral link.'],
                        ['3', 'They spend ₦'.number_format((float)($settings['referral_min_total_spent'] ?? 0), 2), 'Once they reach the minimum spend, your commission is credited automatically.'],
                        ['4', 'Withdraw', 'Transfer your earnings to your main wallet anytime.'],
                    ] as [$step, $title, $desc])
                    <div class="flex items-start gap-3">
                        <div class="h-7 w-7 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-white"
                             style="background: {{ $themeColor }}">{{ $step }}</div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- ── Referred Users Table ─────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Your Referrals</h2>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                {{ $referrals->count() }} total
            </span>
        </div>

        @if ($referrals->isEmpty())
            <div class="flex flex-col items-center justify-center py-14 px-6 text-center">
                <div class="h-14 w-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">No referrals yet</p>
                <p class="text-xs text-slate-400 dark:text-slate-500">Share your link to start earning commissions.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">User</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Joined</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Spent</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Commission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($referrals as $ref)
                        @php
                            $spent   = (float) ($ref->wallet?->total_spent ?? 0);
                            $minReq  = (float) ($settings['referral_min_total_spent'] ?? 0);
                            $pct     = $minReq > 0 ? min(100, ($spent / $minReq) * 100) : 100;
                            $earned  = $ref->referral_commission_paid;
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                         style="background: {{ $themeColor }}">
                                        {{ strtoupper(substr($ref->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800 dark:text-slate-200">{{ $ref->name }}</p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ $ref->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400 text-xs whitespace-nowrap">
                                {{ $ref->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-200">₦{{ number_format($spent, 2) }}</p>
                                    @if (!$earned && $minReq > 0)
                                        <div class="mt-1 h-1.5 w-24 ml-auto rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                            <div class="h-full rounded-full transition-all"
                                                 style="width: {{ $pct }}%; background: {{ $themeColor }}"></div>
                                        </div>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ round($pct) }}% of target</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($earned)
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Paid
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
                                        Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection

@section('scripts')
<script>
function copyRef() {
    const input = document.getElementById('refLink');
    navigator.clipboard.writeText(input.value).then(() => {
        document.getElementById('copyText').textContent = 'Copied!';
        setTimeout(() => document.getElementById('copyText').textContent = 'Copy', 2000);
    });
}
</script>
@endsection

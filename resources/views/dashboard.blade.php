@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')

{{-- ── Greeting ───────────────────────────────────────────────────────────── --}}
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold font-outfit text-slate-900 dark:text-white">
        Hello, {{ explode(' ', $user->name)[0] }} 👋
    </h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
        Here's what's happening with your account today.
    </p>
</div>

{{-- ── Stat Cards ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">

    {{-- Wallet Balance --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-vtu-primary to-indigo-700 p-5 text-white shadow-xl shadow-indigo-500/20">
        <div class="absolute -top-8 -right-8 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-6 -left-6 h-24 w-24 rounded-full bg-vtu-secondary/20 blur-2xl"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-xl bg-white/20 flex items-center justify-center">
                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-white/80">Wallet Balance</span>
                </div>
                <button onclick="toggleBalanceVisibility()" class="text-white/70 hover:text-white transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            <p class="text-3xl font-bold font-outfit tracking-tight balance-display mb-4"
               data-amount="{{ '₦' }}{{ number_format((float) $wallet->balance, 2) }}">
                {{ '₦' }}{{ number_format((float) $wallet->balance, 2) }}
            </p>
            <a href="{{ route('wallet.fund.gateway') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/20 hover:bg-white/30 text-sm font-semibold text-white transition-all duration-150 backdrop-blur-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Fund Wallet
            </a>
        </div>
    </div>

    {{-- Total Spent --}}
    <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center">
                <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Spent</span>
        </div>
        <p class="text-2xl font-bold font-outfit text-slate-900 dark:text-white mb-1 balance-display"
           data-amount="{{ '₦' }}{{ number_format((float) $wallet->total_spent, 2) }}">
            {{ '₦' }}{{ number_format((float) $wallet->total_spent, 2) }}
        </p>
        <p class="text-xs text-slate-400 dark:text-slate-500">All time transactions</p>
    </div>

    {{-- Total Referrals --}}
    <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-xl bg-cyan-50 dark:bg-cyan-500/10 flex items-center justify-center">
                <svg class="h-5 w-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Referrals</span>
        </div>
        <p class="text-2xl font-bold font-outfit text-slate-900 dark:text-white mb-1">
            {{ $totalReferrals }}
        </p>
        <p class="text-xs text-slate-400 dark:text-slate-500">Referral code: <span class="font-mono font-semibold text-indigo-600">{{ $user->referral_code }}</span></p>
    </div>

</div>

{{-- ── Our Services ───────────────────────────────────────────────────────── --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold font-outfit text-slate-900 dark:text-white">Our Services</h2>
        <a href="#" class="text-xs font-semibold text-indigo-600 hover:underline">View all</a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

        <a href="{{ route('services.airtime') }}" class="group flex flex-col items-center gap-3 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-400/40 hover:shadow-lg hover:shadow-indigo-500/10 hover:-translate-y-0.5 transition-all duration-200">
            <div class="h-12 w-12 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center group-hover:bg-indigo-100 dark:group-hover:bg-indigo-500/20 transition-colors">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Airtime</span>
        </a>

        <a href="{{ route('services.data') }}" class="group flex flex-col items-center gap-3 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-cyan-400/40 hover:shadow-lg hover:shadow-cyan-500/10 hover:-translate-y-0.5 transition-all duration-200">
            <div class="h-12 w-12 rounded-2xl bg-cyan-50 dark:bg-cyan-500/10 flex items-center justify-center group-hover:bg-cyan-100 dark:group-hover:bg-cyan-500/20 transition-colors">
                <svg class="h-6 w-6 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Data</span>
        </a>

        <a href="{{ route('services.electricity') }}" class="group flex flex-col items-center gap-3 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-amber-400/40 hover:shadow-lg hover:shadow-amber-500/10 hover:-translate-y-0.5 transition-all duration-200">
            <div class="h-12 w-12 rounded-2xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center group-hover:bg-amber-100 dark:group-hover:bg-amber-500/20 transition-colors">
                <svg class="h-6 w-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Electricity</span>
        </a>

        <a href="{{ route('services.cable') }}" class="group flex flex-col items-center gap-3 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-purple-400/40 hover:shadow-lg hover:shadow-purple-500/10 hover:-translate-y-0.5 transition-all duration-200">
            <div class="h-12 w-12 rounded-2xl bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center group-hover:bg-purple-100 dark:group-hover:bg-purple-500/20 transition-colors">
                <svg class="h-6 w-6 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Cable TV</span>
        </a>

        <a href="{{ route('services.epins') }}" class="group flex flex-col items-center gap-3 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-rose-400/40 hover:shadow-lg hover:shadow-rose-500/10 hover:-translate-y-0.5 transition-all duration-200">
            <div class="h-12 w-12 rounded-2xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center group-hover:bg-rose-100 dark:group-hover:bg-rose-500/20 transition-colors">
                <svg class="h-6 w-6 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Exam Pins</span>
        </a>

        <a href="#" class="group flex flex-col items-center gap-3 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-emerald-400/40 hover:shadow-lg hover:shadow-emerald-500/10 hover:-translate-y-0.5 transition-all duration-200">
            <div class="h-12 w-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-100 dark:group-hover:bg-emerald-500/20 transition-colors">
                <svg class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 text-center leading-tight">Recharge Card<br>PIN</span>
        </a>

        <a href="#" class="group flex flex-col items-center gap-3 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-teal-400/40 hover:shadow-lg hover:shadow-teal-500/10 hover:-translate-y-0.5 transition-all duration-200">
            <div class="h-12 w-12 rounded-2xl bg-teal-50 dark:bg-teal-500/10 flex items-center justify-center group-hover:bg-teal-100 dark:group-hover:bg-teal-500/20 transition-colors">
                <svg class="h-6 w-6 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Data Card</span>
        </a>

        <a href="#" class="group flex flex-col items-center gap-3 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-slate-400/40 hover:shadow-lg hover:shadow-slate-500/10 hover:-translate-y-0.5 transition-all duration-200">
            <div class="h-12 w-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center group-hover:bg-slate-200 dark:group-hover:bg-slate-700 transition-colors">
                <svg class="h-6 w-6 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Profile</span>
        </a>

    </div>
</div>

{{-- ── Upgrade to Agent Banner ────────────────────────────────────────────── --}}
@if (!$user->isAgent())
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500 p-5 sm:p-6 shadow-xl shadow-amber-500/20 mb-8">
    <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
    <div class="absolute -bottom-8 left-10 h-28 w-28 rounded-full bg-orange-600/20 blur-2xl"></div>
    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-900/70">Upgrade Now</span>
            </div>
            <h3 class="text-lg font-bold font-outfit text-white mb-1">Become an Agent & Earn More</h3>
            <p class="text-sm text-amber-100 max-w-sm">
                Unlock lower transaction rates, higher earning potential, and an exclusive agent dashboard.
            </p>
        </div>
        <div class="flex-shrink-0">
            <a href="{{ route('settings', ['tab' => 'upgrade-agent']) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white text-amber-700 font-semibold text-sm hover:bg-amber-50 transition-all duration-150 shadow-md">
                Upgrade to Agent
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>
@endif

{{-- ── Recent Transactions ─────────────────────────────────────────────────── --}}
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Recent Transactions</h2>
        <a href="#" class="text-xs font-semibold text-indigo-600 hover:underline">View all</a>
    </div>

    @if ($recentTx->isEmpty())
        <div class="flex flex-col items-center justify-center py-14 px-6 text-center">
            <div class="h-14 w-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">No transactions yet</p>
            <p class="text-xs text-slate-400 dark:text-slate-500">Your transactions will appear here after your first activity.</p>
        </div>
    @else
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($recentTx as $tx)
            <li class="flex items-center gap-4 px-5 py-3.5">
                <div class="h-9 w-9 rounded-xl flex-shrink-0 flex items-center justify-center
                    {{ $tx->isCredit() ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'bg-rose-50 dark:bg-rose-500/10' }}">
                    <svg class="h-4 w-4 {{ $tx->isCredit() ? 'text-emerald-500' : 'text-rose-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        @if ($tx->isCredit())
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        @endif
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">{{ $tx->description }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ $tx->created_at->format('d M Y, h:ia') }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-semibold {{ $tx->isCredit() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $tx->isCredit() ? '+' : '-' }}{{ '₦' }}{{ number_format((float) $tx->amount, 2) }}
                    </p>
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded
                        {{ $tx->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : ($tx->status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400') }}">
                        {{ ucfirst($tx->status) }}
                    </span>
                </div>
            </li>
            @endforeach
        </ul>
    @endif
</div>

@endsection

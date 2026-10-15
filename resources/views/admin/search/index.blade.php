@extends('layouts.admin')

@section('title', 'Search Results' . ($q ? ': ' . $q : ''))
@section('heading', 'Global Search')
@section('subheading', $q ? 'Results for "' . e($q) . '"' : 'Search across all transactions and users')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold font-outfit text-slate-800 dark:text-slate-100">Global Search</h1>
            <p class="text-xs text-slate-400 mt-0.5">{{ $q ? 'Results for "' . $q . '"' : 'Search across all transactions, users, and wallet records' }}</p>
        </div>
    </div>

    {{-- Search Bar Header --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.search') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ $q }}" placeholder="Search by name, email, phone, username, reference ID, recipient..."
                       autofocus
                       class="w-full pl-11 pr-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-all">
            </div>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white shadow-md hover:opacity-95 transition-all flex items-center justify-center gap-2"
                    style="background:{{ $themeColor }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Search
            </button>
        </form>

        @if($q)
        <div class="flex flex-wrap gap-2 mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
            <span class="text-slate-400 self-center mr-1">Found:</span>
            <span class="px-2.5 py-1 rounded-full font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                {{ count($users) }} Users
            </span>
            <span class="px-2.5 py-1 rounded-full font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                {{ count($serviceTransactions) }} Service Orders
            </span>
            <span class="px-2.5 py-1 rounded-full font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                {{ count($walletTransactions) }} Wallet Records
            </span>
        </div>
        @endif
    </div>

    @if(!$q)
    {{-- Empty Prompt --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500 shadow-sm">
        <div class="h-16 w-16 mx-auto mb-4 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Start Searching</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto mt-1">Enter a user's name, email, phone number, username, or transaction reference ID above to search across the entire platform.</p>
    </div>
    @elseif($users->isEmpty() && $serviceTransactions->isEmpty() && $walletTransactions->isEmpty())
    {{-- No Results Found --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500 shadow-sm">
        <div class="h-16 w-16 mx-auto mb-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 flex items-center justify-center">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">No matches found for "{{ $q }}"</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto mt-1">Try checking for typos or searching with fewer keywords, such as part of an email, phone number, or transaction reference.</p>
    </div>
    @else

    {{-- ── SECTION 1: MATCHED USERS ────────────────────────────────────── --}}
    @if($users->isNotEmpty())
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Users ({{ count($users) }})</h3>
            </div>
            <a href="{{ route('admin.users.index', ['search' => $q]) }}" class="text-xs font-semibold text-vtu-primary hover:underline">
                View in Users Manager &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">User</th>
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Contact</th>
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Type</th>
                        <th class="text-right text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Wallet Balance</th>
                        <th class="text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Status</th>
                        <th class="text-right text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                @if ($user->avatar)
                                    <img src="{{ $user->avatar }}" alt="Avatar" class="h-9 w-9 rounded-full object-cover">
                                @else
                                <div class="h-9 w-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:{{ $themeColor }}">
                                    {{ $user->initials() }}
                                </div>
                                @endif
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-400">@<span>{{ $user->username ?? 'user' }}</span></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                            <p>{{ $user->email }}</p>
                            <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $user->phone ?? 'No phone' }}</p>
                        </td>
                        <td class="px-3 py-3.5 text-xs capitalize text-slate-700 dark:text-slate-300">
                            @if($user->is_admin)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">Admin</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $user->user_type ?? 'User' }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3.5 text-xs font-bold text-slate-800 dark:text-slate-200 text-right">
                            &#8358;{{ number_format((float)($user->wallet?->balance ?? 0), 2) }}
                        </td>
                        <td class="px-3 py-3.5 text-center">
                            <span class="inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                  style="{{ $user->is_active ? 'background:#f0fdf4;color:#166534' : 'background:#fef2f2;color:#991b1b' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('admin.users.show', $user->id) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                                View Account &rarr;
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── SECTION 2: MATCHED SERVICE TRANSACTIONS ─────────────────────── --}}
    @if($serviceTransactions->isNotEmpty())
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Service Transactions ({{ count($serviceTransactions) }})</h3>
            </div>
            <a href="{{ route('admin.transactions.index', ['search' => $q]) }}" class="text-xs font-semibold text-vtu-primary hover:underline">
                View in Transactions Table &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Reference / Order ID</th>
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Service</th>
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">User</th>
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Recipient</th>
                        <th class="text-right text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Amount</th>
                        <th class="text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Status</th>
                        <th class="text-right text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($serviceTransactions as $tx)
                    @php
                        $statusStyle = match($tx->status) {
                            'success'  => 'background:#f0fdf4;color:#166534',
                            'failed'   => 'background:#fef2f2;color:#991b1b',
                            'pending'  => 'background:#fffbeb;color:#92400e',
                            default    => 'background:#f1f5f9;color:#475569',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <a href="{{ route('admin.transactions.index', ['search' => $tx->reference]) }}" class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $tx->reference }}
                            </a>
                        </td>
                        <td class="px-3 py-3.5 text-xs font-medium text-slate-700 dark:text-slate-300 capitalize">
                            {{ str_replace('_', ' ', $tx->service_type) }}
                        </td>
                        <td class="px-3 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                            @if($tx->user)
                                <a href="{{ route('admin.users.show', $tx->user->id) }}" class="font-medium hover:underline text-slate-800 dark:text-slate-200">
                                    {{ $tx->user->name }}
                                </a>
                            @else
                                <span class="text-slate-400">User #{{ $tx->user_id }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3.5 text-xs font-mono text-slate-600 dark:text-slate-400">
                            {{ $tx->recipient ?? '-' }}
                        </td>
                        <td class="px-3 py-3.5 text-xs font-bold text-slate-800 dark:text-slate-200 text-right">
                            &#8358;{{ number_format((float)$tx->amount, 2) }}
                        </td>
                        <td class="px-3 py-3.5 text-center">
                            <span class="inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize" style="{{ $statusStyle }}">
                                {{ $tx->status }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-slate-400 text-right">
                            {{ $tx->created_at->format('d M Y, H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── SECTION 3: MATCHED WALLET TRANSACTIONS ───────────────────────── --}}
    @if($walletTransactions->isNotEmpty())
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Wallet Records (Funding, Credits & Debits) ({{ count($walletTransactions) }})</h3>
            </div>
            <a href="{{ route('admin.transactions.index', ['tab' => 'wallet', 'search' => $q]) }}" class="text-xs font-semibold text-vtu-primary hover:underline">
                View in Wallet Log &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Reference</th>
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Type</th>
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">User</th>
                        <th class="text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Description</th>
                        <th class="text-right text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Amount</th>
                        <th class="text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 py-3">Status</th>
                        <th class="text-right text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-5 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($walletTransactions as $wtx)
                    @php
                        $wStatusStyle = match($wtx->status) {
                            'success'  => 'background:#f0fdf4;color:#166534',
                            'failed'   => 'background:#fef2f2;color:#991b1b',
                            'pending'  => 'background:#fffbeb;color:#92400e',
                            default    => 'background:#f1f5f9;color:#475569',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-5 py-3.5">
                            <a href="{{ route('admin.transactions.index', ['tab' => 'wallet', 'search' => $wtx->reference]) }}" class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $wtx->reference }}
                            </a>
                        </td>
                        <td class="px-3 py-3.5 text-xs font-semibold capitalize">
                            @if($wtx->type === 'credit')
                                <span class="text-emerald-600 dark:text-emerald-400">+ Credit</span>
                            @else
                                <span class="text-rose-600 dark:text-rose-400">- Debit</span>
                            @endif
                        </td>
                        <td class="px-3 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                            @if($wtx->user)
                                <a href="{{ route('admin.users.show', $wtx->user->id) }}" class="font-medium hover:underline text-slate-800 dark:text-slate-200">
                                    {{ $wtx->user->name }}
                                </a>
                            @else
                                <span class="text-slate-400">User #{{ $wtx->user_id }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-3.5 text-xs text-slate-600 dark:text-slate-400 max-w-xs truncate">
                            {{ $wtx->description ?? '-' }}
                        </td>
                        <td class="px-3 py-3.5 text-xs font-bold text-slate-800 dark:text-slate-200 text-right">
                            &#8358;{{ number_format((float)$wtx->amount, 2) }}
                        </td>
                        <td class="px-3 py-3.5 text-center">
                            <span class="inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize" style="{{ $wStatusStyle }}">
                                {{ $wtx->status }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-slate-400 text-right">
                            {{ $wtx->created_at->format('d M Y, H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @endif
</div>
@endsection

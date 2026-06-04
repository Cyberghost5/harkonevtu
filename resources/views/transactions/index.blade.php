@extends('layouts.dashboard')

@section('title', 'Transaction History')

@section('content')
@php
    $serviceIcons = [
        'airtime'     => ['icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',        'bg' => 'bg-blue-50 dark:bg-blue-500/10',   'color' => 'text-blue-500'],
        'data'        => ['icon' => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0', 'bg' => 'bg-indigo-50 dark:bg-indigo-500/10', 'color' => 'text-indigo-500'],
        'electricity' => ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z',             'bg' => 'bg-amber-50 dark:bg-amber-500/10',   'color' => 'text-amber-500'],
        'cable'       => ['icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'bg' => 'bg-purple-50 dark:bg-purple-500/10', 'color' => 'text-purple-500'],
        'epin'        => ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'bg' => 'bg-rose-50 dark:bg-rose-500/10',    'color' => 'text-rose-500'],
    ];
    $serviceLabels = ['airtime'=>'Airtime','data'=>'Data','electricity'=>'Electricity','cable'=>'Cable TV','epin'=>'Exam Pin'];
    $networkLabels = ['mtn'=>'MTN','airtel'=>'Airtel','glo'=>'Glo','etisalat'=>'9mobile'];
    $filterParams  = request()->only(['search','service','date_from','date_to']);
@endphp

<div class="max-w-7xl mx-auto space-y-5">

    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Transaction History</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">All your service purchases and wallet activity.</p>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-1 p-1 rounded-xl bg-slate-100 dark:bg-slate-800 w-fit">
        <a href="{{ route('transactions', array_merge($filterParams, ['tab' => 'services'])) }}"
           class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $tab === 'services' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Services
            <span class="text-xs px-1.5 py-0.5 rounded-full {{ $tab === 'services' ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }}">{{ number_format($serviceTxCount) }}</span>
        </a>
        <a href="{{ route('transactions', array_merge($filterParams, ['tab' => 'wallet'])) }}"
           class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $tab === 'wallet' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            Wallet
            <span class="text-xs px-1.5 py-0.5 rounded-full {{ $tab === 'wallet' ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }}">{{ number_format($walletTxCount) }}</span>
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('transactions') }}" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm p-4">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $tab === 'wallet' ? 'Reference, description…' : 'Reference, recipient…' }}"
                       class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
            </div>
            @if ($tab === 'services')
            <select name="service" class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
                <option value="">All services</option>
                @foreach ($serviceTypes as $type)
                    <option value="{{ $type }}" @selected(request('service') === $type)>{{ $serviceLabels[$type] ?? ucfirst($type) }}</option>
                @endforeach
            </select>
            @else
            <div></div>
            @endif
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
        </div>
        <div class="flex items-center gap-2 mt-3">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90" style="background: {{ $themeColor }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                Filter
            </button>
            @if (request()->hasAny(['search','service','date_from','date_to']))
                <a href="{{ route('transactions', ['tab' => $tab]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </a>
            @endif
            <span class="ml-auto text-xs text-slate-400 dark:text-slate-500">{{ $transactions->total() }} result{{ $transactions->total() !== 1 ? 's' : '' }}</span>
        </div>
    </form>

    {{-- Table --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        @if ($transactions->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 px-6 text-center">
                <div class="h-16 w-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">No transactions found</p>
                <p class="text-xs text-slate-400 dark:text-slate-500">
                    @if (request()->hasAny(['search','service','date_from','date_to'])) Try adjusting your filters.
                    @elseif ($tab === 'wallet') Your wallet activity will appear here once you fund or use your wallet.
                    @else Your service purchases will appear here after your first transaction.
                    @endif
                </p>
            </div>

        @elseif ($tab === 'services')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50">
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Service</th>
                            <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Recipient</th>
                            <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide hidden md:table-cell">Reference</th>
                            <th class="text-right px-4 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Amount</th>
                            <th class="text-center px-4 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</th>
                            <th class="text-right px-5 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide hidden sm:table-cell">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($transactions as $tx)
                        @php
                            $si = $serviceIcons[$tx->service_type] ?? ['icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2','bg'=>'bg-slate-100 dark:bg-slate-800','color'=>'text-slate-500'];
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl {{ $si['bg'] }} flex items-center justify-center flex-shrink-0">
                                        <svg class="h-4 w-4 {{ $si['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $si['icon'] }}"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 dark:text-slate-200 leading-tight">{{ $serviceLabels[$tx->service_type] ?? ucfirst($tx->service_type) }}</p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ $networkLabels[$tx->provider] ?? ucfirst($tx->provider) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5"><span class="font-mono text-sm text-slate-700 dark:text-slate-300">{{ $tx->recipient }}</span></td>
                            <td class="px-4 py-3.5 hidden md:table-cell"><span class="font-mono text-xs text-slate-400 dark:text-slate-500 select-all">{{ $tx->reference }}</span></td>
                            <td class="px-4 py-3.5 text-right"><span class="font-bold text-slate-900 dark:text-white whitespace-nowrap">₦{{ number_format((float) $tx->amount, 2) }}</span></td>
                            <td class="px-4 py-3.5 text-center"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $tx->statusBadgeClass() }}">{{ ucfirst($tx->status) }}</span></td>
                            <td class="px-5 py-3.5 text-right hidden sm:table-cell">
                                <span class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $tx->created_at->format('d M Y') }}<br><span class="text-slate-400 dark:text-slate-600">{{ $tx->created_at->format('h:i A') }}</span></span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50">
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Type</th>
                            <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Description</th>
                            <th class="text-left px-4 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide hidden md:table-cell">Reference</th>
                            <th class="text-right px-4 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Amount</th>
                            <th class="text-right px-4 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide hidden lg:table-cell">Balance After</th>
                            <th class="text-right px-5 py-3.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide hidden sm:table-cell">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($transactions as $tx)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl flex items-center justify-center flex-shrink-0 {{ $tx->isCredit() ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'bg-rose-50 dark:bg-rose-500/10' }}">
                                        <svg class="h-4 w-4 {{ $tx->isCredit() ? 'text-emerald-500' : 'text-rose-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            @if ($tx->isCredit()) <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                                            @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            @endif
                                        </svg>
                                    </div>
                                    <span class="font-semibold {{ $tx->isCredit() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $tx->isCredit() ? 'Credit' : 'Debit' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-slate-700 dark:text-slate-300 max-w-xs"><span class="line-clamp-2">{{ $tx->description }}</span></td>
                            <td class="px-4 py-3.5 hidden md:table-cell"><span class="font-mono text-xs text-slate-400 dark:text-slate-500 select-all">{{ $tx->reference }}</span></td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <span class="font-bold {{ $tx->isCredit() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $tx->isCredit() ? '+' : '-' }}₦{{ number_format((float) $tx->amount, 2) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right hidden lg:table-cell">
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">₦{{ number_format((float) $tx->balance_after, 2) }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right hidden sm:table-cell">
                                <span class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $tx->created_at->format('d M Y') }}<br><span class="text-slate-400 dark:text-slate-600">{{ $tx->created_at->format('h:i A') }}</span></span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Pagination --}}
        @if ($transactions->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-4">
                <p class="text-xs text-slate-400 dark:text-slate-500">Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} of {{ $transactions->total() }}</p>
                <div class="flex items-center gap-1">
                    @if ($transactions->onFirstPage())
                        <span class="px-3 py-1.5 rounded-lg text-xs text-slate-300 dark:text-slate-600 cursor-not-allowed">← Prev</span>
                    @else
                        <a href="{{ $transactions->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">← Prev</a>
                    @endif
                    @foreach ($transactions->getUrlRange(max(1, $transactions->currentPage()-2), min($transactions->lastPage(), $transactions->currentPage()+2)) as $page => $url)
                        @if ($page === $transactions->currentPage())
                            <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-white" style="background: {{ $themeColor }}">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">{{ $page }}</a>
                        @endif
                    @endforeach
                    @if ($transactions->hasMorePages())
                        <a href="{{ $transactions->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Next →</a>
                    @else
                        <span class="px-3 py-1.5 rounded-lg text-xs text-slate-300 dark:text-slate-600 cursor-not-allowed">Next →</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

</div>
@endsection

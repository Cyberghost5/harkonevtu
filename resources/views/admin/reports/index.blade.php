@extends('layouts.admin')

@section('title', 'Reports & Analytics')
@section('heading', 'Reports & Analytics')
@section('subheading', 'System financial audit, revenue performance, service analytics and user activity')

@section('content')

@php
    $currentSortSvc = request('sort_service', 'volume');
    $currentDirSvc  = strtolower(request('dir_service', 'desc'));

    $getSortUrlSvc = function($column, $defaultDir = 'desc') use ($currentSortSvc, $currentDirSvc) {
        $nextDir = ($currentSortSvc === $column)
            ? ($currentDirSvc === 'asc' ? 'desc' : 'asc')
            : $defaultDir;
        return request()->fullUrlWithQuery([
            'sort_service' => $column,
            'dir_service'  => $nextDir,
        ]);
    };

    $currentSortGw = request('sort_gateway', 'volume');
    $currentDirGw  = strtolower(request('dir_gateway', 'desc'));

    $getSortUrlGw = function($column, $defaultDir = 'desc') use ($currentSortGw, $currentDirGw) {
        $nextDir = ($currentSortGw === $column)
            ? ($currentDirGw === 'asc' ? 'desc' : 'asc')
            : $defaultDir;
        return request()->fullUrlWithQuery([
            'sort_gateway' => $column,
            'dir_gateway'  => $nextDir,
        ]);
    };

    $currentSortUsr = request('sort_users', 'total_spent');
    $currentDirUsr  = strtolower(request('dir_users', 'desc'));

    $getSortUrlUsr = function($column, $defaultDir = 'desc') use ($currentSortUsr, $currentDirUsr) {
        $nextDir = ($currentSortUsr === $column)
            ? ($currentDirUsr === 'asc' ? 'desc' : 'asc')
            : $defaultDir;
        return request()->fullUrlWithQuery([
            'sort_users' => $column,
            'dir_users'  => $nextDir,
        ]);
    };
@endphp

{{-- ── Top Action Toolbar ────────────────────────────────────────────────── --}}
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ $range['label'] }}
            </span>
            <span class="text-xs text-slate-400">Generated: {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>

    {{-- Export & Automation Actions --}}
    <div class="flex flex-wrap items-center gap-2.5">
        {{-- PDF Export Button --}}
        <a href="{{ route('admin.reports.pdf', request()->query()) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-all hover:shadow">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export PDF Report
        </a>

        {{-- Print View Button --}}
        <a href="{{ route('admin.reports.print', request()->query()) }}" target="_blank"
           class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print
        </a>

        {{-- Send Email Now Button --}}
        <form method="POST" action="{{ route('admin.reports.send-email', request()->query()) }}" class="inline"
              onsubmit="return confirm('Send this report with attached PDF to the configured admin emails now?');">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-indigo-700 dark:text-indigo-400 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/50 border border-indigo-200 dark:border-indigo-800 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Email Report Now
            </button>
        </form>

        {{-- Schedule Settings Modal Button --}}
        <button type="button" onclick="openScheduleModal()"
                class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm">
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            </svg>
            Auto-Delivery Schedule
            @if($schedule['enabled'])
                <span class="h-2 w-2 rounded-full bg-emerald-500" title="Automated delivery active"></span>
            @endif
        </button>
    </div>
</div>

{{-- ── Date Presets & Custom Filter Bar ──────────────────────────────────── --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        {{-- Quick Presets --}}
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <span class="font-bold uppercase tracking-wider text-slate-400 mr-1 text-[10px]">Period:</span>
            @php
                $presets = [
                    'today'      => 'Today',
                    'yesterday'  => 'Yesterday',
                    'this_week'  => 'This Week',
                    'last_week'  => 'Last Week',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                ];
            @endphp
            @foreach($presets as $key => $title)
                <a href="{{ route('admin.reports.index', array_merge(request()->except(['date_from', 'date_to', 'page']), ['preset' => $key])) }}"
                   class="px-3 py-1.5 rounded-xl font-medium transition-all {{ $preset === $key ? 'bg-vtu-primary text-white shadow-sm font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    {{ $title }}
                </a>
            @endforeach
        </div>

        {{-- Custom Date Picker Form --}}
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-center gap-2 text-xs">
            <input type="hidden" name="preset" value="custom">
            @if(request('sort_service'))<input type="hidden" name="sort_service" value="{{ request('sort_service') }}">@endif
            @if(request('dir_service'))<input type="hidden" name="dir_service" value="{{ request('dir_service') }}">@endif
            <div class="flex items-center gap-1.5">
                <input type="date" name="date_from" value="{{ $from ?? $range['start']->format('Y-m-d') }}"
                       class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs focus:outline-none">
                <span class="text-slate-400">to</span>
                <input type="date" name="date_to" value="{{ $to ?? $range['end']->format('Y-m-d') }}"
                       class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs focus:outline-none">
            </div>
            <button type="submit" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold rounded-xl transition-colors">
                Apply
            </button>
        </form>
    </div>
</div>

{{-- ── 6 Executive KPI Metric Cards ───────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    {{-- Card 1: Total Inflow --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 bottom-0 w-1 bg-emerald-500"></div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Inflow</p>
            <span class="h-6 w-6 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xs">🏦</span>
        </div>
        <p class="text-xl font-bold font-outfit text-slate-900 dark:text-white">₦{{ number_format($overview['total_inflow'], 2) }}</p>
        <p class="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
            <span>{{ $overview['total_inflow_count'] }} Deposits</span>
            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">+₦{{ number_format($overview['funding_fees'], 2) }} fees</span>
        </p>
    </div>

    {{-- Card 2: Total Outflow --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 bottom-0 w-1 bg-indigo-500"></div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Bills Consumed</p>
            <span class="h-6 w-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs">⚡</span>
        </div>
        <p class="text-xl font-bold font-outfit text-slate-900 dark:text-white">₦{{ number_format($overview['total_outflow'], 2) }}</p>
        <p class="text-[11px] text-slate-500 mt-1 flex items-center justify-between">
            <span>{{ $overview['total_outflow_count'] }} Purchases</span>
            <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ $overview['success_rate'] }}% Success</span>
        </p>
    </div>

    {{-- Card 3: Est. Gross Profit --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 bottom-0 w-1 bg-amber-500"></div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Est. Gross Margin</p>
            <span class="h-6 w-6 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xs">📈</span>
        </div>
        <p class="text-xl font-bold font-outfit text-emerald-600 dark:text-emerald-400">₦{{ number_format($overview['estimated_profit'], 2) }}</p>
        <p class="text-[11px] text-slate-500 mt-1">Discounts + fees collected</p>
    </div>

    {{-- Card 4: Active Users in Period --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 bottom-0 w-1 bg-sky-500"></div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Users</p>
            <span class="h-6 w-6 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-xs">👥</span>
        </div>
        <p class="text-xl font-bold font-outfit text-slate-900 dark:text-white">{{ number_format($overview['active_users']) }}</p>
        <p class="text-[11px] text-slate-500 mt-1">{{ $overview['active_user_pct'] }}% of {{ number_format($overview['total_users']) }} users</p>
    </div>

    {{-- Card 5: New Signups --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 bottom-0 w-1 bg-purple-500"></div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">New Registrations</p>
            <span class="h-6 w-6 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xs">✨</span>
        </div>
        <p class="text-xl font-bold font-outfit text-slate-900 dark:text-white">+{{ number_format($overview['new_signups']) }}</p>
        <p class="text-[11px] text-slate-500 mt-1">{{ number_format($overview['total_users']) }} Total Registered</p>
    </div>

    {{-- Card 6: Net Liquidity Delta --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 bottom-0 w-1 bg-teal-500"></div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Net Flow Delta</p>
            <span class="h-6 w-6 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xs">⚖️</span>
        </div>
        <p class="text-xl font-bold font-outfit {{ $overview['net_flow'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
            {{ $overview['net_flow'] >= 0 ? '+' : '' }}₦{{ number_format($overview['net_flow'], 2) }}
        </p>
        <p class="text-[11px] text-slate-500 mt-1 truncate">{{ $overview['net_flow'] >= 0 ? 'Positive Capital Inflow' : 'Net Wallet Drawdown' }}</p>
    </div>
</div>

{{-- ── 2 Columns: Service Breakdown & Gateway Channels ─────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
    {{-- Left: Service Performance Breakdown (7 cols) --}}
    <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
            <div>
                <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Service Sales Performance</h3>
                <p class="text-xs text-slate-400">Sort by volume, transaction count, or margin</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                {{ count($services) }} Services
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400 select-none">
                        <th class="px-4 py-3">
                            <a href="{{ $getSortUrlSvc('name', 'asc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                                <span class="{{ $currentSortSvc === 'name' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Service</span>
                                @if($currentSortSvc === 'name')
                                    <span class="text-vtu-primary">{{ $currentDirSvc === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <a href="{{ $getSortUrlSvc('count', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                                <span class="{{ $currentSortSvc === 'count' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Count</span>
                                @if($currentSortSvc === 'count')
                                    <span class="text-vtu-primary">{{ $currentDirSvc === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-right">
                            <a href="{{ $getSortUrlSvc('volume', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                                <span class="{{ $currentSortSvc === 'volume' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Volume</span>
                                @if($currentSortSvc === 'volume')
                                    <span class="text-vtu-primary">{{ $currentDirSvc === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <a href="{{ $getSortUrlSvc('success_rate', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                                <span class="{{ $currentSortSvc === 'success_rate' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Success</span>
                                @if($currentSortSvc === 'success_rate')
                                    <span class="text-vtu-primary">{{ $currentDirSvc === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-right">
                            <a href="{{ $getSortUrlSvc('margin', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                                <span class="{{ $currentSortSvc === 'margin' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Margin</span>
                                @if($currentSortSvc === 'margin')
                                    <span class="text-vtu-primary">{{ $currentDirSvc === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @forelse($services as $s)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <span>{{ $s['icon'] }}</span>
                            <span>{{ $s['name'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ number_format($s['count']) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-bold text-slate-900 dark:text-white">₦{{ number_format($s['volume'], 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $s['success_rate'] >= 95 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400' }}">
                                {{ $s['success_rate'] }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-xs font-semibold text-emerald-600 dark:text-emerald-400">₦{{ number_format($s['margin'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No service transactions in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Right: Gateway & Funding Inflow Channels (5 cols) --}}
    <div class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
            <div>
                <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Funding Inflow Channels</h3>
                <p class="text-xs text-slate-400">Gateways and automated bank transfer stats</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                Inflow: ₦{{ number_format($overview['total_inflow'], 0) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400 select-none">
                        <th class="px-4 py-3">
                            <a href="{{ $getSortUrlGw('name', 'asc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                                <span class="{{ $currentSortGw === 'name' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Channel</span>
                                @if($currentSortGw === 'name')<span class="text-vtu-primary">{{ $currentDirGw === 'asc' ? '↑' : '↓' }}</span>@endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <a href="{{ $getSortUrlGw('count', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                                <span class="{{ $currentSortGw === 'count' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Tx</span>
                                @if($currentSortGw === 'count')<span class="text-vtu-primary">{{ $currentDirGw === 'asc' ? '↑' : '↓' }}</span>@endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-right">
                            <a href="{{ $getSortUrlGw('volume', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                                <span class="{{ $currentSortGw === 'volume' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Funded</span>
                                @if($currentSortGw === 'volume')<span class="text-vtu-primary">{{ $currentDirGw === 'asc' ? '↑' : '↓' }}</span>@endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-right">
                            <a href="{{ $getSortUrlGw('fees', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                                <span class="{{ $currentSortGw === 'fees' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Fees</span>
                                @if($currentSortGw === 'fees')<span class="text-vtu-primary">{{ $currentDirGw === 'asc' ? '↑' : '↓' }}</span>@endif
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    @forelse($gateways as $gw)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200">
                            <div class="flex items-center gap-2">
                                <span>{{ $gw['icon'] }}</span>
                                <span class="truncate max-w-[150px]" title="{{ $gw['name'] }}">{{ $gw['name'] }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-slate-500">{{ number_format($gw['count']) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-bold text-slate-900 dark:text-white">₦{{ number_format($gw['volume'], 2) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-semibold text-emerald-600 dark:text-emerald-400">₦{{ number_format($gw['fees'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No funding recorded in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Top Transacting Active Users Leaderboard ────────────────────────────── --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap justify-between items-center gap-2">
        <div>
            <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Top Active Users Leaderboard</h3>
            <p class="text-xs text-slate-400">Accounts driving the highest transaction volume during this period</p>
        </div>
        <span class="text-xs text-slate-400">{{ count($topUsers) }} Top Customers</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-[11px] font-semibold uppercase tracking-wider text-slate-400 select-none">
                    <th class="px-5 py-3">
                        <a href="{{ $getSortUrlUsr('name', 'asc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                            <span class="{{ $currentSortUsr === 'name' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Customer</span>
                            @if($currentSortUsr === 'name')<span class="text-vtu-primary">{{ $currentDirUsr === 'asc' ? '↑' : '↓' }}</span>@endif
                        </a>
                    </th>
                    <th class="px-5 py-3 text-center">Type</th>
                    <th class="px-5 py-3 text-center">
                        <a href="{{ $getSortUrlUsr('tx_count', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                            <span class="{{ $currentSortUsr === 'tx_count' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Transactions</span>
                            @if($currentSortUsr === 'tx_count')<span class="text-vtu-primary">{{ $currentDirUsr === 'asc' ? '↑' : '↓' }}</span>@endif
                        </a>
                    </th>
                    <th class="px-5 py-3 text-right">
                        <a href="{{ $getSortUrlUsr('total_spent', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                            <span class="{{ $currentSortUsr === 'total_spent' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Total Spent</span>
                            @if($currentSortUsr === 'total_spent')<span class="text-vtu-primary">{{ $currentDirUsr === 'asc' ? '↑' : '↓' }}</span>@endif
                        </a>
                    </th>
                    <th class="px-5 py-3 text-right">
                        <a href="{{ $getSortUrlUsr('balance', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                            <span class="{{ $currentSortUsr === 'balance' ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Wallet Balance</span>
                            @if($currentSortUsr === 'balance')<span class="text-vtu-primary">{{ $currentDirUsr === 'asc' ? '↑' : '↓' }}</span>@endif
                        </a>
                    </th>
                    <th class="px-5 py-3 text-center">
                        <a href="{{ $getSortUrlUsr('joined_ts', 'desc') }}" class="group inline-flex items-center gap-1 hover:text-slate-700 dark:hover:text-slate-200">
                            <span class="{{ in_array($currentSortUsr, ['joined', 'joined_ts']) ? 'text-vtu-primary dark:text-emerald-400 font-bold' : '' }}">Joined</span>
                            @if(in_array($currentSortUsr, ['joined', 'joined_ts']))<span class="text-vtu-primary">{{ $currentDirUsr === 'asc' ? '↑' : '↓' }}</span>@endif
                        </a>
                    </th>
                    <th class="px-5 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                @forelse($topUsers as $u)
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-vtu-primary/10 text-vtu-primary flex items-center justify-center font-bold text-xs">
                                {{ $u['initials'] }}
                            </div>
                            <div>
                                <a href="{{ route('admin.users.show', $u['user_id']) }}" class="font-semibold text-slate-800 dark:text-white hover:underline block leading-tight">
                                    {{ $u['name'] }}
                                </a>
                                <span class="text-xs text-slate-400 font-mono">{{ $u['phone'] }} • {{ $u['email'] }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $u['user_type'] === 'agent' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                            {{ ucfirst($u['user_type']) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ number_format($u['tx_count']) }}
                    </td>
                    <td class="px-5 py-3 text-right text-xs font-bold text-emerald-600 dark:text-emerald-400">
                        ₦{{ number_format($u['total_spent'], 2) }}
                    </td>
                    <td class="px-5 py-3 text-right text-xs font-mono font-semibold text-slate-800 dark:text-slate-200">
                        ₦{{ number_format($u['balance'], 2) }}
                    </td>
                    <td class="px-5 py-3 text-center text-xs text-slate-400">
                        {{ $u['joined'] }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('admin.users.show', $u['user_id']) }}"
                           class="text-xs font-semibold text-vtu-primary hover:underline">
                            View Account →
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">No transacting customers found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Schedule Settings Modal ────────────────────────────────────────────── --}}
<div id="scheduleModal" class="fixed inset-0 z-50 bg-black/60 hidden items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Automated Report Delivery Schedule</h3>
                <p class="text-xs text-slate-400">Automatically deliver executive reports with PDF attachments</p>
            </div>
            <button type="button" onclick="closeScheduleModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.reports.settings') }}" class="space-y-4">
            @csrf
            {{-- Automation Active Toggle --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Automated Email Delivery</label>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="reports_automated_enabled" value="1" {{ $schedule['enabled'] ? 'checked' : '' }} class="text-vtu-primary">
                        <span class="text-sm font-medium text-slate-800 dark:text-slate-200">Enabled (Active)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="reports_automated_enabled" value="0" {{ !$schedule['enabled'] ? 'checked' : '' }} class="text-vtu-primary">
                        <span class="text-sm font-medium text-slate-500">Disabled</span>
                    </label>
                </div>
            </div>

            {{-- Frequency --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Sending Frequency</label>
                <select name="reports_frequency" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
                    <option value="weekly" {{ $schedule['frequency'] === 'weekly' ? 'selected' : '' }}>Weekly (Every Monday at 07:00 AM)</option>
                    <option value="daily"  {{ $schedule['frequency'] === 'daily'  ? 'selected' : '' }}>Daily (Every Morning at 06:00 AM)</option>
                    <option value="both"   {{ $schedule['frequency'] === 'both'   ? 'selected' : '' }}>Both Daily & Weekly Reports</option>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Daily reports audit the previous day; weekly reports audit the entire prior week.</p>
            </div>

            {{-- Additional Email Recipients --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Recipient Email Addresses</label>
                <textarea name="reports_email_recipients" rows="3" placeholder="admin@dakeventures.com.ng, finance@dakeventures.com.ng"
                          class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">{{ $schedule['recipients'] }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">Separate multiple email addresses with commas. The primary platform administrator email is always included automatically.</p>
            </div>

            @if($schedule['last_sent'])
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-xs text-slate-500 flex items-center justify-between">
                <span>Last automated report dispatched:</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($schedule['last_sent'])->format('d M Y, h:i A') }}</span>
            </div>
            @endif

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="closeScheduleModal()" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-vtu-primary hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">
                    Save Schedule Settings
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeScheduleModal() {
        const modal = document.getElementById('scheduleModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection

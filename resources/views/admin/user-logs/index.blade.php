@extends('layouts.admin')

@section('title', 'User Login Logs')
@section('heading', 'User Login Logs')
@section('subheading', 'Authentication history across Web and Mobile App channels')

@section('content')

{{-- Metrics Overview Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex items-center gap-4 shadow-sm">
        <div class="h-12 w-12 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center font-bold text-xl">
            🔐
        </div>
        <div>
            <p class="text-xs font-medium text-slate-500">Logins Today</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($todayCount) }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex items-center gap-4 shadow-sm">
        <div class="h-12 w-12 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center font-bold text-xl">
            💻
        </div>
        <div>
            <p class="text-xs font-medium text-slate-500">Web Logins</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($webCount) }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex items-center gap-4 shadow-sm">
        <div class="h-12 w-12 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold text-xl">
            📱
        </div>
        <div>
            <p class="text-xs font-medium text-slate-500">Mobile App Logins</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($mobileCount) }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 flex items-center gap-4 shadow-sm">
        <div class="h-12 w-12 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center font-bold text-xl">
            ⚠️
        </div>
        <div>
            <p class="text-xs font-medium text-slate-500">Failed Attempts</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($failedCount) }}</p>
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm mb-5 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-slate-500 mb-1">Search User / IP</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="User name, email, or IP address..."
               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Login Channel</label>
        <select name="channel" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Channels</option>
            <option value="web"    {{ request('channel')==='web'    ? 'selected' : '' }}>💻 Website</option>
            <option value="mobile" {{ request('channel')==='mobile' ? 'selected' : '' }}>📱 Mobile App</option>
        </select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
        <select name="status" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="success"     {{ request('status')==='success'     ? 'selected' : '' }}>Successful</option>
            <option value="failed"      {{ request('status')==='failed'      ? 'selected' : '' }}>Failed</option>
            <option value="otp_pending" {{ request('status')==='otp_pending' ? 'selected' : '' }}>OTP Pending</option>
        </select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">From Date</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">To Date</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
    </div>

    <div class="flex gap-2">
        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-vtu-primary rounded-xl hover:bg-vtu-primary-hover transition-colors shadow-sm">
            Filter
        </button>
        @if(request()->anyFilled(['search', 'channel', 'status', 'date_from', 'date_to']))
        <a href="{{ route('admin.user-logs.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
            Reset
        </a>
        @endif
    </div>
</form>

{{-- User Login Logs Table --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Channel</th>
                    <th class="px-4 py-3">Device & Browser</th>
                    <th class="px-4 py-3">IP Address</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Login Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="px-4 py-3">
                        @if($log->user)
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-full bg-vtu-primary/10 text-vtu-primary flex items-center justify-center font-bold text-xs">
                                {{ $log->user->initials() }}
                            </div>
                            <div>
                                <a href="{{ route('admin.users.show', $log->user) }}" class="font-semibold text-slate-800 dark:text-white hover:underline block leading-tight">
                                    {{ $log->user->name }}
                                </a>
                                <span class="text-xs text-slate-500">{{ $log->user->email }}</span>
                            </div>
                        </div>
                        @else
                        <div>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $log->email_or_username ?? 'Unknown Account' }}</span>
                            <span class="block text-xs text-rose-500">(Unregistered / Failed)</span>
                        </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($log->channel === 'mobile')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300">
                            📱 Mobile App
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                            💻 Website
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $log->device_type }}</span>
                            <span class="text-slate-400 mx-1">•</span>
                            <span class="text-slate-500">{{ $log->browser }}</span>
                        </div>
                        @if($log->user_agent)
                        <span class="text-[10px] text-slate-400 truncate max-w-[200px] block" title="{{ $log->user_agent }}">
                            {{ Str::limit($log->user_agent, 35) }}
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($log->status === 'success')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">
                            ✓ Success
                        </span>
                        @elseif($log->status === 'otp_pending')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
                            ⏳ OTP Pending
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400" title="{{ $log->failure_reason }}">
                            ✕ Failed
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap text-xs text-slate-500">
                        <div>{{ $log->created_at->format('M d, Y') }}</div>
                        <div class="text-[11px] text-slate-400">{{ $log->created_at->format('h:i:s A') }}</div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                        No user login logs found matching your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800">
        {{ $logs->links() }}
    </div>
    @endif
</div>

@endsection

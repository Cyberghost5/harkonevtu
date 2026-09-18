@extends('layouts.admin')

@section('title', 'API Logs')
@section('heading', 'API Logs')
@section('subheading', 'Outbound API call & webhook history with device & IP tracking')

@section('content')

{{-- Filters --}}
<form method="GET" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm mb-5 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-slate-500 mb-1">Search Reference / IP / Device</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference, IP address, device, location..."
               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Source / Channel</label>
        <select name="channel" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Sources</option>
            <option value="mobile"  {{ request('channel')==='mobile'  ? 'selected' : '' }}>📱 Mobile App</option>
            <option value="web"     {{ request('channel')==='web'     ? 'selected' : '' }}>💻 Web Interface</option>
            <option value="webhook" {{ request('channel')==='webhook' ? 'selected' : '' }}>⚡ Webhook</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Service</label>
        <select name="service" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Services</option>
            @foreach($services as $s)
            <option value="{{ $s }}" {{ request('service')===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Provider</label>
        <select name="provider" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Providers</option>
            @foreach($providers as $p)
            <option value="{{ $p }}" {{ request('provider')===$p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
        <select name="status" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="success" {{ request('status')==='success' ? 'selected' : '' }}>Success (OK)</option>
            <option value="failed"  {{ request('status')==='failed'  ? 'selected' : '' }}>Failed</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
    </div>
    <div class="flex gap-2">
        <button type="submit" class="px-4 py-2 text-sm font-medium bg-vtu-primary text-white rounded-xl hover:bg-indigo-700 transition-colors">Filter</button>
        @if(request()->hasAny(['search','channel','service','provider','status','date_from','date_to']))
        <a href="{{ route('admin.api-logs.index') }}" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-white">Clear</a>
        @endif
    </div>
</form>

{{-- Table --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">User</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Source</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Device</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">IP & Location</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Service / Provider</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">HTTP</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Duration</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                @forelse($logs as $log)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors cursor-pointer"
                    onclick="toggleLogDetail('log-{{ $log->id }}')">
                    <td class="px-4 py-2.5 font-mono text-[11px] text-slate-500">{{ Str::limit($log->reference, 18) }}</td>
                    <td class="px-4 py-2.5 text-xs text-slate-600 dark:text-slate-400 font-medium">{{ $log->user?->name ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-xs whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $log->channelBadgeClass }}">
                            @if($log->channelLabel === 'Mobile App')
                                📱 Mobile
                            @elseif($log->channelLabel === 'Webhook')
                                ⚡ Webhook
                            @else
                                💻 Web
                            @endif
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-xs">
                        <span class="text-slate-700 dark:text-slate-300 font-medium block text-[11px] truncate max-w-[130px]" title="{{ $log->device_info }}">
                            {{ $log->device_info ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-xs font-mono">
                        <span class="text-slate-600 dark:text-slate-400 block text-[11px]">{{ $log->ip_address ?? '—' }}</span>
                        @if($log->location)
                        <span class="text-[10px] font-sans font-medium text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-0.5">
                            📍 {{ $log->location }}
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-xs">
                        <span class="capitalize font-semibold text-slate-800 dark:text-slate-200">{{ $log->service }}</span>
                        <span class="text-slate-400 text-[10px] block capitalize">{{ $log->provider }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-xs">
                        <span class="font-mono {{ ($log->http_status >= 200 && $log->http_status < 300) ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-red-600 dark:text-red-400 font-bold' }}">
                            {{ $log->http_status ?? '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-slate-400 whitespace-nowrap">{{ $log->duration_ms ? round($log->duration_ms).'ms' : '-' }}</td>
                    <td class="px-4 py-2.5 whitespace-nowrap">
                        @if($log->success)
                        <span class="text-[11px] px-1.5 py-0.5 rounded-full font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">OK</span>
                        @else
                        <span class="text-[11px] px-1.5 py-0.5 rounded-full font-semibold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400">Fail</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-[11px] text-slate-400 whitespace-nowrap">{{ $log->created_at->format('d M y, H:i') }}</td>
                </tr>

                {{-- Detail row --}}
                <tr id="log-{{ $log->id }}" class="hidden bg-slate-50 dark:bg-slate-800/30">
                    <td colspan="10" class="px-4 py-4">
                        <div class="mb-3 p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Client IP Address</span>
                                <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $log->ip_address ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Estimated Location</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">📍 {{ $log->location ?? 'Unknown' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Device & Browser</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $log->device_info ?? 'Unknown' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Endpoint Route</span>
                                <span class="font-mono text-slate-600 dark:text-slate-400 truncate block" title="{{ $log->endpoint }}">{{ $log->method }} {{ $log->endpoint }}</span>
                            </div>
                            @if($log->user_agent)
                            <div class="md:col-span-4 border-t border-slate-100 dark:border-slate-800 pt-2">
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">User-Agent Header</span>
                                <span class="font-mono text-[11px] text-slate-500 break-all">{{ $log->user_agent }}</span>
                            </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div>
                                <p class="font-semibold text-slate-500 mb-1 uppercase tracking-wider text-[10px]">Payload</p>
                                <pre class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-3 overflow-auto max-h-48 text-[11px] text-slate-600 dark:text-slate-400 whitespace-pre-wrap">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-500 mb-1 uppercase tracking-wider text-[10px]">Response</p>
                                <pre class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-3 overflow-auto max-h-48 text-[11px] text-slate-600 dark:text-slate-400 whitespace-pre-wrap">{{ json_encode($log->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="px-5 py-8 text-center text-sm text-slate-400">No API logs found matching your filters.</td></tr>
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

<script>
function toggleLogDetail(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.toggle('hidden');
    }
}
</script>

@endsection

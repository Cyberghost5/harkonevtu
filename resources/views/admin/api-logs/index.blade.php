@extends('layouts.admin')

@section('title', 'API Logs')
@section('heading', 'API Logs')
@section('subheading', 'Outbound API call history')

@section('content')

{{-- Filters --}}
<form method="GET" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm mb-5 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[160px]">
        <label class="block text-xs font-medium text-slate-500 mb-1">Search Reference</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference, endpoint..."
               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Service</label>
        <select name="service" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All</option>
            @foreach($services as $s)
            <option value="{{ $s }}" {{ request('service')===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Provider</label>
        <select name="provider" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All</option>
            @foreach($providers as $p)
            <option value="{{ $p }}" {{ request('provider')===$p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
        <select name="status" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All</option>
            <option value="success" {{ request('status')==='success' ? 'selected' : '' }}>Success</option>
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
    <button type="submit" class="px-4 py-2 text-sm font-medium bg-vtu-primary text-white rounded-xl hover:bg-indigo-700">Filter</button>
    @if(request()->hasAny(['search','service','provider','status','date_from','date_to']))
    <a href="{{ route('admin.api-logs.index') }}" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-white">Clear</a>
    @endif
</form>

{{-- Table --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">User</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Service</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Provider</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Endpoint</th>
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
                    <td class="px-4 py-2.5 font-mono text-[11px] text-slate-500">{{ Str::limit($log->reference, 20) }}</td>
                    <td class="px-4 py-2.5 text-xs text-slate-600 dark:text-slate-400">{{ $log->user?->name ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-xs capitalize font-medium text-slate-700 dark:text-slate-300">{{ $log->service }}</td>
                    <td class="px-4 py-2.5 text-xs capitalize text-slate-500">{{ $log->provider }}</td>
                    <td class="px-4 py-2.5 font-mono text-[11px] text-slate-400 max-w-[150px] truncate">{{ $log->endpoint }}</td>
                    <td class="px-4 py-2.5 text-xs">
                        <span class="font-mono {{ ($log->http_status >= 200 && $log->http_status < 300) ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $log->http_status ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-slate-400">{{ $log->duration_ms ? round($log->duration_ms).'ms' : '—' }}</td>
                    <td class="px-4 py-2.5">
                        @if($log->success)
                        <span class="text-[11px] px-1.5 py-0.5 rounded-full font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">OK</span>
                        @else
                        <span class="text-[11px] px-1.5 py-0.5 rounded-full font-semibold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400">Fail</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-[11px] text-slate-400">{{ $log->created_at->format('d M y, H:i') }}</td>
                </tr>
                {{-- Detail row --}}
                <tr id="log-{{ $log->id }}" class="hidden bg-slate-50 dark:bg-slate-800/30">
                    <td colspan="9" class="px-4 py-4">
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
                <tr><td colspan="9" class="px-5 py-8 text-center text-sm text-slate-400">No API logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800">
        {{ $logs->links() }}
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
function toggleLogDetail(id) {
    const row = document.getElementById(id);
    if (row) row.classList.toggle('hidden');
}
</script>
@endsection

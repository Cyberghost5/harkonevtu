@extends('layouts.admin')

@section('title', 'Transactions')
@section('heading', 'Transactions')
@section('subheading', 'All service transactions')

@section('content')

{{-- Filters --}}
<form method="GET" action="{{ route('admin.transactions.index') }}"
      class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm mb-5 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[180px]">
        <label class="block text-xs font-medium text-slate-500 mb-1">Search Reference / User</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference, name, email, phone..."
               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Service</label>
        <select name="service" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Services</option>
            @foreach($serviceTypes as $st)
            <option value="{{ $st }}" {{ request('service')===$st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
        <select name="status" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Status</option>
            <option value="success" {{ request('status')==='success' ? 'selected' : '' }}>Success</option>
            <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>Pending</option>
            <option value="failed"  {{ request('status')==='failed'  ? 'selected' : '' }}>Failed</option>
            <option value="refunded"{{ request('status')==='refunded'? 'selected' : '' }}>Refunded</option>
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
    <button type="submit" class="px-4 py-2 text-sm font-medium bg-vtu-primary text-white rounded-xl hover:bg-indigo-700 transition-colors">Filter</button>
    @if(request()->hasAny(['search','service','status','date_from','date_to']))
    <a href="{{ route('admin.transactions.index') }}" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-white">Clear</a>
    @endif
</form>

{{-- Table --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">User</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Service</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Recipient</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                @forelse($transactions as $tx)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                    <td class="px-5 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $tx->reference }}</td>
                    <td class="px-5 py-3">
                        @if($tx->user)
                        <a href="{{ route('admin.users.show', $tx->user) }}" class="text-xs font-medium text-vtu-primary hover:underline">{{ $tx->user->name }}</a>
                        @else <span class="text-xs text-slate-400">N/A</span>
                        @endif
                    </td>
                    <td class="px-5 py-3"><span class="capitalize text-xs font-medium text-slate-700 dark:text-slate-300">{{ $tx->service_type }}</span></td>
                    <td class="px-5 py-3 text-xs text-slate-500">{{ $tx->recipient }}</td>
                    <td class="px-5 py-3 text-xs font-semibold text-slate-900 dark:text-white">₦{{ number_format($tx->amount,2) }}</td>
                    <td class="px-5 py-3">
                        @if($tx->status==='success')   <span class="text-[11px] px-2 py-0.5 rounded-full font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">Success</span>
                        @elseif($tx->status==='pending')<span class="text-[11px] px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">Pending</span>
                        @else                          <span class="text-[11px] px-2 py-0.5 rounded-full font-semibold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400">{{ ucfirst($tx->status) }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-[11px] text-slate-400">{{ $tx->created_at->format('d M y, H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">No transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800">
        {{ $transactions->links() }}
    </div>
    @endif
</div>

@endsection

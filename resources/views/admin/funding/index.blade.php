@extends('layouts.admin')

@section('title', 'Funding Requests')
@section('heading', 'Funding Requests')
@section('subheading', 'Review and approve manual funding proofs')

@section('content')

{{-- Filters --}}
<form method="GET" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm mb-5 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
        <select name="status" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All</option>
            <option value="pending"  {{ request('status')==='pending'  ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status')==='approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status')==='rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
    </div>
    <button type="submit" class="px-4 py-2 text-sm font-medium bg-vtu-primary text-white rounded-xl hover:bg-indigo-700">Filter</button>
    @if(request('status'))
    <a href="{{ route('admin.funding.index') }}" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-white">Clear</a>
    @endif
</form>

{{-- List --}}
<div class="space-y-4">
    @forelse($requests as $req)
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-vtu-primary to-vtu-secondary flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ $req->user->initials() }}
                </div>
                <div>
                    <a href="{{ route('admin.users.show', $req->user) }}" class="text-sm font-semibold text-slate-900 dark:text-white hover:underline">{{ $req->user->name }}</a>
                    <p class="text-xs text-slate-400">{{ $req->user->email }} &middot; {{ $req->user->phone }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-lg font-bold font-outfit text-slate-900 dark:text-white">₦{{ number_format($req->amount, 2) }}</span>
                @if($req->status==='pending')
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">Pending</span>
                @elseif($req->status==='approved')
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">Approved</span>
                @else
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400">Rejected</span>
                @endif
                <span class="text-xs text-slate-400">{{ $req->created_at->format('d M Y, H:i') }}</span>
            </div>
        </div>

        <div class="px-5 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Info --}}
                <div class="space-y-2 text-sm">
                    <div class="flex gap-2"><span class="text-slate-400 w-28 flex-shrink-0">Bank Ref:</span><span class="font-mono text-xs text-slate-700 dark:text-slate-300">{{ $req->bank_reference ?? '—' }}</span></div>
                    @if($req->admin_note)<div class="flex gap-2"><span class="text-slate-400 w-28 flex-shrink-0">Admin Note:</span><span class="text-xs text-slate-600 dark:text-slate-400">{{ $req->admin_note }}</span></div>@endif
                    @if($req->reviewed_at)<div class="flex gap-2"><span class="text-slate-400 w-28 flex-shrink-0">Reviewed:</span><span class="text-xs text-slate-500">{{ $req->reviewed_at->format('d M Y, H:i') }}</span></div>@endif
                </div>

                {{-- Proof image --}}
                @if($req->proof_image)
                <div>
                    <p class="text-xs text-slate-400 mb-2">Payment Proof</p>
                    <a href="{{ Storage::url($req->proof_image) }}" target="_blank">
                        <img src="{{ Storage::url($req->proof_image) }}" alt="Proof"
                             class="h-28 rounded-xl object-cover border border-slate-200 dark:border-slate-700 hover:opacity-80 transition">
                    </a>
                </div>
                @endif
            </div>

            {{-- Actions for pending --}}
            @if($req->isPending())
            <div class="mt-4 flex flex-wrap gap-3">
                {{-- Approve --}}
                <form method="POST" action="{{ route('admin.funding.approve', $req->id) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="note" placeholder="Optional note..." maxlength="500"
                           class="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 w-48">
                    <button type="submit"
                            onclick="return confirm('Approve ₦{{ number_format($req->amount,2) }} for {{ $req->user->name }}?')"
                            class="px-4 py-2 text-xs font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors">
                        Approve
                    </button>
                </form>

                {{-- Reject --}}
                <form method="POST" action="{{ route('admin.funding.reject', $req->id) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="note" placeholder="Rejection reason (required)" maxlength="500" required
                           class="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-red-400/30 w-52">
                    <button type="submit"
                            onclick="return confirm('Reject this funding request?')"
                            class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors">
                        Reject
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-10 text-center shadow-sm">
        <p class="text-slate-400">No funding requests found.</p>
    </div>
    @endforelse
</div>

@if($requests->hasPages())
<div class="mt-5">{{ $requests->links() }}</div>
@endif

@endsection

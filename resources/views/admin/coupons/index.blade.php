@extends('layouts.admin')

@section('title', 'Coupons')
@section('heading', 'Coupons')
@section('subheading', 'Manage discount coupons')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Create form --}}
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-outfit mb-4">Create Coupon</h3>
            <form method="POST" action="{{ route('admin.coupons.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Code</label>
                    <input type="text" name="code" required maxlength="50" placeholder="e.g. WELCOME500"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 uppercase">
                    @error('code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Amount (₦)</label>
                    <input type="number" name="amount" required min="1" step="0.01"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    @error('amount')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Max Uses <span class="text-slate-400">(0 = unlimited)</span></label>
                    <input type="number" name="max_uses" required min="0" value="0"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Expires At <span class="text-slate-400">(optional)</span></label>
                    <input type="date" name="expires_at"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="rounded">
                    <label for="is_active" class="text-sm text-slate-600 dark:text-slate-400">Active immediately</label>
                </div>
                <button type="submit" class="w-full py-2.5 text-sm font-semibold bg-vtu-primary text-white rounded-xl hover:bg-indigo-700 transition-colors">
                    Create Coupon
                </button>
            </form>
        </div>
    </div>

    {{-- Coupon list --}}
    <div class="lg:col-span-2">

        {{-- Filter --}}
        <form method="GET" class="flex gap-3 items-center mb-4">
            <select name="status" onchange="this.form.submit()"
                    class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 focus:outline-none">
                <option value="">All Coupons</option>
                <option value="active" {{ request('status')==='active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status')==='inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </form>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Code</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Uses</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Expires</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse($coupons as $coupon)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-900 dark:text-white">{{ $coupon->code }}</td>
                            <td class="px-4 py-3 text-xs font-semibold text-emerald-600 dark:text-emerald-400">₦{{ number_format($coupon->amount,2) }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                                {{ $coupon->uses_count }} / {{ $coupon->max_uses == 0 ? '∞' : $coupon->max_uses }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ $coupon->expires_at ? $coupon->expires_at->format('d M Y') : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($coupon->is_active)
                                <span class="text-[11px] px-1.5 py-0.5 rounded-full font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">Active</span>
                                @else
                                <span class="text-[11px] px-1.5 py-0.5 rounded-full font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $coupon->is_active ? '0' : '1' }}">
                                        <button type="submit" class="text-xs text-slate-400 hover:text-vtu-primary transition-colors">
                                            {{ $coupon->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                          onsubmit="return confirm('Delete coupon {{ $coupon->code }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition-colors">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">No coupons found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($coupons->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800">
                {{ $coupons->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

@endsection

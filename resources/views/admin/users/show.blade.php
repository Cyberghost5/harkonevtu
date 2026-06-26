@extends('layouts.admin')

@section('title', $user->name)
@section('heading', $user->name)
@section('subheading', 'User detail & wallet management')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Left: User info + actions --}}
    <div class="lg:col-span-1 space-y-4">

        {{-- Profile Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-vtu-primary to-vtu-secondary flex items-center justify-center text-white font-bold text-base flex-shrink-0">
                    {{ $user->initials() }}
                </div>
                <div>
                    <p class="font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                    <p class="text-xs text-slate-400">{{ $user->username }}</p>
                </div>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Email</span><span class="text-slate-900 dark:text-white truncate ml-2">{{ $user->email }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Phone</span><span class="text-slate-900 dark:text-white">{{ $user->phone }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Type</span>
                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full
                        {{ $user->isAgent() ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400' }}">
                        {{ ucfirst($user->user_type) }}
                    </span>
                </div>
                <div class="flex justify-between"><span class="text-slate-400">Status</span>
                    <span class="{{ $user->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }} font-medium">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="flex justify-between"><span class="text-slate-400">Joined</span><span class="text-slate-900 dark:text-white">{{ $user->created_at->format('d M Y') }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Referral</span><span class="text-slate-900 dark:text-white font-mono text-xs">{{ $user->referral_code }}</span></div>
            </div>
        </div>

        {{-- Wallet Summary --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Wallet</p>
            @if($user->wallet)
            <p class="text-2xl font-bold font-outfit text-slate-900 dark:text-white mb-2">₦{{ number_format($user->wallet->balance, 2) }}</p>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-2.5">
                    <p class="text-slate-400 mb-0.5">Total Funded</p>
                    <p class="font-semibold text-emerald-600 dark:text-emerald-400">₦{{ number_format($user->wallet->total_funded, 2) }}</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-2.5">
                    <p class="text-slate-400 mb-0.5">Total Spent</p>
                    <p class="font-semibold text-rose-600 dark:text-rose-400">₦{{ number_format($user->wallet->total_spent, 2) }}</p>
                </div>
            </div>
            @else
            <p class="text-sm text-slate-400">No wallet found.</p>
            @endif
        </div>

        {{-- Toggle Status --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Account Actions</p>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3">
                @csrf @method('PATCH')
                <div class="flex items-center justify-between">
                    <label class="text-sm text-slate-600 dark:text-slate-400">Account Active</label>
                    <button type="submit" name="is_active" value="{{ $user->is_active ? '0' : '1' }}"
                            class="px-3 py-1.5 text-xs font-medium rounded-xl transition-colors
                                   {{ $user->is_active ? 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 hover:bg-red-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 hover:bg-emerald-200' }}">
                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </div>
            </form>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-2">
                @csrf @method('PATCH')
                <div class="flex items-center justify-between">
                    <label class="text-sm text-slate-600 dark:text-slate-400">User Type</label>
                    <div class="flex items-center gap-2">
                        <select name="user_type" class="text-xs px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
                            <option value="user" {{ $user->user_type === 'user' ? 'selected' : '' }}>User</option>
                            <option value="agent" {{ $user->user_type === 'agent' ? 'selected' : '' }}>Agent</option>
                        </select>
                        <button type="submit" class="px-2 py-1 text-xs font-medium bg-vtu-primary text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Wallet Adjustment --}}
        @if($user->wallet)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Adjust Wallet</p>
            <form method="POST" action="{{ route('admin.users.adjust-wallet', $user) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Type</label>
                    <select name="type" required class="w-full text-sm px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        <option value="credit">Credit (Add)</option>
                        <option value="debit">Debit (Subtract)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Amount (₦)</label>
                    <input type="number" name="amount" min="1" step="0.01" required
                           class="w-full text-sm px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Description</label>
                    <input type="text" name="description" required maxlength="255"
                           class="w-full text-sm px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                </div>
                <button type="submit" class="w-full py-2 text-sm font-medium bg-vtu-primary text-white rounded-xl hover:bg-indigo-700 transition-colors">
                    Apply Adjustment
                </button>
            </form>
        </div>
        @endif

    </div>

    {{-- Right: Transactions --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Service Transactions --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-outfit">Recent Service Transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Service</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Recipient</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse($serviceTransactions as $tx)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                            <td class="px-4 py-2.5 text-xs capitalize font-medium text-slate-700 dark:text-slate-300">{{ $tx->service_type }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-500">{{ $tx->recipient }}</td>
                            <td class="px-4 py-2.5 text-xs font-semibold text-slate-900 dark:text-white">₦{{ number_format($tx->amount,2) }}</td>
                            <td class="px-4 py-2.5">
                                @if($tx->status==='success') <span class="text-[11px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 font-semibold">Success</span>
                                @elseif($tx->status==='pending') <span class="text-[11px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 font-semibold">Pending</span>
                                @else <span class="text-[11px] px-1.5 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 font-semibold">{{ ucfirst($tx->status) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-[11px] text-slate-400">{{ $tx->created_at->format('d M y, H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">No service transactions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Wallet Transactions --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-outfit">Wallet Transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Description</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Type</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Balance After</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse($transactions as $wt)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                            <td class="px-4 py-2.5 text-xs text-slate-600 dark:text-slate-400">{{ $wt->description }}</td>
                            <td class="px-4 py-2.5">
                                <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded-full
                                    {{ $wt->type==='credit' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400' }}">
                                    {{ ucfirst($wt->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-xs font-semibold {{ $wt->type==='credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $wt->type==='credit' ? '+' : '-' }}₦{{ number_format($wt->amount,2) }}
                            </td>
                            <td class="px-4 py-2.5 text-xs font-medium text-slate-900 dark:text-white">₦{{ number_format($wt->balance_after,2) }}</td>
                            <td class="px-4 py-2.5 text-[11px] text-slate-400">{{ $wt->created_at->format('d M y, H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">No wallet transactions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions?->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800">
                {{ $transactions->links() }}
            </div>
            @endif
        </div>

    </div>

</div>

@endsection

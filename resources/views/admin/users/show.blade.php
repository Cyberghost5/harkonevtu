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
                @if ($user->avatar)
                    <img src="{{ $user->avatar }}" alt="Avatar" class="h-12 w-12 rounded-2xl object-cover">
                @else
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-vtu-primary to-vtu-secondary flex items-center justify-center text-white font-bold text-base flex-shrink-0">
                        {{ $user->initials() }}
                    </div>
                @endif
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
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('admin.users.edit', $user) }}" class="w-full inline-flex justify-center items-center py-2 text-xs font-semibold text-vtu-primary bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20 rounded-xl transition-colors">
                    Edit Profile Details
                </a>
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
            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                @csrf
                <button type="submit" class="w-full inline-flex justify-center items-center py-2 text-xs font-semibold bg-vtu-primary text-white rounded-xl transition-colors">
                    Impersonate User
                </button>
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
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-outfit">Recent Service Transactions</h3>
                    @if(count($serviceTransactions) > 0)
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        {{ count($serviceTransactions) }}
                    </span>
                    @endif
                </div>
                <a href="{{ route('admin.transactions.index', ['tab' => 'services', 'search' => $user->email]) }}" class="text-xs font-semibold text-vtu-primary hover:underline flex items-center gap-1">
                    <span>View in Purchases</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto custom-scrollbar-light dark:custom-scrollbar">
                <table class="w-full text-sm min-w-[620px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Service</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Recipient</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Date</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse($serviceTransactions as $tx)
                        <tr onclick="window.location='{{ route('admin.transactions.show', ['type' => 'service', 'id' => $tx->id]) }}'"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors cursor-pointer group">
                            <td class="px-4 py-3 text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-vtu-primary transition-colors whitespace-nowrap capitalize">
                                {{ str_replace('_', ' ', $tx->service_type) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 font-mono whitespace-nowrap">{{ $tx->recipient ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs font-bold text-slate-900 dark:text-white text-right whitespace-nowrap">₦{{ number_format($tx->amount, 2) }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($tx->status==='success') <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 font-semibold">Success</span>
                                @elseif($tx->status==='pending') <span class="text-[11px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 font-semibold">Pending</span>
                                @else <span class="text-[11px] px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400 font-semibold">{{ ucfirst($tx->status) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[11px] text-slate-400 whitespace-nowrap text-right">{{ $tx->created_at->format('d M y, H:i') }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                <a href="{{ route('admin.transactions.show', ['type' => 'service', 'id' => $tx->id]) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg text-vtu-primary bg-indigo-50 hover:bg-vtu-primary hover:text-white dark:bg-slate-800 dark:text-indigo-400 dark:hover:bg-vtu-primary dark:hover:text-white transition-colors">
                                    <span>Details &rarr;</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">No service transactions found for this user.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Wallet Transactions --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-outfit">Wallet Transactions</h3>
                    @if($transactions && $transactions->total() > 0)
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        {{ $transactions->total() }}
                    </span>
                    @endif
                </div>
                <a href="{{ route('admin.transactions.index', ['tab' => 'wallet', 'search' => $user->email]) }}" class="text-xs font-semibold text-vtu-primary hover:underline flex items-center gap-1">
                    <span>View Full Ledger</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
            <div class="overflow-x-auto custom-scrollbar-light dark:custom-scrollbar">
                <table class="w-full text-sm min-w-[700px]">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reference</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Description</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-400">Type</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Balance After</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Date</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse($transactions as $wt)
                        <tr onclick="window.location='{{ route('admin.transactions.show', ['type' => 'wallet', 'id' => $wt->id]) }}'"
                            class="cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group">
                            <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">
                                <a href="{{ route('admin.transactions.show', ['type' => 'wallet', 'id' => $wt->id]) }}" class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-vtu-primary hover:underline">
                                    {{ substr($wt->reference ?? (string)$wt->id, 0, 16) }}...
                                </a>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400 max-w-[200px] xl:max-w-xs truncate" title="{{ $wt->description }}">
                                {{ $wt->description }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full
                                    {{ $wt->type==='credit' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400' }}">
                                    {{ ucfirst($wt->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs font-bold whitespace-nowrap text-right {{ $wt->type==='credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                {{ $wt->type==='credit' ? '+' : '-' }}₦{{ number_format($wt->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-xs font-semibold text-slate-900 dark:text-white whitespace-nowrap text-right font-mono">
                                ₦{{ number_format($wt->balance_after, 2) }}
                            </td>
                            <td class="px-4 py-3 text-[11px] text-slate-400 whitespace-nowrap text-right">
                                {{ $wt->created_at->format('d M y, H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                <a href="{{ route('admin.transactions.show', ['type' => 'wallet', 'id' => $wt->id]) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg text-vtu-primary bg-indigo-50 hover:bg-vtu-primary hover:text-white dark:bg-slate-800 dark:text-indigo-400 dark:hover:bg-vtu-primary dark:hover:text-white transition-colors">
                                    <span>Details &rarr;</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400">No wallet transactions found for this user.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions?->hasPages())
            <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800">
                {{ $transactions->links() }}
            </div>
            @endif
        </div>

        {{-- User Login History --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm mt-5">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-outfit">Login History</h3>
                <a href="{{ route('admin.user-logs.index', ['search' => $user->email]) }}" class="text-xs font-medium text-vtu-primary hover:underline">
                    View All User Logs →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Channel</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Device & Browser</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">IP Address</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        @forelse($loginLogs as $log)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @if($log->channel === 'mobile')
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300">
                                    📱 Mobile App
                                </span>
                                @else
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                                    💻 Website
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-xs text-slate-600 dark:text-slate-400">
                                <span class="font-medium text-slate-800 dark:text-slate-200">{{ $log->device_type }}</span>
                                <span class="text-slate-400">•</span> {{ $log->browser }}
                            </td>
                            <td class="px-4 py-2.5 text-xs font-mono text-slate-500">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                @if($log->status === 'success')
                                <span class="text-[11px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 font-semibold">Success</span>
                                @elseif($log->status === 'otp_pending')
                                <span class="text-[11px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 font-semibold">OTP Pending</span>
                                @else
                                <span class="text-[11px] px-1.5 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400 font-semibold">Failed</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-[11px] text-slate-400 whitespace-nowrap">{{ $log->created_at->format('d M y, H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">No login activity recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($loginLogs?->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800">
                {{ $loginLogs->appends(['log_page' => request('log_page')])->links() }}
            </div>
            @endif
        </div>

    </div>

</div>

@endsection

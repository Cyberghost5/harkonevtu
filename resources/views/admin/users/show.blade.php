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

        {{-- Dedicated Virtual Accounts (DVA) --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Virtual Accounts (DVA)</p>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $user->virtualAccounts->isNotEmpty() ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                        {{ $user->virtualAccounts->count() }}
                    </span>
                </div>
                <button type="button" onclick="openDvaModal()"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-vtu-primary bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $user->virtualAccounts->isEmpty() ? 'Generate DVA' : 'Add / Generate' }}
                </button>
            </div>

            @if($user->virtualAccounts->isNotEmpty())
                <div class="space-y-3">
                    @foreach($user->virtualAccounts as $va)
                    <div class="relative rounded-xl border border-slate-200 dark:border-slate-800 p-3 bg-slate-50/70 dark:bg-slate-800/40 hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                @if($va->provider === 'paystack')
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400
                                @elseif($va->provider === 'payscribe')
                                    bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-400
                                @elseif($va->provider === 'monnify')
                                    bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400
                                @else
                                    bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400
                                @endif">
                                {{ $va->providerLabel() }}
                            </span>
                            
                            {{-- Delete action --}}
                            <form method="POST" action="{{ route('admin.users.virtual-accounts.destroy', [$user, $va]) }}"
                                  onsubmit="return confirm('Unlink this virtual account ({{ $va->account_number }}) from user?');" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-rose-500 p-0.5 transition-colors" title="Unlink Account">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ $va->bank_name }}</p>
                                <p class="text-base font-mono font-bold tracking-wider text-slate-900 dark:text-white select-all">
                                    {{ $va->account_number }}
                                </p>
                            </div>
                            <button type="button" onclick="copyDvaText('{{ $va->account_number }}', this)"
                                    class="p-1.5 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-500 hover:text-vtu-primary hover:border-vtu-primary transition-all flex items-center justify-center flex-shrink-0"
                                    title="Copy Account Number">
                                <svg class="w-4 h-4 copy-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1 truncate">Account Name: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $va->account_name }}</span></p>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5 px-3 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-800/20">
                    <div class="h-10 w-10 mx-auto rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-vtu-primary flex items-center justify-center text-lg mb-2">
                        🏦
                    </div>
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-0.5">No Virtual Account Assigned</p>
                    <p class="text-[11px] text-slate-400 max-w-xs mx-auto mb-3">Generate automated personal bank accounts (9PSB, PalmPay, Wema, etc.) for this user.</p>
                    <button type="button" onclick="openDvaModal()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-vtu-primary hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Generate DVA Now
                    </button>
                </div>
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

            {{-- Revoke All Sessions --}}
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>Active Sessions</span>
                    <span class="flex items-center gap-1.5 font-medium">
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 font-semibold" title="Active mobile app login tokens">
                            📱 {{ $activeTokensCount }} Mobile
                        </span>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-semibold" title="Active web browser sessions">
                            💻 {{ $activeSessionsCount }} Web
                        </span>
                    </span>
                </div>
                <form method="POST" action="{{ route('admin.users.revoke-sessions', $user) }}"
                      onsubmit="return confirm('Are you sure you want to log {{ addslashes($user->name) }} out from all devices? All active mobile tokens and web sessions will be terminated immediately.');">
                    @csrf
                    <button type="submit" class="w-full inline-flex justify-center items-center gap-1.5 py-2 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Log Out From All Devices
                    </button>
                </form>
            </div>
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
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap justify-between items-center gap-2">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 font-outfit">Login History</h3>
                    <span class="text-xs text-slate-400">({{ $activeTokensCount + $activeSessionsCount }} active)</span>
                </div>
                <div class="flex items-center gap-3">
                    @if(($activeTokensCount + $activeSessionsCount) > 0)
                    <form method="POST" action="{{ route('admin.users.revoke-sessions', $user) }}"
                          onsubmit="return confirm('Log out {{ addslashes($user->name) }} from all active devices?');" class="inline">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400 inline-flex items-center gap-1 hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Revoke Active Sessions</span>
                        </button>
                    </form>
                    <span class="text-slate-300 dark:text-slate-700">|</span>
                    @endif
                    <a href="{{ route('admin.user-logs.index', ['search' => $user->email]) }}" class="text-xs font-medium text-vtu-primary hover:underline">
                        View All User Logs →
                    </a>
                </div>
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

{{-- ── DVA Generation & Link Modal ────────────────────────────────────────── --}}
<div id="dvaModal" class="fixed inset-0 z-50 bg-black/60 hidden items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-lg w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Dedicated Virtual Account (DVA)</h3>
                <p class="text-xs text-slate-400">Generate or link bank deposit accounts for <strong class="text-slate-700 dark:text-slate-300">{{ $user->name }}</strong></p>
            </div>
            <button type="button" onclick="closeDvaModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg">✕</button>
        </div>

        {{-- Modal Tabs --}}
        <div class="flex gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl mb-4 text-xs font-semibold">
            <button type="button" id="tabBtnGenerate" onclick="switchDvaTab('generate')"
                    class="flex-1 py-1.5 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm transition-all text-center">
                ⚡ Auto-Generate (Live API)
            </button>
            <button type="button" id="tabBtnManual" onclick="switchDvaTab('manual')"
                    class="flex-1 py-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition-all text-center">
                🔗 Link Account Manually
            </button>
        </div>

        {{-- Tab 1: Live API Auto-Generation --}}
        <div id="tabContentGenerate">
            <form method="POST" action="{{ route('admin.users.generate-dva', $user) }}" class="space-y-4"
                  onsubmit="document.getElementById('btnSubmitDva').disabled = true; document.getElementById('btnSubmitDva').innerText = 'Generating via Gateway API...';">
                @csrf

                {{-- User summary card --}}
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 text-xs space-y-1">
                    <div class="flex justify-between text-slate-500">
                        <span>Account Holder:</span>
                        <strong class="text-slate-800 dark:text-slate-200">{{ $user->name }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Phone / Email:</span>
                        <span class="text-slate-700 dark:text-slate-300 font-mono">{{ $user->phone }} &bull; {{ $user->email }}</span>
                    </div>
                </div>

                {{-- Gateway selection --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                        Select Payment Gateway
                    </label>
                    <select name="provider" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        <option value="all">All Active Providers (Auto-provision best available)</option>
                        <option value="payscribe">PayScribe (9PSB & PalmPay)</option>
                        <option value="monnify">Monnify (Wema / Providus / Fidelity)</option>
                        <option value="paystack">Paystack (Wema Bank & Titan)</option>
                        <option value="flutterwave">Flutterwave</option>
                    </select>
                </div>

                {{-- BVN Input --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                        Customer BVN (11 Digits) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="bvn" required maxlength="11" minlength="11" pattern="\d{11}"
                           placeholder="Enter customer's 11-digit BVN"
                           class="w-full px-3 py-2.5 text-base font-mono tracking-widest rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    <p class="text-[11px] text-slate-400 mt-1.5 flex items-start gap-1">
                        <span>ℹ️</span>
                        <span>Under CBN regulations, a valid BVN is strictly required by licensed banks to create and bind dedicated NUBAN virtual accounts.</span>
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeDvaModal()" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitDva" class="px-5 py-2 text-xs font-bold text-white bg-vtu-primary hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">
                        Generate Account Now
                    </button>
                </div>
            </form>
        </div>

        {{-- Tab 2: Manual Account Assignment --}}
        <div id="tabContentManual" class="hidden">
            <form method="POST" action="{{ route('admin.users.assign-dva', $user) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Gateway Provider</label>
                    <select name="provider" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
                        <option value="payscribe">PayScribe</option>
                        <option value="monnify">Monnify</option>
                        <option value="paystack">Paystack</option>
                        <option value="flutterwave">Flutterwave</option>
                        <option value="safehaven">SafeHaven</option>
                        <option value="manual">Manual Bank Account</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Bank Name</label>
                        <input type="text" name="bank_name" required placeholder="e.g. 9PSB or PalmPay"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Bank Code (Optional)</label>
                        <input type="text" name="bank_code" placeholder="e.g. 120001"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Account Number (10 Digits)</label>
                    <input type="text" name="account_number" required maxlength="12" minlength="9" placeholder="e.g. 9012345678"
                           class="w-full px-3 py-2 text-base font-mono tracking-wider rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Account Name</label>
                    <input type="text" name="account_name" required value="{{ $user->name }}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none">
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeDvaModal()" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-vtu-primary hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">
                        Link Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openDvaModal() {
        const modal = document.getElementById('dvaModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDvaModal() {
        const modal = document.getElementById('dvaModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function switchDvaTab(tab) {
        const btnGen = document.getElementById('tabBtnGenerate');
        const btnMan = document.getElementById('tabBtnManual');
        const contentGen = document.getElementById('tabContentGenerate');
        const contentMan = document.getElementById('tabContentManual');

        if (tab === 'generate') {
            btnGen.className = 'flex-1 py-1.5 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm transition-all text-center';
            btnMan.className = 'flex-1 py-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition-all text-center';
            contentGen.classList.remove('hidden');
            contentMan.classList.add('hidden');
        } else {
            btnMan.className = 'flex-1 py-1.5 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm transition-all text-center';
            btnGen.className = 'flex-1 py-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition-all text-center';
            contentMan.classList.remove('hidden');
            contentGen.classList.add('hidden');
        }
    }

    function copyDvaText(text, btn) {
        if (!navigator.clipboard) {
            const temp = document.createElement('textarea');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            showDvaCopyFeedback(btn);
            return;
        }

        navigator.clipboard.writeText(text).then(() => {
            showDvaCopyFeedback(btn);
        });
    }

    function showDvaCopyFeedback(btn) {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">✓</span>';
        setTimeout(() => {
            btn.innerHTML = originalHtml;
        }, 1800);
    }
</script>
@endsection


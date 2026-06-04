@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
@php
    $kycColors = [
        'pending'   => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400',
        'submitted' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
        'verified'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400',
        'rejected'  => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400',
    ];
    $kycBadge = $kycColors[$user->kyc_status ?? 'pending'] ?? $kycColors['pending'];
    $tabs = ['profile' => 'Profile', 'account-details' => 'Account Details', 'account' => 'Change Password', 'transactions' => 'Change PIN', 'api' => 'API'];
@endphp

<div x-data="{ tab: @js($tab) }" class="max-w-7xl mx-auto">

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Settings</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Adjust settings to your account.</p>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400">
        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if ($errors->any() && !$errors->has('old_pin'))
    <div class="mb-5 flex items-start gap-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 px-4 py-3 text-sm text-rose-700 dark:text-rose-400">
        <svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <ul class="space-y-0.5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        {{-- ══ LEFT PROFILE CARD ══════════════════════════════════════════════ --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

            {{-- Avatar + name --}}
            <div class="flex flex-col items-center px-6 pt-8 pb-6 bg-slate-50/60 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800">
                <div class="relative group mb-4">
                    <div class="h-24 w-24 rounded-2xl overflow-hidden ring-4 ring-white dark:ring-slate-900 shadow-md">
                        @if ($user->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" alt="Avatar" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full flex items-center justify-center text-2xl font-bold text-white"
                                 style="background: linear-gradient(135deg, {{ $themeColor }}, {{ $themeSecondary }})">
                                {{ $user->initials() }}
                            </div>
                        @endif
                    </div>
                    {{-- Avatar upload trigger --}}
                    <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" id="avatar-form">
                        @csrf @method('PUT')
                        <label class="absolute -bottom-1 -right-1 h-7 w-7 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center cursor-pointer shadow hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors" title="Change photo">
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <input type="file" name="avatar" accept="image/*" class="hidden" onchange="this.form.submit()">
                        </label>
                        {{-- Hidden fields so this quick-upload form doesn't clear other fields --}}
                        <input type="hidden" name="username" value="{{ $user->username }}">
                        <input type="hidden" name="name" value="{{ $user->name }}">
                    </form>
                </div>
                <p class="font-bold text-slate-900 dark:text-white text-base">{{ $user->displayName() }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $user->name }}</p>
            </div>

            {{-- Stats --}}
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Balance</span>
                    <span class="font-bold text-slate-900 dark:text-white">₦{{ number_format((float)($wallet?->balance ?? 0), 2) }}</span>
                </div>
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Referrals</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $referrals }}</span>
                </div>
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span class="text-slate-500 dark:text-slate-400">Referral Balance</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">₦{{ number_format((float)($wallet?->referral_balance ?? 0), 2) }}</span>
                </div>
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span class="text-slate-500 dark:text-slate-400">KYC Status</span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $kycBadge }}">{{ ucfirst($user->kyc_status ?? 'pending') }}</span>
                </div>
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span class="text-slate-500 dark:text-slate-400">User Type</span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $user->isAgent() ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400' }}">
                        {{ $user->isAgent() ? 'Agent' : 'Normal' }}
                    </span>
                </div>
            </div>

            {{-- Quick-action buttons --}}
            <div class="p-4 space-y-2 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('referral') }}"
                   class="flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-sm font-bold text-white transition-opacity hover:opacity-90"
                   style="background: {{ $themeColor }}">
                    View Referrals
                </a>
                <button @click="tab = 'transactions'"
                        class="flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-sm font-bold text-white transition-opacity hover:opacity-90"
                        style="background: {{ $themeColor }}">
                    Change PIN
                </button>
                <button @click="tab = 'account'"
                        class="flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-sm font-bold text-white transition-opacity hover:opacity-90"
                        style="background: {{ $themeColor }}">
                    Change Password
                </button>
                <button x-data
                        @click="$dispatch('open-delete-modal')"
                        class="flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-rose-500 hover:bg-rose-600 transition-colors">
                    Delete Account
                </button>
            </div>
        </div>

        {{-- ══ RIGHT TABBED FORMS ══════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

            {{-- Tabs --}}
            <div class="flex border-b border-slate-200 dark:border-slate-800 overflow-x-auto">
                @foreach ($tabs as $key => $label)
                <button @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-b-2 text-[color:var(--vtu-primary)] font-semibold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                        class="flex-shrink-0 px-5 py-3.5 text-sm border-b-2 border-transparent transition-colors"
                        :style="tab === '{{ $key }}' ? 'border-color: {{ $themeColor }}' : ''">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            <div class="p-6">

                {{-- ── Profile Tab ───────────────────────────────────────── --}}
                <div x-show="tab === 'profile'" x-cloak>
                    <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="space-y-5">

                            {{-- Username --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Username</label>
                                <input type="text" name="username" value="{{ old('username', $user->username) }}"
                                       class="w-full px-4 py-2.5 text-sm border @error('username') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
                                @error('username')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                            </div>

                            {{-- Full Name --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Full Name</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                       class="w-full px-4 py-2.5 text-sm border @error('name') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
                                @error('name')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                            </div>

                            {{-- Email (read-only) --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                                <div class="relative">
                                    <input type="email" value="{{ $user->email }}" readonly disabled
                                           class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-100 dark:bg-slate-800/50 text-slate-400 dark:text-slate-500 cursor-not-allowed">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 bg-slate-100 dark:bg-slate-800/50 px-1.5 py-0.5 rounded">locked</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Contact support to change your email address.</p>
                            </div>

                            {{-- Phone (read-only) --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Phone No.</label>
                                <div class="relative">
                                    <input type="text" value="{{ $user->phone ?? '-' }}" readonly disabled
                                           class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-100 dark:bg-slate-800/50 text-slate-400 dark:text-slate-500 cursor-not-allowed">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 bg-slate-100 dark:bg-slate-800/50 px-1.5 py-0.5 rounded">locked</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Contact support to change your phone number.</p>
                            </div>

                            {{-- Low Balance Notification --}}
                            <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Enable Low Balance Notification</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">A notification will be sent to your email when your balance is less than ₦1,000.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-0.5">
                                    <input type="checkbox" name="low_balance_notification" value="1" class="sr-only peer"
                                           {{ $user->low_balance_notification ? 'checked' : '' }}
                                           onchange="this.form.action='{{ route('settings.notification') }}'; this.form.submit()">
                                    <div class="w-10 h-6 bg-slate-300 peer-focus:ring-2 peer-focus:ring-[color:var(--vtu-primary)]/30 dark:bg-slate-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[color:var(--vtu-primary)]"></div>
                                </label>
                            </div>

                            <div class="pt-2">
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-opacity hover:opacity-90"
                                        style="background: {{ $themeColor }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- ── Account Details Tab (Bank Info) ───────────────────── --}}
                <div x-show="tab === 'account-details'" x-cloak>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white mb-1">Payout Bank Details</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Used for Airtime-to-Cash and manual withdrawals.</p>
                    <form method="POST" action="{{ route('settings.bank.update') }}" class="space-y-5">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $user->bank_name) }}" placeholder="e.g. GTBank"
                                   class="w-full px-4 py-2.5 text-sm border @error('bank_name') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
                            @error('bank_name')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Account Number</label>
                            <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}" placeholder="10-digit account number" maxlength="10"
                                   class="w-full px-4 py-2.5 text-sm border @error('bank_account_number') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
                            @error('bank_account_number')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Account Name</label>
                            <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $user->bank_account_name) }}" placeholder="Name as on your bank account"
                                   class="w-full px-4 py-2.5 text-sm border @error('bank_account_name') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
                            @error('bank_account_name')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-opacity hover:opacity-90" style="background: {{ $themeColor }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                Save Bank Details
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ── Transactions Tab (Change PIN / Reset PIN) ──────────── --}}
                <div x-show="tab === 'transactions'" x-cloak x-data="{ form: '{{ session('active_form', 'change_pin') }}' }">

                    {{-- Sub-tabs --}}
                    <div class="flex gap-1 p-1 rounded-xl bg-slate-100 dark:bg-slate-800 w-fit mb-6">
                        <button @click="form = 'change_pin'" :class="form === 'change_pin' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all">Change PIN</button>
                        <button @click="form = 'reset_pin'" :class="form === 'reset_pin' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all">Reset PIN</button>
                    </div>

                    {{-- Change PIN Form --}}
                    <div x-show="form === 'change_pin'">
                        <form method="POST" action="{{ route('settings.pin.change') }}" class="space-y-5 max-w-sm">
                            @csrf @method('PUT')
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Current PIN</label>
                                <input type="password" name="old_pin" maxlength="4" inputmode="numeric" autocomplete="current-password"
                                       class="w-full px-4 py-2.5 text-sm border @error('old_pin') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] tracking-[0.5em] text-center font-bold placeholder:tracking-normal placeholder:font-normal" placeholder="••••">
                                @error('old_pin')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">New PIN</label>
                                <input type="password" name="new_pin" maxlength="4" inputmode="numeric" autocomplete="new-password"
                                       class="w-full px-4 py-2.5 text-sm border @error('new_pin') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] tracking-[0.5em] text-center font-bold placeholder:tracking-normal placeholder:font-normal" placeholder="••••">
                                @error('new_pin')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Confirm New PIN</label>
                                <input type="password" name="confirm_pin" maxlength="4" inputmode="numeric" autocomplete="new-password"
                                       class="w-full px-4 py-2.5 text-sm border @error('confirm_pin') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] tracking-[0.5em] text-center font-bold placeholder:tracking-normal placeholder:font-normal" placeholder="••••">
                                @error('confirm_pin')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                            </div>
                            <div class="pt-2">
                                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-opacity hover:opacity-90" style="background: {{ $themeColor }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Save New PIN
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Reset PIN (request email) --}}
                    <div x-show="form === 'reset_pin'">
                        <div class="max-w-sm">
                            <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 p-4 mb-5">
                                <p class="text-sm text-amber-700 dark:text-amber-400 font-medium mb-1">Forgot your PIN?</p>
                                <p class="text-sm text-amber-600 dark:text-amber-500">
                                    Click below and we'll send a secure link to <strong>{{ $user->email }}</strong>.
                                    Use that link to set a brand-new PIN. The link expires in 60 minutes.
                                </p>
                            </div>
                            <form method="POST" action="{{ route('settings.pin.reset.request') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-opacity hover:opacity-90" style="background: {{ $themeColor }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    Send PIN Reset Email
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ── Account Tab (Change Password) ─────────────────────── --}}
                <div x-show="tab === 'account'" x-cloak>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white mb-5">Change Password</h3>
                    <form method="POST" action="{{ route('settings.password.change') }}" class="space-y-5">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Current Password</label>
                            <input type="password" name="current_password" autocomplete="current-password"
                                   class="w-full px-4 py-2.5 text-sm border @error('current_password') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
                            @error('current_password')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">New Password</label>
                            <input type="password" name="password" autocomplete="new-password"
                                   class="w-full px-4 py-2.5 text-sm border @error('password') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
                            @error('password')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Confirm New Password</label>
                            <input type="password" name="password_confirmation" autocomplete="new-password"
                                   class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--vtu-primary)]/30 focus:border-[color:var(--vtu-primary)] transition-colors">
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-opacity hover:opacity-90" style="background: {{ $themeColor }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ── API Tab ────────────────────────────────────────────── --}}
                <div x-show="tab === 'api'" x-cloak>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white mb-1">API Access</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Use this token to access the API programmatically. Keep it secret.</p>

                    @if ($user->api_token)
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Your API Token</label>
                        <div class="flex items-center gap-2">
                            <input type="text" value="{{ $user->api_token }}" readonly id="api-token-input"
                                   class="flex-1 px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono focus:outline-none select-all">
                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('api-token-input').value);this.textContent='Copied!';"
                                    class="flex-shrink-0 px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                Copy
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">Token generated {{ $user->updated_at->diffForHumans() }}</p>
                    </div>
                    @else
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 mb-5 text-center">
                        <svg class="h-10 w-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <p class="text-sm text-slate-500 dark:text-slate-400">No API token yet. Generate one to get started.</p>
                    </div>
                    @endif

                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('settings.api.generate') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white rounded-xl transition-opacity hover:opacity-90" style="background: {{ $themeColor }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                {{ $user->api_token ? 'Regenerate Token' : 'Generate Token' }}
                            </button>
                        </form>
                        @if ($user->api_token)
                        <span class="text-xs text-slate-400 dark:text-slate-500">Regenerating will invalidate the existing token immediately.</span>
                        @endif
                    </div>
                </div>

            </div>{{-- /p-6 --}}
        </div>{{-- /right card --}}

    </div>{{-- /grid --}}

</div>

{{-- ══ Delete Account Modal ══════════════════════════════════════════════════ --}}
<div x-data="{ open: false }"
     @open-delete-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true">
    <div @click="open = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div @click.stop class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="h-12 w-12 rounded-xl bg-rose-100 dark:bg-rose-500/10 flex items-center justify-center flex-shrink-0">
                <svg class="h-6 w-6 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white">Delete Account</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">This action is permanent and cannot be undone.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('settings.delete') }}" class="space-y-4">
            @csrf @method('DELETE')
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Confirm your password to continue</label>
                <input type="password" name="confirm_password" placeholder="Enter your password"
                       class="w-full px-4 py-2.5 text-sm border @error('confirm_password') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rose-400/30 focus:border-rose-400 transition-colors">
                @error('confirm_password')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-3 pt-1">
                <button type="submit" class="flex-1 py-2.5 text-sm font-bold text-white bg-rose-500 hover:bg-rose-600 rounded-xl transition-colors">Delete My Account</button>
                <button type="button" @click="open = false" class="flex-1 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

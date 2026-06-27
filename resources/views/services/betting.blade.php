@extends('layouts.dashboard')

@section('title', 'Betting Wallet')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{
    platform: '{{ old('platform', '') }}',
    customerId: '{{ old('customer_id', '') }}',
    customerName: '{{ old('customer_name', '') }}',
    amount: '{{ old('amount', '') }}',
    pin: '',
    isValidated: false,
    isValidating: false,
    errorMessage: '',

    init() {
        if (this.customerName !== '') {
            this.isValidated = true;
        }
        this.$watch('platform', () => this.resetValidation());
        this.$watch('customerId', () => this.resetValidation());
    },

    resetValidation() {
        this.isValidated = false;
        this.customerName = '';
        this.errorMessage = '';
    },

    validateCustomer() {
        if (!this.platform || !this.customerId) {
            this.errorMessage = 'Please select a betting platform and enter Customer ID.';
            return;
        }

        this.isValidating = true;
        this.errorMessage = '';

        fetch('{{ route('services.betting.validate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                platform: this.platform,
                customer_id: this.customerId
            })
        })
        .then(response => response.json())
        .then(data => {
            this.isValidating = false;
            if (data.error) {
                this.errorMessage = data.error;
                this.isValidated = false;
            } else {
                this.customerName = data.customer_name;
                this.isValidated = true;
            }
        })
        .catch(err => {
            this.isValidating = false;
            this.errorMessage = 'Network error occurred. Please try again.';
            this.isValidated = false;
        });
    }
}">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Fund Betting Wallet</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Fund your sports betting account instantly. Validate your account name before purchasing.
        </p>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Betting Fund Form (2 cols) ═════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">New Funding Request</h2>
                </div>

                <form method="POST" action="{{ route('services.betting.purchase') }}" class="p-6 space-y-5">
                    @csrf

                    {{-- Platform Selector --}}
                    <div>
                        <label for="platform" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Betting Platform <span class="text-red-500">*</span>
                        </label>
                        <select id="platform" name="platform" x-model="platform" required
                                class="w-full px-3 py-3.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            <option value="">-- Choose Platform --</option>
                            @foreach($platforms as $p)
                                <option value="{{ $p->slug }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('platform')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Customer ID --}}
                    <div>
                        <label for="customer_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Customer ID / User ID <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input id="customer_id" type="text" name="customer_id" x-model="customerId" required placeholder="e.g. 1234567"
                                   class="flex-1 px-3.5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"/>
                            
                            <button type="button" @click="validateCustomer()" :disabled="!platform || !customerId || isValidating"
                                    class="px-5 py-3 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 text-indigo-650 dark:text-indigo-400 font-semibold rounded-xl text-sm transition flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="isValidating">
                                    <svg class="animate-spin h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                Validate
                            </button>
                        </div>
                        @error('customer_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Customer Name (Validated) --}}
                    <div x-show="isValidated" x-transition class="p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/25 rounded-xl">
                        <label class="block text-[10px] uppercase font-bold text-emerald-650 dark:text-emerald-400 tracking-wider">Account Owner Name</label>
                        <p class="mt-0.5 text-sm font-bold text-emerald-800 dark:text-emerald-300" x-text="customerName"></p>
                        <input type="hidden" name="customer_name" :value="customerName"/>
                    </div>

                    {{-- Validation Error alert --}}
                    <div x-show="errorMessage" x-transition class="p-3.5 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/25 rounded-xl text-xs text-rose-600 dark:text-rose-450" x-text="errorMessage"></div>

                    {{-- Amount --}}
                    <div>
                        <label for="amount" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Amount to Fund <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">₦</span>
                            <input id="amount" type="number" name="amount" x-model="amount" required min="{{ $minAmount }}" placeholder="Min {{ number_format($minAmount) }}"
                                   class="w-full pl-8 pr-3.5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"/>
                        </div>
                        @error('amount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Details Info --}}
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl space-y-2 text-xs">
                        <div class="flex justify-between text-slate-500 dark:text-slate-400">
                            <span>Convenience Fee:</span>
                            <span class="font-semibold text-slate-900 dark:text-white">₦{{ number_format($charge, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-500 dark:text-slate-400">
                            <span>Daily Limit Remaining:</span>
                            <span class="font-semibold text-slate-900 dark:text-white">₦{{ number_format($dailyLimit, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold border-t border-slate-200 dark:border-slate-700 pt-2 text-slate-800 dark:text-white">
                            <span>Total Wallet Debit:</span>
                            <span class="text-indigo-600 dark:text-indigo-400 font-outfit" x-text="'₦' + ((parseFloat(amount) || 0) + {{ $charge }}).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})">₦0.00</span>
                        </div>
                    </div>

                    {{-- PIN and Submit --}}
                    <div class="space-y-4" x-show="isValidated" x-transition>
                        <div>
                            <label for="pin" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                Transaction PIN <span class="text-red-500">*</span>
                            </label>
                            <input id="pin" type="password" name="pin" x-model="pin" maxlength="4" required placeholder="Enter 4-digit PIN"
                                   class="w-full px-3.5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white tracking-widest text-center focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"/>
                            @error('pin')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" :disabled="!isValidated || !amount || pin.length !== 4"
                                class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-750 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold rounded-xl shadow-sm transition-all duration-150 flex items-center justify-center gap-2">
                            Fund Wallet Now
                        </button>
                    </div>

                </form>
            </div>
        </div>

        {{-- ══ RIGHT: Recent Transactions History (3 cols) ══════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="bg-white dark:bg-vtu-darkCard rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden flex flex-col h-full">
                
                {{-- Header --}}
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Betting Logs</h2>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="px-6 py-3.5">Details</th>
                                <th class="px-6 py-3.5">Account ID</th>
                                <th class="px-6 py-3.5">Amount</th>
                                <th class="px-6 py-3.5">Date</th>
                                <th class="px-6 py-3.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            @forelse($recentTx as $tx)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-700 dark:text-slate-300 uppercase">{{ $tx->provider }}</span>
                                    <p class="text-[10px] text-slate-450 mt-0.5 font-mono select-all">{{ $tx->reference }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $tx->recipient }}</span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">₦{{ number_format($tx->amount, 2) }}</td>
                                <td class="px-6 py-4 text-slate-450">{{ $tx->created_at->format('d M Y, h:ia') }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full
                                        {{ $tx->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-450' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-450' }}">
                                        {{ ucfirst($tx->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    No betting transactions yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($recentTx->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $recentTx->links() }}
                </div>
                @endif

            </div>
        </div>

    </div>

</div>
@endsection

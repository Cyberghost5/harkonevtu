@extends('layouts.dashboard')

@section('title', 'Convert Airtime to Cash')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
    network: '',
    amount: '',
    chargePercent: {{ $settings['airtime2cash_tx_charge'] ?? 20 }},
    get receiveAmount() {
        let val = parseFloat(this.amount) || 0;
        let fee = (val * this.chargePercent) / 100;
        let rec = val - fee;
        return rec > 0 ? rec : 0;
    }
}">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Convert Airtime to Cash</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Convert your excess or mistakenly loaded airtime to cash transferred directly to your wallet.
        </p>
    </div>

    @if(!$settings['airtime2cash_phone'])
    <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-800 dark:text-amber-400 text-sm">
        <strong>Notice:</strong> This service is currently unavailable as the administrator has not configured a receiver phone number. Please check back later.
    </div>
    @endif

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Form (3 cols) ══════════════════════════════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Conversion Details</h2>
                </div>

                <form method="POST" action="{{ route('services.airtime-to-cash.submit') }}" enctype="multipart/form-data" class="p-6 space-y-5">
                    @csrf

                    {{-- Network Selector --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            Select Network <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" name="network" :value="network"/>
                        <div class="grid grid-cols-4 gap-3">
                            @foreach([
                                'mtn' => ['bg' => 'bg-yellow-400', 'text' => 'text-black', 'border' => 'border-yellow-400', 'name' => 'MTN'],
                                'airtel' => ['bg' => 'bg-red-500', 'text' => 'text-white', 'border' => 'border-red-400', 'name' => 'Airtel'],
                                'glo' => ['bg' => 'bg-green-500', 'text' => 'text-white', 'border' => 'border-green-500', 'name' => 'Glo'],
                                '9mobile' => ['bg' => 'bg-teal-700', 'text' => 'text-white', 'border' => 'border-teal-600', 'name' => '9Mobile']
                            ] as $key => $net)
                            <button type="button" @click="network = '{{ $key }}'"
                                    class="relative flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all duration-150 group"
                                    :class="network === '{{ $key }}' ? 'border-indigo-500 dark:border-indigo-400 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-700 hover:{{ $net['border'] }}'">
                                <div class="h-10 w-10 rounded-xl {{ $net['bg'] }} flex items-center justify-center shadow-sm">
                                    <span class="text-[10px] font-black {{ $net['text'] }} leading-none">{{ strtoupper(substr($net['name'],0,3)) }}</span>
                                </div>
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white">{{ $net['name'] }}</span>
                                
                                <div class="absolute top-1.5 right-1.5 h-4 w-4 rounded-full bg-indigo-600 flex items-center justify-center" x-show="network === '{{ $key }}'">
                                    <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        @error('network')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Sender Phone Number --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Sender Phone Number (Your Line) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required placeholder="e.g. 08012345678"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"/>
                        </div>
                        @error('phone')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label for="amount" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Airtime Amount (₦) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="text-slate-400 font-medium text-sm">₦</span>
                            </div>
                            <input id="amount" type="number" name="amount" x-model="amount" required placeholder="Enter amount to convert"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"/>
                        </div>
                        @error('amount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Screenshot --}}
                    <div>
                        <label for="screenshot" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Upload Proof of Transfer (Screenshot) <span class="text-red-500">*</span>
                        </label>
                        <input id="screenshot" type="file" name="screenshot" accept="image/*" required
                               class="w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-500/10 dark:file:text-indigo-400 hover:file:opacity-90"/>
                        @error('screenshot')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" :disabled="!network || !amount"
                            class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-750 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold rounded-xl shadow-sm transition-all duration-150 flex items-center justify-center gap-2">
                        Submit Conversion Request
                    </button>

                </form>
            </div>
        </div>

        {{-- ══ RIGHT: Guidelines & Summary (2 cols) ═════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Recipient Info Box --}}
            <div class="p-6 rounded-2xl bg-gradient-to-tr from-indigo-900 to-indigo-850 text-white shadow-sm space-y-4">
                <h3 class="text-base font-bold font-outfit">Transfer Instructions</h3>
                
                @if($settings['airtime2cash_phone'])
                <div class="bg-indigo-950/40 rounded-xl p-4 border border-indigo-500/25">
                    <p class="text-[10px] text-indigo-300 font-semibold uppercase tracking-wider mb-0.5">Transfer Airtime To:</p>
                    <p class="text-lg font-mono font-bold tracking-wider select-all">{{ $settings['airtime2cash_phone'] }}</p>
                </div>
                @endif

                <div class="space-y-2 text-xs text-indigo-200">
                    <div class="flex justify-between border-b border-indigo-800/40 pb-2">
                        <span>Conversion Fee:</span>
                        <span class="font-semibold text-white">{{ $settings['airtime2cash_tx_charge'] ?? 20 }}%</span>
                    </div>
                    <div class="flex justify-between border-b border-indigo-800/40 pb-2">
                        <span>Min. Per Request:</span>
                        <span class="font-semibold text-white">₦{{ number_format($settings['airtime2cash_min_per_payment'] ?? 500) }}</span>
                    </div>
                    <div class="flex justify-between pb-1">
                        <span>Max. Per Request:</span>
                        <span class="font-semibold text-white">₦{{ number_format($settings['airtime2cash_max_per_payment'] ?? 50000) }}</span>
                    </div>
                </div>
            </div>

            {{-- Calculations Summary --}}
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 p-6 space-y-4 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider">Calculation</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Airtime Value:</span>
                        <span class="font-medium text-slate-900 dark:text-white" x-text="'₦' + (parseFloat(amount) || 0).toLocaleString()">₦0</span>
                    </div>
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Service Charge (<span x-text="chargePercent"></span>%):</span>
                        <span class="font-medium text-red-500" x-text="'- ₦' + ((parseFloat(amount) || 0) * chargePercent / 100).toLocaleString()">- ₦0</span>
                    </div>
                    <div class="border-t border-slate-100 dark:border-slate-700 my-2"></div>
                    <div class="flex justify-between text-base font-bold">
                        <span class="text-slate-800 dark:text-white">You'll Receive:</span>
                        <span class="text-indigo-600 dark:text-indigo-400 font-outfit" x-text="'₦' + receiveAmount.toLocaleString()">₦0</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Previous Requests History ────────────────────────────────────── --}}
    <div class="bg-white dark:bg-vtu-darkCard rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Conversion History</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Date</th>
                        <th class="px-6 py-3.5">Details</th>
                        <th class="px-6 py-3.5">Airtime Amount</th>
                        <th class="px-6 py-3.5">Cash Credit</th>
                        <th class="px-6 py-3.5">Proof</th>
                        <th class="px-6 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    @forelse($requests as $req)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="px-6 py-4 text-slate-500 truncate">{{ $req->created_at->format('M d, Y h:i A') }}</td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-700 dark:text-slate-300 uppercase">{{ $req->network }}</span>
                            <p class="text-[10px] text-slate-400 mt-0.5">From: {{ $req->phone }}</p>
                        </td>
                        <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">₦{{ number_format($req->amount, 2) }}</td>
                        <td class="px-6 py-4 font-bold text-indigo-600 dark:text-indigo-400">₦{{ number_format($req->receive_amount, 2) }}</td>
                        <td class="px-6 py-4">
                            @if($req->screenshot)
                            <a href="{{ Storage::url($req->screenshot) }}" target="_blank" class="text-indigo-500 hover:underline">View Receipt</a>
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                @if($req->status === 'approved') bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400
                                @elseif($req->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400
                                @else bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400
                                @endif">
                                {{ ucfirst($req->status) }}
                            </span>
                            @if($req->admin_note)
                            <p class="text-[10px] text-slate-400 mt-1">Note: {{ $req->admin_note }}</p>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                            No airtime conversion requests submitted yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@extends('layouts.dashboard')

@section('title', 'Voucher Printing')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{
    type: '{{ $type }}',
    network: '',
    value: '',
    quantity: '1',
    nameOnCard: '',
    selectedIds: [],
    selectAll: false,
    toggleAll() {
        this.selectAll = !this.selectAll;
        if (this.selectAll) {
            this.selectedIds = Array.from(document.querySelectorAll('.row-checkbox')).map(el => el.value);
        } else {
            this.selectedIds = [];
        }
    },
    toggleRow(id) {
        if (this.selectedIds.includes(id)) {
            this.selectedIds = this.selectedIds.filter(x => x !== id);
        } else {
            this.selectedIds.push(id);
        }
        this.selectAll = this.selectedIds.length === document.querySelectorAll('.row-checkbox').length;
    }
}">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Voucher PIN Printing</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Generate and print bulk recharge card and data vouchers with your custom business name.
        </p>
    </div>

    {{-- Tabs --}}
    <div class="flex border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('services.print-pins', ['type' => 'airtime']) }}"
           class="px-5 py-3 text-sm font-semibold border-b-2 transition-all"
           :class="type === 'airtime' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-white'">
            Airtime Vouchers
        </a>
        <a href="{{ route('services.print-pins', ['type' => 'data']) }}"
           class="px-5 py-3 text-sm font-semibold border-b-2 transition-all"
           :class="type === 'data' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-white'">
            Data Vouchers
        </a>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Generator Form (2 cols) ════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Generate Vouchers</h2>
                </div>

                <form method="POST" action="{{ route('services.print-pins.generate') }}" class="p-6 space-y-5">
                    @csrf
                    <input type="hidden" name="type" :value="type"/>

                    {{-- Network Selector --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            Select Network <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" name="network" :value="network"/>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach([
                                'mtn' => ['bg' => 'bg-yellow-400', 'text' => 'text-black', 'border' => 'border-yellow-400', 'name' => 'MTN'],
                                'airtel' => ['bg' => 'bg-red-500', 'text' => 'text-white', 'border' => 'border-red-400', 'name' => 'Airtel'],
                                'glo' => ['bg' => 'bg-green-500', 'text' => 'text-white', 'border' => 'border-green-500', 'name' => 'Glo'],
                                '9mobile' => ['bg' => 'bg-teal-700', 'text' => 'text-white', 'border' => 'border-teal-600', 'name' => '9Mobile']
                            ] as $key => $net)
                            <button type="button" @click="network = '{{ $key }}'"
                                    class="relative flex flex-col items-center gap-1.5 py-2.5 px-2 rounded-xl border-2 transition-all duration-150 group"
                                    :class="network === '{{ $key }}' ? 'border-indigo-500 dark:border-indigo-400 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-700 hover:{{ $net['border'] }}'">
                                <div class="h-8 w-8 rounded-lg {{ $net['bg'] }} flex items-center justify-center shadow-sm">
                                    <span class="text-[9px] font-black {{ $net['text'] }} leading-none">{{ strtoupper(substr($net['name'],0,3)) }}</span>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white">{{ $net['name'] }}</span>
                                
                                <div class="absolute top-1 right-1 h-3.5 w-3.5 rounded-full bg-indigo-600 flex items-center justify-center" x-show="network === '{{ $key }}'">
                                    <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        @error('network')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Denomination / Value --}}
                    <div>
                        <label for="value" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Voucher Value <span class="text-red-500">*</span>
                        </label>
                        <select id="value" name="value" x-model="value" required
                                class="w-full px-3 py-3.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            <option value="">-- Choose Value --</option>
                            <template x-if="type === 'airtime'">
                                <optgroup label="Airtime Denominations">
                                    <option value="100">₦100</option>
                                    <option value="200">₦200</option>
                                    <option value="500">₦500</option>
                                    <option value="1000">₦1,000</option>
                                </optgroup>
                            </template>
                            <template x-if="type === 'data'">
                                <optgroup label="Data Plans">
                                    <option value="1000">1.5GB (₦1,000)</option>
                                    <option value="1200">2GB (₦1,200)</option>
                                    <option value="1500">3GB (₦1,500)</option>
                                    <option value="2000">5GB (₦2,000)</option>
                                </optgroup>
                            </template>
                        </select>
                        @error('value')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Quantity --}}
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Quantity to Generate <span class="text-red-500">*</span>
                        </label>
                        <input id="quantity" type="number" name="quantity" min="1" max="50" x-model="quantity" required
                               class="w-full px-3.5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"/>
                        <p class="mt-1 text-[10px] text-slate-400">Min 1, Max 50 per batch.</p>
                        @error('quantity')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Name on Card --}}
                    <div>
                        <label for="name_on_card" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Custom Business Name (Prints on voucher)
                        </label>
                        <input id="name_on_card" type="text" name="name_on_card" x-model="nameOnCard" placeholder="e.g. Joy Telecoms"
                               class="w-full px-3.5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"/>
                        @error('name_on_card')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Cost Summary --}}
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl space-y-2">
                        <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Cost per voucher:</span>
                            <span class="font-semibold text-slate-900 dark:text-white" x-text="value ? '₦' + parseFloat(value).toLocaleString() : '₦0'">₦0</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold border-t border-slate-200 dark:border-slate-700 pt-2">
                            <span class="text-slate-800 dark:text-white">Total Wallet Debit:</span>
                            <span class="text-indigo-600 dark:text-indigo-400 font-outfit" x-text="'₦' + ((parseFloat(value) || 0) * (parseInt(quantity) || 0)).toLocaleString()">₦0</span>
                        </div>
                    </div>

                    <button type="submit" :disabled="!network || !value || !quantity"
                            class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-750 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold rounded-xl shadow-sm transition-all duration-150 flex items-center justify-center gap-2">
                        Generate & Pay
                    </button>

                </form>
            </div>
        </div>

        {{-- ══ RIGHT: Generated Vouchers List (3 cols) ══════════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="bg-white dark:bg-vtu-darkCard rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden flex flex-col h-full">
                
                {{-- Header --}}
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Generated PINs</h2>

                    {{-- Print selected --}}
                    <form method="GET" action="{{ route('services.print-pins.print') }}" target="_blank" x-show="selectedIds.length > 0" class="inline-block transition-all">
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="ids[]" :value="id"/>
                        </template>
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-indigo-650 hover:bg-indigo-700 text-white rounded-xl shadow-sm">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2v5a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Print Selected (<span x-text="selectedIds.length"></span>)
                        </button>
                    </form>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="px-6 py-3.5 w-12 text-center">
                                    <input type="checkbox" @click="toggleAll()" :checked="selectAll" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                </th>
                                <th class="px-6 py-3.5">Details</th>
                                <th class="px-6 py-3.5">Pin / Serial</th>
                                <th class="px-6 py-3.5">Value</th>
                                <th class="px-6 py-3.5">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            @forelse($vouchers as $v)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" :checked="selectedIds.includes('{{ $v->id }}')" @click="toggleRow('{{ $v->id }}')" value="{{ $v->id }}" class="row-checkbox rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-700 dark:text-slate-300 uppercase">{{ $v->network }}</span>
                                    <p class="text-[10px] text-slate-400 mt-0.5 truncate">{{ $v->name_on_card ?: 'Default' }}</p>
                                </td>
                                <td class="px-6 py-4 font-mono">
                                    <span class="font-bold tracking-widest text-slate-900 dark:text-white">{{ $v->pin }}</span>
                                    <p class="text-[10px] text-slate-400 mt-0.5">S/N: {{ $v->serial_number }}</p>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">₦{{ number_format($v->value, 2) }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('services.print-pins.print', ['ids' => [$v->id]]) }}" target="_blank"
                                       class="inline-flex items-center gap-1 text-[10px] font-semibold text-indigo-500 hover:text-indigo-700">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2v5a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Print
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    No vouchers generated yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($vouchers->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $vouchers->links() }}
                </div>
                @endif

            </div>
        </div>

    </div>

</div>
@endsection

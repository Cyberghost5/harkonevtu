@extends('layouts.dashboard')

@section('title', 'Exam Pins')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Exam Result Pins</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Buy WAEC, NECO and NABTEB result checker PINs instantly.
        </p>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Purchase Form (3 cols) ══════════════════════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Purchase Details</h2>
                </div>

                <div class="p-6 space-y-5">

                    {{-- Exam Type Selector --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Exam Type <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach($examTypes as $type)
                            <button type="button"
                                    onclick="selectExamType({{ $type->id }}, '{{ addslashes($type->name) }}', {{ (float) $type->amount }}, this)"
                                    data-type-id="{{ $type->id }}"
                                    class="exam-type-btn group flex flex-col items-center gap-2 py-4 px-3 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-vtu-primary transition-all duration-150 text-center">
                                @php
                                    $iconMap = [
                                        'waec'   => '🎓',
                                        'neco'   => '📘',
                                        'nabteb' => '📗',
                                        'nbais'  => '📙',
                                    ];
                                    $colorMap = [
                                        'waec'   => 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400',
                                        'neco'   => 'bg-green-100 dark:bg-green-500/10 text-green-700 dark:text-green-400',
                                        'nabteb' => 'bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400',
                                        'nbais'  => 'bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400',
                                    ];
                                @endphp
                                <div class="h-10 w-10 rounded-xl {{ $colorMap[$type->slug] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-600' }} flex items-center justify-center text-xl">
                                    {{ $iconMap[$type->slug] ?? '📋' }}
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-tight">
                                        {{ $type->name }}
                                    </p>
                                    <p class="mt-0.5 text-xs font-bold text-vtu-primary">
                                        ₦{{ number_format($type->amount, 0) }}/pin
                                    </p>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        <p id="exam-type-error" class="mt-1.5 text-xs text-red-500 hidden">Please select an exam type.</p>
                    </div>

                    {{-- Quantity Selector --}}
                    <div id="quantity-row" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Number of PINs <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="adjustQuantity(-1)"
                                    class="h-10 w-10 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 font-bold text-lg transition flex items-center justify-center">
                                −
                            </button>
                            <div class="flex-1 text-center">
                                <span id="qty-display" class="text-2xl font-bold text-slate-900 dark:text-white">1</span>
                                <p id="qty-total" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Total: ₦0.00</p>
                            </div>
                            <button type="button" onclick="adjustQuantity(1)"
                                    class="h-10 w-10 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 font-bold text-lg transition flex items-center justify-center">
                                +
                            </button>
                        </div>
                        <p id="qty-error" class="mt-1.5 text-xs text-red-500 hidden">Quantity must be between 1 and 10.</p>
                    </div>

                    {{-- Phone Number --}}
                    <div id="phone-row" class="hidden">
                        <label for="phone-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Phone Number <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <input id="phone-input" type="tel" inputmode="numeric" maxlength="14"
                                   placeholder="080XXXXXXXX"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"
                                   oninput="onPhoneInput()"/>
                        </div>
                        <p id="phone-error" class="mt-1.5 text-xs text-red-500 hidden">Enter a valid Nigerian phone number.</p>
                    </div>

                    {{-- Instructions box (shown after exam type selected) --}}
                    <div id="instructions-box" class="hidden rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 p-3.5">
                        <div class="flex gap-2.5">
                            <svg class="h-4 w-4 text-blue-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p id="instructions-text" class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed"></p>
                        </div>
                    </div>

                    {{-- Summary Box --}}
                    <div id="summary-box" class="hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-4 space-y-2.5">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Order Summary</p>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Exam Type</span>
                                <span id="sum-exam-type" class="font-medium text-slate-800 dark:text-slate-200 text-right max-w-[55%]"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Quantity</span>
                                <span id="sum-qty" class="font-medium text-slate-800 dark:text-slate-200"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Total Amount</span>
                                <span id="sum-amount" class="font-semibold text-slate-900 dark:text-white"></span>
                            </div>
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-2 flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Balance After</span>
                                <span id="sum-balance" class="font-semibold"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Buy Button --}}
                    <button type="button" id="buy-btn" onclick="initiatePurchase()" disabled
                            class="w-full py-3.5 rounded-xl text-sm font-semibold text-white bg-vtu-primary hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150 flex items-center justify-center gap-2">
                        <svg id="buy-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span id="buy-btn-label">Buy Pins</span>
                    </button>

                </div>{{-- /p-6 --}}
            </div>{{-- /card --}}
        </div>{{-- /left col --}}

        {{-- ══ RIGHT: Wallet + Info (2 cols) ══════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Wallet Balance --}}
            <div class="rounded-2xl bg-gradient-to-br from-vtu-primary to-vtu-secondary p-5 text-white shadow-lg shadow-indigo-500/20">
                <p class="text-xs font-medium text-indigo-200 mb-1">Wallet Balance</p>
                <p id="wallet-balance-display" class="text-2xl font-outfit font-bold">
                    ₦{{ number_format((float) ($user->wallet?->balance ?? 0), 2) }}
                </p>
                <a href="{{ route('wallet.fund.gateway') }}"
                   class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-200 hover:text-white transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Fund Wallet
                </a>
            </div>

            {{-- Quick Tips --}}
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-3">How It Works</h3>
                <ol class="space-y-2.5">
                    @php
                        $steps = [
                            ['icon' => '1', 'text' => 'Select your exam type (WAEC, NECO or NABTEB)'],
                            ['icon' => '2', 'text' => 'Choose the number of PINs (1–10)'],
                            ['icon' => '3', 'text' => 'Enter your phone number'],
                            ['icon' => '4', 'text' => 'Confirm with your transaction PIN'],
                            ['icon' => '5', 'text' => 'Your PINs will be displayed instantly'],
                        ];
                    @endphp
                    @foreach($steps as $step)
                    <li class="flex gap-2.5">
                        <span class="flex-shrink-0 h-5 w-5 rounded-full bg-indigo-100 dark:bg-indigo-500/10 text-vtu-primary text-xs font-bold flex items-center justify-center">
                            {{ $step['icon'] }}
                        </span>
                        <span class="text-xs text-slate-600 dark:text-slate-400 leading-snug">{{ $step['text'] }}</span>
                    </li>
                    @endforeach
                </ol>
            </div>

        </div>{{-- /right col --}}
    </div>{{-- /grid --}}

    {{-- ══ Transaction History ═══════════════════════════════════════════════ --}}
    <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Transactions</h2>
            @if($history->isNotEmpty())
            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $history->total() }} total</span>
            @endif
        </div>

        @if($history->isEmpty())
        <div class="py-12 text-center">
            <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-2xl">🎓</div>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">No exam pin purchases yet</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Your transactions will appear here after your first purchase.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700">
                        <th class="px-6 py-3 text-left">Exam</th>
                        <th class="px-6 py-3 text-left">Qty</th>
                        <th class="px-6 py-3 text-left">Amount</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Reference</th>
                        <th class="px-6 py-3 text-left">Date</th>
                        <th class="px-6 py-3 text-left">Pins</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($history as $tx)
                    @php
                        $txData   = $tx->api_response ?? [];
                        $txPins   = $txData['pins'] ?? [];
                        $txQty    = $txData['quantity'] ?? count($txPins) ?: '-';
                        $txExam   = $txData['exam_type'] ?? strtoupper($tx->provider);
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-6 py-3.5">
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $txExam }}</span>
                        </td>
                        <td class="px-6 py-3.5 text-xs text-slate-600 dark:text-slate-400">{{ $txQty }}</td>
                        <td class="px-6 py-3.5 font-semibold text-slate-900 dark:text-white">₦{{ number_format($tx->amount, 2) }}</td>
                        <td class="px-6 py-3.5">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $tx->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                   : ($tx->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                   : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400') }}">
                                {{ ucfirst($tx->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 font-mono text-xs text-slate-400 dark:text-slate-500">{{ $tx->reference }}</td>
                        <td class="px-6 py-3.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ $tx->created_at->format('d M Y, H:i') }}
                        </td>
                        <td class="px-6 py-3.5">
                            @if(count($txPins) > 0)
                            <button type="button"
                                    onclick='showPinsModal(@json($txPins), "{{ addslashes($txExam) }}")'
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 dark:bg-indigo-500/10 text-vtu-primary hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition-colors">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View Pins
                            </button>
                            @else
                            <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($history->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700">
            {{ $history->links() }}
        </div>
        @endif
        @endif
    </div>

</div>{{-- /max-w --}}

{{-- ── Success / Pins Modal ─────────────────────────────────────────────── --}}
<div id="pins-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closePinsModal()"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Your Exam PINs</h3>
                    <p id="pins-modal-subtitle" class="text-xs text-slate-500 dark:text-slate-400"></p>
                </div>
            </div>
            <button onclick="closePinsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        {{-- Pins List --}}
        <div id="pins-modal-list" class="p-5 space-y-3 max-h-96 overflow-y-auto"></div>
        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700">
            <p id="pins-modal-ref" class="text-xs font-mono text-slate-400 dark:text-slate-500 mb-3"></p>
            <button onclick="closePinsModal()"
                    class="w-full py-2.5 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 text-white transition-colors">
                Done
            </button>
        </div>
    </div>
</div>

{{-- ── Result Modal (error) ─────────────────────────────────────────────── --}}
<div id="result-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeResultModal()"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-2xl p-6 text-center">
        <div id="result-icon" class="mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center"></div>
        <h3 id="result-title" class="text-lg font-outfit font-bold text-slate-900 dark:text-white mb-2"></h3>
        <p id="result-message" class="text-sm text-slate-500 dark:text-slate-400 mb-5"></p>
        <button onclick="closeResultModal()"
                class="w-full py-3 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 text-white transition-colors">
            OK
        </button>
    </div>
</div>

@endsection

@section('scripts')
<script>
// ─── State ────────────────────────────────────────────────────────────────────
let selectedExamType = null;  // { id, name, amount }
let quantity         = 1;
let currentBalance   = {{ (float) ($user->wallet?->balance ?? 0) }};

// ─── Exam Type Selection ──────────────────────────────────────────────────────
function selectExamType(id, name, amount, btn) {
    selectedExamType = { id, name, amount };
    quantity         = 1;

    document.querySelectorAll('.exam-type-btn').forEach(b => {
        b.classList.remove('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30', 'bg-indigo-50', 'dark:bg-indigo-500/5');
    });
    btn.classList.add('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30', 'bg-indigo-50', 'dark:bg-indigo-500/5');

    document.getElementById('exam-type-error').classList.add('hidden');

    // Show quantity, phone, instructions
    document.getElementById('quantity-row').classList.remove('hidden');
    document.getElementById('phone-row').classList.remove('hidden');

    // Instructions
    const instrMap = @json($examTypes->pluck('instructions', 'id'));
    const instr = instrMap[id];
    if (instr) {
        document.getElementById('instructions-text').textContent = instr;
        document.getElementById('instructions-box').classList.remove('hidden');
    } else {
        document.getElementById('instructions-box').classList.add('hidden');
    }

    updateQuantityDisplay();
    updateSummary();
    updateBuyBtn();
}

// ─── Quantity ─────────────────────────────────────────────────────────────────
function adjustQuantity(delta) {
    quantity = Math.max(1, Math.min(10, quantity + delta));
    updateQuantityDisplay();
    updateSummary();
    updateBuyBtn();
}

function updateQuantityDisplay() {
    document.getElementById('qty-display').textContent = quantity;
    const unitPrice = selectedExamType?.amount ?? 0;
    const total     = unitPrice * quantity;
    document.getElementById('qty-total').textContent =
        'Total: ₦' + total.toLocaleString('en-NG', { minimumFractionDigits: 2 });
}

// ─── Phone Input ──────────────────────────────────────────────────────────────
function onPhoneInput() {
    document.getElementById('phone-error').classList.add('hidden');
    updateSummary();
    updateBuyBtn();
}

// ─── Summary ─────────────────────────────────────────────────────────────────
function updateSummary() {
    if (!selectedExamType) {
        document.getElementById('summary-box').classList.add('hidden');
        return;
    }

    const phone   = document.getElementById('phone-input').value.trim();
    const phoneOk = /^(0|\+234)[789][01]\d{8}$/.test(phone);
    const total   = selectedExamType.amount * quantity;

    if (!phoneOk) {
        document.getElementById('summary-box').classList.add('hidden');
        return;
    }

    document.getElementById('sum-exam-type').textContent = selectedExamType.name;
    document.getElementById('sum-qty').textContent        = quantity + ' pin' + (quantity > 1 ? 's' : '');
    document.getElementById('sum-amount').textContent     = '₦' + total.toLocaleString('en-NG', { minimumFractionDigits: 2 });

    const balAfter = currentBalance - total;
    const balEl    = document.getElementById('sum-balance');
    balEl.textContent = balAfter >= 0
        ? '₦' + balAfter.toLocaleString('en-NG', { minimumFractionDigits: 2 })
        : '⚠ Insufficient balance';
    balEl.className = 'font-semibold ' + (balAfter >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500');

    document.getElementById('summary-box').classList.remove('hidden');
}

// ─── Buy Button State ─────────────────────────────────────────────────────────
function updateBuyBtn() {
    const phone   = document.getElementById('phone-input').value.trim();
    const phoneOk = /^(0|\+234)[789][01]\d{8}$/.test(phone);
    const ready   = selectedExamType && quantity >= 1 && quantity <= 10 && phoneOk;
    document.getElementById('buy-btn').disabled = !ready;
}

document.getElementById('phone-input').addEventListener('input', updateBuyBtn);

// ─── Initiate Purchase ────────────────────────────────────────────────────────
function initiatePurchase() {
    if (!selectedExamType) {
        document.getElementById('exam-type-error').textContent = 'Please select an exam type.';
        document.getElementById('exam-type-error').classList.remove('hidden');
        return;
    }
    const phone = document.getElementById('phone-input').value.trim();
    if (!phone || !/^(0|\+234)[789][01]\d{8}$/.test(phone)) {
        document.getElementById('phone-error').textContent = 'Enter a valid Nigerian phone number.';
        document.getElementById('phone-error').classList.remove('hidden');
        return;
    }
    requirePinConfirmation(async (pin) => { await submitPurchase(pin); });
}

async function submitPurchase(pin) {
    const phone  = document.getElementById('phone-input').value.trim();
    const btn    = document.getElementById('buy-btn');
    const label  = document.getElementById('buy-btn-label');
    const spinner = document.getElementById('buy-spinner');

    btn.disabled = true;
    label.textContent = 'Processing…';
    spinner.classList.remove('hidden');

    try {
        const resp = await fetch('{{ route("services.epins.purchase") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                exam_type_id:    selectedExamType.id,
                quantity:        quantity,
                phone:           phone,
                transaction_pin: pin,
            }),
        });
        const json = await resp.json();

        if (json.pin_error) {
            showResultModal(false, 'Incorrect PIN', json.message);
            return;
        }

        if (json.success) {
            currentBalance = parseFloat(json.balance?.replace(/[₦,]/g, '') ?? currentBalance);
            document.getElementById('wallet-balance-display').textContent = json.balance ?? '';

            // Show pins modal
            showPinsModal(json.pins ?? [], json.exam_type ?? selectedExamType.name, json.reference);

            // Reset form
            selectedExamType = null;
            quantity         = 1;
            document.querySelectorAll('.exam-type-btn').forEach(b =>
                b.classList.remove('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30', 'bg-indigo-50', 'dark:bg-indigo-500/5'));
            document.getElementById('quantity-row').classList.add('hidden');
            document.getElementById('phone-row').classList.add('hidden');
            document.getElementById('summary-box').classList.add('hidden');
            document.getElementById('instructions-box').classList.add('hidden');
            document.getElementById('phone-input').value = '';
            document.getElementById('qty-display').textContent = '1';

            // Reload history after 2.5 s
            setTimeout(() => location.reload(), 2500);
        } else {
            showResultModal(false, 'Purchase Failed', json.message);
        }
    } catch (e) {
        showResultModal(false, 'Error', 'An unexpected error occurred. Please try again.');
    } finally {
        btn.disabled = false;
        label.textContent = 'Buy Pins';
        spinner.classList.add('hidden');
    }
}

// ─── Pins Modal ───────────────────────────────────────────────────────────────
function showPinsModal(pins, examType, reference) {
    const list     = document.getElementById('pins-modal-list');
    const subtitle = document.getElementById('pins-modal-subtitle');
    const refEl    = document.getElementById('pins-modal-ref');

    subtitle.textContent = examType + (pins.length ? ' – ' + pins.length + ' pin' + (pins.length > 1 ? 's' : '') : '');
    refEl.textContent    = reference ? 'Ref: ' + reference : '';

    list.innerHTML = '';
    if (!pins || pins.length === 0) {
        list.innerHTML = '<p class="text-sm text-slate-500 text-center py-4">No pins to display.</p>';
    } else {
        pins.forEach((item, idx) => {
            const pinValue    = item.pin    ?? '-';
            const serialValue = item.serial ?? null;
            const card = document.createElement('div');
            card.className = 'rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-3.5';
            card.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-0.5">Pin ${idx + 1}</p>
                        <p class="font-mono font-bold text-sm text-slate-900 dark:text-white break-all">${pinValue}</p>
                        ${serialValue ? `<p class="font-mono text-xs text-slate-500 dark:text-slate-400 mt-0.5">Serial: ${serialValue}</p>` : ''}
                    </div>
                    <button type="button" onclick="copyPin('${pinValue}', '${serialValue ?? ''}', this)"
                            class="shrink-0 p-2 rounded-lg text-slate-400 hover:text-vtu-primary hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            `;
            list.appendChild(card);
        });
    }

    document.getElementById('pins-modal').classList.remove('hidden');
}

function closePinsModal() {
    document.getElementById('pins-modal').classList.add('hidden');
}

function copyPin(pin, serial, btn) {
    const text = serial ? pin + ' | Serial: ' + serial : pin;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = `<svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>`;
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    }).catch(() => {});
}

// ─── Result Modal (errors) ────────────────────────────────────────────────────
function showResultModal(success, title, message) {
    const modal   = document.getElementById('result-modal');
    const icon    = document.getElementById('result-icon');
    const titleEl = document.getElementById('result-title');
    const msgEl   = document.getElementById('result-message');

    icon.className = 'mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center ' +
        (success ? 'bg-emerald-100 dark:bg-emerald-500/10' : 'bg-red-100 dark:bg-red-500/10');
    icon.innerHTML = success
        ? `<svg class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
           </svg>`
        : `<svg class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
           </svg>`;

    titleEl.textContent = title;
    msgEl.textContent   = message ?? '';
    modal.classList.remove('hidden');
}

function closeResultModal() {
    document.getElementById('result-modal').classList.add('hidden');
}
</script>
@endsection

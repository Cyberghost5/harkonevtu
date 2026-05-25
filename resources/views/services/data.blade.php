@extends('layouts.dashboard')

@section('title', 'Buy Data – PayPulse')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Buy Data Bundle</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Purchase data bundles for all Nigerian networks instantly.
        </p>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Purchase Form ════════════════════════════════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Data Details</h2>
                </div>

                <div class="p-6 space-y-5">

                    {{-- ── Step 1: Network Selector ──────────────────────── --}}
                    @php
                        $networkStyles = [
                            'mtn'      => ['bg' => 'bg-yellow-400', 'text' => 'text-black',  'border' => 'border-yellow-400', 'abbr' => 'MTN'],
                            'airtel'   => ['bg' => 'bg-red-500',    'text' => 'text-white',  'border' => 'border-red-400',    'abbr' => 'AIR'],
                            'glo'      => ['bg' => 'bg-green-500',  'text' => 'text-white',  'border' => 'border-green-500',  'abbr' => 'GLO'],
                            'etisalat' => ['bg' => 'bg-teal-700',   'text' => 'text-white',  'border' => 'border-teal-600',   'abbr' => '9MB'],
                        ];
                        $networkBorders = [];
                        $networkLabels  = [];
                        foreach($networks as $net) {
                            $s = $networkStyles[$net->network_key] ?? ['border'=>'border-slate-400','abbr'=>strtoupper(substr($net->network_key,0,3))];
                            $networkBorders[$net->network_key] = $s['border'];
                            $networkLabels[$net->network_key]  = $net->name;
                        }
                        $typeLabels   = ['sme' => 'SME', 'gifting' => 'Gifting', 'cg' => 'Corp. Gifting', 'awoof' => 'AWOOF'];
                        $networkNames = $networkLabels; // plain assoc array for JS
                    @endphp
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            Select Network <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-4 gap-3">
                            @foreach($networks as $net)
                            @php $style = $networkStyles[$net->network_key] ?? ['bg'=>'bg-slate-400','text'=>'text-white','border'=>'border-slate-400','abbr'=>strtoupper(substr($net->network_key,0,3))]; @endphp
                            <button type="button" onclick="selectNetwork('{{ $net->network_key }}', this)"
                                    data-network="{{ $net->network_key }}"
                                    class="network-btn relative flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:{{ $style['border'] }} transition-all duration-150 group">
                                <div class="h-10 w-10 rounded-xl {{ $style['bg'] }} flex items-center justify-center shadow-sm">
                                    <span class="text-[10px] font-black {{ $style['text'] }} leading-none">{{ $style['abbr'] }}</span>
                                </div>
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white">{{ $net->name }}</span>
                                @if(($discounts[$net->network_key] ?? 0) > 0)
                                <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400">{{ $discounts[$net->network_key] }}% off</span>
                                @endif
                                <div class="network-check absolute top-1.5 right-1.5 h-4 w-4 rounded-full bg-vtu-primary hidden items-center justify-center">
                                    <svg class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </button>
                            @endforeach
                        </div>
                        <p id="network-error" class="mt-1.5 text-xs text-red-500 hidden">Please select a network.</p>
                    </div>

                    {{-- ── Step 2: Data Type Selector ────────────────────── --}}
                    <div id="type-section" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Data Type <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-2" id="type-pills">
                            {{-- dynamically populated by JS --}}
                        </div>
                        <p id="type-error" class="mt-1.5 text-xs text-red-500 hidden">Please select a data type.</p>
                    </div>

                    {{-- ── Step 3: Plan Selector ─────────────────────────── --}}
                    <div id="plan-section" class="hidden">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Select Plan <span class="text-red-500">*</span>
                        </label>

                        {{-- Loading skeleton --}}
                        <div id="plan-loading" class="hidden grid grid-cols-3 gap-2">
                            @for ($i = 0; $i < 6; $i++)
                            <div class="h-20 rounded-xl bg-slate-100 dark:bg-slate-700 animate-pulse"></div>
                            @endfor
                        </div>

                        {{-- Plan grid --}}
                        <div id="plan-list" class="grid grid-cols-3 gap-2">
                            {{-- populated by JS --}}
                        </div>
                        <p id="plan-error" class="mt-1.5 text-xs text-red-500 hidden">Please select a plan.</p>
                    </div>

                    {{-- ── Step 4: Phone Number ──────────────────────────── --}}
                    <div id="phone-section" class="hidden">
                        <label for="phone-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Phone Number <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <input id="phone-input" type="tel" inputmode="numeric" maxlength="14"
                                   placeholder="e.g. 08012345678"
                                   oninput="onPhoneInput(this)"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition"/>
                        </div>
                        <p id="phone-hint" class="mt-1 text-xs text-slate-400">Enter the recipient's mobile number.</p>
                        <p id="phone-error" class="mt-1 text-xs text-red-500 hidden">Enter a valid Nigerian mobile number.</p>
                    </div>

                    {{-- ── Summary Box ──────────────────────────────────────── --}}
                    <div id="summary-box" class="hidden rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 p-4 space-y-2.5">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Order Summary</h3>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Network</span>
                                <span id="sum-network" class="font-medium text-slate-900 dark:text-white">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Plan</span>
                                <span id="sum-plan" class="font-medium text-slate-900 dark:text-white">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Validity</span>
                                <span id="sum-validity" class="font-medium text-slate-900 dark:text-white">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Recipient</span>
                                <span id="sum-phone" class="font-medium text-slate-900 dark:text-white">-</span>
                            </div>
                            <div class="border-t border-slate-200 dark:border-slate-700 pt-2 flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">You Pay</span>
                                <span id="sum-amount" class="text-lg font-bold text-vtu-primary">-</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-400">Balance After</span>
                                <span id="sum-balance" class="text-slate-500 dark:text-slate-400">-</span>
                            </div>
                        </div>
                    </div>

                    {{-- ── Buy Button ────────────────────────────────────── --}}
                    <button id="buy-btn" type="button" onclick="initiatePurchase()"
                            disabled
                            class="w-full py-3.5 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600
                                   disabled:opacity-50 disabled:cursor-not-allowed text-white transition-colors
                                   flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20">
                        <span id="buy-btn-label">Purchase Data</span>
                        <svg id="buy-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </button>

                </div>{{-- /p-6 --}}
            </div>{{-- /card --}}
        </div>{{-- /left --}}

        {{-- ══ RIGHT: Wallet + How it works ══════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Wallet Balance --}}
            <div class="rounded-2xl bg-gradient-to-br from-vtu-primary to-vtu-secondary p-5 text-white shadow-lg shadow-indigo-500/20">
                <p class="text-xs font-semibold uppercase tracking-wider opacity-70 mb-1">Wallet Balance</p>
                <p class="text-3xl font-outfit font-bold" id="wallet-balance-display">
                    ₦{{ number_format((float) auth()->user()->wallet?->balance ?? 0, 2) }}
                </p>
                <a href="{{ route('wallet.fund.gateway') }}"
                   class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold opacity-80 hover:opacity-100 transition">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Fund Wallet
                </a>
            </div>

            {{-- How it works --}}
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">How it works</h3>
                <ul class="space-y-2.5">
                    @foreach(['Select your network provider', 'Choose a data type (SME, Gifting, etc.)', 'Pick your preferred data plan', 'Confirm - data is delivered instantly'] as $i => $step)
                    <li class="flex items-start gap-2.5">
                        <span class="flex-shrink-0 h-5 w-5 rounded-full bg-indigo-100 dark:bg-indigo-500/10 text-vtu-primary text-[10px] font-bold flex items-center justify-center mt-0.5">{{ $i + 1 }}</span>
                        <span class="text-xs text-slate-600 dark:text-slate-400">{{ $step }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>

        </div>{{-- /right --}}
    </div>{{-- /grid --}}

    {{-- ── Transaction History ───────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Recent Data Purchases</h2>
            <span class="text-xs text-slate-400">{{ $history->total() }} total</span>
        </div>

        @if($history->isEmpty())
        <div class="py-12 text-center">
            <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                </svg>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">No data purchases yet</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700">
                        <th class="px-6 py-3 text-left">Network</th>
                        <th class="px-6 py-3 text-left">Phone</th>
                        <th class="px-6 py-3 text-left">Plan</th>
                        <th class="px-6 py-3 text-left">Amount</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Date</th>
                        <th class="px-6 py-3 text-left">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($history as $tx)
                    @php
                        $netColors = [
                            'mtn'      => 'bg-yellow-400 text-black',
                            'airtel'   => 'bg-red-500 text-white',
                            'glo'      => 'bg-green-500 text-white',
                            'etisalat' => 'bg-teal-700 text-white',
                        ];
                        $netNames = ['mtn'=>'MTN','airtel'=>'Airtel','glo'=>'Glo','etisalat'=>'9mobile'];
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-6 py-3.5">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-5 w-5 rounded-md {{ $netColors[$tx->provider] ?? 'bg-slate-200' }} text-[8px] font-black flex items-center justify-center">
                                    {{ strtoupper(substr($tx->provider, 0, 3)) }}
                                </span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $netNames[$tx->provider] ?? strtoupper($tx->provider) }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-slate-600 dark:text-slate-400 font-mono">{{ $tx->recipient }}</td>
                        <td class="px-6 py-3.5 text-slate-700 dark:text-slate-300">{{ $tx->description ?? ($tx->api_response['plan'] ?? 'Data Bundle') }}</td>
                        <td class="px-6 py-3.5 font-semibold text-slate-900 dark:text-white">₦{{ number_format($tx->amount, 2) }}</td>
                        <td class="px-6 py-3.5">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $tx->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                   : ($tx->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                   : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400') }}">
                                {{ ucfirst($tx->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-slate-500 dark:text-slate-400 text-xs">
                            {{ $tx->created_at->format('d M Y, H:i') }}
                        </td>
                        <td class="px-6 py-3.5 font-mono text-xs text-slate-400 dark:text-slate-500">{{ $tx->reference }}</td>
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

{{-- ── Result Modal (shared style with airtime) ───────────────────────── --}}
<div id="result-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeResultModal()"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-2xl p-6 text-center">
        <div id="result-icon" class="mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center"></div>
        <h3 id="result-title" class="text-lg font-outfit font-bold text-slate-900 dark:text-white mb-2"></h3>
        <p id="result-message" class="text-sm text-slate-500 dark:text-slate-400 mb-2"></p>
        <p id="result-reference" class="text-xs font-mono text-slate-400 dark:text-slate-500 mb-5"></p>
        <button onclick="closeResultModal()"
                class="w-full py-3 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 text-white transition-colors">
            Done
        </button>
    </div>
</div>

@endsection

@section('scripts')
<script>
// ─── State ──────────────────────────────────────────────────────────────────
const DATA_TYPES  = @json($dataTypes);   // { mtn: ['sme','awoof','gifting'], ... }
const TYPE_LABELS = { cheap_data: 'Cheap Data', sme: 'SME', gifting: 'Gifting', cg: 'Corp. Gifting', awoof: 'AWOOF' };
const DISCOUNTS   = @json($discounts);   // { mtn: 0, glo: 0, ... }
const WALLET_BAL  = {{ (float) auth()->user()->wallet?->balance ?? 0 }};

let selectedNetwork  = null;
let selectedDataType = null;
let selectedPlan     = null;    // { id, name, validity, amount }
let currentBalance   = WALLET_BAL;

// ─── Network Selection ───────────────────────────────────────────────────────
function selectNetwork(key, btn) {
    selectedNetwork  = key;
    selectedDataType = null;
    selectedPlan     = null;

    // Update button styles
    document.querySelectorAll('.network-btn').forEach(b => {
        b.classList.remove('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30');
        b.querySelector('.network-check')?.classList.replace('flex', 'hidden');
    });
    btn.classList.add('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30');
    const chk = btn.querySelector('.network-check');
    if (chk) { chk.classList.replace('hidden', 'flex'); }

    document.getElementById('network-error').classList.add('hidden');

    // Show type selector for this network
    const types = DATA_TYPES[key] ?? [];
    const typePills = document.getElementById('type-pills');
    typePills.innerHTML = '';

    if (types.length === 0) {
        typePills.innerHTML = '<p class="text-xs text-slate-400">No data types available for this network.</p>';
    } else {
        types.forEach(type => {
            const pill = document.createElement('button');
            pill.type = 'button';
            pill.dataset.type = type;
            pill.textContent = TYPE_LABELS[type] ?? type.toUpperCase();
            pill.onclick = () => selectType(type, pill);
            pill.className = 'type-pill px-4 py-1.5 rounded-full text-sm font-medium border-2 border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:border-vtu-primary hover:text-vtu-primary transition-all duration-150';
            typePills.appendChild(pill);
        });
    }

    document.getElementById('type-section').classList.remove('hidden');
    document.getElementById('plan-section').classList.add('hidden');
    document.getElementById('phone-section').classList.add('hidden');
    document.getElementById('summary-box').classList.add('hidden');
    document.getElementById('plan-list').innerHTML = '';
    updateBuyBtn();

    // Auto-select first type
    if (types.length > 0) {
        const firstPill = typePills.querySelector('.type-pill');
        if (firstPill) selectType(types[0], firstPill);
    }
}

// ─── Data Type Selection ─────────────────────────────────────────────────────
function selectType(type, btn) {
    selectedDataType = type;
    selectedPlan     = null;

    document.querySelectorAll('.type-pill').forEach(p => {
        p.classList.remove('border-vtu-primary', 'text-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-900/20');
    });
    btn.classList.add('border-vtu-primary', 'text-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-900/20');
    document.getElementById('type-error').classList.add('hidden');

    loadPlans(selectedNetwork, type);
}

// ─── Load Plans via AJAX ─────────────────────────────────────────────────────
async function loadPlans(networkKey, dataType) {
    const planSection  = document.getElementById('plan-section');
    const planLoading  = document.getElementById('plan-loading');
    const planList     = document.getElementById('plan-list');

    planSection.classList.remove('hidden');
    planLoading.classList.remove('hidden');
    planList.innerHTML = '';
    document.getElementById('phone-section').classList.add('hidden');
    document.getElementById('summary-box').classList.add('hidden');
    selectedPlan = null;
    updateBuyBtn();

    try {
        const resp = await fetch('{{ route("services.data.plans") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ network_key: networkKey, data_type: dataType }),
        });
        const json = await resp.json();

        planLoading.classList.add('hidden');

        if (!json.success || json.plans.length === 0) {
            planList.innerHTML = '<p class="text-sm text-slate-400 py-2">No plans available for this type.</p>';
            return;
        }

        json.plans.forEach((plan, idx) => {
            const card = document.createElement('button');
            card.type  = 'button';
            card.dataset.planId = plan.id;
            card.onclick = () => selectPlan(plan, card);
            card.className = 'plan-row flex flex-col items-center justify-center text-center gap-0.5 p-2.5 rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-vtu-primary hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-all duration-150 group';
            card.innerHTML = `
                <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight group-hover:text-vtu-primary">${plan.name}</p>
                <p class="text-[10px] text-slate-400 leading-tight">${plan.validity}</p>
                <p class="text-xs font-bold text-vtu-primary mt-1">₦${numberFormat(plan.amount)}</p>`;
            planList.appendChild(card);
        });

        // Show phone section
        document.getElementById('phone-section').classList.remove('hidden');
    } catch (err) {
        planLoading.classList.add('hidden');
        planList.innerHTML = '<p class="text-sm text-red-400 py-2">Failed to load plans. Please try again.</p>';
    }
}

// ─── Plan Selection ──────────────────────────────────────────────────────────
function selectPlan(plan, btn) {
    selectedPlan = plan;

    document.querySelectorAll('.plan-row').forEach(r => {
        r.classList.remove('border-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-900/20');
    });
    btn.classList.add('border-vtu-primary', 'bg-indigo-50', 'dark:bg-indigo-900/20');
    document.getElementById('plan-error').classList.add('hidden');

    updateSummary();
    updateBuyBtn();
}

// ─── Phone Input ─────────────────────────────────────────────────────────────
function onPhoneInput(input) {
    const cleaned = input.value.replace(/[^0-9+]/g, '');
    input.value = cleaned;
    const valid = /^(0|\+234)[789][01]\d{8}$/.test(cleaned);
    document.getElementById('phone-error').classList.toggle('hidden', valid || cleaned.length < 10);
    updateSummary();
    updateBuyBtn();
}

// ─── Summary ─────────────────────────────────────────────────────────────────
function updateSummary() {
    const phone = document.getElementById('phone-input').value.trim();
    const networks = @json($networkNames);

    if (!selectedNetwork || !selectedPlan) {
        document.getElementById('summary-box').classList.add('hidden');
        return;
    }

    const amount   = selectedPlan.amount;
    const balAfter = currentBalance - amount;

    document.getElementById('sum-network').textContent  = networks[selectedNetwork] ?? selectedNetwork;
    document.getElementById('sum-plan').textContent     = selectedPlan.name;
    document.getElementById('sum-validity').textContent = selectedPlan.validity;
    document.getElementById('sum-phone').textContent    = phone || '-';
    document.getElementById('sum-amount').textContent   = '₦' + numberFormat(amount);
    document.getElementById('sum-balance').textContent  = balAfter >= 0
        ? '₦' + numberFormat(balAfter)
        : '⚠ Insufficient balance';
    document.getElementById('sum-balance').classList.toggle('text-red-500', balAfter < 0);

    document.getElementById('summary-box').classList.remove('hidden');
}

// ─── Buy Button State ────────────────────────────────────────────────────────
function updateBuyBtn() {
    const phone = document.getElementById('phone-input')?.value?.trim() ?? '';
    const phoneOk = /^(0|\+234)[789][01]\d{8}$/.test(phone);
    const ready = selectedNetwork && selectedDataType && selectedPlan && phoneOk;
    document.getElementById('buy-btn').disabled = !ready;
}

// ─── Initiate Purchase ───────────────────────────────────────────────────────
function initiatePurchase() {
    if (!selectedNetwork || !selectedDataType || !selectedPlan) return;
    const phone = document.getElementById('phone-input').value.trim();
    if (!/^(0|\+234)[789][01]\d{8}$/.test(phone)) {
        document.getElementById('phone-error').classList.remove('hidden');
        return;
    }

    const networks = @json($networkNames);
    requirePinConfirmation(
        async (pin) => { await submitPurchase(pin); }
    );
}

async function submitPurchase(pin) {
    const phone   = document.getElementById('phone-input').value.trim();
    const btn     = document.getElementById('buy-btn');
    const label   = document.getElementById('buy-btn-label');
    const spinner = document.getElementById('buy-spinner');

    btn.disabled = true;
    label.textContent = 'Processing…';
    spinner.classList.remove('hidden');

    try {
        const resp = await fetch('{{ route("services.data.purchase") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                network_key:     selectedNetwork,
                data_type:       selectedDataType,
                plan_id:         selectedPlan.id,
                phone:           phone,
                transaction_pin: pin,
            }),
        });
        const json = await resp.json();

        if (json.success) {
            showResultModal(true, 'Data Sent!', json.message, json.reference);
            currentBalance = parseFloat(json.balance.replace(/[^0-9.]/g, ''));
            document.getElementById('wallet-balance-display').textContent = json.balance;
            // Reset form
            resetForm();
        } else {
            if (json.pin_error) {
                requirePinConfirmation(async (p) => { await submitPurchase(p); });
                setTimeout(() => setPinError(json.message), 50);
            } else {
                showResultModal(false, 'Purchase Failed', json.message, null);
            }
        }
    } catch (err) {
        showResultModal(false, 'Network Error', 'Could not reach the server. Please check your connection.', null);
    } finally {
        btn.disabled = false;
        label.textContent = 'Purchase Data';
        spinner.classList.add('hidden');
        updateBuyBtn();
    }
}

function resetForm() {
    selectedNetwork  = null;
    selectedDataType = null;
    selectedPlan     = null;

    document.querySelectorAll('.network-btn').forEach(b => {
        b.classList.remove('border-vtu-primary', 'ring-2', 'ring-vtu-primary/30');
        b.querySelector('.network-check')?.classList.replace('flex', 'hidden');
    });
    document.getElementById('type-section').classList.add('hidden');
    document.getElementById('plan-section').classList.add('hidden');
    document.getElementById('phone-section').classList.add('hidden');
    document.getElementById('summary-box').classList.add('hidden');
    document.getElementById('phone-input').value = '';
    document.getElementById('plan-list').innerHTML = '';
    updateBuyBtn();
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function numberFormat(n) {
    return Number(n).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ─── Result Modal ─────────────────────────────────────────────────────────────
function showResultModal(success, title, message, reference) {
    const icon = document.getElementById('result-icon');
    icon.className = 'mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center ' +
        (success ? 'bg-emerald-100 dark:bg-emerald-500/10' : 'bg-red-100 dark:bg-red-500/10');
    icon.innerHTML = success
        ? `<svg class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`
        : `<svg class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;
    document.getElementById('result-title').textContent     = title;
    document.getElementById('result-message').textContent   = message;
    document.getElementById('result-reference').textContent = reference ? 'Ref: ' + reference : '';
    document.getElementById('result-modal').classList.remove('hidden');
}

function closeResultModal() {
    document.getElementById('result-modal').classList.add('hidden');
    if (document.getElementById('result-title').textContent === 'Data Sent!') {
        window.location.reload();
    }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeResultModal(); });
</script>
@endsection

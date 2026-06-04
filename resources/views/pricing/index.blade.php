@extends('layouts.dashboard')

@section('title', 'Pricing')

@section('content')
@php
    $networkColors = [
        'mtn'      => ['bg' => 'bg-yellow-400',  'text' => 'text-black',  'light' => 'bg-yellow-50 dark:bg-yellow-500/10',  'badge' => 'text-yellow-700 dark:text-yellow-400',  'label' => 'MTN'],
        'airtel'   => ['bg' => 'bg-red-500',      'text' => 'text-white',  'light' => 'bg-red-50 dark:bg-red-500/10',        'badge' => 'text-red-700 dark:text-red-400',        'label' => 'Airtel'],
        'glo'      => ['bg' => 'bg-green-500',    'text' => 'text-white',  'light' => 'bg-green-50 dark:bg-green-500/10',    'badge' => 'text-green-700 dark:text-green-400',    'label' => 'Glo'],
        'etisalat' => ['bg' => 'bg-teal-600',     'text' => 'text-white',  'light' => 'bg-teal-50 dark:bg-teal-500/10',      'badge' => 'text-teal-700 dark:text-teal-400',      'label' => '9mobile'],
    ];

    $dataTypeLabels = [
        'sme'                => 'SME',
        'gifting'            => 'Gifting',
        'cheap_data'         => 'Cheap Data',
        'corporate_gifting'  => 'Corporate Gifting',
        'sme2'               => 'SME 2',
        'awoof'              => 'Awoof',
    ];
@endphp

<div class="max-w-6xl mx-auto space-y-8">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Pricing</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                All prices shown are for
                {{ $isAgent ? 'agent' : 'standard' }} accounts.
                @if ($txChargeValue > 0)
                    A
                    @if ($txChargeType === 'percentage')
                        {{ $txChargeValue }}% service fee
                    @else
                        ₦{{ number_format($txChargeValue, 2) }} service fee
                    @endif
                    applies per transaction.
                @endif
            </p>
        </div>
        {{-- Tab pills for jumping to sections --}}
        <div class="hidden sm:flex items-center gap-2 flex-wrap justify-end">
            @foreach ([
                ['airtime-section',     'Airtime'],
                ['data-section',        'Data'],
                ['cable-section',       'Cable TV'],
                ['electricity-section', 'Electricity'],
                ['epins-section',       'Exam Pins'],
            ] as [$id, $label])
            <a href="#{{ $id }}"
               class="px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── 1. AIRTIME ─────────────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="airtime-section" class="scroll-mt-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-8 w-8 rounded-lg flex items-center justify-center text-white text-sm font-bold"
                 style="background: {{ $themeColor }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <h2 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Airtime</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @forelse ($networks as $network)
            @php
                $nc      = $networkColors[$network->network_key] ?? ['bg' => 'bg-slate-500', 'text' => 'text-white', 'light' => 'bg-slate-50 dark:bg-slate-800', 'badge' => 'text-slate-600 dark:text-slate-400', 'label' => $network->name];
                $disc    = $airtimeDiscounts[$network->network_key] ?? 0;
                $finalEx = $disc > 0 ? round(1000 * (100 - $disc) / 100, 2) : null;
            @endphp
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-800 {{ $nc['light'] }}">
                    <div class="h-9 w-9 rounded-xl {{ $nc['bg'] }} flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-black {{ $nc['text'] }}">{{ strtoupper(substr($network->name, 0, 3)) }}</span>
                    </div>
                    <span class="font-semibold text-slate-900 dark:text-white text-sm">{{ $network->name }}</span>
                </div>
                <div class="px-5 py-4 space-y-2 text-sm">
                    @if ($disc > 0)
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Discount</span>
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $disc }}% off</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">You pay per ₦1,000</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">₦{{ number_format($finalEx, 2) }}</span>
                        </div>
                    @else
                        <div class="flex justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Rate</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">Face value</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Min amount</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">₦50</span>
                    </div>
                    <div class="pt-1">
                        <a href="{{ route('services.airtime') }}"
                           class="block text-center text-xs font-semibold py-2 rounded-lg transition-opacity hover:opacity-90 text-white"
                           style="background: {{ $themeColor }}">Buy Now</a>
                    </div>
                </div>
            </div>
            @empty
                <p class="col-span-4 text-sm text-slate-400">No airtime networks available.</p>
            @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── 2. DATA ──────────────────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="data-section" class="scroll-mt-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-8 w-8 rounded-lg flex items-center justify-center text-white"
                 style="background: {{ $themeColor }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                </svg>
            </div>
            <h2 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Data Plans</h2>
        </div>

        @forelse ($dataPlans as $networkKey => $byType)
        @php
            $nc = $networkColors[$networkKey] ?? ['bg' => 'bg-slate-500', 'text' => 'text-white', 'light' => 'bg-slate-50 dark:bg-slate-800', 'badge' => 'text-slate-600', 'label' => strtoupper($networkKey)];
        @endphp
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-4">
            {{-- Network header --}}
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 dark:border-slate-800 {{ $nc['light'] }}">
                <div class="h-9 w-9 rounded-xl {{ $nc['bg'] }} flex items-center justify-center flex-shrink-0">
                    <span class="text-xs font-black {{ $nc['text'] }}">{{ strtoupper(substr($nc['label'], 0, 3)) }}</span>
                </div>
                <span class="font-bold text-slate-900 dark:text-white">{{ $nc['label'] }} Data Plans</span>
            </div>

            {{-- Tabbed by data type --}}
            @php $firstType = $byType->keys()->first(); @endphp
            <div x-data="{ tab: '{{ $networkKey }}_{{ $firstType }}' }" class="p-4">
                {{-- Type tabs --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach ($byType as $dataType => $plans)
                    @php $tabId = $networkKey . '_' . $dataType; @endphp
                    <button @click="tab = '{{ $tabId }}'"
                            :class="tab === '{{ $tabId }}'
                                ? 'text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
                            :style="tab === '{{ $tabId }}' ? 'background: {{ $themeColor }}' : ''"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all">
                        {{ $dataTypeLabels[$dataType] ?? ucfirst(str_replace('_', ' ', $dataType)) }}
                        <span class="ml-1 opacity-70">({{ $plans->count() }})</span>
                    </button>
                    @endforeach
                </div>

                {{-- Plan tables per type --}}
                @foreach ($byType as $dataType => $plans)
                @php $tabId = $networkKey . '_' . $dataType; @endphp
                <div x-show="tab === '{{ $tabId }}'" x-cloak>
                    <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Plan</th>
                                    <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Validity</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Price</th>
                                    <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide w-24"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($plans as $plan)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200">{{ $plan->plan_name }}</td>
                                    <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs">{{ $plan->validity ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">
                                        ₦{{ number_format((float)($isAgent && $plan->amount_agent > 0 ? $plan->amount_agent : $plan->amount), 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('services.data') }}"
                                           class="text-xs font-semibold px-3 py-1.5 rounded-lg text-white transition-opacity hover:opacity-90 whitespace-nowrap"
                                           style="background: {{ $themeColor }}">Buy</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @empty
            <p class="text-sm text-slate-400 dark:text-slate-500 py-4">No data plans available.</p>
        @endforelse
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── 3. CABLE TV ─────────────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="cable-section" class="scroll-mt-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-8 w-8 rounded-lg flex items-center justify-center text-white"
                 style="background: {{ $themeColor }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Cable TV Plans</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($cableProviders as $provider)
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $provider->name }}</span>
                    <a href="{{ route('services.cable') }}"
                       class="text-xs font-semibold px-3 py-1.5 rounded-lg text-white transition-opacity hover:opacity-90"
                       style="background: {{ $themeColor }}">Subscribe</a>
                </div>
                @if ($provider->plans->isEmpty())
                    <p class="px-5 py-4 text-xs text-slate-400">No plans configured.</p>
                @else
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($provider->plans as $plan)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ $plan->name }}</td>
                            <td class="px-5 py-3 text-right font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                ₦{{ number_format((float) $plan->amount, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
            @empty
                <p class="col-span-2 text-sm text-slate-400 dark:text-slate-500 py-4">No cable TV providers available.</p>
            @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── 4. ELECTRICITY ──────────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="electricity-section" class="scroll-mt-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-8 w-8 rounded-lg flex items-center justify-center text-white"
                 style="background: {{ $themeColor }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h2 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Electricity DISCOs</h2>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            @if ($discos->isEmpty())
                <p class="px-6 py-6 text-sm text-slate-400">No electricity providers available.</p>
            @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 dark:divide-slate-800">
                @foreach ($discos as $disco)
                <div class="flex items-center justify-between px-5 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $disco->name }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">Prepaid &amp; Postpaid</p>
                        </div>
                    </div>
                    <a href="{{ route('services.electricity') }}"
                       class="text-xs font-semibold px-3 py-1.5 rounded-lg text-white transition-opacity hover:opacity-90 flex-shrink-0"
                       style="background: {{ $themeColor }}">Pay</a>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
            Electricity tokens are priced at face value - you pay exactly what you want to recharge.
            @if ($txChargeValue > 0)
                A service fee applies per transaction.
            @endif
        </p>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── 5. EXAM PINS ────────────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div id="epins-section" class="scroll-mt-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-8 w-8 rounded-lg flex items-center justify-center text-white"
                 style="background: {{ $themeColor }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h2 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Exam Pins (e-Pins)</h2>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            @if ($examPins->isEmpty())
                <p class="px-6 py-6 text-sm text-slate-400">No exam pin types available.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Exam Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Instructions</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Price per Pin</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($examPins as $pin)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-lg bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $pin->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-500 dark:text-slate-400 max-w-xs">
                                {{ $pin->instructions ? Str::limit($pin->instructions, 80) : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                ₦{{ number_format((float) $pin->amount, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('services.epins') }}"
                                   class="text-xs font-semibold px-3 py-1.5 rounded-lg text-white transition-opacity hover:opacity-90 whitespace-nowrap"
                                   style="background: {{ $themeColor }}">Buy</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Notice ───────────────────────────────────────────────────────────── --}}
    <div class="flex items-start gap-3 p-4 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/20 text-sm text-blue-700 dark:text-blue-400">
        <svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p>Prices are subject to change based on provider rates. All transactions are instant. Fund your wallet to get started.</p>
    </div>

</div>
@endsection

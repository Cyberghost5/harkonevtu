@extends('layouts.admin')

@section('title', 'Transaction ' . $transaction->reference)
@section('heading', 'Transaction Details')
@section('subheading', 'Detailed transaction ledger, user profile and API telemetry')

@section('content')

{{-- Breadcrumbs & Back Bar --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('admin.transactions.index', ['tab' => $transactionType]) }}" class="hover:text-vtu-primary transition-colors flex items-center gap-1 font-medium">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to {{ $transactionType === 'wallet' ? 'Wallet & Funding Ledger' : 'Service Purchases' }}
        </a>
        <span>/</span>
        <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold truncate max-w-xs">{{ $transaction->reference }}</span>
    </div>

    <div class="flex items-center gap-2">
        @if($user)
        <a href="{{ route('admin.users.show', $user) }}" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors inline-flex items-center gap-1.5 shadow-sm">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            View User Account
        </a>
        @endif
        <button type="button" onclick="copyToClipboard('{{ $transaction->reference }}', 'Reference copied!')" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-vtu-primary text-white hover:bg-indigo-700 transition-colors inline-flex items-center gap-1.5 shadow-sm shadow-indigo-500/20">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Copy Reference
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Left 2 Columns: Transaction Details & API Logs ───────────────────── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- 1. Main Transaction Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 relative overflow-hidden">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <span class="text-xs uppercase font-bold tracking-wider text-slate-400 block mb-1">
                        {{ $transactionType === 'wallet' ? 'Wallet Transaction Record' : 'Service Purchase Order' }}
                    </span>
                    <div class="flex items-center gap-2">
                        <h2 class="text-3xl font-bold font-outfit tracking-tight {{ ($transactionType === 'wallet' && $transaction->type === 'credit') ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                            {{ ($transactionType === 'wallet' && $transaction->type === 'credit') ? '+' : (($transactionType === 'wallet' && $transaction->type === 'debit') ? '-' : '') }}₦{{ number_format((float) $transaction->amount, 2) }}
                        </h2>
                        @if($transactionType === 'wallet')
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider {{ $transaction->type === 'credit' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400' }}">
                                {{ $transaction->type }}
                            </span>
                        @else
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400">
                                {{ str_replace('_', ' ', $transaction->service_type ?? 'Service') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        {{ $transaction->description ?? ($transaction->service_type ? ucfirst($transaction->service_type) . ' purchase for ' . ($transaction->recipient ?? 'user') : 'Transaction') }}
                    </p>
                </div>

                <div class="flex flex-col items-end gap-1.5">
                    @php
                        $status = strtolower($transaction->status ?? 'success');
                        $badgeClasses = match($status) {
                            'success', 'successful', 'paid', 'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                            'failed' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400 border-rose-200 dark:border-rose-800',
                            'refunded' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                        };
                    @endphp
                    <span class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wider border {{ $badgeClasses }}">
                        {{ $transaction->status ?? 'Success' }}
                    </span>
                    <span class="text-[11px] text-slate-400">
                        {{ $transaction->created_at->format('d M Y, H:i:s') }}
                    </span>
                </div>
            </div>

            {{-- Key Metrics Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-0.5">Reference ID</span>
                    <span class="font-mono text-xs font-bold text-slate-800 dark:text-slate-200 break-all select-all">{{ $transaction->reference }}</span>
                </div>

                @if($transactionType === 'wallet')
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-0.5">Balance Progression</span>
                    <div class="flex items-center gap-1.5 text-xs font-semibold">
                        <span class="text-slate-500 font-mono">₦{{ number_format((float) ($transaction->balance_before ?? 0), 2) }}</span>
                        <span class="text-slate-400">&rarr;</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">₦{{ number_format((float) ($transaction->balance_after ?? 0), 2) }}</span>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-0.5">Fee / Charge</span>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                        ₦{{ number_format((float) ($transaction->metadata['charge'] ?? 0), 2) }}
                    </span>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-0.5">Gateway / Provider</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        {{ $transaction->metadata['gateway'] ?? ($transaction->metadata['source'] ?? 'System') }}
                    </span>
                </div>
                @else
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-0.5">Provider</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        {{ $transaction->provider ?? '-' }}
                    </span>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-0.5">Recipient</span>
                    <span class="font-mono text-xs font-bold text-slate-800 dark:text-slate-200">{{ $transaction->recipient ?? '-' }}</span>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-0.5">API Reference</span>
                    <span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300 break-all">{{ $transaction->api_reference ?? '-' }}</span>
                </div>
                @endif
            </div>

            {{-- Special Service Output: Token / PIN / Electricity Units --}}
            @if($transactionType === 'service' && ($transaction->token || $transaction->units))
            <div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300 block mb-2">
                    ⚡ Generated Service Voucher / Token Details
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    @if($transaction->token)
                    <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-amber-200/60 dark:border-amber-800/40">
                        <span class="text-[11px] text-slate-400 block mb-0.5">Token / Recharge PIN</span>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-base font-bold text-amber-700 dark:text-amber-400 tracking-wider">{{ $transaction->token }}</span>
                            <button type="button" onclick="copyToClipboard('{{ $transaction->token }}', 'Token copied!')" class="text-xs text-amber-600 hover:underline">Copy</button>
                        </div>
                    </div>
                    @endif
                    @if($transaction->units)
                    <div class="p-3 bg-white dark:bg-slate-900 rounded-lg border border-amber-200/60 dark:border-amber-800/40">
                        <span class="text-[11px] text-slate-400 block mb-0.5">Token Units</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $transaction->units }} kWh</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Metadata / Extra Transaction Info --}}
            @if(!empty($transaction->metadata) || !empty($transaction->api_response))
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                        {{ $transactionType === 'wallet' ? 'Transaction Metadata Payload' : 'Provider API Response Data' }}
                    </span>
                    <button type="button" onclick="document.getElementById('raw-meta-box').classList.toggle('hidden')" class="text-[11px] font-semibold text-vtu-primary hover:underline">
                        Toggle Raw JSON
                    </button>
                </div>

                {{-- Table breakdown of metadata --}}
                <div class="overflow-hidden border border-slate-100 dark:border-slate-800 rounded-xl mb-3">
                    <table class="w-full text-xs">
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                            @php
                                $metaData = $transactionType === 'wallet' ? ($transaction->metadata ?? []) : ($transaction->api_response ?? []);
                            @endphp
                            @forelse($metaData as $mKey => $mVal)
                            <tr>
                                <td class="px-4 py-2 font-mono font-semibold text-slate-500 w-1/3">{{ $mKey }}</td>
                                <td class="px-4 py-2 font-mono text-slate-800 dark:text-slate-200">
                                    @if(is_array($mVal) || is_object($mVal))
                                        <pre class="text-[11px] overflow-auto max-h-24 whitespace-pre-wrap">{{ json_encode($mVal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @elseif(is_bool($mVal))
                                        <span class="font-bold {{ $mVal ? 'text-emerald-600' : 'text-rose-600' }}">{{ $mVal ? 'true' : 'false' }}</span>
                                    @else
                                        {{ (string) $mVal }}
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="px-4 py-2 text-slate-400 italic">No structured metadata available</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Hidden Raw JSON Block --}}
                <div id="raw-meta-box" class="hidden">
                    <pre class="bg-slate-950 text-slate-200 p-4 rounded-xl text-xs font-mono overflow-auto max-h-60 whitespace-pre-wrap">{{ json_encode($metaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

            {{-- Linked Transaction Link if available --}}
            @if($linkedTransaction)
            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="text-slate-400">Linked Record:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">
                        {{ $transactionType === 'wallet' ? 'Service Purchase Order' : 'Wallet Ledger Debit' }}
                    </span>
                    <span class="font-mono text-slate-500">({{ $linkedTransaction->reference }})</span>
                </div>
                <a href="{{ route('admin.transactions.show', ['type' => $transactionType === 'wallet' ? 'service' : 'wallet', 'id' => $linkedTransaction->id]) }}" class="font-bold text-vtu-primary hover:underline flex items-center gap-1">
                    Open Linked Record &rarr;
                </a>
            </div>
            @endif
        </div>

        {{-- 2. API Request & Response Telemetry Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-vtu-primary flex items-center justify-center font-bold">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">API Request & Telemetry Logs</h3>
                        <p class="text-xs text-slate-400">Full outbound provider requests and incoming webhooks recorded for this transaction</p>
                    </div>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    {{ count($apiLogs) }} {{ Str::plural('Log', count($apiLogs)) }}
                </span>
            </div>

            @forelse($apiLogs as $index => $log)
            <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden mb-4 last:mb-0">
                {{-- Log Header --}}
                <div class="p-4 bg-slate-50 dark:bg-slate-800/40 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Method Badge --}}
                        <span class="px-2 py-0.5 text-xs font-mono font-bold rounded {{ $log->method === 'POST' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                            {{ $log->method ?? 'POST' }}
                        </span>

                        {{-- HTTP Status Badge --}}
                        @php
                            $statusNum = (int) ($log->http_status ?? 0);
                            $statusColor = match(true) {
                                $statusNum >= 200 && $statusNum < 300 => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400',
                                $statusNum >= 400 && $statusNum < 500 => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400',
                                $statusNum >= 500 => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400',
                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                            };
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-mono font-bold rounded {{ $statusColor }}">
                            {{ $log->http_status ?? '-' }} {{ $log->success ? 'OK' : 'Error' }}
                        </span>

                        {{-- Channel / Source Badge --}}
                        <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full {{ $log->channel_badge_class }}">
                            {{ $log->channel_label }}
                        </span>

                        {{-- Provider / Service --}}
                        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">
                            {{ $log->provider }} / {{ $log->service }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3 text-xs text-slate-400 font-mono">
                        @if($log->duration_ms)
                        <span>⏱️ {{ round($log->duration_ms) }}ms</span>
                        @endif
                        <span>{{ $log->created_at->format('d M y, H:i:s') }}</span>
                    </div>
                </div>

                {{-- Log Meta Info --}}
                <div class="px-4 py-3 bg-white dark:bg-slate-900 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Endpoint Route</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200 truncate block text-[11px]" title="{{ $log->endpoint }}">
                            {{ $log->endpoint }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Client IP Address</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">{{ $log->ip_address ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Location</span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">📍 {{ $log->location ?? 'Nigeria' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Device & User-Agent</span>
                        <span class="text-slate-800 dark:text-slate-200 truncate block text-[11px]" title="{{ $log->user_agent }}">
                            {{ $log->device_info ?? '—' }}
                        </span>
                    </div>
                </div>

                {{-- Request & Response Side-by-Side --}}
                <div class="p-4 bg-slate-900 text-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Request Payload --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                    Request Payload
                                </span>
                                <button type="button" onclick="copyToClipboard('{{ addslashes(json_encode($log->payload, JSON_PRETTY_PRINT)) }}', 'Payload copied!')" class="text-[10px] text-slate-400 hover:text-white transition-colors">
                                    Copy
                                </button>
                            </div>
                            <pre class="bg-slate-950 p-3 rounded-lg font-mono text-[11px] text-slate-300 overflow-auto max-h-64 whitespace-pre-wrap border border-slate-800">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}' }}</pre>
                        </div>

                        {{-- Response Payload --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Response Payload
                                </span>
                                <button type="button" onclick="copyToClipboard('{{ addslashes(json_encode($log->response, JSON_PRETTY_PRINT)) }}', 'Response copied!')" class="text-[10px] text-slate-400 hover:text-white transition-colors">
                                    Copy
                                </button>
                            </div>
                            <pre class="bg-slate-950 p-3 rounded-lg font-mono text-[11px] text-emerald-400 overflow-auto max-h-64 whitespace-pre-wrap border border-slate-800">{{ json_encode($log->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}' }}</pre>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="py-10 text-center rounded-xl bg-slate-50 dark:bg-slate-800/30 border border-dashed border-slate-200 dark:border-slate-800">
                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">No external HTTP API calls recorded</p>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">This transaction was processed internally or as a ledger balance transfer without invoking third-party provider endpoints.</p>
            </div>
            @endforelse
        </div>

    </div>

    {{-- ── Right Column: Complete User Profile & Telemetry ─────────────────── --}}
    <div class="lg:col-span-1 space-y-6">

        @if($user)
        {{-- User Identity Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-4">
                @if ($user->avatar)
                    <img src="{{ $user->avatar }}" alt="Avatar" class="h-12 w-12 rounded-2xl object-cover shadow-sm">
                @else
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-vtu-primary to-vtu-secondary flex items-center justify-center text-white font-bold text-base flex-shrink-0 shadow-sm">
                        {{ $user->initials() }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-slate-900 dark:text-white truncate">{{ $user->name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ $user->email }}</p>
                </div>
            </div>

            {{-- Quick User Badges --}}
            <div class="flex flex-wrap gap-1.5 mb-4">
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider {{ $user->isAgent() ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400' }}">
                    {{ ucfirst($user->user_type ?? 'User') }}
                </span>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider {{ $user->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400' }}">
                    {{ $user->is_active ? 'Active' : 'Suspended' }}
                </span>
                @if($user->is_admin)
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400">
                    Admin
                </span>
                @endif
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider {{ ($user->kyc_status ?? 'unverified') === 'verified' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                    KYC: {{ ucfirst($user->kyc_status ?? 'unverified') }}
                </span>
            </div>

            {{-- User Detailed Attributes --}}
            <div class="space-y-2 text-xs divide-y divide-slate-100 dark:divide-slate-800">
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-400">User ID</span>
                    <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">#{{ $user->id }}</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-400">Phone Number</span>
                    <a href="tel:{{ $user->phone }}" class="font-semibold text-vtu-primary hover:underline">{{ $user->phone ?? 'N/A' }}</a>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-400">Email Address</span>
                    <a href="mailto:{{ $user->email }}" class="font-semibold text-slate-800 dark:text-slate-200 hover:text-vtu-primary truncate ml-2 max-w-[170px]" title="{{ $user->email }}">{{ $user->email }}</a>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-400">Member Since</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $user->created_at->format('d M Y') }}</span>
                </div>
                @if($user->bvn)
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-400">BVN Status</span>
                    <span class="font-mono text-emerald-600 font-semibold">Verified (***{{ substr($user->bvn, -4) }})</span>
                </div>
                @endif
            </div>

            {{-- Wallet Summary Widget --}}
            <div class="mt-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Current Balance</span>
                    <a href="{{ route('admin.users.show', $user) }}" class="text-[11px] font-semibold text-vtu-primary hover:underline">Manage &rarr;</a>
                </div>
                <p class="text-2xl font-bold font-outfit text-slate-900 dark:text-white">
                    ₦{{ number_format((float) ($user->wallet?->balance ?? 0), 2) }}
                </p>
                <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-200/60 dark:border-slate-700/60 text-[11px]">
                    <div>
                        <span class="text-slate-400 block text-[10px]">Total Funded</span>
                        <span class="font-semibold text-emerald-600 font-mono">₦{{ number_format((float) ($user->wallet?->total_funded ?? 0), 2) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px]">Total Spent</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300 font-mono">₦{{ number_format((float) ($user->wallet?->total_spent ?? 0), 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Assigned Virtual Accounts --}}
            @if($user->virtualAccounts && $user->virtualAccounts->isNotEmpty())
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-2">Dedicated Virtual Accounts</span>
                <div class="space-y-1.5">
                    @foreach($user->virtualAccounts as $va)
                    <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $va->bank_name }}</span>
                            <span class="font-mono text-slate-600 dark:text-slate-400 font-semibold select-all">{{ $va->account_number }}</span>
                        </div>
                        <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            {{ $va->providerLabel() }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- User Profile Link CTA --}}
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('admin.users.show', $user) }}" class="w-full inline-flex justify-center items-center py-2 text-xs font-semibold text-vtu-primary bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20 rounded-xl transition-colors gap-1.5">
                    <span>View User Management Profile</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 text-center text-slate-400">
            <p class="text-sm font-semibold">User account not found or deleted.</p>
        </div>
        @endif

    </div>

</div>

{{-- Notification Toast JS --}}
<script>
function copyToClipboard(text, message = 'Copied to clipboard!') {
    navigator.clipboard.writeText(text).then(function() {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-5 right-5 z-50 bg-slate-900 text-white px-4 py-2.5 rounded-xl shadow-lg text-xs font-semibold flex items-center gap-2 border border-slate-700 animate-fade-in';
        toast.innerHTML = '<span>✅</span> <span>' + message + '</span>';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    });
}
</script>

@endsection

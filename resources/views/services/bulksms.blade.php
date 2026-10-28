@extends('layouts.dashboard')

@section('title', 'Bulk SMS Portal')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="bulkSmsApp()">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Bulk SMS Portal</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Broadcast instant SMS messages to thousands of Nigerian phone numbers with customized alphanumeric Sender IDs.
        </p>
    </div>

    {{-- ── Main 5-Column Grid ───────────────────────────────────────────── --}}
    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ══ LEFT: Compose Campaign Form (3 cols) ══════════════════════════ --}}
        <div class="lg:col-span-3 space-y-4">
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Compose Campaign
                    </h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Alphanumeric Sender</span>
                </div>

                <div class="p-6 space-y-5">
                    {{-- Sender ID Input --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Sender ID <span class="text-red-500">*</span>
                            </label>
                            <span class="text-xs text-slate-400 font-mono" x-text="`${senderId.length}/11 chars`"></span>
                        </div>
                        <input type="text"
                               x-model="senderId"
                               maxlength="11"
                               @input="senderId = senderId.replace(/[^a-zA-Z0-9]/g, '')"
                               placeholder="e.g. MyBrand"
                               class="w-full px-3.5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition text-sm font-medium">
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Maximum 11 alphanumeric characters (no spaces or special symbols).
                        </p>
                    </div>

                    {{-- Recipients Input --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Recipient Phone Numbers <span class="text-red-500">*</span>
                            </label>
                            <label class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-semibold text-vtu-primary hover:underline">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Upload File (.txt / .csv)
                                <input type="file" accept=".txt,.csv" class="hidden" @change="handleFileUpload($event)">
                            </label>
                        </div>
                        <textarea x-model="recipients"
                                  @input.debounce.250ms="recalculate()"
                                  rows="5"
                                  placeholder="Type or paste phone numbers separated by comma, space, or line breaks&#10;e.g.&#10;08031234567&#10;08123456789, 09012345678"
                                  class="w-full px-3.5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition text-sm font-mono resize-y"></textarea>

                        {{-- Live Recipient Badges --}}
                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Valid Numbers: <strong class="font-bold" x-text="validRecipients.length">0</strong>
                            </span>
                            <template x-if="duplicatesRemoved > 0">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                    Duplicates Filtered: <strong x-text="duplicatesRemoved">0</strong>
                                </span>
                            </template>
                            <template x-if="invalidCount > 0">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">
                                    Invalid Inputs: <strong x-text="invalidCount">0</strong>
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Message Body --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Message Body <span class="text-red-500">*</span>
                            </label>
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                                  x-text="isUnicode ? 'Unicode (70 chars/page)' : 'GSM 7-bit (160 chars/page)'"></span>
                        </div>
                        <textarea x-model="message"
                                  @input="updateMessageStats()"
                                  rows="5"
                                  maxlength="1000"
                                  placeholder="Type your message text here..."
                                  class="w-full px-3.5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary focus:border-transparent transition text-sm"></textarea>

                        <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span x-text="`${charCount} character${charCount === 1 ? '' : 's'} (${pages} page${pages > 1 ? 's' : ''})`"></span>
                            <span x-text="`${remainingInPage} chars left in page`"></span>
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <button type="button"
                            @click="initiateBroadcast()"
                            :disabled="!canSubmit || submitting"
                            class="w-full py-3.5 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-colors flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20">
                        <template x-if="!submitting">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Broadcast Campaign
                            </span>
                        </template>
                        <template x-if="submitting">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Dispatching Campaign...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>

        {{-- ══ RIGHT: Wallet + Summary + Tips (2 cols) ═══════════════════════ --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Wallet Balance Card --}}
            <div class="rounded-2xl bg-gradient-to-br from-vtu-primary to-vtu-secondary p-5 text-white shadow-lg shadow-indigo-500/20">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs font-semibold uppercase tracking-wider opacity-70">Wallet Balance</p>
                    <span class="text-xs font-bold bg-white/20 backdrop-blur-sm px-2.5 py-0.5 rounded-full">₦{{ number_format($rate, 2) }} / unit</span>
                </div>
                <p class="text-3xl font-outfit font-bold" id="wallet-balance-display">
                    ₦{{ number_format((float) ($user->wallet?->balance ?? 0), 2) }}
                </p>
                <a href="{{ route('wallet.fund.gateway') }}"
                   class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold opacity-80 hover:opacity-100 transition">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Fund Wallet
                </a>
            </div>

            {{-- Live Campaign Summary Card --}}
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-3">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Campaign Summary</h3>
                    <span class="text-xs text-slate-400">Live Breakdown</span>
                </div>

                <div class="space-y-2.5 text-xs sm:text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Rate per Unit:</span>
                        <span class="font-medium text-slate-900 dark:text-white">₦{{ number_format($rate, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Valid Recipients:</span>
                        <span class="font-bold text-slate-900 dark:text-white" x-text="validRecipients.length">0</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Pages per Recipient:</span>
                        <span class="font-medium text-slate-900 dark:text-white" x-text="pages">1</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Total SMS Units:</span>
                        <span class="font-medium text-slate-900 dark:text-white" x-text="validRecipients.length * pages">0</span>
                    </div>
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <span class="font-semibold text-slate-700 dark:text-slate-300">Total Cost:</span>
                        <span class="text-xl font-outfit font-bold text-vtu-primary" x-text="formattedTotalCost">₦0.00</span>
                    </div>
                </div>

                {{-- Insufficient Balance Warning --}}
                <template x-if="!hasSufficientBalance && validRecipients.length > 0">
                    <div class="p-3 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/25 rounded-xl flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="text-xs text-rose-700 dark:text-rose-400 leading-tight">
                            <strong>Insufficient Wallet Balance.</strong><br>
                            Please top up your wallet with at least <span class="font-bold" x-text="formattedTotalCost"></span>.
                        </div>
                    </div>
                </template>
            </div>

            {{-- Tips Card --}}
            <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tips for High Delivery
                </h3>
                <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-400">
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-500 font-bold">•</span>
                        <span>Avoid generic Sender IDs like <em>INFO</em>, <em>ALERT</em>, or bank names.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-500 font-bold">•</span>
                        <span>Avoid financial terms that trigger spam gateway filters.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-500 font-bold">•</span>
                        <span>DND (Do-Not-Disturb) numbers are automatically routed via corporate bypass.</span>
                    </li>
                </ul>
            </div>

        </div>
    </div>

    {{-- ── Sent Campaigns History ───────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Sent Campaign History</h2>
            <span class="text-xs text-slate-400">{{ $campaigns->total() }} total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Date & Reference</th>
                        <th class="px-6 py-3 font-semibold">Sender ID</th>
                        <th class="px-6 py-3 font-semibold">Recipients</th>
                        <th class="px-6 py-3 font-semibold">Pages</th>
                        <th class="px-6 py-3 font-semibold">Total Cost</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($campaigns as $camp)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-medium text-slate-900 dark:text-white block">{{ $camp->created_at->format('d M Y, h:i A') }}</span>
                            <span class="text-xs text-slate-400 font-mono">{{ $camp->reference }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 dark:bg-indigo-500/10 text-vtu-primary border border-indigo-100 dark:border-indigo-500/20 font-mono">
                                {{ $camp->sender_id }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($camp->recipient_count) }}</span>
                            <span class="text-xs text-slate-400 block truncate max-w-xs">{{ implode(', ', array_slice((array) $camp->recipients, 0, 2)) }}...</span>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-700 dark:text-slate-300">
                            {{ $camp->pages }} {{ Str::plural('page', $camp->pages) }}
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                            ₦{{ number_format($camp->total_amount, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            @if($camp->status === 'sent')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Sent
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    Failed
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            No Bulk SMS campaigns sent yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
            {{ $campaigns->links() }}
        </div>
        @endif
    </div>

</div>

{{-- ── Result / Status Modal ─────────────────────────────────────────────── --}}
<div id="result-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeResultModal()"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-2xl p-6 text-center">
        <div id="result-icon" class="mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center"></div>
        <h3 id="result-title" class="text-lg font-outfit font-bold text-slate-900 dark:text-white mb-2"></h3>
        <p id="result-message" class="text-sm text-slate-500 dark:text-slate-400 mb-5"></p>
        <button onclick="closeResultModal()"
                class="w-full py-3 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-650 text-white transition-colors">
            Done
        </button>
    </div>
</div>

<script>
function bulkSmsApp() {
    return {
        senderId: '{{ $defaultSender }}',
        recipients: '',
        message: '',
        unitRate: {{ (float) $rate }},
        walletBalance: {{ (float) ($user->wallet?->balance ?? 0) }},

        validRecipients: [],
        duplicatesRemoved: 0,
        invalidCount: 0,

        charCount: 0,
        pages: 1,
        isUnicode: false,
        remainingInPage: 160,

        submitting: false,

        init() {
            this.recalculate();
            this.updateMessageStats();
        },

        get totalCost() {
            return this.validRecipients.length * this.pages * this.unitRate;
        },

        get formattedTotalCost() {
            return '₦' + this.totalCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        get hasSufficientBalance() {
            return this.walletBalance >= this.totalCost;
        },

        get canSubmit() {
            return this.senderId.trim().length > 0
                && this.validRecipients.length > 0
                && this.message.trim().length > 0
                && this.hasSufficientBalance;
        },

        updateMessageStats() {
            const text = this.message || '';
            const len = text.length;

            // Unicode detection
            const isUnicode = /[^\x20-\x7E\r\n\t]/.test(text);
            const singleLimit = isUnicode ? 70 : 160;
            const concatLimit = isUnicode ? 67 : 153;

            let pages = 1;
            let remaining = singleLimit;

            if (len > 0) {
                if (len <= singleLimit) {
                    pages = 1;
                    remaining = singleLimit - len;
                } else {
                    pages = Math.ceil(len / concatLimit);
                    remaining = (pages * concatLimit) - len;
                }
            }

            this.charCount = len;
            this.isUnicode = isUnicode;
            this.pages = Math.max(1, pages);
            this.remainingInPage = Math.max(0, remaining);
        },

        recalculate() {
            const rawTokens = (this.recipients || '').split(/[\s,;|]+/);
            const validSet = new Set();
            let dups = 0;
            let invalid = 0;

            for (let raw of rawTokens) {
                let cleaned = (raw || '').replace(/[^0-9+]/g, '').trim();
                if (!cleaned) continue;

                if (cleaned.startsWith('+234')) {
                    cleaned = cleaned.substring(1);
                } else if (cleaned.startsWith('0') && cleaned.length === 11) {
                    cleaned = '234' + cleaned.substring(1);
                } else if (cleaned.length === 10 && /^[789]/.test(cleaned)) {
                    cleaned = '234' + cleaned;
                }

                // Standard Nigerian format (13 digits: 234...) or international (10-15 digits)
                if (/^234[789][01]\d{8}$/.test(cleaned) || /^\d{10,15}$/.test(cleaned)) {
                    if (validSet.has(cleaned)) {
                        dups++;
                    } else {
                        validSet.add(cleaned);
                    }
                } else {
                    invalid++;
                }
            }

            this.validRecipients = Array.from(validSet);
            this.duplicatesRemoved = dups;
            this.invalidCount = invalid;
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                if (this.recipients && this.recipients.trim().length > 0) {
                    this.recipients += '\n' + text;
                } else {
                    this.recipients = text;
                }
                this.recalculate();
            };
            reader.readAsText(file);
        },

        initiateBroadcast() {
            if (!this.canSubmit || this.submitting) return;

            // Trigger the platform-wide transaction PIN modal
            if (typeof requirePinConfirmation === 'function') {
                requirePinConfirmation((pin) => {
                    this.executeBroadcast(pin);
                });
            } else {
                const pin = prompt('Enter your 4-digit Transaction PIN:');
                if (pin) this.executeBroadcast(pin);
            }
        },

        async executeBroadcast(pin) {
            if (!pin || pin.length !== 4) return;

            this.submitting = true;

            try {
                const response = await fetch('{{ route('services.bulksms.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        sender_id: this.senderId,
                        message: this.message,
                        recipients: this.validRecipients.join(','),
                        transaction_pin: pin,
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.showResultModal(false, 'Dispatch Failed', data.message || 'SMS broadcast failed.');
                    this.submitting = false;
                    return;
                }

                this.showResultModal(true, 'Campaign Broadcasted!', data.message || 'Bulk SMS sent successfully.');
            } catch (err) {
                this.showResultModal(false, 'Network Error', 'A network connection error occurred. Please try again.');
                this.submitting = false;
            }
        },

        showResultModal(success, title, message) {
            const modal = document.getElementById('result-modal');
            const icon = document.getElementById('result-icon');
            const titleEl = document.getElementById('result-title');
            const msgEl = document.getElementById('result-message');

            titleEl.textContent = title;
            msgEl.textContent = message;

            if (success) {
                icon.className = 'mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400';
                icon.innerHTML = `<svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
            } else {
                icon.className = 'mx-auto mb-4 h-14 w-14 rounded-full flex items-center justify-center bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400';
                icon.innerHTML = `<svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>`;
            }

            modal.classList.remove('hidden');
        }
    };
}

function closeResultModal() {
    const modal = document.getElementById('result-modal');
    modal.classList.add('hidden');
    window.location.reload();
}
</script>
@endsection

@extends('layouts.dashboard')

@section('title', 'Bulk SMS Portal')

@section('content')
<div class="max-w-6xl mx-auto space-y-6 pb-12" x-data="bulkSmsApp()">

    {{-- ── Header Banner ──────────────────────────────────────────────────────── --}}
    <div class="glass-panel p-6 sm:p-8 rounded-3xl relative overflow-hidden bg-gradient-to-br from-indigo-900/40 via-slate-900/60 to-slate-900/90 border border-slate-800">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    High-Delivery Messaging Engine
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Bulk SMS Portal</h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1 max-w-xl">
                    Broadcast instant SMS messages to thousands of Nigerian phone numbers with customized alphanumeric Sender IDs and live delivery status.
                </p>
            </div>

            <div class="flex flex-wrap sm:flex-nowrap items-center gap-3">
                <div class="px-4 py-3 rounded-2xl bg-slate-900/80 border border-slate-800 text-right">
                    <span class="block text-[11px] text-slate-400 font-medium uppercase tracking-wider">SMS Rate</span>
                    <span class="text-lg font-black text-emerald-400">₦{{ number_format($rate, 2) }} <span class="text-xs font-normal text-slate-400">/ unit</span></span>
                </div>
                <div class="px-4 py-3 rounded-2xl bg-slate-900/80 border border-slate-800 text-right">
                    <span class="block text-[11px] text-slate-400 font-medium uppercase tracking-wider">Wallet Balance</span>
                    <span class="text-lg font-black text-white">₦{{ number_format($user->wallet?->balance ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Grid ──────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left 2 Columns: Compose SMS Form --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-panel p-6 sm:p-7 rounded-3xl border border-slate-800 bg-slate-900/60 space-y-5">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Compose Campaign
                </h2>

                {{-- Sender ID Input --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                            Sender ID <span class="text-rose-400">*</span>
                        </label>
                        <span class="text-[11px] text-slate-400 font-mono" x-text="`${senderId.length}/11 chars`"></span>
                    </div>
                    <input type="text"
                           x-model="senderId"
                           maxlength="11"
                           @input="senderId = senderId.replace(/[^a-zA-Z0-9]/g, '')"
                           placeholder="e.g. MyBrand"
                           class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white font-medium text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all placeholder-slate-600">
                    <p class="text-[11px] text-slate-400 mt-1">
                        Maximum 11 alphanumeric characters (no spaces or special symbols).
                    </p>
                </div>

                {{-- Recipients Input --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                            Recipient Phone Numbers <span class="text-rose-400">*</span>
                        </label>
                        <label class="cursor-pointer inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Upload File (.txt / .csv)
                            <input type="file" accept=".txt,.csv" class="hidden" @change="handleFileUpload($event)">
                        </label>
                    </div>

                    <textarea x-model="recipients"
                              @input.debounce.300ms="recalculate()"
                              rows="5"
                              placeholder="Type or paste phone numbers separated by comma, space, or line breaks&#10;e.g.&#10;08031234567&#10;08123456789, 09012345678"
                              class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs sm:text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all placeholder-slate-600 resize-y"></textarea>

                    {{-- Live Recipient Stats Bar --}}
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-medium">
                            Valid Numbers: <strong class="font-bold" x-text="validRecipients.length">0</strong>
                        </span>
                        <template x-if="duplicatesRemoved > 0">
                            <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400">
                                Duplicates Filtered: <strong x-text="duplicatesRemoved">0</strong>
                            </span>
                        </template>
                        <template x-if="invalidCount > 0">
                            <span class="px-2.5 py-1 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400">
                                Invalid Format: <strong x-text="invalidCount">0</strong>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Message Body Input --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                            Message Body <span class="text-rose-400">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <template x-if="isUnicode">
                                <span class="px-2 py-0.5 rounded text-[10px] bg-purple-500/20 text-purple-300 font-semibold border border-purple-500/30">
                                    Unicode (70 chars/page)
                                </span>
                            </template>
                            <template x-if="!isUnicode">
                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 font-semibold border border-slate-700">
                                    GSM 7-bit (160 chars/page)
                                </span>
                            </template>
                        </div>
                    </div>

                    <textarea x-model="message"
                              @input="recalculate()"
                              rows="5"
                              placeholder="Type your SMS message here..."
                              class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all placeholder-slate-600 resize-y"></textarea>

                    {{-- Live Character & Page Meter --}}
                    <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                        <div class="flex items-center gap-3">
                            <span>Length: <strong class="text-white" x-text="charCount">0</strong> chars</span>
                            <span>Pages: <strong class="text-indigo-400 font-bold" x-text="pages">1</strong> page(s)</span>
                        </div>
                        <span class="text-slate-500" x-text="`${remainingChars} chars remaining in current page`"></span>
                    </div>
                </div>

                {{-- Action Trigger --}}
                <div class="pt-2">
                    <button type="button"
                            @click="openConfirmationModal()"
                            :disabled="!canSubmit"
                            class="w-full py-3.5 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span>Send Bulk SMS</span>
                        <span x-show="totalCost > 0" class="text-indigo-200 text-xs font-normal" x-text="`(${formattedCost})`"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Right 1 Column: Campaign Pricing Summary Card --}}
        <div class="space-y-6">
            <div class="glass-panel p-6 rounded-3xl border border-slate-800 bg-slate-900/60 space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Campaign Summary
                </h3>

                <div class="space-y-3 divide-y divide-slate-800 text-xs">
                    <div class="flex items-center justify-between pt-2">
                        <span class="text-slate-400">Rate per Unit:</span>
                        <span class="font-bold text-white">₦{{ number_format($rate, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <span class="text-slate-400">Valid Recipients:</span>
                        <span class="font-bold text-white" x-text="validRecipients.length">0</span>
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <span class="text-slate-400">Pages per Recipient:</span>
                        <span class="font-bold text-indigo-400" x-text="pages">1</span>
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <span class="text-slate-400">Total SMS Units:</span>
                        <span class="font-bold text-white" x-text="totalUnits">0</span>
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <span class="text-slate-300 font-semibold">Total Cost:</span>
                        <span class="font-black text-emerald-400 text-base" x-text="formattedCost">₦0.00</span>
                    </div>
                </div>

                {{-- Balance Warning Alert --}}
                <div x-show="totalCost > 0 && !hasSufficientBalance"
                     class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-start gap-2.5">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <strong class="font-bold">Insufficient Balance</strong>
                        <p class="mt-0.5 text-rose-300/80">Please fund your wallet to complete this broadcast.</p>
                        <a href="{{ route('wallet.fund.gateway') }}" class="inline-block mt-2 text-rose-400 hover:underline font-semibold">
                            Fund Wallet &rarr;
                        </a>
                    </div>
                </div>

                {{-- Delivery Guidelines --}}
                <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-[11px] text-slate-400 space-y-1.5">
                    <p class="font-semibold text-slate-300">Tips for High Delivery:</p>
                    <ul class="list-disc list-inside space-y-1 text-slate-400">
                        <li>Do not use generic sender IDs like "INFO" or "ALERT".</li>
                        <li>Avoid financial terms that trigger network spam filters.</li>
                        <li>DND (Do-Not-Disturb) numbers are routed via corporate bypass.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Campaign History Table ─────────────────────────────────────────────── --}}
    <div class="glass-panel p-6 sm:p-7 rounded-3xl border border-slate-800 bg-slate-900/60 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Sent Campaigns History
            </h3>
        </div>

        @if($campaigns->isEmpty())
            <div class="py-12 text-center text-slate-500 text-xs">
                <svg class="w-10 h-10 mx-auto text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                You have not sent any Bulk SMS campaigns yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[10px]">
                            <th class="pb-3 font-semibold">Reference</th>
                            <th class="pb-3 font-semibold">Sender ID</th>
                            <th class="pb-3 font-semibold">Message Preview</th>
                            <th class="pb-3 font-semibold text-center">Recipients</th>
                            <th class="pb-3 font-semibold text-center">Pages</th>
                            <th class="pb-3 font-semibold text-right">Cost</th>
                            <th class="pb-3 font-semibold text-center">Status</th>
                            <th class="pb-3 font-semibold text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono">
                        @foreach($campaigns as $c)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="py-3 text-slate-300 font-bold">{{ $c->reference }}</td>
                                <td class="py-3 text-white font-semibold">{{ $c->sender_id }}</td>
                                <td class="py-3 text-slate-400 font-sans max-w-xs truncate" title="{{ $c->message }}">
                                    {{ Str::limit($c->message, 45) }}
                                </td>
                                <td class="py-3 text-center text-slate-300">{{ number_format($c->recipient_count) }}</td>
                                <td class="py-3 text-center text-indigo-400 font-bold">{{ $c->pages }}</td>
                                <td class="py-3 text-right font-bold text-emerald-400">₦{{ number_format($c->total_amount, 2) }}</td>
                                <td class="py-3 text-center font-sans">
                                    @if($c->status === 'sent')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Sent</span>
                                    @elseif($c->status === 'failed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">Failed</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">{{ ucfirst($c->status) }}</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right text-slate-500 font-sans text-[11px]">{{ $c->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>

    {{-- ── Transaction PIN Modal ─────────────────────────────────────────────── --}}
    <div x-show="showPinModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @keydown.escape.window="if(!submitting) showPinModal = false">
        <div class="glass-panel p-6 sm:p-7 rounded-3xl max-w-md w-full bg-slate-900 border border-slate-800 space-y-5"
             @click.outside="if(!submitting) showPinModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Authorize Broadcast
                </h3>
                <button type="button" @click="showPinModal = false" :disabled="submitting" class="text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 space-y-2 text-xs">
                <div class="flex justify-between text-slate-400">
                    <span>Sender ID:</span>
                    <strong class="text-white font-mono" x-text="senderId"></strong>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>Recipients:</span>
                    <strong class="text-white font-mono" x-text="validRecipients.length"></strong>
                </div>
                <div class="flex justify-between text-slate-400">
                    <span>Pages:</span>
                    <strong class="text-indigo-400 font-mono" x-text="pages"></strong>
                </div>
                <div class="flex justify-between text-slate-400 pt-2 border-t border-slate-800">
                    <span class="font-semibold text-slate-300">Total Debit:</span>
                    <strong class="text-emerald-400 font-black text-sm" x-text="formattedCost"></strong>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2 text-center">
                    Enter 4-Digit Transaction PIN
                </label>
                <input type="password"
                       x-model="pin"
                       maxlength="4"
                       inputmode="numeric"
                       placeholder="••••"
                       class="w-full py-3 text-center text-2xl tracking-[0.5em] font-mono rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="button"
                        @click="showPinModal = false"
                        :disabled="submitting"
                        class="flex-1 py-3 px-4 rounded-xl border border-slate-800 hover:bg-slate-800 text-slate-300 font-semibold text-xs transition">
                    Cancel
                </button>
                <button type="button"
                        @click="submitBroadcast()"
                        :disabled="pin.length !== 4 || submitting"
                        class="flex-1 py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2">
                    <template x-if="submitting">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </template>
                    <span x-text="submitting ? 'Broadcasting...' : 'Confirm & Send'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

{{-- ── Alpine.js App Script ─────────────────────────────────────────────────── --}}
<script>
function bulkSmsApp() {
    return {
        senderId: '{{ old('sender_id', $defaultSender) }}',
        message: '',
        recipients: '',
        unitRate: {{ (float) $rate }},
        walletBalance: {{ (float) ($user->wallet?->balance ?? 0) }},
        charCount: 0,
        pages: 1,
        isUnicode: false,
        remainingChars: 160,
        validRecipients: [],
        duplicatesRemoved: 0,
        invalidCount: 0,
        pin: '',
        showPinModal: false,
        submitting: false,

        init() {
            this.recalculate();
        },

        get totalUnits() {
            return this.validRecipients.length * this.pages;
        },

        get totalCost() {
            return this.totalUnits * this.unitRate;
        },

        get formattedCost() {
            return '₦' + this.totalCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        get hasSufficientBalance() {
            return this.walletBalance >= this.totalCost;
        },

        get canSubmit() {
            return this.senderId.trim().length > 0 &&
                   this.validRecipients.length > 0 &&
                   this.message.trim().length > 0 &&
                   this.hasSufficientBalance &&
                   !this.submitting;
        },

        recalculate() {
            // GSM calculation
            const msg = this.message || '';
            this.charCount = msg.length;
            this.isUnicode = /[^\u0020-\u007E\r\n\t]/.test(msg);

            const singleLimit = this.isUnicode ? 70 : 160;
            const concatLimit = this.isUnicode ? 67 : 153;

            if (this.charCount === 0) {
                this.pages = 1;
                this.remainingChars = singleLimit;
            } else if (this.charCount <= singleLimit) {
                this.pages = 1;
                this.remainingChars = singleLimit - this.charCount;
            } else {
                this.pages = Math.ceil(this.charCount / concatLimit);
                this.remainingChars = (this.pages * concatLimit) - this.charCount;
            }

            // Recipient sanitization
            const raw = this.recipients || '';
            const tokens = raw.split(/[\s,;|]+/).filter(t => t.trim().length > 0);

            const validSet = new Set();
            let dups = 0;
            let invalid = 0;

            for (let t of tokens) {
                let cleaned = t.replace(/[^0-9+]/g, '').trim();
                if (!cleaned) continue;

                if (cleaned.startsWith('+234')) {
                    cleaned = cleaned.substring(1);
                } else if (cleaned.startsWith('0') && cleaned.length === 11) {
                    cleaned = '234' + cleaned.substring(1);
                } else if (cleaned.length === 10 && ['7','8','9'].includes(cleaned.charAt(0))) {
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
                if (this.recipients.trim().length > 0) {
                    this.recipients += '\n' + text;
                } else {
                    this.recipients = text;
                }
                this.recalculate();
            };
            reader.readAsText(file);
        },

        openConfirmationModal() {
            if (!this.canSubmit) return;
            this.pin = '';
            this.showPinModal = true;
        },

        async submitBroadcast() {
            if (this.pin.length !== 4 || this.submitting) return;

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
                        transaction_pin: this.pin,
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    alert(data.message || 'Bulk SMS dispatch failed.');
                    this.submitting = false;
                    return;
                }

                alert(data.message || 'Campaign broadcasted successfully!');
                window.location.reload();
            } catch (err) {
                alert('A network error occurred. Please try again.');
                this.submitting = false;
            }
        }
    };
}
</script>
@endsection

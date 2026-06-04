@extends('layouts.dashboard')

@section('title', 'Contact Support')

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- ── Page Header ──────────────────────────────────────────────────────── --}}
    <div class="mb-6">
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Contact Us</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">We're here to help with any questions or issues.</p>
    </div>

    {{-- ── Main Card ────────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

        {{-- Illustration + headline --}}
        <div class="flex flex-col items-center text-center px-8 pt-12 pb-8">
            {{-- SVG illustration (megaphone + chat bubbles) --}}
            <div class="relative mb-6 select-none" aria-hidden="true">
                <svg width="130" height="110" viewBox="0 0 130 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                    {{-- Megaphone body --}}
                    <ellipse cx="68" cy="62" rx="28" ry="18" fill="#FFA94D" opacity="0.15"/>
                    <path d="M42 52 L78 34 L78 78 L42 68 Z" fill="#FF922B" rx="4"/>
                    <rect x="30" y="52" width="14" height="18" rx="3" fill="#FD7E14"/>
                    {{-- Bell of megaphone --}}
                    <ellipse cx="79" cy="56" rx="6" ry="11" fill="#E8590C"/>
                    {{-- Handle --}}
                    <rect x="27" y="68" width="6" height="10" rx="3" fill="#E8590C"/>
                    {{-- Blue chat bubble --}}
                    <rect x="5" y="8" width="52" height="36" rx="12" fill="#339AF0"/>
                    <path d="M18 44 L14 54 L28 44Z" fill="#339AF0"/>
                    <circle cx="21" cy="26" r="4" fill="white"/>
                    <circle cx="31" cy="26" r="4" fill="white"/>
                    <circle cx="41" cy="26" r="4" fill="white"/>
                    {{-- Orange chat bubble --}}
                    <rect x="74" y="2" width="48" height="34" rx="11" fill="#FF922B"/>
                    <path d="M110 36 L116 46 L100 36Z" fill="#FF922B"/>
                    <circle cx="88" cy="19" r="3.5" fill="white"/>
                    <circle cx="98" cy="19" r="3.5" fill="white"/>
                    <circle cx="108" cy="19" r="3.5" fill="white"/>
                </svg>
            </div>

            <h2 class="text-xl font-bold font-outfit text-slate-900 dark:text-white mb-2">
                How Can We Help You?
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md leading-relaxed">
                We value our customers and strive to provide exceptional service. If you have any
                questions, suggestions, or concerns, please don't hesitate to reach out to us.
            </p>
        </div>

        {{-- ── Contact Buttons ────────────────────────────────────────────── --}}
        <div class="px-8 pb-10">
            <div class="flex flex-wrap justify-center gap-3">

                {{-- WhatsApp --}}
                @if (!empty($s['support_whatsapp']))
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $s['support_whatsapp']) }}"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2.5 px-6 py-3 rounded-full text-sm font-bold text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200"
                   style="background: #25D366">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.132.559 4.13 1.535 5.86L0 24l6.343-1.509A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.89 0-3.663-.5-5.195-1.373l-.373-.22-3.766.896.944-3.668-.242-.388A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                    </svg>
                    WhatsApp
                </a>
                @endif

                {{-- Email Us --}}
                @if (!empty($s['support_email']))
                <a href="mailto:{{ $s['support_email'] }}"
                   class="inline-flex items-center gap-2.5 px-6 py-3 rounded-full text-sm font-bold text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200"
                   style="background: #F03E3E">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Email Us
                </a>
                @endif

                {{-- Call Us --}}
                @if (!empty($s['support_phone']))
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $s['support_phone']) }}"
                   class="inline-flex items-center gap-2.5 px-6 py-3 rounded-full text-sm font-bold text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200"
                   style="background: #1C7ED6">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span>
                        Call Us
                        @if (!empty($s['support_hours']))
                            <span class="font-normal opacity-90 text-xs block leading-tight">({{ $s['support_hours'] }})</span>
                        @endif
                    </span>
                </a>
                @endif

                {{-- Create a Ticket --}}
                @if (!empty($s['support_ticket_url']))
                <a href="{{ $s['support_ticket_url'] }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2.5 px-6 py-3 rounded-full text-sm font-bold text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200"
                   style="background: #F59F00">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    Create a ticket
                </a>
                @endif

                {{-- Fallback when nothing is configured yet --}}
                @if (empty($s['support_whatsapp']) && empty($s['support_phone']) && empty($s['support_email']) && empty($s['support_ticket_url']))
                <div class="text-center py-6 text-slate-400 dark:text-slate-500">
                    <svg class="h-10 w-10 mx-auto mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <p class="text-sm">Support contact details have not been configured yet.</p>
                </div>
                @endif

            </div>
        </div>

        {{-- ── Info strip ──────────────────────────────────────────────────── --}}
        @if (!empty($s['support_hours']))
        <div class="border-t border-slate-100 dark:border-slate-800 px-8 py-4 bg-slate-50/60 dark:bg-slate-800/40">
            <div class="flex items-center justify-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Support is available <strong class="text-slate-700 dark:text-slate-300 mx-1">{{ $s['support_hours'] }}</strong> on business days.
            </div>
        </div>
        @endif

    </div>

</div>
@endsection

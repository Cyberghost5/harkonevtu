<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') – @endif{{ $siteName }}</title>
    @if($siteFavicon)<link rel="icon" href="{{ Storage::url($siteFavicon) }}">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans:   ['"Plus Jakarta Sans"', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        vtu: {
                            primary:  '{{ $themeColor }}',
                            secondary:'{{ $themeSecondary }}',
                            dark:     '#0B0F19',
                            darkCard: '#1E293B',
                            light:    '#F8FAFC',
                            accent:   '#F59E0B',
                        }
                    },
                }
            }
        }
    </script>

    <style type="text/css">
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(156,163,175,.25); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(156,163,175,.45); }
    </style>

    {{-- Prevent dark-mode flash --}}
    <script>
        (function(){
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @yield('styles')
</head>
<body class="h-full overflow-hidden bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200 antialiased">

    {{-- Mobile overlay --}}
    <div id="mobile-overlay"
         onclick="closeSidebar()"
         class="fixed inset-0 bg-black/50 z-20 lg:hidden hidden transition-opacity duration-300"></div>

    <div class="flex h-screen overflow-hidden">

        {{-- ══════════════════════════════════════════════════════════════
             SIDEBAR
        ══════════════════════════════════════════════════════════════ --}}
        <aside id="sidebar"
               class="fixed lg:static inset-y-0 left-0 z-30
                      flex flex-col w-64 flex-shrink-0
                      bg-white dark:bg-slate-900
                      border-r border-slate-200 dark:border-slate-800
                      -translate-x-full lg:translate-x-0
                      transition-transform duration-300 ease-in-out">

            {{-- Logo --}}
            <div class="flex-shrink-0 flex items-center px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <a href="/" class="flex items-center space-x-2.5">
                    <div class="h-9 w-9 rounded-xl flex items-center justify-center overflow-hidden {{ $siteLogo1 ? '' : 'bg-gradient-to-tr from-vtu-primary to-vtu-secondary shadow-md shadow-indigo-500/20' }}">
                        @if($siteLogo1)
                        <img src="{{ Storage::url($siteLogo1) }}" class="h-full w-full object-contain" alt="{{ $siteName }}">
                        @else
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        @endif
                    </div>
                    <span class="text-lg font-bold font-outfit tracking-tight bg-gradient-to-r from-vtu-primary to-vtu-secondary bg-clip-text text-transparent">{{ $siteName }}</span>
                </a>
            </div>

            {{-- User Profile --}}
            <div class="flex-shrink-0 px-4 py-4 border-b border-slate-100 dark:border-slate-800 space-y-3">
                {{-- Avatar + name --}}
                <div class="flex items-center gap-3">
                    <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-vtu-primary to-vtu-secondary flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow-md shadow-indigo-500/20">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ auth()->user()->displayName() }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                {{-- Balance + type --}}
                <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-2.5">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-0.5">Balance</p>
                        <p class="text-sm font-bold text-slate-900 dark:text-white balance-display"
                           data-amount="₦{{ number_format((float) (auth()->user()->wallet?->balance ?? 0), 2) }}">
                            ₦{{ number_format((float) (auth()->user()->wallet?->balance ?? 0), 2) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="toggleBalanceVisibility()" class="text-slate-400 hover:text-vtu-primary transition-colors" title="Toggle balance">
                            <svg id="balance-eye" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <span class="text-xs font-semibold px-2 py-1 rounded-lg
                            {{ auth()->user()->isAgent()
                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
                                : 'bg-indigo-100 text-vtu-primary dark:bg-indigo-500/20 dark:text-indigo-400' }}">
                            {{ auth()->user()->isAgent() ? 'Agent' : 'Normal' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto custom-scrollbar px-3 py-3 space-y-0.5">

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}"
                   class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                          {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-500/10 text-vtu-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                {{-- Fund Wallet Accordion --}}
                <div x-data="{ open: {{ request()->routeIs('wallet.fund.*') ? 'true' : 'false' }} }">
                    <button onclick="toggleAccordion('fund-wallet-menu', this)"
                            data-accordion="fund-wallet-menu"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                                   {{ request()->routeIs('wallet.fund.*') ? 'bg-indigo-50 dark:bg-indigo-500/10 text-vtu-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            Fund Wallet
                        </div>
                        <svg id="fund-wallet-chevron" class="h-4 w-4 text-slate-400 transition-transform duration-200 {{ request()->routeIs('wallet.fund.*') ? 'rotate-90' : '' }}"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    {{-- Sub-items --}}
                    <div id="fund-wallet-menu"
                         class="{{ request()->routeIs('wallet.fund.*') ? '' : 'hidden' }} mt-0.5 ml-8 space-y-0.5 border-l border-slate-200 dark:border-slate-700 pl-3">
                        <a href="{{ route('wallet.fund.gateway') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('wallet.fund.gateway') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Card / ATM Funding
                        </a>
                        <a href="{{ route('wallet.fund.auto') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('wallet.fund.auto') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Auto Bank Transfer
                        </a>
                        <a href="{{ route('wallet.fund.manual') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('wallet.fund.manual') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Manual Funding
                        </a>
                        <a href="{{ route('wallet.fund.coupon') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('wallet.fund.coupon') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Coupon Funding
                        </a>
                    </div>
                </div>

                {{-- Services Accordion --}}
                <div>
                    <button onclick="toggleAccordion('services-menu', this)"
                            data-accordion="services-menu"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                                   {{ request()->routeIs('services.*') ? 'bg-indigo-50 dark:bg-indigo-500/10 text-vtu-primary' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            Services
                        </div>
                        <svg class="h-4 w-4 text-slate-400 transition-transform duration-200 {{ request()->routeIs('services.*') ? 'rotate-90' : '' }}"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    <div id="services-menu"
                         class="{{ request()->routeIs('services.*') ? '' : 'hidden' }} mt-0.5 ml-8 space-y-0.5 border-l border-slate-200 dark:border-slate-700 pl-3">
                        <a href="{{ route('services.airtime') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('services.airtime') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Airtime Top-Up
                        </a>
                        <!-- <a href="{{ route('services.airtime-to-cash') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('services.airtime-to-cash*') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Airtime to Cash
                        </a> -->
                        <a href="{{ route('services.data') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('services.data*') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Data Bundles
                        </a>
                        <a href="{{ route('services.electricity') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('services.electricity') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Electricity
                        </a>
                        <a href="{{ route('services.cable') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('services.cable*') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Cable TV
                        </a>
                        <a href="{{ route('services.epins') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('services.epins*') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Exam Pins
                        </a>
                        <a href="{{ route('services.betting') }}"
                           class="block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150
                                  {{ request()->routeIs('services.betting*') ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                            Betting
                        </a>
                    </div>
                </div>

                {{-- KYC Verification --}}
                @php
                    $kycStatus = auth()->user()->kyc_status ?? 'pending';
                    $dotColor = 'bg-red-500';
                    if ($kycStatus === 'verified') $dotColor = 'bg-emerald-500';
                    elseif ($kycStatus === 'submitted') $dotColor = 'bg-blue-500';
                    $kycActive = request()->routeIs('kyc.*');
                @endphp
                <a href="{{ route('kyc.index') }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                          {{ $kycActive ? 'text-vtu-primary bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-650 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>KYC Verification</span>
                    </div>
                    <span class="h-2 w-2 rounded-full {{ $dotColor }} flex-shrink-0"></span>
                </a>

                {{-- Virtual Card --}}
                <a href="#"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-150">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Virtual Card
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400 flex-shrink-0">Soon</span>
                </a>

                {{-- Referral --}}
                <a href="{{ route('referral') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('referral*') ? 'bg-[color:var(--vtu-primary)]/10 text-[color:var(--vtu-primary)] font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }} transition-all duration-150">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Referral
                </a>

                {{-- Pricing --}}
                <a href="{{ route('pricing') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('pricing') ? 'bg-[color:var(--vtu-primary)]/10 text-[color:var(--vtu-primary)] font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }} transition-all duration-150">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Pricing
                </a>

                {{-- Transaction History --}}
                <a href="{{ route('transactions') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('transactions') ? 'bg-[color:var(--vtu-primary)]/10 text-[color:var(--vtu-primary)] font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }} transition-all duration-150">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    Transaction History
                </a>

                {{-- Contact Support --}}
                <a href="{{ route('support') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('support') ? 'bg-[color:var(--vtu-primary)]/10 text-[color:var(--vtu-primary)] font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }} transition-all duration-150">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Contact Support
                </a>

                {{-- Settings --}}
                <a href="{{ route('settings') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('settings') ? 'bg-[color:var(--vtu-primary)]/10 text-[color:var(--vtu-primary)] font-semibold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }} transition-all duration-150">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </a>

                {{-- Ticket --}}
                <a href="{{ route('support') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-all duration-150">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    Ticket
                </a>

                {{-- Admin Panel (admin users only) --}}
                @if(auth()->user()->isAdmin())
                <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-all duration-150">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Admin Panel
                    <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400">Admin</span>
                </a>
                @endif

            </nav>

            {{-- Logout --}}
            <div class="flex-shrink-0 px-3 py-3 border-t border-slate-100 dark:border-slate-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all duration-150">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>

        </aside>
        {{-- /SIDEBAR --}}


        {{-- ══════════════════════════════════════════════════════════════
             MAIN AREA
        ══════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Top Header --}}
            <header class="flex-shrink-0 flex items-center justify-between h-16 px-4 sm:px-6
                           bg-white dark:bg-slate-900
                           border-b border-slate-200 dark:border-slate-800 z-10">

                {{-- Hamburger (mobile) --}}
                <button onclick="openSidebar()"
                        class="lg:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors mr-3">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                {{-- Search --}}
                <div class="flex-1 max-w-sm hidden sm:block">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" placeholder="Search Transaction..."
                               class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 focus:border-vtu-primary transition-all">
                    </div>
                </div>

                {{-- Right actions --}}
                <div class="flex items-center gap-2 ml-auto">

                    {{-- Dark mode toggle --}}
                    <button onclick="toggleTheme()"
                            class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <svg id="header-sun" class="hidden h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/>
                        </svg>
                        <svg id="header-moon" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                        </svg>
                    </button>

                    {{-- Notifications --}}
                    @php
                        $serviceTxs = auth()->user()->serviceTransactions()->latest()->take(5)->get();
                        $walletTxs = auth()->user()->walletTransactions()->whereNull('metadata->service')->latest()->take(5)->get();
                        $allNotifs = $serviceTxs->concat($walletTxs)->sortByDesc('created_at')->take(5);
                        $hasRecentNotif = $allNotifs->first() && $allNotifs->first()->created_at->gt(now()->subDay());
                    @endphp
                    <div class="relative">
                        <button onclick="toggleNotificationDropdown(event)" id="notification-btn" class="relative p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors focus:outline-none">
                            <svg class="h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($hasRecentNotif)
                            <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500 pointer-events-none"></span>
                            @endif
                        </button>
                        
                        <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Recent Notifications</span>
                            </div>
                            <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse($allNotifs as $item)
                                @if($item instanceof \App\Models\ServiceTransaction)
                                <a href="{{ route('transactions') }}" class="block px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <div class="flex gap-3">
                                        <div class="h-8 w-8 rounded-full flex items-center justify-center flex-shrink-0
                                            {{ $item->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : ($item->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400') }}">
                                            @if($item->status === 'success')
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            @elseif($item->status === 'pending')
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @else
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-medium text-slate-800 dark:text-slate-200 capitalize truncate">{{ str_replace('_', ' ', $item->service_type) }} purchase &mdash; {{ $item->status }}</p>
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Amount: ₦{{ number_format($item->amount, 2) }} &bull; {{ $item->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </a>
                                @else
                                <a href="{{ route('transactions') }}" class="block px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <div class="flex gap-3">
                                        <div class="h-8 w-8 rounded-full flex items-center justify-center flex-shrink-0
                                            {{ $item->type === 'credit' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400' }}">
                                            @if($item->type === 'credit')
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                            @else
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-medium text-slate-800 dark:text-slate-200 truncate">Wallet {{ ucfirst($item->type) }}</p>
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $item->description }} (₦{{ number_format($item->amount, 2) }}) &bull; {{ $item->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </a>
                                @endif
                                @empty
                                <div class="px-4 py-6 text-center text-xs text-slate-400 dark:text-slate-500">
                                    <svg class="mx-auto h-8 w-8 text-slate-350 dark:text-slate-700 mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    No recent activity
                                </div>
                                @endforelse
                            </div>
                            <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800/30 text-center border-t border-slate-100 dark:border-slate-800">
                                <a href="{{ route('transactions') }}" class="text-[11px] font-semibold text-vtu-primary hover:underline">View All History</a>
                            </div>
                        </div>
                    </div>

                    {{-- Avatar / Profile Dropdown --}}
                    <div class="relative">
                        <button onclick="toggleProfileDropdown(event)" id="profile-btn" class="h-9 w-9 rounded-xl bg-gradient-to-tr from-vtu-primary to-vtu-secondary flex items-center justify-center text-white text-xs font-bold shadow-md shadow-indigo-500/20 focus:outline-none focus:ring-2 focus:ring-vtu-primary/50">
                            {{ auth()->user()->initials() }}
                        </button>
                        
                        <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('settings') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    My Profile
                                </a>
                                <a href="{{ route('transactions') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    Transactions
                                </a>
                                <a href="{{ route('pricing') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    Pricing Table
                                </a>
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-rose-650 dark:text-rose-450 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Admin Panel
                                </a>
                                @endif
                            </div>
                            <div class="border-t border-slate-100 dark:border-slate-800 py-1 bg-slate-50/50 dark:bg-slate-800/20">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs text-red-650 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto custom-scrollbar p-4 sm:p-6 bg-slate-100 dark:bg-slate-950">
                @yield('content')
            </main>

        </div>
        {{-- /MAIN AREA --}}

    </div>

    {{-- ── Scripts ─────────────────────────────────────────────────────────── --}}
    <script>
        // Sidebar toggle
        function openSidebar() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('mobile-overlay').classList.remove('hidden');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('mobile-overlay').classList.add('hidden');
        }

        // Accordion toggle for sidebar sub-menus
        function toggleAccordion(menuId, btn) {
            const menu    = document.getElementById(menuId);
            const chevron = document.getElementById(menuId + '-chevron') || btn.querySelector('svg:last-child');
            const hidden  = menu.classList.contains('hidden');
            menu.classList.toggle('hidden', !hidden);
            if (chevron) chevron.classList.toggle('rotate-90', hidden);
        }

        // Dark mode toggle
        function toggleTheme() {
            var html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            updateThemeIcons();
        }
        function updateThemeIcons() {
            var isDark = document.documentElement.classList.contains('dark');
            document.getElementById('header-sun').classList.toggle('hidden', !isDark);
            document.getElementById('header-moon').classList.toggle('hidden', isDark);
        }
        updateThemeIcons();

        // Dropdown toggles
        function toggleNotificationDropdown(e) {
            var drop = document.getElementById('notification-dropdown');
            var profileDrop = document.getElementById('profile-dropdown');
            profileDrop.classList.add('hidden');
            drop.classList.toggle('hidden');
            if (e) e.stopPropagation();
        }
        function toggleProfileDropdown(e) {
            var drop = document.getElementById('profile-dropdown');
            var notifDrop = document.getElementById('notification-dropdown');
            notifDrop.classList.add('hidden');
            drop.classList.toggle('hidden');
            if (e) e.stopPropagation();
        }
        window.addEventListener('click', function(e) {
            var notifDrop = document.getElementById('notification-dropdown');
            var profileDrop = document.getElementById('profile-dropdown');
            var notifBtn = document.getElementById('notification-btn');
            var profileBtn = document.getElementById('profile-btn');

            if (notifDrop && !notifDrop.classList.contains('hidden') && !notifDrop.contains(e.target) && e.target !== notifBtn && !notifBtn.contains(e.target)) {
                notifDrop.classList.add('hidden');
            }
            if (profileDrop && !profileDrop.classList.contains('hidden') && !profileDrop.contains(e.target) && e.target !== profileBtn && !profileBtn.contains(e.target)) {
                profileDrop.classList.add('hidden');
            }
        });

        // Balance visibility toggle (sidebar + all balance-display elements on page)
        var balanceHidden = false;
        function toggleBalanceVisibility() {
            balanceHidden = !balanceHidden;
            var elements = document.querySelectorAll('.balance-display');
            elements.forEach(function(el) {
                if (balanceHidden) {
                    el.textContent = '₦ ••••••';
                } else {
                    el.textContent = el.dataset.amount;
                }
            });
            var eye = document.getElementById('balance-eye');
            if (balanceHidden) {
                eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            } else {
                eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }
    </script>

    @yield('scripts')

    {{-- ── Global PIN Confirmation Modal ───────────────────────────────────── --}}
    <div id="pin-modal"
         class="fixed inset-0 z-[999] hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm p-4"
         role="dialog" aria-modal="true" aria-labelledby="pin-modal-title">
        <div class="w-full max-w-sm rounded-3xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-2xl p-6">
            <div class="text-center mb-5">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-vtu-primary to-vtu-secondary mb-3">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h2 id="pin-modal-title" class="text-lg font-outfit font-bold text-slate-900 dark:text-white">Confirm Transaction</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Enter your 4-digit transaction PIN to continue</p>
            </div>

            <div class="flex justify-center gap-3 mb-2" id="modal-pin-grid">
                <input type="password" inputmode="numeric" maxlength="1" pattern="[0-9]*"
                       class="modal-pin-digit w-12 h-12 text-center text-xl font-bold rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-vtu-primary focus:ring-4 focus:ring-vtu-primary/10 transition-all"/>
                <input type="password" inputmode="numeric" maxlength="1" pattern="[0-9]*"
                       class="modal-pin-digit w-12 h-12 text-center text-xl font-bold rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-vtu-primary focus:ring-4 focus:ring-vtu-primary/10 transition-all"/>
                <input type="password" inputmode="numeric" maxlength="1" pattern="[0-9]*"
                       class="modal-pin-digit w-12 h-12 text-center text-xl font-bold rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-vtu-primary focus:ring-4 focus:ring-vtu-primary/10 transition-all"/>
                <input type="password" inputmode="numeric" maxlength="1" pattern="[0-9]*"
                       class="modal-pin-digit w-12 h-12 text-center text-xl font-bold rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-vtu-primary focus:ring-4 focus:ring-vtu-primary/10 transition-all"/>
            </div>

            <p id="pin-modal-error" class="hidden text-xs text-center text-red-500 mb-3"></p>

            @if(auth()->user()->webauthnCredentials->isNotEmpty())
            <div class="flex flex-col items-center mt-4">
                <div class="flex items-center justify-center w-full my-3">
                    <div class="border-t border-slate-200 dark:border-slate-800 w-1/4"></div>
                    <span class="mx-3 text-[9px] uppercase font-bold text-slate-400">or use biometric</span>
                    <div class="border-t border-slate-200 dark:border-slate-800 w-1/4"></div>
                </div>
                <button type="button" onclick="triggerBiometricPinBypass()" id="modal-biometric-btn"
                        class="h-12 w-12 rounded-full flex items-center justify-center bg-vtu-primary text-white shadow-md hover:scale-105 transition-transform duration-150 relative">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 009 11a5 5 0 00-10 0c0 .768.111 1.51.319 2.214m12.438-10.462A9.947 9.947 0 0114 11c0 1.259-.234 2.463-.66 3.575m0 0a3 3 0 10-4.47-4.47m3.44 2.214a13.916 13.916 0 01-2.18 7.74" />
                    </svg>
                    <svg id="modal-biometric-spinner" class="hidden absolute inset-0 h-12 w-12 animate-spin text-vtu-primary" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Scan Fingerprint</p>
            </div>
            @endif

            <div class="flex gap-3 mt-5">
                <button onclick="closePinModal()" type="button"
                        class="flex-1 py-3 rounded-xl text-sm font-semibold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    Cancel
                </button>
                <button id="pin-modal-confirm" type="button"
                        onclick="confirmPin()"
                        class="flex-1 py-3 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 text-white transition-colors">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <script>
        // ─── Global PIN Modal ──────────────────────────────────────────────────
        let _pinCallback = null;

        const _pinModal   = document.getElementById('pin-modal');
        const _pinDigits  = document.querySelectorAll('.modal-pin-digit');
        const _pinErr     = document.getElementById('pin-modal-error');

        // Wire up digit inputs
        _pinDigits.forEach((inp, i) => {
            inp.addEventListener('input', function () {
                this.value = this.value.replace(/\D/, '');
                if (this.value && i < _pinDigits.length - 1) _pinDigits[i + 1].focus();
            });
            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && i > 0) _pinDigits[i - 1].focus();
                if (e.key === 'Enter') confirmPin();
            });
        });

        function openPinModal() {
            _pinModal.classList.remove('hidden');
            _pinModal.classList.add('flex');
            _pinDigits.forEach(d => {
                d.value = '';
                d.classList.remove('border-red-400');
                d.classList.add('border-slate-300', 'dark:border-slate-600');
            });
            _pinErr.classList.add('hidden');
            _pinErr.textContent = '';
            setTimeout(() => _pinDigits[0]?.focus(), 50);
        }

        function closePinModal() {
            _pinModal.classList.add('hidden');
            _pinModal.classList.remove('flex');
            _pinCallback = null;
        }

        /**
         * Show the PIN modal. When confirmed, calls callback(pin).
         * @param {function} callback - receives the 4-digit pin string
         */
        function requirePinConfirmation(callback) {
            _pinCallback = callback;
            openPinModal();
        }

        function confirmPin() {
            const pin = Array.from(_pinDigits).map(d => d.value).join('');
            if (pin.length < 4) {
                setPinError('Please enter all 4 digits.');
                return;
            }
            const cb = _pinCallback;
            closePinModal();
            if (cb) cb(pin);
        }

        function setPinError(msg) {
            _pinErr.textContent = msg;
            _pinErr.classList.remove('hidden');
            _pinDigits.forEach(d => {
                d.classList.remove('border-slate-300', 'dark:border-slate-600');
                d.classList.add('border-red-400');
            });
            _pinDigits.forEach(d => d.value = '');
            _pinDigits[0]?.focus();
        }

        // WebAuthn helpers for Biometric PIN Bypass
        function bufferToBase64url(buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            return btoa(binary)
                .replace(/\+/g, '-')
                .replace(/\//g, '_')
                .replace(/=/g, '');
        }

        function base64urlToBuffer(base64url) {
            let base64 = base64url
                .replace(/-/g, '+')
                .replace(/_/g, '/');
            while (base64.length % 4) {
                base64 += '=';
            }
            const binary = atob(base64);
            const buffer = new ArrayBuffer(binary.length);
            const bytes = new Uint8Array(buffer);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return buffer;
        }

        async function triggerBiometricPinBypass() {
            try {
                const spinner = document.getElementById('modal-biometric-spinner');
                const btn = document.getElementById('modal-biometric-btn');
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                _pinErr.classList.add('hidden');
                _pinErr.textContent = '';

                if (spinner) spinner.classList.remove('hidden');
                if (btn) btn.disabled = true;

                // 1. Fetch challenge options
                const response = await fetch('/lockscreen/fingerprint/options', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    }
                });

                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }

                // 2. Decode standard WebAuthn parameters
                const options = data.publicKey;
                options.challenge = base64urlToBuffer(options.challenge);

                if (options.allowCredentials) {
                    options.allowCredentials = options.allowCredentials.map(cred => {
                        cred.id = base64urlToBuffer(cred.id);
                        return cred;
                    });
                }

                // 3. Prompt user's biometric authenticator
                const credential = await navigator.credentials.get({
                    publicKey: options
                });

                if (!credential) {
                    throw new Error('Biometric signature failed or was cancelled.');
                }

                // 4. Encode assertion signature results
                const assertion = {
                    id: credential.id,
                    clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                    authenticatorData: bufferToBase64url(credential.response.authenticatorData),
                    signature: bufferToBase64url(credential.response.signature)
                };

                // 5. Send assertion verification request
                const verifyResponse = await fetch('/lockscreen/fingerprint/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(assertion)
                });

                const verifyData = await verifyResponse.json();
                if (verifyData.success) {
                    // Success! Invoke PIN callback with dummy PIN '9999' which passes request validations
                    const cb = _pinCallback;
                    closePinModal();
                    if (cb) cb('9999');
                } else {
                    throw new Error(verifyData.error || 'Biometric verification failed.');
                }

            } catch (err) {
                console.error(err);
                if (err.name !== 'NotAllowedError') {
                    _pinErr.textContent = err.message || 'Biometric authentication failed.';
                    _pinErr.classList.remove('hidden');
                }
            } finally {
                const spinner = document.getElementById('modal-biometric-spinner');
                const btn = document.getElementById('modal-biometric-btn');
                if (spinner) spinner.classList.add('hidden');
                if (btn) btn.disabled = false;
            }
        }

        // Close on backdrop click
        _pinModal.addEventListener('click', function (e) {
            if (e.target === _pinModal) closePinModal();
        });
    </script>

    {{-- Flash toast notifications --}}
    @if (session('success') || session('error') || session('info'))
    <div id="toast"
         class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-5 py-4 rounded-2xl shadow-xl text-sm font-medium
                {{ session('error') ? 'bg-red-500' : (session('info') ? 'bg-cyan-500' : 'bg-emerald-500') }} text-white
                translate-y-0 opacity-100 transition-all duration-500">
        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="{{ session('error') ? 'M6 18L18 6M6 6l12 12' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }}"/>
        </svg>
        {{ session('success') ?? session('error') ?? session('info') }}
        <button onclick="document.getElementById('toast').remove()" class="ml-2 opacity-70 hover:opacity-100">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    <script>setTimeout(function(){ var t = document.getElementById('toast'); if(t) t.remove(); }, 4000);</script>
    @endif

    {{-- OneSignal Web Push Integration --}}
    @php
        $onesignalAppId = \App\Models\AppSetting::get('onesignal_app_id');
    @endphp
    @if($onesignalAppId)
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        OneSignalDeferred.push(async function(OneSignal) {
            await OneSignal.init({
                appId: "{{ $onesignalAppId }}",
                allowLocalhostAsSecureOrigin: true,
            });
            await OneSignal.login("{{ auth()->id() }}");
        });
    </script>
    @endif

    @auth
    <script>
        (function() {
            let idleTimer;
            const idleTimeout = {{ (int) \App\Models\AppSetting::get('session_idle_timeout', 5) }} * 60 * 1000;
            
            function resetIdleTimer() {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(lockSession, idleTimeout);
            }
            
            function lockSession() {
                const currentPath = window.location.pathname;
                if (currentPath.includes('/lockscreen') || currentPath.includes('/login') || currentPath.includes('/register')) {
                    return;
                }
                window.location.href = '/lockscreen';
            }
            
            ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => {
                document.addEventListener(evt, resetIdleTimer, true);
            });
            
            resetIdleTimer();
        })();
    </script>
    @endauth
</body>
</html>

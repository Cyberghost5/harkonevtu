<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') &ndash; {{ $siteName }}</title>
    @if($siteFavicon)<link rel="icon" href="{{ Storage::url($siteFavicon) }}">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
                            primary: '{{ $themeColor }}',
                            secondary: '{{ $themeSecondary }}',
                            dark:    '{{ $themeDark }}',
                        }
                    },
                }
            }
        }
    </script>
    {{-- Prevent dark-mode flash --}}
    <script>
        (function(){
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
        .custom-scrollbar-light::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar-light::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar-light::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 4px; }

        /* Dark mode overrides for cards, panels, inputs, and texts to ensure compatibility */
        .dark body {
            background-color: #0b0f19 !important;
            color: #cbd5e1 !important;
        }
        .dark .bg-white {
            background-color: #1e293b !important;
        }
        .dark .border-slate-100,
        .dark .border-slate-200,
        .dark .border-gray-200 {
            border-color: #334155 !important;
        }
        .dark .text-slate-800,
        .dark .text-slate-700,
        .dark .text-gray-800,
        .dark .text-gray-700 {
            color: #f1f5f9 !important;
        }
        .dark .text-slate-650,
        .dark .text-slate-600,
        .dark .text-gray-650,
        .dark .text-gray-600 {
            color: #cbd5e1 !important;
        }
        .dark .text-slate-400,
        .dark .text-gray-400 {
            color: #94a3b8 !important;
        }
        .dark .custom-scrollbar-light::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.12);
        }
        /* inputs in settings/admin views */
        .dark input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]),
        .dark textarea,
        .dark select {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #cbd5e1 !important;
        }
        .dark input:focus,
        .dark textarea:focus,
        .dark select:focus {
            border-color: {{ $themeColor }} !important;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.25) !important;
        }
    </style>
    @yield('styles')
</head>
<body class="h-full overflow-hidden bg-gray-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200 antialiased">
    <div id="mobile-overlay" onclick="closeSidebar()" class="fixed inset-0 bg-black/60 z-20 lg:hidden hidden"></div>
    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR -->
        <aside id="sidebar"
               class="fixed lg:static inset-y-0 left-0 z-30 flex flex-col w-60 flex-shrink-0
                      -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out"
               style="background:{{ $themeDark }};">

            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center gap-3 px-5 py-4" style="border-bottom:1px solid rgba(255,255,255,0.1)">
                <div class="h-9 w-9 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0 overflow-hidden" style="{{ $siteLogo1 ? '' : 'background:'.$themeColor }}">
                    @if($siteLogo1)
                    <img src="{{ Storage::url($siteLogo1) }}" class="h-full w-full object-contain" alt="{{ $siteName }}">
                    @else
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    @endif
                </div>
                <span class="text-lg font-bold font-outfit text-white tracking-tight">{{ $siteName }}</span>
                <span class="ml-auto text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-widest" style="background-color:{{ $themeColor }}40;color:{{ $themeColor }}">Admin</span>
            </div>

            <!-- Admin user -->
            <div class="flex-shrink-0 px-4 py-3" style="border-bottom:1px solid rgba(255,255,255,0.1)">
                <div class="flex items-center gap-2.5">
                    @if (auth()->user()->avatar)
                        <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="Avatar" class="h-8 w-8 rounded-full object-cover">
                    @else
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:{{ $themeColor }}">
                        {{ auth()->user()->initials() }}
                    </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->displayName() }}</p>
                        <p class="text-[10px]" style="color:{{ $themeColor }}">Administrator</p>
                    </div>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto custom-scrollbar px-3 py-3 space-y-0.5">

                <p class="px-3 pt-1 pb-1.5 text-[9px] font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.3)">Main</p>

                @php $a = request()->routeIs('admin.dashboard'); @endphp
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="{{ $a ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                   @unless($a) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';" @endunless>
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>

                <p class="px-3 pt-3 pb-1.5 text-[9px] font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.3)">Users</p>

                @php $a = request()->routeIs('admin.users.*'); @endphp
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="{{ $a ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                   @unless($a) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';" @endunless>
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Users
                </a>

                <p class="px-3 pt-3 pb-1.5 text-[9px] font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.3)">Finance</p>

                @php $a = request()->routeIs('admin.transactions.*'); @endphp
                <a href="{{ route('admin.transactions.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="{{ $a ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                   @unless($a) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';" @endunless>
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Transactions
                </a>

                @php $a = request()->routeIs('admin.funding.*'); $pf = \App\Models\FundingRequest::where('status','pending')->count(); @endphp
                <a href="{{ route('admin.funding.index') }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="{{ $a ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                   @unless($a) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';" @endunless>
                    <div class="flex items-center gap-3">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Funding
                    </div>
                    @if($pf > 0)<span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full" style="background:rgba(245,158,11,0.3);color:#fcd34d">{{ $pf }}</span>@endif
                </a>

                @php $a = request()->routeIs('admin.coupons.*'); @endphp
                <a href="{{ route('admin.coupons.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="{{ $a ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                   @unless($a) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';" @endunless>
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    Coupons
                </a>

                @php $a = request()->routeIs('admin.airtime-to-cash.*'); $pa = \App\Models\AirtimeToCashRequest::where('status','pending')->count(); @endphp
                <a href="{{ route('admin.airtime-to-cash.index') }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="{{ $a ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                   @unless($a) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';" @endunless>
                    <div class="flex items-center gap-3">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Airtime to Cash
                    </div>
                    @if($pa > 0)<span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full" style="background:rgba(245,158,11,0.3);color:#fcd34d">{{ $pa }}</span>@endif
                </a>

                <p class="px-3 pt-3 pb-1.5 text-[9px] font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.3)">System</p>

                @php $settingsOpen = request()->routeIs('admin.settings.*'); @endphp
                {{-- Settings parent --}}
                <button type="button" id="settings-toggle" onclick="toggleSettingsMenu()"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                        style="{{ $settingsOpen ? 'background:rgba(255,255,255,0.1);color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                        onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';"
                        onmouseout="if(!document.getElementById('settings-menu').offsetParent||document.getElementById('settings-menu').classList.contains('hidden')){this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';}">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="flex-1 text-left">Settings</span>
                    <svg id="settings-chevron" class="h-3 w-3 flex-shrink-0 transition-transform duration-200 {{ $settingsOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                {{-- Settings sub-menu --}}
                <div id="settings-menu" class="{{ $settingsOpen ? '' : 'hidden' }} ml-6 mt-0.5 space-y-0.5">
                    @php
                        $settingsSubs = [
                            ['route' => 'admin.settings.general',    'label' => 'General Settings'],
                            ['route' => 'admin.settings.email',       'label' => 'Email Settings'],
                            ['route' => 'admin.settings.api-keys',    'label' => 'API Keys Settings'],
                            ['route' => 'admin.settings.api',         'label' => 'API Settings'],
                            ['route' => 'admin.settings.accounts',    'label' => 'Account Settings'],
                            ['route' => 'admin.settings.data-plans',  'label' => 'Data Plan Settings'],
                            ['route' => 'admin.settings.cable-plans', 'label' => 'Cable Plan Settings'],
                        ];
                    @endphp
                    @foreach($settingsSubs as $sub)
                    @php $si = request()->routeIs($sub['route']); @endphp
                    <a href="{{ route($sub['route']) }}"
                       class="block px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-150"
                       style="{{ $si ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.6)' }}"
                       @unless($si) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.6)';" @endunless>
                        {{ $sub['label'] }}
                    </a>
                    @endforeach
                </div>

                @php $a = request()->routeIs('admin.api-logs.*'); @endphp
                <a href="{{ route('admin.api-logs.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="{{ $a ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                   @unless($a) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';" @endunless>
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                    API Logs
                </a>

                @php $a = request()->routeIs('admin.monitoring'); @endphp
                <a href="{{ route('admin.monitoring') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="{{ $a ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                   @unless($a) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';" @endunless>
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                    Server Monitoring
                </a>

                <div class="my-2" style="border-top:1px solid rgba(255,255,255,0.1)"></div>

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="color:rgba(255,255,255,0.45)"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';"
                   onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.45)';">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to App
                </a>
            </nav>

            <div class="flex-shrink-0 px-3 py-3" style="border-top:1px solid rgba(255,255,255,0.1)">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                            style="color:rgba(239,68,68,0.75)"
                            onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#f87171';"
                            onmouseout="this.style.background='transparent';this.style.color='rgba(239,68,68,0.75)';">
                        <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>
        <!-- /SIDEBAR -->

        <!-- MAIN -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="flex-shrink-0 flex items-center gap-4 h-14 px-4 sm:px-6 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 z-10">
                <button onclick="openSidebar()" class="lg:hidden p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex-1 max-w-xs hidden sm:block">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" placeholder="Search Transaction/User"
                               class="w-full pl-9 pr-4 py-2 text-sm rounded-lg text-gray-600 dark:text-slate-300 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    </div>
                </div>
                <div class="flex items-center gap-3 ml-auto">
                    {{-- Dark mode toggle --}}
                    <button onclick="toggleTheme()"
                            class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <svg id="header-sun" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/>
                        </svg>
                        <svg id="header-moon" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                        </svg>
                    </button>

                    {{-- Notifications Dropdown --}}
                    <div class="relative">
                        <button onclick="toggleNotificationDropdown(event)" id="notification-btn" class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                            <svg class="h-5 w-5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if($pf > 0)
                            <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500 pointer-events-none"></span>
                            @endif
                        </button>
                        
                        <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Notifications</span>
                                @if($pf > 0)
                                <span class="text-[10px] bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 px-2 py-0.5 rounded-full font-bold">{{ $pf }} Pending</span>
                                @endif
                            </div>
                            <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                @php
                                    $pendingFundings = \App\Models\FundingRequest::with('user')->where('status', 'pending')->latest()->take(5)->get();
                                @endphp
                                @forelse($pendingFundings as $req)
                                <a href="{{ route('admin.funding.index') }}" class="block px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                    <div class="flex gap-3">
                                        <div class="h-8 w-8 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 13v-1"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-slate-800 dark:text-slate-200 truncate">Funding request of ₦{{ number_format($req->amount, 2) }}</p>
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">By {{ optional($req->user)->name ?? 'User' }} &bull; {{ $req->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </a>
                                @empty
                                <div class="px-4 py-6 text-center text-xs text-slate-400 dark:text-slate-500">
                                    <svg class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-700 mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    No notifications
                                </div>
                                @endforelse
                            </div>
                            <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800/30 text-center border-t border-slate-100 dark:border-slate-800">
                                <a href="{{ route('admin.funding.index') }}" class="text-[11px] font-semibold text-vtu-primary hover:underline">View All Requests</a>
                            </div>
                        </div>
                    </div>

                    {{-- Profile Dropdown --}}
                    <div class="relative">
                        <button onclick="toggleProfileDropdown(event)" id="profile-btn" class="h-8 w-8 rounded-full flex items-center justify-center text-white text-xs font-bold focus:outline-none focus:ring-2 focus:ring-vtu-primary/50" style="background:{{ $themeColor }}">
                            {{ auth()->user()->initials() }}
                        </button>
                        
                        <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('admin.settings.accounts') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Account Settings
                                </a>
                                <a href="{{ route('admin.settings.general') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    General Settings
                                </a>
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                    Back to App
                                </a>
                            </div>
                            <div class="border-t border-slate-100 dark:border-slate-800 py-1 bg-slate-50/50 dark:bg-slate-800/20">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            @if(session('success') || session('error'))
            <div class="px-4 sm:px-6 pt-3">
                @if(session('success'))
                <div class="flex items-center gap-3 p-3 rounded-xl text-sm mb-2" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="flex items-center gap-3 p-3 rounded-xl text-sm" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('error') }}
                </div>
                @endif
            </div>
            @endif

            <main class="flex-1 overflow-y-auto custom-scrollbar-light p-4 sm:p-6">
                @yield('content')
            </main>
        </div>
        <!-- /MAIN -->
    </div>
    <script>
        function openSidebar()  { document.getElementById('sidebar').classList.remove('-translate-x-full'); document.getElementById('mobile-overlay').classList.remove('hidden'); }
        function closeSidebar() { document.getElementById('sidebar').classList.add('-translate-x-full');    document.getElementById('mobile-overlay').classList.add('hidden'); }
        function toggleSettingsMenu() {
            var menu    = document.getElementById('settings-menu');
            var chevron = document.getElementById('settings-chevron');
            var btn     = document.getElementById('settings-toggle');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                chevron.classList.add('rotate-180');
                btn.style.background = 'rgba(255,255,255,0.1)';
                btn.style.color = '#fff';
            } else {
                menu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
                btn.style.background = 'transparent';
                btn.style.color = 'rgba(255,255,255,0.7)';
            }
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
    </script>
    @yield('scripts')

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

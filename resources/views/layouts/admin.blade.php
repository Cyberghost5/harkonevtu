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
            theme: {
                extend: {
                    fontFamily: {
                        sans:   ['"Plus Jakarta Sans"', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        vtu: {
                            primary: '{{ $themeColor }}',
                            dark:    '{{ $themeDark }}',
                        }
                    },
                }
            }
        }
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
    </style>
    @yield('styles')
</head>
<body class="h-full overflow-hidden bg-gray-100 text-slate-800 antialiased">
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
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:{{ $themeColor }}">
                        {{ auth()->user()->initials() }}
                    </div>
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

                <p class="px-3 pt-3 pb-1.5 text-[9px] font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.3)">System</p>

                @php $a = request()->routeIs('admin.api-logs.*'); @endphp
                <a href="{{ route('admin.api-logs.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                   style="{{ $a ? 'background:'.$themeColor.';color:#fff' : 'color:rgba(255,255,255,0.7)' }}"
                   @unless($a) onmouseover="this.style.background='rgba(255,255,255,0.08)';this.style.color='#fff';" onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';" @endunless>
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                    API Logs
                </a>

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
            <header class="flex-shrink-0 flex items-center gap-4 h-14 px-4 sm:px-6 bg-white z-10" style="border-bottom:1px solid #e5e7eb">
                <button onclick="openSidebar()" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex-1 max-w-xs hidden sm:block">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" placeholder="Search Transaction/User"
                               class="w-full pl-9 pr-4 py-2 text-sm rounded-lg text-gray-600 placeholder-gray-400 focus:outline-none"
                               style="background:#f9fafb;border:1px solid #e5e7eb">
                    </div>
                </div>
                <div class="flex items-center gap-3 ml-auto">
                    <button class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500"></span>
                    </button>
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background:{{ $themeColor }}">
                        {{ auth()->user()->initials() }}
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
    </script>
    @yield('scripts')
</body>
</html>

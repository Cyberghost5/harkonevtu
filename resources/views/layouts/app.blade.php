<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') – @endif{{ $siteName }}</title>
    @if($siteFavicon)<link rel="icon" href="{{ Storage::url($siteFavicon) }}">@endif

    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $siteDescription ?: 'Purchase cheap airtime, data bundles, electricity tokens, and cable TV subscriptions instantly.' }}">
    @if(!empty($siteKeywords))<meta name="keywords" content="{{ $siteKeywords }}">@endif
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        vtu: {
                            primary: '{{ $themeColor }}',
                            secondary: '{{ $themeSecondary }}',
                            dark: '#0B0F19',
                            darkCard: '#1E293B',
                            light: '#F8FAFC',
                            accent: '#F59E0B'
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>

    <!-- Custom Premium Styles -->
    <style type="text/css">
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-attachment: fixed;
        }
        
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .dark .glass {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .gradient-bg {
            background: radial-gradient(circle at 10% 20%, rgba({{ $themeColorRgb }}, 0.08) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba({{ $themeSecondaryRgb }}, 0.08) 0%, transparent 40%);
        }

        .dark .gradient-bg {
            background: radial-gradient(circle at 10% 20%, rgba({{ $themeColorRgb }}, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 90% 80%, rgba({{ $themeSecondaryRgb }}, 0.12) 0%, transparent 50%);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.3);
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.5);
        }
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
<body class="bg-vtu-light dark:bg-vtu-dark text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen flex flex-col gradient-bg custom-scrollbar">

    <!-- Header / Navbar -->
    <header class="sticky top-0 z-50 glass transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center space-x-2">
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center overflow-hidden {{ $siteLogo1 ? '' : 'bg-gradient-to-tr from-vtu-primary to-vtu-secondary shadow-lg shadow-indigo-500/20' }}">
                                @if($siteLogo1)
                                <img src="{{ Storage::url($siteLogo1) }}" class="h-full w-full object-contain" alt="{{ $siteName }}">
                                @else
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                @endif
                            </div>
                        <span class="text-xl font-bold font-outfit tracking-tight bg-gradient-to-r from-vtu-primary to-vtu-secondary bg-clip-text text-transparent">{{ $siteName }}</span>
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden md:flex space-x-8">
                    <a href="{{ url('/') }}" class="text-sm font-medium hover:text-vtu-primary transition-colors py-2 {{ Request::is('/') ? 'text-vtu-primary border-b-2 border-vtu-primary' : 'text-slate-600 dark:text-slate-300' }}">Home</a>
                    <a href="#services" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-vtu-primary transition-colors py-2">Services</a>
                    <a href="#pricing" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-vtu-primary transition-colors py-2">Pricing</a>
                    <a href="#about" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-vtu-primary transition-colors py-2">About</a>
                </nav>

                <!-- Right Side Actions -->
                <div class="hidden md:flex items-center space-x-4">
                    <!-- Light/Dark Mode Switcher -->
                    <button id="theme-toggle" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                        <!-- Sun Icon -->
                        <svg id="theme-toggle-light-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 14.121a1 1 0 011.414 0l.707.707a1 1 0 01-1.414 1.414l-.707-.707a1 1 0 010-1.414zm-.707-8.485a1 1 0 010-1.414l.707-.707a1 1 0 111.414 1.414l-.707.707a1 1 0 01-1.414 0zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" />
                        </svg>
                        <!-- Moon Icon -->
                        <svg id="theme-toggle-dark-icon" class="hidden h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                        </svg>
                    </button>

                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-vtu-primary hover:bg-indigo-700 shadow-md shadow-indigo-600/10 hover:shadow-lg hover:shadow-indigo-600/20 hover:-translate-y-0.5 transition-all duration-200">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 dark:text-slate-300 hover:text-vtu-primary transition-colors">Sign In</a>
                        <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-vtu-primary hover:bg-indigo-700 shadow-md shadow-indigo-600/10 hover:shadow-lg hover:shadow-indigo-600/20 hover:-translate-y-0.5 transition-all duration-200">Get Started</a>
                    @endauth
                </div>

                <!-- Hamburger Button (Mobile) -->
                <div class="flex items-center md:hidden space-x-2">
                    <!-- Light/Dark Mode Switcher -->
                    <button id="theme-toggle-mobile" class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                        <svg id="theme-toggle-mobile-icon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    
                    <button id="mobile-menu-btn" class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-vtu-dark px-4 py-4 space-y-3 shadow-xl">
            <a href="{{ url('/') }}" class="block px-3 py-2 rounded-xl text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-vtu-primary">Home</a>
            <a href="#services" class="block px-3 py-2 rounded-xl text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-vtu-primary">Services</a>
            <a href="#pricing" class="block px-3 py-2 rounded-xl text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-vtu-primary">Pricing</a>
            <a href="#about" class="block px-3 py-2 rounded-xl text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-vtu-primary">About</a>
            <hr class="border-slate-100 dark:border-slate-800 my-2">
            @auth
                <a href="{{ url('/dashboard') }}" class="block text-center px-4 py-2.5 rounded-xl text-base font-semibold text-white bg-vtu-primary">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="block text-center px-4 py-2 rounded-xl text-base font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Sign In</a>
                <a href="{{ route('register') }}" class="block text-center px-4 py-2.5 rounded-xl text-base font-semibold text-white bg-vtu-primary">Get Started</a>
            @endauth
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Branding column -->
                <div class="col-span-1 md:col-span-1 space-y-4">
                    <div class="flex items-center space-x-2">
                        <div class="h-8 w-8 rounded-lg flex items-center justify-center overflow-hidden {{ $siteLogo1 ? '' : 'bg-gradient-to-tr from-vtu-primary to-vtu-secondary' }}">
                                @if($siteLogo1)
                                <img src="{{ Storage::url($siteLogo1) }}" class="h-full w-full object-contain" alt="{{ $siteName }}">
                                @else
                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                @endif
                            </div>
                        <span class="text-lg font-bold font-outfit tracking-tight text-white">{{ $siteName }}</span>
                    </div>
                    <p class="text-sm">Nigeria's most reliable platform for cheap data, airtime, electricity, and cable TV subscription topups.</p>
                </div>
                
                <!-- Services column -->
                <div class="col-span-1">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Services</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Airtime Purchase</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Cheap Data Bundles</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Cable TV Subscription</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Electricity Bill Payment</a></li>
                    </ul>
                </div>

                <!-- Support column -->
                <div class="col-span-1">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Help & Support</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">FAQs</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>

                <!-- Contact column -->
                <div class="col-span-1">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Contact Info</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center space-x-2">
                            <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $adminEmail ?: 'support@'.request()->getHost() }}</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 00.099.281L10 8.583a1 1 0 00-.547.547L8.383 10.2A1 1 0 009 11.233A8.997 8.997 0 0013 15a1.003 1.003 0 001.233-.617l1.07-1.07a1 1 0 00.547-.547l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span>+234 903 170 4109</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 border-t border-slate-800 pt-8 flex flex-col md:flex-row items-center justify-between text-xs">
                <p>@if($siteCopyright){{ $siteCopyright }}@else&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.@endif</p>
                <p class="mt-4 md:mt-0 flex space-x-4">
                    <span>Designed with ❤️ by <a href="https://harkone.com.ng" target="_blank">Harkone Designs</a></span>
                </p>
            </div>
        </div>
    </footer>

    <!-- Theme Toggle Scripts -->
    <script type="text/javascript">
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleMobileBtn = document.getElementById('theme-toggle-mobile');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        // Toggle mobile menu
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Determine initial theme
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            document.documentElement.classList.remove('dark');
            themeToggleDarkIcon.classList.remove('hidden');
        }

        function toggleTheme() {
            // Toggle icon
            themeToggleLightIcon.classList.toggle('hidden');
            themeToggleDarkIcon.classList.toggle('hidden');

            // Toggle document class & save setting
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }

        themeToggleBtn.addEventListener('click', toggleTheme);
        themeToggleMobileBtn.addEventListener('click', toggleTheme);
    </script>
    @yield('scripts')
</body>
</html>

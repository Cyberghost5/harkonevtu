@extends('layouts.app')

@section('title', 'Instant Airtime, Cheap Data, Electricity & Cable TV')

@section('styles')
<style>
    .hero-gradient {
        background: linear-gradient(135deg, rgba({{ $themeColorRgb }},0.06) 0%, rgba({{ $themeSecondaryRgb }},0.04) 100%);
    }
    .service-tab-btn.active {
        border-color: {{ $themeColor }};
        color: {{ $themeColor }};
        background: rgba({{ $themeColorRgb }},0.06);
    }
    .service-tab-pane { display: none; }
    .service-tab-pane.active { display: block; }
    .step-line::after {
        content: '';
        position: absolute;
        left: 50%;
        top: 100%;
        transform: translateX(-50%);
        width: 2px;
        height: 2rem;
        background: linear-gradient(to bottom, rgba({{ $themeColorRgb }},0.4), transparent);
    }
    .testimonial-item { display: none; }
    .testimonial-item.active { display: flex; }
    .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
    .faq-item.open .faq-answer { max-height: 200px; }
    .faq-item.open .faq-chevron { transform: rotate(180deg); }
    .faq-chevron { transition: transform 0.3s ease; }
</style>
@endsection

@section('content')

{{-- ═══════════════════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════════════════ --}}
<section class="hero-gradient relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

            {{-- Left: text --}}
            <div class="text-center lg:text-left space-y-7">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-full px-5 py-2 text-xs font-bold shadow-sm" style="color:{{ $themeColor }}">
                    <span>#1 VTU Platform in Nigeria</span>
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </div>

                {{-- Headline --}}
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold font-outfit leading-tight text-slate-900 dark:text-white">
                    Best app for your<br>
                    <span style="color:{{ $themeColor }}">various daily</span> needs
                </h1>

                {{-- Subtext --}}
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-lg mx-auto lg:mx-0 leading-relaxed">
                    Needs such as airtime purchase, data purchase &amp; utility bills payment such as Cable TV &amp; Electricity should be paid from your comfort zone.
                </p>

                {{-- CTAs --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-8 py-4 rounded-xl text-base font-semibold text-white shadow-lg hover:-translate-y-0.5 transition-all duration-200" style="background:{{ $themeColor }}">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-8 py-4 rounded-xl text-base font-semibold text-white shadow-lg hover:opacity-90 hover:-translate-y-0.5 transition-all duration-200" style="background:{{ $themeColor }}">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="px-8 py-4 rounded-xl text-base font-semibold border-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" style="border-color:{{ $themeColor }};color:{{ $themeColor }}">
                            Sign Up
                        </a>
                    @endauth
                </div>

                {{-- Partner logos --}}
                <div class="pt-4">
                    <p class="text-xs text-slate-400 dark:text-slate-500 uppercase tracking-widest font-medium mb-4">Powered by Companies</p>
                    <div class="flex flex-wrap gap-3 justify-center lg:justify-start items-center">
                        <span class="px-3 py-1.5 bg-yellow-400 text-black text-xs font-bold rounded-lg shadow-sm">MTN</span>
                        <span class="px-3 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg shadow-sm">Airtel</span>
                        <span class="px-3 py-1.5 bg-green-600 text-white text-xs font-bold rounded-lg shadow-sm">Glo</span>
                        <span class="px-3 py-1.5 bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-sm">9mobile</span>
                        <span class="px-3 py-1.5 bg-slate-700 text-white text-xs font-bold rounded-lg shadow-sm">JEDplc</span>
                        <span class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-lg shadow-sm">EKEDC</span>
                    </div>
                </div>
            </div>

            {{-- Right: App mockup illustration --}}
            <div class="relative flex items-center justify-center lg:justify-end">
                <div class="relative w-72 sm:w-80">
                    {{-- Glow --}}
                    <div class="absolute inset-0 rounded-[40px] blur-3xl opacity-20" style="background:{{ $themeColor }}"></div>
                    {{-- Phone frame --}}
                    <div class="relative bg-slate-900 rounded-[40px] p-3 shadow-2xl">
                        <div class="rounded-[32px] overflow-hidden" style="background:linear-gradient(160deg,rgba({{ $themeColorRgb }},0.12) 0%,#0f172a 100%)">
                            {{-- Status bar --}}
                            <div class="flex items-center justify-between px-5 pt-4 pb-2">
                                <span class="text-white text-xs font-semibold">9:41</span>
                                <div class="w-20 h-4 bg-slate-800 rounded-full"></div>
                                <div class="flex gap-1">
                                    <div class="w-3 h-3 rounded-full bg-slate-600"></div>
                                    <div class="w-3 h-3 rounded-full bg-slate-600"></div>
                                </div>
                            </div>
                            {{-- App header --}}
                            <div class="px-5 pb-4 pt-1">
                                <p class="text-slate-400 text-xs">Welcome back 👋</p>
                                <p class="text-white text-base font-bold font-outfit">My Wallet</p>
                            </div>
                            {{-- Wallet card --}}
                            <div class="mx-5 rounded-2xl p-4 mb-4 shadow-lg" style="background:linear-gradient(135deg,{{ $themeColor }},{{ $themeSecondary }})">
                                <p class="text-white/70 text-xs mb-1">Available Balance</p>
                                <p class="text-white text-2xl font-bold font-outfit">₦12,450.00</p>
                                <div class="flex gap-2 mt-3">
                                    <span class="bg-white/20 text-white text-[10px] px-2 py-0.5 rounded-full">Fund Wallet</span>
                                    <span class="bg-white/20 text-white text-[10px] px-2 py-0.5 rounded-full">Transfer</span>
                                </div>
                            </div>
                            {{-- Quick services --}}
                            <div class="px-5 pb-2">
                                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-3">Quick Services</p>
                                <div class="grid grid-cols-4 gap-2 mb-4">
                                    @foreach([['📱','Airtime'],['⚡','Data'],['📺','Cable'],['💡','Bills']] as [$icon,$label])
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-lg">{{ $icon }}</div>
                                        <span class="text-slate-400 text-[9px]">{{ $label }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            {{-- Recent TX --}}
                            <div class="px-5 pb-5 space-y-2">
                                <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Recent</p>
                                @foreach([['Airtime – MTN','−₦500','text-red-400'],['Data – 2GB Airtel','−₦700','text-red-400'],['Wallet Fund','+ ₦5,000','text-green-400']] as [$tx,$amt,$cls])
                                <div class="flex items-center justify-between bg-slate-800/60 rounded-xl px-3 py-2">
                                    <span class="text-white text-[11px]">{{ $tx }}</span>
                                    <span class="text-[11px] font-semibold {{ $cls }}">{{ $amt }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     FEATURES SECTION
═══════════════════════════════════════════════════════ --}}
<section id="features" class="py-20 bg-white dark:bg-slate-900/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section intro --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
            <div class="space-y-4">
                <span class="inline-block text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full" style="color:{{ $themeColor }};background:rgba({{ $themeColorRgb }},0.08)">{{ $siteName }} features</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold font-outfit text-slate-900 dark:text-white leading-tight">
                    Increase flexibility with a<br>simple VTU website.
                </h2>
                <p class="text-slate-600 dark:text-slate-400 text-base leading-relaxed">
                    App for meeting your daily needs from your comfort zone. Fast, secure, and always available.
                </p>
            </div>
            {{-- Right: decorative illustration --}}
            <div class="flex justify-center lg:justify-end">
                <div class="w-64 h-64 rounded-3xl flex items-center justify-center relative overflow-hidden shadow-xl" style="background:linear-gradient(135deg,rgba({{ $themeColorRgb }},0.12),rgba({{ $themeSecondaryRgb }},0.08))">
                    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 30% 30%,{{ $themeColor }},transparent 70%)"></div>
                    <svg class="w-32 h-32 opacity-70" viewBox="0 0 120 120" fill="none">
                        <rect x="20" y="10" width="80" height="100" rx="12" fill="none" stroke="{{ $themeColor }}" stroke-width="3"/>
                        <rect x="30" y="25" width="60" height="8" rx="4" fill="{{ $themeColor }}" opacity="0.4"/>
                        <rect x="30" y="40" width="40" height="8" rx="4" fill="{{ $themeColor }}" opacity="0.3"/>
                        <rect x="30" y="58" width="60" height="30" rx="8" fill="{{ $themeColor }}" opacity="0.2"/>
                        <circle cx="90" cy="90" r="18" fill="{{ $themeColor }}" opacity="0.15" stroke="{{ $themeColor }}" stroke-width="2"/>
                        <path d="M83 90l5 5 9-9" stroke="{{ $themeColor }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Feature cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['⚡','Fast performance','Get your airtime, data bundles and other various bills quickly and efficiently. Automated dispatch in seconds.'],
                ['📋','Documentation','Have a list of all your spendings in one place. Helps to keep your budget in check with instant receipts.'],
                ['🌐','Full Control','You have full control & access to your account from anywhere across the globe, any time of day.'],
            ] as [$icon,$title,$desc])
            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-8 border border-slate-100 dark:border-slate-800 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 group text-center">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5 text-3xl group-hover:scale-110 transition-transform" style="background:rgba({{ $themeColorRgb }},0.1)">{{ $icon }}</div>
                <h3 class="text-lg font-bold font-outfit text-slate-900 dark:text-white mb-3">{{ $title }}</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     CTA BANNER
═══════════════════════════════════════════════════════ --}}
<section class="py-16" style="background:linear-gradient(135deg,{{ $themeColor }},{{ $themeSecondary }})">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            {{-- Illustration --}}
            <div class="flex justify-center order-2 lg:order-1">
                <div class="w-56 h-56 rounded-full bg-white/10 flex items-center justify-center relative">
                    <div class="absolute w-44 h-44 rounded-full bg-white/10"></div>
                    <svg class="w-24 h-24 text-white relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            {{-- Text --}}
            <div class="order-1 lg:order-2 text-center lg:text-left space-y-4">
                <span class="inline-block bg-white/20 text-white text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full">Designed & Built for You</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold font-outfit text-white leading-tight">
                    Designed &amp; Built using the latest code integration
                </h2>
                <p class="text-white/80 text-base leading-relaxed">
                    A modern, fast and secure platform built with the latest technology to serve you better.
                </p>
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-white px-6 py-3 rounded-xl text-sm font-bold shadow-md hover:-translate-y-0.5 transition-all" style="color:{{ $themeColor }}">
                    Learn more
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     WHY CHOOSE US
═══════════════════════════════════════════════════════ --}}
<section id="services" class="py-20 bg-white dark:bg-slate-900/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14 space-y-3">
            <span class="inline-block text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full" style="color:{{ $themeColor }};background:rgba({{ $themeColorRgb }},0.08)">Why you should choose us</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold font-outfit text-slate-900 dark:text-white">Easy to use, fast and automated</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['🚀','Fast & Automated','Instant funding for your various services, no middle man, no manual payment. Everything is done in real time.'],
                ['🧾','Invoice & Receipt','Get receipts and invoice for every transaction made on our platform. Perfect for bookkeeping and reference.'],
                ['🎧','Customer Support','We are available 24/7 to quickly rectify any technical glitch. Reach us via chat, email or WhatsApp.'],
            ] as [$icon,$title,$desc])
            <div class="flex flex-col items-center text-center p-8 rounded-2xl border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 bg-slate-50 dark:bg-slate-800/40 group">
                <div class="w-16 h-16 rounded-full flex items-center justify-center text-3xl mb-5 group-hover:scale-110 transition-transform" style="background:rgba({{ $themeColorRgb }},0.1)">{{ $icon }}</div>
                <h3 class="text-lg font-bold font-outfit text-slate-900 dark:text-white mb-3">{{ $title }}</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     HOW TO USE
═══════════════════════════════════════════════════════ --}}
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            {{-- Left: Steps --}}
            <div class="space-y-6">
                <div class="space-y-2">
                    <span class="inline-block text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full" style="color:{{ $themeColor }};background:rgba({{ $themeColorRgb }},0.08)">How to use?</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold font-outfit text-slate-900 dark:text-white">Usage of our app is very simple.</h2>
                </div>

                @foreach([
                    ['1','Sign Up','Log on to our website, and fill the registration form. We don\'t ask for too much!'],
                    ['2','Login','After the registration process, you can go ahead and login with your details.'],
                    ['3','Fund Wallet','Fund your wallet with either of the payment methods we have provided for ease.'],
                    ['4','Purchase Services','All done, you can now purchase our cheap and top-notch services we offer!'],
                ] as [$num,$title,$desc])
                <div class="flex items-start gap-5">
                    <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shadow-lg" style="background:{{ $themeColor }}">{{ $num }}</div>
                    <div>
                        <h4 class="font-bold text-slate-900 dark:text-white font-outfit">{{ $title }}</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach

                <div class="pt-2">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white shadow-md hover:opacity-90 transition-all" style="background:{{ $themeColor }}">
                        See all
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- Right: Illustration --}}
            <div class="flex justify-center lg:justify-end">
                <div class="relative w-72 h-72">
                    <div class="absolute inset-0 rounded-full opacity-15 blur-2xl" style="background:{{ $themeColor }}"></div>
                    <div class="relative w-full h-full rounded-3xl overflow-hidden shadow-2xl flex items-center justify-center" style="background:linear-gradient(145deg,rgba({{ $themeColorRgb }},0.1),rgba({{ $themeSecondaryRgb }},0.06))">
                        <svg class="w-40 h-40 opacity-60" viewBox="0 0 120 120" fill="none">
                            <circle cx="60" cy="60" r="50" stroke="{{ $themeColor }}" stroke-width="2" stroke-dasharray="8 4"/>
                            <rect x="35" y="28" width="50" height="64" rx="10" fill="none" stroke="{{ $themeColor }}" stroke-width="2.5"/>
                            <rect x="43" y="40" width="34" height="6" rx="3" fill="{{ $themeColor }}" opacity="0.5"/>
                            <rect x="43" y="52" width="22" height="6" rx="3" fill="{{ $themeColor }}" opacity="0.35"/>
                            <rect x="43" y="64" width="34" height="16" rx="6" fill="{{ $themeColor }}" opacity="0.2"/>
                            <circle cx="82" cy="82" r="14" fill="white" opacity="0.08" stroke="{{ $themeColor }}" stroke-width="2"/>
                            <path d="M77 82l4 4 7-7" stroke="{{ $themeColor }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     SERVICES / PRICING TABS
═══════════════════════════════════════════════════════ --}}
<section id="pricing" class="py-20 bg-white dark:bg-slate-900/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 space-y-3">
            <span class="inline-block text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full" style="color:{{ $themeColor }};background:rgba({{ $themeColorRgb }},0.08)">Get awesome services, without extra charges</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold font-outfit text-slate-900 dark:text-white">Our services pricing is relatively fair and moderate.</h2>
        </div>

        {{-- Tab buttons --}}
        <div class="flex flex-wrap justify-center gap-3 mb-10">
            <button class="service-tab-btn active px-6 py-2.5 rounded-full border text-sm font-semibold transition-all" data-tab="airtime" style="border-color:{{ $themeColor }};color:{{ $themeColor }};background:rgba({{ $themeColorRgb }},0.06)">Airtime &amp; Data</button>
            <button class="service-tab-btn px-6 py-2.5 rounded-full border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:border-current transition-all" data-tab="electricity" style="">Electricity Bill</button>
            <button class="service-tab-btn px-6 py-2.5 rounded-full border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:border-current transition-all" data-tab="cable">Cable TV</button>
        </div>

        {{-- Tab panes --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Airtime & Data --}}
            <div class="service-tab-pane active lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-8" id="tab-airtime">
                <div class="rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-8 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background:rgba({{ $themeColorRgb }},0.1)">📱</div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Airtime &amp; Data</span>
                            <h3 class="text-xl font-extrabold font-outfit text-slate-900 dark:text-white">Get Cheap Airtime &amp; Data</h3>
                        </div>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">For personal &amp; Business use</p>
                    <ul class="space-y-2.5">
                        @foreach(['MTN Data & Airtime','Airtel Data & Airtime','9Mobile Data & Airtime','Glo Data & Airtime'] as $item)
                        <li class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center shrink-0" style="background:rgba({{ $themeColorRgb }},0.15)">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="{{ $themeColor }}" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 mt-2 px-6 py-3 rounded-xl text-sm font-bold text-white shadow-md hover:opacity-90 transition-all" style="background:{{ $themeColor }}">
                        Get Started
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                {{-- Decorative right panel --}}
                <div class="hidden md:flex items-center justify-center rounded-2xl overflow-hidden relative" style="background:linear-gradient(135deg,rgba({{ $themeColorRgb }},0.08),rgba({{ $themeSecondaryRgb }},0.06));min-height:220px">
                    <div class="text-center space-y-3 p-8">
                        <p class="text-5xl">📶</p>
                        <p class="text-lg font-bold font-outfit text-slate-800 dark:text-white">Stay Connected</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Instant top-up across all Nigerian networks</p>
                    </div>
                </div>
            </div>

            {{-- Electricity --}}
            <div class="service-tab-pane lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-8" id="tab-electricity">
                <div class="rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-8 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background:rgba({{ $themeColorRgb }},0.1)">💡</div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Electricity Bill</span>
                            <h3 class="text-xl font-extrabold font-outfit text-slate-900 dark:text-white">Pay with ease</h3>
                        </div>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">For personal &amp; Business use</p>
                    <ul class="space-y-2.5">
                        @foreach(['Abuja Electricity (AEDC)','Eko Electricity (EKEDC)','Ibadan Electricity (IBEDC)','Enugu Electricity (EEDC)','Jos Electricity (JEDplc)'] as $item)
                        <li class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center shrink-0" style="background:rgba({{ $themeColorRgb }},0.15)">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="{{ $themeColor }}" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 mt-2 px-6 py-3 rounded-xl text-sm font-bold text-white shadow-md hover:opacity-90 transition-all" style="background:{{ $themeColor }}">
                        Get Started
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="hidden md:flex items-center justify-center rounded-2xl overflow-hidden relative" style="background:linear-gradient(135deg,rgba({{ $themeColorRgb }},0.08),rgba({{ $themeSecondaryRgb }},0.06));min-height:220px">
                    <div class="text-center space-y-3 p-8">
                        <p class="text-5xl">⚡</p>
                        <p class="text-lg font-bold font-outfit text-slate-800 dark:text-white">Instant Tokens</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Prepaid &amp; postpaid - tokens generated in seconds</p>
                    </div>
                </div>
            </div>

            {{-- Cable TV --}}
            <div class="service-tab-pane lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-8" id="tab-cable">
                <div class="rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 p-8 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" style="background:rgba({{ $themeColorRgb }},0.1)">📺</div>
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Cable TV</span>
                            <h3 class="text-xl font-extrabold font-outfit text-slate-900 dark:text-white">Recharge your Decoder</h3>
                        </div>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">For personal &amp; Business use</p>
                    <ul class="space-y-2.5">
                        @foreach(['DStv','GOTV','Startimes','Free TV'] as $item)
                        <li class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center shrink-0" style="background:rgba({{ $themeColorRgb }},0.15)">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="{{ $themeColor }}" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 mt-2 px-6 py-3 rounded-xl text-sm font-bold text-white shadow-md hover:opacity-90 transition-all" style="background:{{ $themeColor }}">
                        Get Started
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="hidden md:flex items-center justify-center rounded-2xl overflow-hidden relative" style="background:linear-gradient(135deg,rgba({{ $themeColorRgb }},0.08),rgba({{ $themeSecondaryRgb }},0.06));min-height:220px">
                    <div class="text-center space-y-3 p-8">
                        <p class="text-5xl">🎬</p>
                        <p class="text-lg font-bold font-outfit text-slate-800 dark:text-white">Never Miss a Show</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">DStv, GOTV, Startimes &amp; more - renewed instantly</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     TESTIMONIALS
═══════════════════════════════════════════════════════ --}}
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 space-y-3">
            <span class="inline-block text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full" style="color:{{ $themeColor }};background:rgba({{ $themeColorRgb }},0.08)">Meet Clients Satisfied by our product</span>
            <h2 class="text-3xl font-extrabold font-outfit text-slate-900 dark:text-white">See some of our client reactions to our app.</h2>
        </div>

        {{-- Testimonial carousel --}}
        <div class="relative">
            @foreach([
                ['Fast & Cheap Quality Service.','I must say that this platform has the best cheap and ultimately fast VTU service I have ever experienced. The user interface looks smooth and awesome - it\'s highly responsive and looks/feels cool. Giving this my 5 star and also recommend to everyone.','A.C.','CEO, Harkone Designs'],
                ['Super Reliable Platform!','Funding my wallet and buying data has never been this smooth. The transactions are instant and the support team is always available when I need help. Highly recommended for everyone.','O.B.','Freelance Developer'],
                ['Best VTU Site in Nigeria','I have tried several VTU platforms but this one stands out. The prices are very affordable and the service is always instant. No delays, no stress. My go-to platform for all bills.','E.N.','Business Owner'],
            ] as $i => [$title,$quote,$name,$role])
            <div class="testimonial-item {{ $i === 0 ? 'active' : '' }} flex-col items-center text-center gap-6">
                <div class="flex justify-center mb-4 gap-1">
                    @for($s=0;$s<5;$s++)
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <h4 class="text-lg font-bold font-outfit text-slate-900 dark:text-white">{{ $title }}</h4>
                <p class="text-slate-600 dark:text-slate-400 text-base leading-relaxed max-w-2xl mx-auto italic">"{{ $quote }}"</p>
                <div class="mt-4 flex flex-col items-center gap-1">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold text-white" style="background:{{ $themeColor }}">{{ substr($name,0,1) }}</div>
                    <p class="font-bold text-slate-900 dark:text-white text-sm">{{ $name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $role }}</p>
                </div>
            </div>
            @endforeach

            {{-- Carousel controls --}}
            <div class="flex justify-center gap-3 mt-8">
                <button id="prev-testimonial" class="w-10 h-10 rounded-full border flex items-center justify-center text-slate-500 hover:text-white transition-all" style="border-color:{{ $themeColor }}" onmouseenter="this.style.background='{{ $themeColor }}'" onmouseleave="this.style.background='transparent'">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button id="next-testimonial" class="w-10 h-10 rounded-full text-white flex items-center justify-center transition-all hover:opacity-90" style="background:{{ $themeColor }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     FAQ
═══════════════════════════════════════════════════════ --}}
<section id="about" class="py-20 bg-white dark:bg-slate-900/60">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 space-y-3">
            <span class="inline-block text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full" style="color:{{ $themeColor }};background:rgba({{ $themeColorRgb }},0.08)">Frequently asked questions</span>
            <h2 class="text-3xl font-extrabold font-outfit text-slate-900 dark:text-white">Get answers to some frequently asked questions.</h2>
        </div>

        <div class="space-y-4" id="faq-list">
            @foreach([
                ['Who/What is '.$siteName.'?', $siteName.' is a reliable Nigerian VTU platform that lets you buy airtime, data bundles, pay electricity bills, and renew cable TV subscriptions instantly at the best prices.'],
                ['How do I register on '.$siteName.'?','Simply click on "Sign Up", fill in your name, email address, and create a password. Your account is activated immediately - no verification delays.'],
                ['How do I fund my wallet?','You can fund your wallet via bank transfer, USSD, or online card payment through Paystack/Flutterwave. Funding is reflected instantly on your account.'],
                ['How do I purchase services on '.$siteName.'?','After funding your wallet, navigate to the service you want (Airtime, Data, Cable, or Electricity), fill in the required details, and confirm. Delivery is instant.'],
            ] as $i => [$q,$a])
            <div class="faq-item{{ $i === 0 ? ' open' : '' }} bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                <button class="faq-toggle w-full flex items-center justify-between px-6 py-5 text-left focus:outline-none">
                    <h3 class="font-semibold text-slate-900 dark:text-white font-outfit text-base">{{ $q }}</h3>
                    <span class="faq-chevron ml-4 shrink-0 w-8 h-8 rounded-full flex items-center justify-center" style="background:rgba({{ $themeColorRgb }},0.1);color:{{ $themeColor }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
                <div class="faq-answer px-6 {{ $i === 0 ? 'pb-5' : '' }}">
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{{ $a }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     APP DOWNLOAD CTA
═══════════════════════════════════════════════════════ --}}
<section class="py-20 bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl overflow-hidden relative" style="background:linear-gradient(135deg,{{ $themeColor }},{{ $themeSecondary }})">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 800 300" preserveAspectRatio="xMidYMid slice">
                    <circle cx="700" cy="50" r="200" fill="white"/>
                    <circle cx="100" cy="250" r="150" fill="white"/>
                </svg>
            </div>
            <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-10 items-center px-8 py-14 sm:px-14">
                {{-- Text --}}
                <div class="space-y-5 text-center lg:text-left">
                    <h2 class="text-3xl sm:text-4xl font-extrabold font-outfit text-white leading-tight">Download our App now</h2>
                    <p class="text-white/80 text-base leading-relaxed">
                        Our app is currently available for download for both Android and iOS devices. Users can get the app on their various app store.
                    </p>
                    {{-- App store badges --}}
                    <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
                        <a href="#" class="flex items-center gap-3 bg-black/30 hover:bg-black/50 border border-white/20 text-white px-5 py-3 rounded-xl transition-colors">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.7 9.05 7.42c1.39.07 2.35.82 3.16.82.82 0 2.35-1.01 3.96-.86 1.53.12 2.67.75 3.4 1.97-3.54 2.1-2.95 7.09.48 8.93zm-3.23-15.6c-3.29.37-3.72 3.54-3.72 3.54 3.29-.08 3.72-3.54 3.72-3.54z"/></svg>
                            <div class="text-left">
                                <div class="text-[10px] text-white/70 leading-none">Download on the</div>
                                <div class="text-sm font-bold leading-tight">App Store</div>
                            </div>
                        </a>
                        <a href="#" class="flex items-center gap-3 bg-black/30 hover:bg-black/50 border border-white/20 text-white px-5 py-3 rounded-xl transition-colors">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.61c.28.15.6.18.92.08l10.4-5.99-2.32-2.33-9 8.24zM.54 1.55C.2 1.88 0 2.4 0 3.05v17.88c0 .65.2 1.18.55 1.51l.08.07 10.02-10.02v-.24L.62 1.49l-.08.06zM20.19 10.6l-2.85-1.65-2.54 2.54 2.54 2.55 2.87-1.65c.82-.47.82-1.32-.02-1.79zM3.18.39l9 8.24 2.32-2.32L4.1.31c-.36-.2-.7-.18-.92.08z"/></svg>
                            <div class="text-left">
                                <div class="text-[10px] text-white/70 leading-none">Get it on</div>
                                <div class="text-sm font-bold leading-tight">Google Play</div>
                            </div>
                        </a>
                    </div>
                </div>
                {{-- Illustration --}}
                <div class="flex justify-center lg:justify-end">
                    <div class="relative">
                        <div class="w-48 h-48 rounded-full bg-white/10 flex items-center justify-center">
                            <div class="w-36 h-36 rounded-full bg-white/10 flex items-center justify-center">
                                <svg class="w-20 h-20 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script type="text/javascript">
    // ── Service tabs ──────────────────────────────────────────────
    const tabBtns = document.querySelectorAll('.service-tab-btn');
    const tabPanes = document.querySelectorAll('.service-tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => {
                b.classList.remove('active');
                b.style.borderColor = '';
                b.style.color = '';
                b.style.background = '';
            });
            tabPanes.forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const pane = document.getElementById('tab-' + btn.dataset.tab);
            if (pane) pane.classList.add('active');
        });
    });

    // ── Testimonials slider ───────────────────────────────────────
    const testimonials = document.querySelectorAll('.testimonial-item');
    let current = 0;

    function showTestimonial(idx) {
        testimonials.forEach(t => t.classList.remove('active'));
        testimonials[idx].classList.add('active');
    }

    document.getElementById('next-testimonial').addEventListener('click', () => {
        current = (current + 1) % testimonials.length;
        showTestimonial(current);
    });

    document.getElementById('prev-testimonial').addEventListener('click', () => {
        current = (current - 1 + testimonials.length) % testimonials.length;
        showTestimonial(current);
    });

    // Auto-advance every 6 seconds
    setInterval(() => {
        current = (current + 1) % testimonials.length;
        showTestimonial(current);
    }, 6000);

    // ── FAQ accordion ─────────────────────────────────────────────
    document.querySelectorAll('.faq-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = btn.closest('.faq-item');
            const answer = item.querySelector('.faq-answer');
            const isOpen = item.classList.contains('open');

            // Close all
            document.querySelectorAll('.faq-item').forEach(fi => {
                fi.classList.remove('open');
                fi.querySelector('.faq-answer').style.maxHeight = null;
                fi.querySelector('.faq-answer').classList.remove('pb-5');
            });

            // Open clicked if it was closed
            if (!isOpen) {
                item.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
                answer.classList.add('pb-5');
            }
        });
    });

    // Init first FAQ
    const firstAnswer = document.querySelector('.faq-item.open .faq-answer');
    if (firstAnswer) firstAnswer.style.maxHeight = firstAnswer.scrollHeight + 'px';
</script>
@endsection

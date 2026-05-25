@extends('layouts.app')

@section('title', 'PayPulse - Instant Airtime, Cheap Data, Electricity & Cable TV')

@section('content')
<!-- Hero Section -->
<div class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <!-- Left Side Text -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center space-x-2 bg-indigo-500/10 dark:bg-indigo-500/20 text-vtu-primary dark:text-indigo-400 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider">
                    <span>⚡ Super-Fast Delivery System</span>
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold font-outfit tracking-tight leading-none text-slate-900 dark:text-white">
                    Supercharge Your <br>
                    <span class="bg-gradient-to-r from-vtu-primary to-vtu-secondary bg-clip-text text-transparent">Digital Payments</span>
                </h1>
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-xl mx-auto lg:mx-0">
                    Get up to 5% discount on airtime purchases, cheap SME data bundles starting from ₦250/GB, utility bill payments, and cable subscriptions all in one secure platform.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto text-center px-8 py-4 rounded-xl text-base font-semibold text-white bg-gradient-to-r from-vtu-primary to-vtu-secondary hover:from-indigo-600 hover:to-cyan-600 shadow-lg shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30 hover:-translate-y-0.5 transition-all duration-200">
                        Create Free Account
                    </a>
                    <a href="#calculator" class="w-full sm:w-auto text-center px-8 py-4 rounded-xl text-base font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Calculate Discounts
                    </a>
                </div>

                <!-- Trust Badges / Stats -->
                <div class="pt-6 grid grid-cols-3 gap-4 border-t border-slate-200 dark:border-slate-800 max-w-md mx-auto lg:mx-0">
                    <div>
                        <div class="text-2xl font-bold font-outfit text-slate-950 dark:text-white">₦240M+</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Processed Daily</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold font-outfit text-slate-950 dark:text-white">50K+</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Active Users</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold font-outfit text-slate-950 dark:text-white">99.9%</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">System Uptime</div>
                    </div>
                </div>
            </div>

            <!-- Right Side Interactive Showcase / Card -->
            <div class="lg:col-span-5 relative">
                <!-- Decorative blurred circle background -->
                <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-vtu-primary to-vtu-secondary opacity-30 blur-2xl animate-pulse-slow"></div>
                
                <div class="relative bg-white dark:bg-vtu-darkCard rounded-3xl shadow-2xl p-6 sm:p-8 border border-slate-100 dark:border-slate-800">
                    <h3 class="text-lg font-bold font-outfit mb-4 text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-indigo-500/10 text-vtu-primary">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        Quick Top-Up Demo
                    </h3>

                    <!-- Quick Form -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Select Service</label>
                            <div class="grid grid-cols-4 gap-2">
                                <button type="button" class="service-tab active py-3 px-1 rounded-xl text-center border-2 border-vtu-primary bg-indigo-500/5 text-vtu-primary flex flex-col items-center gap-1 transition-all">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[10px] font-semibold">Airtime</span>
                                </button>
                                <button type="button" class="service-tab py-3 px-1 rounded-xl text-center border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:border-vtu-primary flex flex-col items-center gap-1 transition-all">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span class="text-[10px] font-semibold">Data</span>
                                </button>
                                <button type="button" class="service-tab py-3 px-1 rounded-xl text-center border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:border-vtu-primary flex flex-col items-center gap-1 transition-all">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4" />
                                    </svg>
                                    <span class="text-[10px] font-semibold">Cable TV</span>
                                </button>
                                <button type="button" class="service-tab py-3 px-1 rounded-xl text-center border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:border-vtu-primary flex flex-col items-center gap-1 transition-all">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <span class="text-[10px] font-semibold">Utility</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Network Provider</label>
                            <div class="grid grid-cols-4 gap-2">
                                <button type="button" class="network-btn border-2 border-indigo-500 rounded-xl p-1 bg-slate-50 dark:bg-slate-800 hover:-translate-y-0.5 transition-all">
                                    <div class="h-10 rounded-lg flex items-center justify-center font-bold text-slate-900 dark:text-white text-xs bg-amber-400">MTN</div>
                                </button>
                                <button type="button" class="network-btn border border-slate-200 dark:border-slate-800 rounded-xl p-1 bg-slate-50 dark:bg-slate-800 hover:-translate-y-0.5 transition-all">
                                    <div class="h-10 rounded-lg flex items-center justify-center font-bold text-white text-xs bg-red-600">Airtel</div>
                                </button>
                                <button type="button" class="network-btn border border-slate-200 dark:border-slate-800 rounded-xl p-1 bg-slate-50 dark:bg-slate-800 hover:-translate-y-0.5 transition-all">
                                    <div class="h-10 rounded-lg flex items-center justify-center font-bold text-white text-xs bg-green-600">Glo</div>
                                </button>
                                <button type="button" class="network-btn border border-slate-200 dark:border-slate-800 rounded-xl p-1 bg-slate-50 dark:bg-slate-800 hover:-translate-y-0.5 transition-all">
                                    <div class="h-10 rounded-lg flex items-center justify-center font-bold text-white text-xs bg-emerald-500">9mobile</div>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Phone Number</label>
                            <input type="tel" id="phone" placeholder="e.g. 08012345678" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary/50 text-sm">
                        </div>

                        <div>
                            <label for="amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Amount (₦)</label>
                            <input type="number" id="amount" placeholder="e.g. 1000" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vtu-primary/50 text-sm">
                        </div>

                        <a href="{{ route('register') }}" class="block text-center w-full py-4 rounded-xl text-sm font-semibold text-white bg-vtu-primary hover:bg-indigo-700 shadow-md shadow-indigo-600/10 hover:shadow-lg transition-all duration-200 mt-2">
                            Purchase Instantly
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Services Grid -->
<div id="services" class="py-20 bg-white dark:bg-slate-900/50 border-y border-slate-100 dark:border-slate-800/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 space-y-4">
            <h2 class="text-3xl font-bold font-outfit text-slate-900 dark:text-white">Our Main Services</h2>
            <p class="text-slate-600 dark:text-slate-400">Everything you need to stay connected and pay bills in one streamlined interface.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Service Card 1 -->
            <div class="bg-vtu-light dark:bg-vtu-darkCard rounded-3xl p-8 border border-slate-200/50 dark:border-slate-800 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 group">
                <div class="h-12 w-12 rounded-2xl bg-indigo-500/10 text-vtu-primary flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-slate-900 dark:text-white mb-2">Airtime Top-Up</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-4">Instantly load airtime across all Nigerian telecom networks. Enjoy instant recharge and special discounts.</p>
                <a href="{{ route('register') }}" class="text-xs font-semibold text-vtu-primary group-hover:underline flex items-center gap-1">Top-Up Now <span class="text-sm">→</span></a>
            </div>

            <!-- Service Card 2 -->
            <div class="bg-vtu-light dark:bg-vtu-darkCard rounded-3xl p-8 border border-slate-200/50 dark:border-slate-800 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 group">
                <div class="h-12 w-12 rounded-2xl bg-cyan-500/10 text-vtu-secondary flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-slate-900 dark:text-white mb-2">Cheap Data Bundles</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-4">Buy cheap internet bundles. SME, Gifting & Direct data plans with up to 30 days validity.</p>
                <a href="{{ route('register') }}" class="text-xs font-semibold text-vtu-secondary group-hover:underline flex items-center gap-1">Buy Data <span class="text-sm">→</span></a>
            </div>

            <!-- Service Card 3 -->
            <div class="bg-vtu-light dark:bg-vtu-darkCard rounded-3xl p-8 border border-slate-200/50 dark:border-slate-800 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 group">
                <div class="h-12 w-12 rounded-2xl bg-amber-500/10 text-vtu-accent flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-slate-900 dark:text-white mb-2">Cable TV Activation</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-4">Instantly renew or upgrade subscriptions for DSTV, GOTV, and StarTimes. No manual processing delays.</p>
                <a href="{{ route('register') }}" class="text-xs font-semibold text-vtu-accent group-hover:underline flex items-center gap-1">Renew TV <span class="text-sm">→</span></a>
            </div>

            <!-- Service Card 4 -->
            <div class="bg-vtu-light dark:bg-vtu-darkCard rounded-3xl p-8 border border-slate-200/50 dark:border-slate-800 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 group">
                <div class="h-12 w-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-slate-900 dark:text-white mb-2">Electricity Bills</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-4">Pay prepaid and postpaid electricity bills. Generate metered tokens instantly for all major distribution companies.</p>
                <a href="{{ route('register') }}" class="text-xs font-semibold text-emerald-500 group-hover:underline flex items-center gap-1">Pay Bills <span class="text-sm">→</span></a>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Calculator Section -->
<div id="pricing" class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <!-- Text Content -->
            <div class="space-y-6">
                <h2 class="text-3xl sm:text-4xl font-bold font-outfit text-slate-900 dark:text-white leading-tight">
                    Get Real-Time Price Estimations With Our Calculator
                </h2>
                <p class="text-slate-600 dark:text-slate-400">
                    We offer standard wholesale prices to our users. Select a service and network provider to calculate the exact amount you will pay versus the market rate.
                </p>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="h-6 w-6 rounded-full bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0 mt-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 dark:text-white">API Integration Available</h4>
                            <p class="text-sm text-slate-500">Are you a developer? Hook up your VTU reseller business to our API.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="h-6 w-6 rounded-full bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0 mt-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 dark:text-white">Discounted Rates</h4>
                            <p class="text-sm text-slate-500">Up to 4.5% discount on airtime and cheaper data pricing for VIP partners.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calculator Card Widget -->
            <div id="calculator" class="bg-white dark:bg-vtu-darkCard rounded-3xl p-8 border border-slate-100 dark:border-slate-800/80 shadow-xl">
                <h3 class="text-xl font-bold font-outfit mb-6 text-slate-900 dark:text-white">Discounts Calculator</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Service Type</label>
                        <select id="calc-service" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/50 text-sm">
                            <option value="airtime">Airtime Recharge (Discounted)</option>
                            <option value="data">Internet Data Bundles</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Network Provider</label>
                        <select id="calc-network" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/50 text-sm">
                            <option value="mtn" data-airtime-disc="3.5" data-data-price="250">MTN Nigeria</option>
                            <option value="airtel" data-airtime-disc="4.0" data-data-price="270">Airtel Nigeria</option>
                            <option value="glo" data-airtime-disc="5.0" data-data-price="240">Glo Nigeria</option>
                            <option value="mobile9" data-airtime-disc="4.5" data-data-price="260">9mobile</option>
                        </select>
                    </div>

                    <div id="calc-amount-group">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Enter Amount (₦)</label>
                        <input type="number" id="calc-amount" value="1000" min="100" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/50 text-sm">
                    </div>

                    <div id="calc-data-group" class="hidden">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Select Data Volume (GB)</label>
                        <select id="calc-data-gb" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/50 text-sm">
                            <option value="1">1 GB</option>
                            <option value="2">2 GB</option>
                            <option value="5">5 GB</option>
                            <option value="10">10 GB</option>
                        </select>
                    </div>

                    <!-- Output Area -->
                    <div class="mt-6 p-5 rounded-2xl bg-indigo-500/5 dark:bg-slate-800/80 border border-indigo-500/10 dark:border-slate-700 space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Retail price:</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300" id="retail-price">₦1,000.00</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Your price:</span>
                            <span class="font-bold text-lg text-emerald-500" id="discounted-price">₦965.00</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-vtu-primary font-medium">You Save:</span>
                            <span class="font-bold text-vtu-primary" id="saved-price">₦35.00 (3.5%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAQs Accordion -->
<div id="about" class="py-20 bg-white dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800/80">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 space-y-4">
            <h2 class="text-3xl font-bold font-outfit text-slate-900 dark:text-white">Frequently Asked Questions</h2>
            <p class="text-slate-600 dark:text-slate-400">Got questions? We've got answers.</p>
        </div>

        <div class="space-y-4">
            <!-- FAQ 1 -->
            <details class="group bg-vtu-light dark:bg-vtu-darkCard rounded-2xl border border-slate-200/50 dark:border-slate-800 p-6 [&_summary::-webkit-details-marker]:hidden" open>
                <summary class="flex justify-between items-center cursor-pointer focus:outline-none">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white font-outfit">How instant is the top-up delivery?</h3>
                    <span class="ml-1.5 shrink-0 rounded-full bg-indigo-500/10 p-1.5 text-vtu-primary group-open:rotate-180 transition-transform duration-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Our system is fully automated. Airtime, data bundles, electricity tokens, and cable activations are dispatched immediately (within 5-15 seconds) once payment is verified.
                </p>
            </details>

            <!-- FAQ 2 -->
            <details class="group bg-vtu-light dark:bg-vtu-darkCard rounded-2xl border border-slate-200/50 dark:border-slate-800 p-6 [&_summary::-webkit-details-marker]:hidden">
                <summary class="flex justify-between items-center cursor-pointer focus:outline-none">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white font-outfit">How can I fund my virtual wallet?</h3>
                    <span class="ml-1.5 shrink-0 rounded-full bg-indigo-500/10 p-1.5 text-vtu-primary group-open:rotate-180 transition-transform duration-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    You can fund your wallet via automatic bank transfer (custom account numbers are generated for you on sign-up) or instantly online using your credit/debit card via Paystack/Flutterwave.
                </p>
            </details>

            <!-- FAQ 3 -->
            <details class="group bg-vtu-light dark:bg-vtu-darkCard rounded-2xl border border-slate-200/50 dark:border-slate-800 p-6 [&_summary::-webkit-details-marker]:hidden">
                <summary class="flex justify-between items-center cursor-pointer focus:outline-none">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white font-outfit">Are the data bundles compatible with all devices?</h3>
                    <span class="ml-1.5 shrink-0 rounded-full bg-indigo-500/10 p-1.5 text-vtu-primary group-open:rotate-180 transition-transform duration-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Yes, our data plans (including SME, CG, and Direct Gifting) work on all internet-enabled devices including Androids, iPhones, iPads, modems, routers, and smart TVs.
                </p>
            </details>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    // Interactive Calculator Script
    const calcService = document.getElementById('calc-service');
    const calcNetwork = document.getElementById('calc-network');
    const calcAmountGroup = document.getElementById('calc-amount-group');
    const calcDataGroup = document.getElementById('calc-data-group');
    const calcAmount = document.getElementById('calc-amount');
    const calcDataGb = document.getElementById('calc-data-gb');

    const retailPrice = document.getElementById('retail-price');
    const discountedPrice = document.getElementById('discounted-price');
    const savedPrice = document.getElementById('saved-price');

    // Update form visibility based on service
    calcService.addEventListener('change', () => {
        if (calcService.value === 'airtime') {
            calcAmountGroup.classList.remove('hidden');
            calcDataGroup.classList.add('hidden');
        } else {
            calcAmountGroup.classList.add('hidden');
            calcDataGroup.classList.remove('hidden');
        }
        calculate();
    });

    // Recalculate on inputs change
    calcNetwork.addEventListener('change', calculate);
    calcAmount.addEventListener('input', calculate);
    calcDataGb.addEventListener('change', calculate);

    function calculate() {
        const service = calcService.value;
        const selectedOpt = calcNetwork.options[calcNetwork.selectedIndex];
        
        if (service === 'airtime') {
            const amount = parseFloat(calcAmount.value) || 0;
            const discountPercent = parseFloat(selectedOpt.getAttribute('data-airtime-disc'));
            
            const cost = amount - (amount * (discountPercent / 100));
            const saved = amount - cost;

            retailPrice.innerText = '₦' + amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            discountedPrice.innerText = '₦' + cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            savedPrice.innerText = '₦' + saved.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + discountPercent + '%)';
        } else {
            const gbs = parseInt(calcDataGb.value) || 1;
            const pricePerGb = parseFloat(selectedOpt.getAttribute('data-data-price'));
            
            const totalCost = gbs * pricePerGb;
            const marketRate = gbs * 500; // Average retail market rate is ~500 per GB
            const saved = marketRate - totalCost;
            const savedPercent = ((saved / marketRate) * 100).toFixed(0);

            retailPrice.innerText = '₦' + marketRate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            discountedPrice.innerText = '₦' + totalCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            savedPrice.innerText = '₦' + saved.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + savedPercent + '%)';
        }
    }

    // Initial calculation
    calculate();
</script>
@endsection

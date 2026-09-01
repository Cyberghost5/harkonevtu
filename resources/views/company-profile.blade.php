@extends('layouts.app')

@section('title', 'Company Profile – New Millennium Resources Nigeria Limited')

@section('styles')
<style>
    .hero-bg {
        background: radial-gradient(circle at 50% 0%, rgba({{ $themeColorRgb }}, 0.15) 0%, rgba(15, 23, 42, 0.98) 70%), #0B0F19;
    }
    .glass-card {
        background: rgba(30, 41, 59, 0.7);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .glass-card-light {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .sector-card:hover {
        transform: translateY(-4px);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
</style>
@endsection

@section('content')

{{-- ═══════════════════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════════════════ --}}
<section class="relative hero-bg text-white pt-20 pb-24 overflow-hidden">
    {{-- Decorative Background Elements --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-vtu-primary/20 blur-3xl"></div>
        <div class="absolute bottom-0 -left-40 w-96 h-96 rounded-full bg-vtu-secondary/20 blur-3xl"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto">
            
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 border border-white/15 text-xs font-semibold text-vtu-accent uppercase tracking-wider mb-6">
                <span class="w-2 h-2 rounded-full bg-vtu-accent animate-pulse"></span>
                RC: 1162608 • Engineering • ICT • Solar Energy
            </div>

            {{-- Main Title --}}
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold font-outfit tracking-tight leading-tight mb-6">
                New Millennium Resources <span class="bg-clip-text text-transparent bg-gradient-to-r from-vtu-primary via-teal-400 to-vtu-secondary">Nigeria Limited</span>
            </h1>

            <p class="text-lg sm:text-xl text-slate-300 font-light leading-relaxed mb-8">
                Providing broad-based solutions and premier services across Information & Communication Technology, Civil Engineering Infrastructure, and Renewable Solar Power Management.
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-wrap justify-center gap-4">
                <a href="#sectors" class="px-7 py-3.5 rounded-xl bg-vtu-primary hover:bg-vtu-primary/90 text-white font-semibold text-sm shadow-lg shadow-vtu-primary/30 transition-all hover:scale-105">
                    Explore Our Sectors
                </a>
                <a href="/Company Profile.pdf" target="_blank" download class="px-7 py-3.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-sm backdrop-blur-md transition-all">
                    📄 Download Profile (PDF)
                </a>
                <a href="{{ route('login') }}" class="px-7 py-3.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold text-sm transition-all">
                    Client Services Portal →
                </a>
            </div>

        </div>

        {{-- Quick Stats Banner --}}
        <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="glass-card p-6 rounded-2xl text-center">
                <div class="text-3xl font-extrabold text-vtu-primary font-outfit mb-1">10+ Years</div>
                <div class="text-xs text-slate-400 font-medium uppercase tracking-wider">Corporate Heritage</div>
            </div>
            <div class="glass-card p-6 rounded-2xl text-center">
                <div class="text-3xl font-extrabold text-teal-400 font-outfit mb-1">3 Sectors</div>
                <div class="text-xs text-slate-400 font-medium uppercase tracking-wider">ICT, Civil & Solar</div>
            </div>
            <div class="glass-card p-6 rounded-2xl text-center">
                <div class="text-3xl font-extrabold text-vtu-secondary font-outfit mb-1">100%</div>
                <div class="text-xs text-slate-400 font-medium uppercase tracking-wider">Safety & Quality Standard</div>
            </div>
            <div class="glass-card p-6 rounded-2xl text-center">
                <div class="text-3xl font-extrabold text-amber-400 font-outfit mb-1">Nationwide</div>
                <div class="text-xs text-slate-400 font-medium uppercase tracking-wider">Project Execution</div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     ABOUT US & CORPORATE OVERVIEW
═══════════════════════════════════════════════════════ --}}
<section id="about" class="py-20 bg-slate-50 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            
            {{-- Text Column --}}
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-vtu-primary/10 text-vtu-primary text-xs font-semibold uppercase tracking-wider mb-4">
                    Who We Are
                </div>
                <h2 class="text-3xl sm:text-4xl font-bold font-outfit text-slate-900 tracking-tight mb-6">
                    A Multi-Disciplinary Firm Built on Excellence & Integrity
                </h2>
                <p class="text-slate-600 leading-relaxed mb-4">
                    <strong>New Millennium Resources Nigeria Limited</strong> is a registered Nigerian corporate entity established to address critical infrastructure gaps across the national economy through cutting-edge technology, civil construction, and renewable energy.
                </p>
                <p class="text-slate-600 leading-relaxed mb-6">
                    We combine technical mastery, modern equipment, and seasoned professionals to deliver high-yield solutions at low cost — ensuring zero workplace hazards and strict environmental responsibility.
                </p>

                {{-- Key Features List --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm font-medium text-slate-700">
                    <div class="flex items-center gap-2.5">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">✓</span>
                        Unrivaled Engineering Quality
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">✓</span>
                        Optimal Value at Least Cost
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">✓</span>
                        Zero Hazard Safety Standards
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">✓</span>
                        Environmental Impact Minimization
                    </div>
                </div>
            </div>

            {{-- Vision & Mission Cards --}}
            <div class="space-y-6">
                
                {{-- Vision Card --}}
                <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-xl bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-2xl mb-4">
                        🎯
                    </div>
                    <h3 class="text-xl font-bold font-outfit text-slate-900 mb-2">Our Vision</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        To highlight technical challenges and bridge critical infrastructure gaps across industries and the national economy through sustainable innovation.
                    </p>
                </div>

                {{-- Mission Card --}}
                <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-xl bg-vtu-secondary/10 text-vtu-secondary flex items-center justify-center text-2xl mb-4">
                        🚀
                    </div>
                    <h3 class="text-xl font-bold font-outfit text-slate-900 mb-2">Our Mission</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        To work in harmony and foster long-term cooperation and mutual understanding between our company, our clients, and host communities — yielding optimal value at minimal cost with elite consultancy.
                    </p>
                </div>

            </div>

        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     OUR SECTORS & CAPABILITIES
═══════════════════════════════════════════════════════ --}}
<section id="sectors" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-2xl mx-auto mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-vtu-primary/10 text-vtu-primary text-xs font-semibold uppercase tracking-wider mb-3">
                Core Sectors
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold font-outfit text-slate-900 tracking-tight">
                Our Primary Areas of Operations
            </h2>
            <p class="text-slate-500 text-sm mt-3">
                Broad-based engineering, technology, and energy infrastructure solutions tailored to client specifications.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            {{-- Sector 1: ICT & Telecom --}}
            <div class="sector-card bg-slate-50 p-8 rounded-3xl border border-slate-200/70 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-blue-600/30 mb-6">
                        📱
                    </div>
                    <h3 class="text-xl font-bold font-outfit text-slate-900 mb-3">
                        Information & Communication Technology (ICT)
                    </h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">
                        Deploying automated telecom infrastructure, VTU & bill payment platforms, data APIs, enterprise network installations, and digital communication solutions.
                    </p>
                </div>
                <ul class="space-y-2 text-xs font-medium text-slate-700 border-t border-slate-200/80 pt-4">
                    <li class="flex items-center gap-2">🔹 Automated VTU & Payment Infrastructure</li>
                    <li class="flex items-center gap-2">🔹 Enterprise Data & Airtime APIs</li>
                    <li class="flex items-center gap-2">🔹 Network & System Consultancy</li>
                </ul>
            </div>

            {{-- Sector 2: Civil Engineering --}}
            <div class="sector-card bg-slate-50 p-8 rounded-3xl border border-slate-200/70 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-amber-600/30 mb-6">
                        🏗️
                    </div>
                    <h3 class="text-xl font-bold font-outfit text-slate-900 mb-3">
                        Civil Engineering & Construction
                    </h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">
                        Full-scale engineering, procurement, and construction (EPC) of major interest buildings, highway roads, bridges, and comprehensive road drainage networks.
                    </p>
                </div>
                <ul class="space-y-2 text-xs font-medium text-slate-700 border-t border-slate-200/80 pt-4">
                    <li class="flex items-center gap-2">🔹 Road Construction & Drainage Networks</li>
                    <li class="flex items-center gap-2">🔹 Structural & Bridge Engineering</li>
                    <li class="flex items-center gap-2">🔹 Major Interest Buildings Procurement</li>
                </ul>
            </div>

            {{-- Sector 3: Solar Energy --}}
            <div class="sector-card bg-slate-50 p-8 rounded-3xl border border-slate-200/70 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-emerald-600/30 mb-6">
                        ☀️
                    </div>
                    <h3 class="text-xl font-bold font-outfit text-slate-900 mb-3">
                        Solar Power Management
                    </h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">
                        Designing and installing commercial & industrial solar energy systems, energy storage units, and grid power management solutions with low maintenance costs.
                    </p>
                </div>
                <ul class="space-y-2 text-xs font-medium text-slate-700 border-t border-slate-200/80 pt-4">
                    <li class="flex items-center gap-2">🔹 Industrial Solar Installations</li>
                    <li class="flex items-center gap-2">🔹 Renewable Grid Power Management</li>
                    <li class="flex items-center gap-2">🔹 Zero-Hazard Energy System Audits</li>
                </ul>
            </div>

        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     BOARD OF DIRECTORS & LEADERSHIP
═══════════════════════════════════════════════════════ --}}
<section id="leadership" class="py-20 bg-slate-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-2xl mx-auto mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-vtu-accent text-xs font-semibold uppercase tracking-wider mb-3">
                Corporate Governance
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold font-outfit tracking-tight">
                Board of Directors & Management
            </h2>
            <p class="text-slate-400 text-sm mt-3">
                Guided by seasoned Nigerian industry leaders dedicated to operational integrity and growth.
            </p>
        </div>

        {{-- Featured Key Executive Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            
            {{-- Chairman/CEO --}}
            <div class="glass-card rounded-2xl overflow-hidden group hover:border-vtu-primary transition-all">
                <div class="h-64 overflow-hidden bg-slate-800 relative">
                    <img src="{{ asset('assets/images/directors/Umar Ibrahim Idris.jpeg') }}" 
                         alt="Umar Ibrahim Idris" 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                </div>
                <div class="p-5">
                    <h4 class="text-lg font-bold font-outfit text-white">Umar Ibrahim Idris</h4>
                    <p class="text-xs font-semibold text-vtu-primary uppercase tracking-wider mt-0.5">Chairman / CEO</p>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded bg-white/10 text-[10px] text-slate-300">Nigerian</span>
                </div>
            </div>

            {{-- Managing Director --}}
            <div class="glass-card rounded-2xl overflow-hidden group hover:border-vtu-primary transition-all">
                <div class="h-64 overflow-hidden bg-slate-800 relative">
                    <img src="{{ asset('assets/images/directors/Abubakar Ibrahim Idris.jpeg') }}" 
                         alt="Abubakar Ibrahim Idris" 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                </div>
                <div class="p-5">
                    <h4 class="text-lg font-bold font-outfit text-white">Abubakar Ibrahim Idris</h4>
                    <p class="text-xs font-semibold text-teal-400 uppercase tracking-wider mt-0.5">Managing Director</p>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded bg-white/10 text-[10px] text-slate-300">Executive Director</span>
                </div>
            </div>

            {{-- Director: Nur Moh'd Idris --}}
            <div class="glass-card rounded-2xl overflow-hidden group hover:border-vtu-primary transition-all">
                <div class="h-64 overflow-hidden bg-slate-800 relative">
                    <img src="{{ asset('assets/images/directors/Nur Mohammed Idris.jpeg') }}" 
                         alt="Nur Moh’d Idris" 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                </div>
                <div class="p-5">
                    <h4 class="text-lg font-bold font-outfit text-white">Nur Moh’d Idris</h4>
                    <p class="text-xs font-semibold text-vtu-secondary uppercase tracking-wider mt-0.5">Director</p>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded bg-white/10 text-[10px] text-slate-300">Nigerian</span>
                </div>
            </div>

            {{-- Director: Mustapha Moh'd Idris --}}
            <div class="glass-card rounded-2xl overflow-hidden group hover:border-vtu-primary transition-all">
                <div class="h-64 overflow-hidden bg-slate-800 relative">
                    <img src="{{ asset('assets/images/directors/Mustapha Mohammed Idris.jpeg') }}" 
                         alt="Mustapha Moh’d Idris" 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                </div>
                <div class="p-5">
                    <h4 class="text-lg font-bold font-outfit text-white">Mustapha Moh’d Idris</h4>
                    <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider mt-0.5">Director & Secretary</p>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded bg-white/10 text-[10px] text-slate-300">Nigerian</span>
                </div>
            </div>

        </div>

        {{-- Full Board Roster Grid --}}
        <div class="glass-card p-8 rounded-3xl border border-slate-800">
            <h4 class="text-base font-bold font-outfit text-white mb-6 uppercase tracking-wider flex items-center gap-2">
                <span>🏛️</span> Full Board Roster & Officers
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                
                <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                    <div class="font-bold text-sm text-white">Ibrahim Moh’d Idris</div>
                    <div class="text-xs text-slate-400">Director • Nigerian</div>
                </div>

                <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                    <div class="font-bold text-sm text-white">Aisha Ahmed Idris</div>
                    <div class="text-xs text-slate-400">Director</div>
                </div>

                <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                    <div class="font-bold text-sm text-white">Idris Ahmed Idris</div>
                    <div class="text-xs text-slate-400">Director • Nigerian</div>
                </div>

                <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                    <div class="font-bold text-sm text-white">Yakub Moh'd Idris</div>
                    <div class="text-xs text-slate-400">Director • Nigerian</div>
                </div>

                <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                    <div class="font-bold text-sm text-white">Mustapha Moh’d</div>
                    <div class="text-xs text-slate-400">Company Secretary • Nigerian</div>
                </div>

            </div>
        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     CONTACT & LOCATION FOOTER SECTION
═══════════════════════════════════════════════════════ --}}
<section id="contact" class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            
            {{-- Contact Cards --}}
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-vtu-primary/10 text-vtu-primary text-xs font-semibold uppercase tracking-wider mb-4">
                    Get in Touch
                </div>
                <h2 class="text-3xl font-bold font-outfit text-slate-900 tracking-tight mb-6">
                    Head Office & Corporate Contacts
                </h2>
                <p class="text-slate-600 text-sm leading-relaxed mb-8">
                    Have inquiries regarding our engineering, ICT, or solar energy solutions? Contact our corporate office directly.
                </p>

                <div class="space-y-4">
                    
                    {{-- Address --}}
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-vtu-primary/10 text-vtu-primary flex items-center justify-center text-lg flex-shrink-0">
                            📍
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase text-slate-400 tracking-wider">Corporate Address</div>
                            <div class="text-sm font-medium text-slate-800 mt-1">
                                29 Mamman Lawan Strt. opposite Kofa Biyu, along Kashim Ibrahim Expressway, Maiduguri, Borno State, Nigeria.
                            </div>
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                            📞
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase text-slate-400 tracking-wider">Phone Lines</div>
                            <a href="tel:+2347087111000" class="text-sm font-medium text-slate-800 hover:text-vtu-primary transition-colors block mt-1">
                                +234 708 711 1000
                            </a>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg flex-shrink-0">
                            ✉️
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase text-slate-400 tracking-wider">Email Address</div>
                            <a href="mailto:contact@millenniumresource.com.ng" class="text-sm font-medium text-slate-800 hover:text-vtu-primary transition-colors block mt-1">
                                contact@millenniumresource.com.ng
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Quick Message Form --}}
            <div class="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-sm">
                <h3 class="text-xl font-bold font-outfit text-slate-900 mb-2">Write to Us</h3>
                <p class="text-xs text-slate-500 mb-6">Send us a direct message and our team will get back to you within 24 hours.</p>

                @if(session('success'))
                    <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium">
                        ✓ {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('contact.send') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Your Full Name / Company</label>
                        <input type="text" name="name" required placeholder="John Doe / Enterprise Ltd"
                               class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
                        <input type="email" name="email" required placeholder="name@company.com"
                               class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Message / Inquiry</label>
                        <textarea name="message" rows="4" required placeholder="How can we assist you?"
                                  class="w-full px-4 py-3 text-sm border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 resize-none"></textarea>
                    </div>
                    <button type="submit" class="w-full py-3.5 rounded-xl bg-vtu-primary hover:bg-vtu-primary/90 text-white font-semibold text-sm shadow-md transition-all">
                        Send Message
                    </button>
                </form>
            </div>

        </div>

    </div>
</section>

@endsection

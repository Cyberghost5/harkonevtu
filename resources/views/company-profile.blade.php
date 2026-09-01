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
                <a href="/Company Profile.pdf" target="_blank" download class="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-sm backdrop-blur-md transition-all">
                    <svg class="w-4 h-4 text-vtu-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Download Profile (PDF)</span>
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
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold">✓</span>
                        Unrivaled Engineering Quality
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold">✓</span>
                        Optimal Value at Least Cost
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold">✓</span>
                        Zero Hazard Safety Standards
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold">✓</span>
                        Environmental Impact Minimization
                    </div>
                </div>
            </div>

            {{-- Vision & Mission Cards --}}
            <div class="space-y-6">
                
                {{-- Vision Card --}}
                <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-xl bg-vtu-primary/10 text-vtu-primary flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold font-outfit text-slate-900 mb-2">Our Vision</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">
                        To highlight technical challenges and bridge critical infrastructure gaps across industries and the national economy through sustainable innovation.
                    </p>
                </div>

                {{-- Mission Card --}}
                <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 rounded-xl bg-vtu-secondary/10 text-vtu-secondary flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
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
                    <div class="w-14 h-14 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-lg shadow-blue-600/30 mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold font-outfit text-slate-900 mb-3">
                        Information & Communication Technology (ICT)
                    </h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">
                        Deploying automated telecom infrastructure, VTU & bill payment platforms, data APIs, enterprise network installations, and digital communication solutions.
                    </p>
                </div>
                <ul class="space-y-2 text-xs font-medium text-slate-700 border-t border-slate-200/80 pt-4">
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Automated VTU & Payment Infrastructure</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Enterprise Data & Airtime APIs</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Network & System Consultancy</span>
                    </li>
                </ul>
            </div>

            {{-- Sector 2: Civil Engineering --}}
            <div class="sector-card bg-slate-50 p-8 rounded-3xl border border-slate-200/70 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-600 text-white flex items-center justify-center shadow-lg shadow-amber-600/30 mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 10V11m0 0h4m-4 0H7"/></svg>
                    </div>
                    <h3 class="text-xl font-bold font-outfit text-slate-900 mb-3">
                        Civil Engineering & Construction
                    </h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">
                        Full-scale engineering, procurement, and construction (EPC) of major interest buildings, highway roads, bridges, and comprehensive road drainage networks.
                    </p>
                </div>
                <ul class="space-y-2 text-xs font-medium text-slate-700 border-t border-slate-200/80 pt-4">
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Road Construction & Drainage Networks</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Structural & Bridge Engineering</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Major Interest Buildings Procurement</span>
                    </li>
                </ul>
            </div>

            {{-- Sector 3: Solar Energy --}}
            <div class="sector-card bg-slate-50 p-8 rounded-3xl border border-slate-200/70 flex flex-col justify-between">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-600/30 mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold font-outfit text-slate-900 mb-3">
                        Solar Power Management
                    </h3>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">
                        Designing and installing commercial & industrial solar energy systems, energy storage units, and grid power management solutions with low maintenance costs.
                    </p>
                </div>
                <ul class="space-y-2 text-xs font-medium text-slate-700 border-t border-slate-200/80 pt-4">
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Industrial Solar Installations</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Renewable Grid Power Management</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Zero-Hazard Energy System Audits</span>
                    </li>
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
                <svg class="w-5 h-5 text-vtu-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4"/></svg>
                <span>Full Board Roster & Officers</span>
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
                            <svg class="w-5 h-5 text-vtu-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
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
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
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
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
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

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
     WHY CHOOSE US - OUR COMPETITIVE ADVANTAGES
═══════════════════════════════════════════════════════ --}}
<section id="why-choose-us" class="py-20 bg-slate-900 text-white relative overflow-hidden">
    {{-- Glow Accents --}}
    <div class="absolute -top-24 -left-24 w-80 h-80 rounded-full bg-vtu-primary/10 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -right-24 w-80 h-80 rounded-full bg-vtu-secondary/10 blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <div class="text-center max-w-2xl mx-auto mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-vtu-accent text-xs font-semibold uppercase tracking-wider mb-3">
                Value Proposition
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold font-outfit tracking-tight">
                Why Partner With New Millennium Resources
            </h2>
            <p class="text-slate-400 text-sm mt-3">
                Our core operational principles ensure high efficiency, zero hazards, and supreme technical delivery.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            {{-- Advantage 1 --}}
            <div class="glass-card p-8 rounded-3xl border border-slate-800 hover:border-vtu-primary/50 transition-all group">
                <div class="w-12 h-12 rounded-xl bg-vtu-primary/20 text-vtu-primary flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-white mb-2">Unrivaled Quality</h3>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Executing engineering, solar, and ICT projects with zero compromise on international engineering standards and precision.
                </p>
            </div>

            {{-- Advantage 2 --}}
            <div class="glass-card p-8 rounded-3xl border border-slate-800 hover:border-teal-500/50 transition-all group">
                <div class="w-12 h-12 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-white mb-2">Least Cost Value</h3>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Delivering maximum yield and infrastructure durability at optimal, transparent budget structures for clients.
                </p>
            </div>

            {{-- Advantage 3 --}}
            <div class="glass-card p-8 rounded-3xl border border-slate-800 hover:border-amber-500/50 transition-all group">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-white mb-2">Zero Hazard HSE</h3>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Rigorous Health, Safety, and Environment (HSE) protocols ensuring zero casualties and zero site hazards.
                </p>
            </div>

            {{-- Advantage 4 --}}
            <div class="glass-card p-8 rounded-3xl border border-slate-800 hover:border-emerald-500/50 transition-all group">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2h1.5a2.5 2.5 0 002.5-2.5V11a2 2 0 012-2h1.055M11 20.055V18a2 2 0 00-2-2h-1a2 2 0 01-2-2v-1a2 2 0 00-2-2H3.055"/></svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-white mb-2">Environmental Care</h3>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Eco-friendly execution methods and clean renewable solar power options reducing long-term carbon footprint.
                </p>
            </div>

            {{-- Advantage 5 --}}
            <div class="glass-card p-8 rounded-3xl border border-slate-800 hover:border-blue-500/50 transition-all group">
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a2 2 0 00-1.96.808l-.447.597a15.056 15.056 0 01-6.592-6.592l.597-.447a2 2 0 00.808-1.96l-.477-2.387a2 2 0 00-.547-1.022L7.027 3.512A2 2 0 005.61 3H4a2 2 0 00-2 2 16 16 0 0016 16v-1.61a2 2 0 00-.512-1.417l-.89-1.072z"/></svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-white mb-2">Elite Consultancy</h3>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Seasoned engineers and project managers offering expert technical advisory and end-to-end supervision.
                </p>
            </div>

            {{-- Advantage 6 --}}
            <div class="glass-card p-8 rounded-3xl border border-slate-800 hover:border-indigo-500/50 transition-all group">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h3 class="text-lg font-bold font-outfit text-white mb-2">Community Harmony</h3>
                <p class="text-slate-400 text-xs leading-relaxed">
                    Building long-term cooperation and mutual respect with host communities to ensure smooth project operations.
                </p>
            </div>

        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     PROJECTS CAROUSEL SHOWCASE
═══════════════════════════════════════════════════════ --}}
<section id="projects" class="py-20 bg-slate-50 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-vtu-primary/10 text-vtu-primary text-xs font-semibold uppercase tracking-wider mb-3">
                    Project Portfolio
                </div>
                <h2 class="text-3xl sm:text-4xl font-bold font-outfit text-slate-900 tracking-tight">
                    Featured Projects & Execution
                </h2>
                <p class="text-slate-500 text-sm mt-2">
                    Showcasing completed civil construction, solar energy power installations, and ICT telecom infrastructure.
                </p>
            </div>

            {{-- Carousel Nav Controls --}}
            <div class="flex items-center gap-3 mt-6 md:mt-0">
                <button id="project-prev" class="w-12 h-12 rounded-2xl bg-white border border-slate-200 text-slate-700 hover:bg-vtu-primary hover:text-white hover:border-vtu-primary flex items-center justify-center transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button id="project-next" class="w-12 h-12 rounded-2xl bg-white border border-slate-200 text-slate-700 hover:bg-vtu-primary hover:text-white hover:border-vtu-primary flex items-center justify-center transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        {{-- Carousel Container --}}
        <div class="relative overflow-hidden rounded-3xl">
            <div id="project-slider" class="flex transition-transform duration-500 ease-out">
                
                @php
                    $projects = [
                        [
                            'title' => 'Civil Highway & Drainage Engineering',
                            'category' => 'Civil Engineering',
                            'color' => 'amber',
                            'location' => 'Borno State, Nigeria',
                            'desc' => 'Procurement and full-scale EPC execution of major road drainage channels and highway infrastructure.',
                            'image' => asset('assets/images/projects/project1.jpg')
                        ],
                        [
                            'title' => 'Industrial Solar Mini-Grid Facility',
                            'category' => 'Solar Power',
                            'color' => 'emerald',
                            'location' => 'Maiduguri Commercial Hub',
                            'desc' => 'High-capacity renewable solar power battery bank installation providing 24/7 off-grid electricity.',
                            'image' => asset('assets/images/projects/project2.jpg')
                        ],
                        [
                            'title' => 'Automated Telecom VTU & API Gateway',
                            'category' => 'ICT & Telecoms',
                            'color' => 'blue',
                            'location' => 'Enterprise System',
                            'desc' => 'Deploying direct carrier integrations for MTN 8915, Glo ERS, Airtel, and 9mobile VTU disbursement.',
                            'image' => asset('assets/images/projects/project3.jpg')
                        ],
                        [
                            'title' => 'Commercial Building Construction',
                            'category' => 'Civil Engineering',
                            'color' => 'amber',
                            'location' => 'Northern Region',
                            'desc' => 'Structural engineering and architectural construction of high-capacity corporate office facilities.',
                            'image' => asset('assets/images/projects/project4.jpg')
                        ],
                        [
                            'title' => 'Rural Solar Energy Electrification',
                            'category' => 'Solar Power',
                            'color' => 'emerald',
                            'location' => 'Community Project',
                            'desc' => 'Deploying zero-hazard solar street lights and decentralized power kits for community centers.',
                            'image' => asset('assets/images/projects/project5.jpg')
                        ],
                        [
                            'title' => 'Enterprise Fiber Network Installation',
                            'category' => 'ICT & Telecoms',
                            'color' => 'blue',
                            'location' => 'Regional Office',
                            'desc' => 'Structured network cabling, fiber optic links, and enterprise IT infrastructure management.',
                            'image' => asset('assets/images/projects/project6.jpg')
                        ],
                        [
                            'title' => 'Highway Culvert & Bridge Construction',
                            'category' => 'Civil Engineering',
                            'color' => 'amber',
                            'location' => 'Transit Corridor',
                            'desc' => 'Reinforced concrete bridge and storm-water culvert construction under zero safety hazard standards.',
                            'image' => asset('assets/images/projects/project7.jpg')
                        ],
                        [
                            'title' => 'Hybrid Solar & Grid Energy Management',
                            'category' => 'Solar Power',
                            'color' => 'emerald',
                            'location' => 'Commercial Facility',
                            'desc' => 'Smart automated inverter switching units balancing solar energy generation with main grid power.',
                            'image' => asset('assets/images/projects/project8.jpg')
                        ],
                        [
                            'title' => 'Secure Corporate Data Center Setup',
                            'category' => 'ICT & Telecoms',
                            'color' => 'blue',
                            'location' => 'Head Office',
                            'desc' => 'Server rack mounting, firewall deployment, biometric access controls, and power backup units.',
                            'image' => asset('assets/images/projects/project9.jpg')
                        ],
                        [
                            'title' => 'Urban Road Rehabilitation & Paving',
                            'category' => 'Civil Engineering',
                            'color' => 'amber',
                            'location' => 'Metropolitan Zone',
                            'desc' => 'Asphaltic concrete road surfacing, lane marking, and pedestrian safety pathway construction.',
                            'image' => asset('assets/images/projects/project10.jpg')
                        ],
                    ];
                @endphp

                @foreach($projects as $index => $project)
                    <div class="w-full md:w-1/2 lg:w-1/3 flex-shrink-0 p-3">
                        <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 h-full flex flex-col justify-between group">
                            
                            {{-- Image Banner / Visual Block --}}
                            <div class="h-52 bg-slate-800 relative overflow-hidden flex items-center justify-center">
                                <img src="{{ $project['image'] }}" 
                                     alt="{{ $project['title'] }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                                
                                {{-- Fallback Gradient Card if image not loaded --}}
                                <div class="hidden absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-6 flex flex-col justify-between">
                                    <div class="flex items-center justify-between text-white/50 text-xs font-mono">
                                        <span>NMRNL PROJECT #{{ sprintf('%02d', $index + 1) }}</span>
                                        <svg class="w-6 h-6 text-vtu-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4"/></svg>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-vtu-accent block mb-1">{{ $project['category'] }}</span>
                                        <h4 class="text-base font-bold font-outfit text-white">{{ $project['title'] }}</h4>
                                    </div>
                                </div>

                                {{-- Category Pill --}}
                                <div class="absolute top-4 left-4">
                                    <span class="px-3 py-1 rounded-full bg-slate-900/80 backdrop-blur-md text-white text-[11px] font-semibold border border-white/10 shadow-sm">
                                        {{ $project['category'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Card Details --}}
                            <div class="p-6 flex-grow flex flex-col justify-between">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase text-slate-400 tracking-wider mb-1 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-vtu-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>{{ $project['location'] }}</span>
                                    </div>
                                    <h3 class="text-lg font-bold font-outfit text-slate-900 mb-3 group-hover:text-vtu-primary transition-colors">
                                        {{ $project['title'] }}
                                    </h3>
                                    <p class="text-slate-600 text-xs leading-relaxed">
                                        {{ $project['desc'] }}
                                    </p>
                                </div>

                                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-vtu-primary">
                                    <span>Verified Execution</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach

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
                    <img src="{{ asset('assets/images/directors/director1.png') }}" 
                         alt="Umar Ibrahim Idris" 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 from-slate-900 via-transparent to-transparent"></div>
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
                    <img src="{{ asset('assets/images/directors/director2.png') }}" 
                         alt="Abubakar Ibrahim Idris" 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 from-slate-900 via-transparent to-transparent"></div>
                </div>
                <div class="p-5">
                    <h4 class="text-lg font-bold font-outfit text-white">Abubakar Ibrahim Idris</h4>
                    <p class="text-xs font-semibold text-teal-400 uppercase tracking-wider mt-0.5">Managing Director</p>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded bg-white/10 text-[10px] text-slate-300">Nigerian</span>
                </div>
            </div>

            {{-- Director: Nur Mohammed Idris --}}
            <div class="glass-card rounded-2xl overflow-hidden group hover:border-vtu-primary transition-all">
                <div class="h-64 overflow-hidden bg-slate-800 relative">
                    <img src="{{ asset('assets/images/directors/director4.png') }}" 
                         alt="Nur Mohammed Idris" 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 from-slate-900 via-transparent to-transparent"></div>
                </div>
                <div class="p-5">
                    <h4 class="text-lg font-bold font-outfit text-white">Nur Mohammed Idris</h4>
                    <p class="text-xs font-semibold text-vtu-secondary uppercase tracking-wider mt-0.5">Director</p>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded bg-white/10 text-[10px] text-slate-300">Nigerian</span>
                </div>
            </div>

            {{-- Director: Mustapha Mohammed Idris --}}
            <div class="glass-card rounded-2xl overflow-hidden group hover:border-vtu-primary transition-all">
                <div class="h-64 overflow-hidden bg-slate-800 relative">
                    <img src="{{ asset('assets/images/directors/director3.png') }}" 
                         alt="Mustapha Mohammed Idris" 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 from-slate-900 via-transparent to-transparent"></div>
                </div>
                <div class="p-5">
                    <h4 class="text-lg font-bold font-outfit text-white">Mustapha Mohammed Idris</h4>
                    <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider mt-0.5">Director</p>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded bg-white/10 text-[10px] text-slate-300">Nigerian</span>
                </div>
            </div>

            {{-- Director: Idris Ahmed Idris  --}}
            <div class="glass-card rounded-2xl overflow-hidden group hover:border-vtu-primary transition-all">
                <div class="h-64 overflow-hidden bg-slate-800 relative">
                    <img src="{{ asset('assets/images/directors/director5.png') }}" 
                         alt="Idris Ahmed Idris " 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 from-slate-900 via-transparent to-transparent"></div>
                </div>
                <div class="p-5">
                    <h4 class="text-lg font-bold font-outfit text-white">Idris Ahmed Idris </h4>
                    <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider mt-0.5">Director</p>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded bg-white/10 text-[10px] text-slate-300">Nigerian</span>
                </div>
            </div>

            {{-- Director: Mustapha Mohammed --}}
            <div class="glass-card rounded-2xl overflow-hidden group hover:border-vtu-primary transition-all">
                <div class="h-64 overflow-hidden bg-slate-800 relative">
                    <img src="{{ asset('assets/images/directors/director6.png') }}" 
                         alt="Mustapha Mohammed" 
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 from-slate-900 via-transparent to-transparent"></div>
                </div>
                <div class="p-5">
                    <h4 class="text-lg font-bold font-outfit text-white">Mustapha Mohammed</h4>
                    <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider mt-0.5">Secretary</p>
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
                    <div class="font-bold text-sm text-white">Ibrahim Mohammed Idris</div>
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
                    <div class="font-bold text-sm text-white">Yakub Mohammed Idris</div>
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

{{-- Carousel Script --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slider = document.getElementById('project-slider');
        const prevBtn = document.getElementById('project-prev');
        const nextBtn = document.getElementById('project-next');
        if (!slider || !prevBtn || !nextBtn) return;

        let currentIndex = 0;

        function getVisibleCards() {
            if (window.innerWidth >= 1024) return 3;
            if (window.innerWidth >= 768) return 2;
            return 1;
        }

        function updateSlider() {
            const totalCards = 10;
            const visible = getVisibleCards();
            const maxIndex = Math.max(0, totalCards - visible);

            if (currentIndex > maxIndex) currentIndex = maxIndex;
            if (currentIndex < 0) currentIndex = 0;

            const percentage = (100 / visible) * currentIndex;
            slider.style.transform = `translateX(-${percentage}%)`;
        }

        nextBtn.addEventListener('click', function () {
            const totalCards = 10;
            const visible = getVisibleCards();
            const maxIndex = Math.max(0, totalCards - visible);

            if (currentIndex < maxIndex) {
                currentIndex++;
            } else {
                currentIndex = 0; // loop back
            }
            updateSlider();
        });

        prevBtn.addEventListener('click', function () {
            const totalCards = 10;
            const visible = getVisibleCards();
            const maxIndex = Math.max(0, totalCards - visible);

            if (currentIndex > 0) {
                currentIndex--;
            } else {
                currentIndex = maxIndex;
            }
            updateSlider();
        });

        window.addEventListener('resize', updateSlider);
    });
</script>

@endsection

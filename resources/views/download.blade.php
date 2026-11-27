@extends('layouts.app')

@section('title', 'Download Mobile App - ' . $siteName)

@section('styles')
<style>
    .download-hero-gradient {
        background: linear-gradient(135deg, rgba({{ $themeColorRgb }},0.08) 0%, rgba({{ $themeSecondaryRgb }},0.05) 100%);
    }
</style>
@endsection

@section('content')
@php
    $playUrl = !empty($appPlayStoreUrl) ? $appPlayStoreUrl : \App\Models\AppSetting::get('play_store_url', '');
    $iosUrl  = !empty($appStoreUrl) ? $appStoreUrl : \App\Models\AppSetting::get('app_store_url', '');
    $apkUrl  = !empty($appApkUrl) ? $appApkUrl : \App\Models\AppSetting::get('apk_download_url', '');
    $appVer  = \App\Models\AppSetting::get('app_version', '4.5.6');
@endphp

{{-- ═══════════════════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════════════════ --}}
<section class="download-hero-gradient pt-16 pb-20 lg:pt-24 lg:pb-28 border-b border-slate-100 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto space-y-6">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-full px-5 py-2 text-xs font-bold shadow-sm" style="color:{{ $themeColor }}">
                <span>📱 Official Mobile App</span>
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span class="text-slate-500 dark:text-slate-400 font-normal">Version {{ $appVer }}</span>
            </div>

            {{-- Heading --}}
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold font-outfit text-slate-900 dark:text-white tracking-tight leading-tight">
                Get <span style="color:{{ $themeColor }}">{{ $siteName }}</span> on your Phone
            </h1>

            {{-- Subtitle --}}
            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
                Enjoy instant VTU recharges, cheap SME & gifting data bundles, automated virtual wallet funding, and biometric quick login on Android and iOS devices.
            </p>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             DOWNLOAD CHANNELS (CARDS)
        ═══════════════════════════════════════════════════════ --}}
        <div class="mt-14 max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- 1. Google Play Card --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-3xl p-8 border border-slate-200/80 dark:border-slate-700/80 shadow-xl shadow-slate-200/50 dark:shadow-none flex flex-col justify-between hover:-translate-y-1 transition-all duration-300">
                <div class="space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-800/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-sm">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3.18 23.61c.28.15.6.18.92.08l10.4-5.99-2.32-2.33-9 8.24zM.54 1.55C.2 1.88 0 2.4 0 3.05v17.88c0 .65.2 1.18.55 1.51l.08.07 10.02-10.02v-.24L.62 1.49l-.08.06zM20.19 10.6l-2.85-1.65-2.54 2.54 2.54 2.55 2.87-1.65c.82-.47.82-1.32-.02-1.79zM3.18.39l9 8.24 2.32-2.32L4.1.31c-.36-.2-.7-.18-.92.08z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Android</span>
                        <h3 class="text-xl font-bold font-outfit text-slate-900 dark:text-white mt-1">Google Play Store</h3>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Official release via Google Play. Auto-updates enabled with automatic security scans.
                    </p>
                    <div class="pt-2 text-xs text-slate-400 dark:text-slate-500 space-y-1">
                        <div>Requires: Android 5.0+</div>
                        <div>Size: ~18 MB</div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    @if(!empty($playUrl))
                    <a href="{{ $playUrl }}" target="_blank" rel="noopener noreferrer"
                       class="w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-bold text-sm text-white bg-slate-900 hover:bg-black dark:bg-emerald-600 dark:hover:bg-emerald-500 shadow-md hover:shadow-lg transition-all duration-200">
                        <span>Get on Google Play</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    @else
                    <button type="button" disabled
                            class="w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-bold text-sm text-slate-400 bg-slate-100 dark:bg-slate-700/50 cursor-not-allowed">
                        <span>Available Soon</span>
                    </button>
                    @endif
                </div>
            </div>

            {{-- 2. Apple App Store Card --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-3xl p-8 border border-slate-200/80 dark:border-slate-700/80 shadow-xl shadow-slate-200/50 dark:shadow-none flex flex-col justify-between hover:-translate-y-1 transition-all duration-300">
                <div class="space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800/50 flex items-center justify-center text-slate-900 dark:text-white shadow-sm">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.62-.76 1.05-1.81.93-2.87-.9.04-2 .6-2.65 1.36-.57.65-1.07 1.72-.94 2.76 1.01.08 2.04-.49 2.66-1.25z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">iOS / Apple</span>
                        <h3 class="text-xl font-bold font-outfit text-slate-900 dark:text-white mt-1">Apple App Store</h3>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Optimized for iPhone and iPad devices with native biometric Face ID authentication.
                    </p>
                    <div class="pt-2 text-xs text-slate-400 dark:text-slate-500 space-y-1">
                        <div>Requires: iOS 13.0+</div>
                        <div>Size: ~25 MB</div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    @if(!empty($iosUrl))
                    <a href="{{ $iosUrl }}" target="_blank" rel="noopener noreferrer"
                       class="w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-bold text-sm text-white bg-slate-900 hover:bg-black dark:bg-indigo-600 dark:hover:bg-indigo-500 shadow-md hover:shadow-lg transition-all duration-200">
                        <span>Download on App Store</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    @else
                    <button type="button" disabled
                            class="w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-bold text-sm text-slate-400 bg-slate-100 dark:bg-slate-700/50 cursor-not-allowed">
                        <span>Available Soon</span>
                    </button>
                    @endif
                </div>
            </div>

            {{-- 3. Direct APK Card --}}
            <div class="bg-white dark:bg-slate-800/90 rounded-3xl p-8 border border-slate-200/80 dark:border-slate-700/80 shadow-xl shadow-slate-200/50 dark:shadow-none flex flex-col justify-between hover:-translate-y-1 transition-all duration-300">
                <div class="space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-800/50 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-sm">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Direct Installer</span>
                        <h3 class="text-xl font-bold font-outfit text-slate-900 dark:text-white mt-1">Direct APK Download</h3>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        Download and install the raw Android package file directly without signing in to Google Play.
                    </p>
                    <div class="pt-2 text-xs text-slate-400 dark:text-slate-500 space-y-1">
                        <div>Format: Standalone .APK</div>
                        <div>Instant Installation</div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    @if(!empty($apkUrl))
                    <a href="{{ $apkUrl }}" target="_blank" rel="noopener noreferrer"
                       class="w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-bold text-sm text-white shadow-md hover:shadow-lg transition-all duration-200"
                       style="background:{{ $themeColor }}">
                        <span>Download APK Directly</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                    @else
                    <button type="button" disabled
                            class="w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-bold text-sm text-slate-400 bg-slate-100 dark:bg-slate-700/50 cursor-not-allowed">
                        <span>Available on Request</span>
                    </button>
                    @endif
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     KEY APP FEATURES
═══════════════════════════════════════════════════════ --}}
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
            <h2 class="text-3xl font-extrabold font-outfit text-slate-900 dark:text-white">
                Why use the {{ $siteName }} Mobile App?
            </h2>
            <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400">
                Built specifically for speed, ultra-low mobile data consumption, and effortless transaction automation.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            {{-- Feature 1 --}}
            <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white" style="background:{{ $themeColor }}">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h4 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Instant Automated Vending</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Airtime, SME data bundles, and electricity tokens are processed in less than 5 seconds.
                </p>
            </div>

            {{-- Feature 2 --}}
            <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white bg-indigo-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11a7.96 7.96 0 00.75 3.385m12.42 6.615l-.018-.035A13.96 13.96 0 0016 11"/></svg>
                </div>
                <h4 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Biometric Quick Login</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Access your account quickly and securely with Fingerprint or Face ID + automatic session idle timeout protection.
                </p>
            </div>

            {{-- Feature 3 --}}
            <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white bg-emerald-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <h4 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Automated Wallet Funding</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Dedicated virtual bank account numbers credit your wallet automatically 24/7 without delays.
                </p>
            </div>

            {{-- Feature 4 --}}
            <div class="p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white bg-pink-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <h4 class="text-base font-bold font-outfit text-slate-900 dark:text-white">Push Alerts & Receipts</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                    Get real-time transaction notifications and download branded PDF receipts with one tap.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     HOW TO INSTALL DIRECT APK
═══════════════════════════════════════════════════════ --}}
<section class="py-16 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 sm:p-12 border border-slate-200/80 dark:border-slate-800 shadow-xl space-y-8">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Quick Guide</span>
                </div>
                <h3 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white">
                    How to Install the Android APK
                </h3>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Follow these 3 simple steps if you are installing the app directly from our APK download link:
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-2">
                    <span class="inline-flex w-7 h-7 rounded-lg bg-slate-900 text-white dark:bg-slate-700 items-center justify-center text-xs font-bold">1</span>
                    <h5 class="text-sm font-bold text-slate-900 dark:text-white">Download the File</h5>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Click the "Download APK Directly" button above and save the file to your device.</p>
                </div>
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-2">
                    <span class="inline-flex w-7 h-7 rounded-lg bg-slate-900 text-white dark:bg-slate-700 items-center justify-center text-xs font-bold">2</span>
                    <h5 class="text-sm font-bold text-slate-900 dark:text-white">Allow Unknown Apps</h5>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Open the downloaded file. When prompted by Android, enable "Install unknown apps" for your browser.</p>
                </div>
                <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-2">
                    <span class="inline-flex w-7 h-7 rounded-lg bg-slate-900 text-white dark:bg-slate-700 items-center justify-center text-xs font-bold">3</span>
                    <h5 class="text-sm font-bold text-slate-900 dark:text-white">Open & Sign In</h5>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Launch the app, sign in with your {{ $siteName }} account, and start transacting!</p>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

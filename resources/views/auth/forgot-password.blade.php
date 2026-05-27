@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 dark:from-vtu-dark dark:via-vtu-dark dark:to-indigo-950/20">

    {{-- Decorative background blobs --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden -z-10">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-indigo-500/5 dark:bg-indigo-500/10 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-cyan-500/5 dark:bg-cyan-500/10 blur-3xl"></div>
    </div>

    {{-- Logo --}}
    <a href="/" class="mb-8 inline-flex items-center space-x-2.5">
        <div class="h-11 w-11 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/20 overflow-hidden {{ $siteLogo1 ? '' : 'bg-gradient-to-tr from-vtu-primary to-vtu-secondary' }}">
            @if($siteLogo1)
            <img src="{{ Storage::url($siteLogo1) }}" class="h-full w-full object-contain" alt="{{ $siteName }}">
            @else
            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            @endif
        </div>
        <span class="text-2xl font-bold font-outfit tracking-tight bg-gradient-to-r from-vtu-primary to-vtu-secondary bg-clip-text text-transparent">{{ $siteName }}</span>
    </a>

    {{-- Card --}}
    <div class="w-full max-w-md bg-white dark:bg-vtu-darkCard rounded-3xl shadow-xl shadow-slate-200/60 dark:shadow-black/30 border border-slate-100 dark:border-slate-800 p-8 sm:p-10">

        {{-- Icon --}}
        <div class="mb-6 flex justify-center">
            <div class="h-16 w-16 rounded-2xl bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center">
                <svg class="h-8 w-8 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold font-outfit text-slate-900 dark:text-white">Forgot your password?</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                No worries. Enter your registered email and we'll send you a secure link to reset your password.
            </p>
        </div>

        {{-- Success status --}}
        @if (session('status'))
            <div class="mb-6 flex items-start gap-3 px-4 py-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20">
                <svg class="h-5 w-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Email Address</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        autocomplete="email" placeholder="you@example.com" required
                        class="w-full pl-11 pr-4 py-3.5 rounded-xl border text-sm transition-all duration-200 focus:outline-none focus:ring-2 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 {{ $errors->has('email') ? 'border-red-400 focus:ring-red-400/30 focus:border-red-400' : 'border-slate-200 dark:border-slate-700 focus:ring-vtu-primary/30 focus:border-vtu-primary' }}">
                </div>
                @error('email')
                    <p class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                        <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <button type="submit"
                class="w-full py-3.5 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-vtu-primary to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 shadow-lg shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30 hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-vtu-primary/50">
                Send Reset Link
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-vtu-primary dark:hover:text-vtu-primary transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Sign In
            </a>
        </div>
    </div>
</div>
@endsection

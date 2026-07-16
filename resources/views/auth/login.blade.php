@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="min-h-screen flex">

    {{-- ── Left Branding Panel ── --}}
    <div class="hidden lg:flex lg:w-[45%] xl:w-2/5 flex-col bg-gradient-to-br from-indigo-800 via-vtu-primary to-cyan-600 relative overflow-hidden p-12">

        {{-- Decorative blobs --}}
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-white/[0.03] blur-3xl pointer-events-none"></div>

        {{-- Logo --}}
        <div class="relative z-10">
            <a href="/" class="inline-flex items-center space-x-3">
                <div class="h-11 w-11 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg overflow-hidden">
                    @if($siteLogo1)
                    <img src="{{ Storage::url($siteLogo1) }}" class="h-full w-full object-contain" alt="{{ $siteName }}">
                    @else
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    @endif
                </div>
                <span class="text-2xl font-bold font-outfit text-white tracking-tight">{{ $siteName }}</span>
            </a>
        </div>

        {{-- Main copy --}}
        <div class="relative z-10 flex-1 flex flex-col justify-center mt-10">
            <h2 class="text-3xl xl:text-4xl font-bold font-outfit text-white leading-snug">
                Welcome back!<br>
                <span class="text-indigo-200">Ready to top up?</span>
            </h2>
            <p class="mt-4 text-indigo-100 text-sm xl:text-base leading-relaxed max-w-xs">
                Access your account and manage all your digital payments in one place.
            </p>

            <div class="mt-8 space-y-3">
                @foreach([
                    'Instant airtime & data across all networks',
                    'Up to 5% discount on every purchase',
                    'Real-time electricity & cable TV payments',
                    '99.9% uptime – always available for you',
                ] as $feature)
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0 h-6 w-6 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-indigo-100 text-sm">{{ $feature }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Stats bar --}}
        <div class="relative z-10 border-t border-white/20 pt-6">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-xl font-bold font-outfit text-white">₦240M+</div>
                    <div class="text-xs text-indigo-200">Processed Daily</div>
                </div>
                <div>
                    <div class="text-xl font-bold font-outfit text-white">50K+</div>
                    <div class="text-xs text-indigo-200">Active Users</div>
                </div>
                <div>
                    <div class="text-xl font-bold font-outfit text-white">99.9%</div>
                    <div class="text-xs text-indigo-200">Uptime</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Right Form Panel ── --}}
    <div class="flex-1 flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-16 xl:px-24 bg-white dark:bg-vtu-dark overflow-y-auto">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-8 flex items-center space-x-2">
            <div class="h-10 w-10 rounded-xl flex items-center justify-center shadow-md shadow-indigo-500/20 overflow-hidden {{ $siteLogo1 ? '' : 'bg-gradient-to-tr from-vtu-primary to-vtu-secondary' }}">
                @if($siteLogo1)
                <img src="{{ Storage::url($siteLogo1) }}" class="h-full w-full object-contain" alt="{{ $siteName }}">
                @else
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                @endif
            </div>
            <span class="text-xl font-bold font-outfit tracking-tight bg-gradient-to-r from-vtu-primary to-vtu-secondary bg-clip-text text-transparent">{{ $siteName }}</span>
        </div>

        <div class="w-full max-w-md mx-auto">

            {{-- Header --}}
            <div class="mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold font-outfit text-slate-900 dark:text-white">Sign in to your account</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    No account yet?
                    <a href="{{ route('register') }}" class="font-semibold text-vtu-primary hover:text-indigo-700 transition-colors">Create one free</a>
                </p>
            </div>

            {{-- Success status (after password reset) --}}
            @if (session('status'))
                <div class="mb-6 flex items-center gap-3 px-4 py-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20">
                    <svg class="h-5 w-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ session('status') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
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

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input type="password" id="password" name="password"
                            autocomplete="current-password" placeholder="Enter your password" required
                            class="w-full pl-11 pr-12 py-3.5 rounded-xl border text-sm transition-all duration-200 focus:outline-none focus:ring-2 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 {{ $errors->has('password') ? 'border-red-400 focus:ring-red-400/30 focus:border-red-400' : 'border-slate-200 dark:border-slate-700 focus:ring-vtu-primary/30 focus:border-vtu-primary' }}">
                        <button type="button" onclick="togglePassword('password','eye-login')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                            <svg id="eye-login" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                            <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Remember & Forgot --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" id="remember"
                            class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-vtu-primary accent-vtu-primary focus:ring-vtu-primary/30">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-vtu-primary hover:text-indigo-700 transition-colors">
                        Forgot password?
                    </a>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full mt-2 py-3.5 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-vtu-primary to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 shadow-lg shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30 hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-vtu-primary/50">
                    Sign In to {{ $siteName }}
                </button>
            </form>

            <p class="mt-8 text-center text-xs text-slate-400 dark:text-slate-500">
                By signing in you agree to our
                <a href="{{ route('terms-of-service') }}" class="underline underline-offset-2 hover:text-vtu-primary transition-colors">Terms of Service</a>
                and
                <a href="{{ route('privacy-policy') }}" class="underline underline-offset-2 hover:text-vtu-primary transition-colors">Privacy Policy</a>.
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePassword(inputId, iconId) {
        var input = document.getElementById(inputId);
        var icon  = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
        } else {
            input.type = 'password';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
        }
    }
</script>
@endsection

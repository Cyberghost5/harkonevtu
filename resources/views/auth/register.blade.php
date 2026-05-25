@extends('layouts.auth')

@section('title', 'Create Account – PayPulse')

@section('content')
<div class="min-h-screen flex">

    {{-- ── Left Branding Panel ── --}}
    <div class="hidden lg:flex lg:w-[45%] xl:w-2/5 flex-col bg-gradient-to-br from-indigo-800 via-vtu-primary to-cyan-600 relative overflow-hidden p-12">

        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-white/5 pointer-events-none"></div>

        {{-- Logo --}}
        <div class="relative z-10">
            <a href="/" class="inline-flex items-center space-x-3">
                <div class="h-11 w-11 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold font-outfit text-white tracking-tight">PayPulse</span>
            </a>
        </div>

        {{-- Main copy --}}
        <div class="relative z-10 flex-1 flex flex-col justify-center mt-10">
            <h2 class="text-3xl xl:text-4xl font-bold font-outfit text-white leading-snug">
                Join 50,000+<br>
                <span class="text-indigo-200">smart Nigerians.</span>
            </h2>
            <p class="mt-4 text-indigo-100 text-sm xl:text-base leading-relaxed max-w-xs">
                Create your free account and start saving on airtime, data, electricity bills and cable TV today.
            </p>

            <div class="mt-8 space-y-3">
                @foreach([
                    'Free account – no monthly fees ever',
                    'Instant top-ups in under 10 seconds',
                    'Wallet system with easy bank funding',
                    'Transaction history & spending reports',
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

            {{-- Trust badge --}}
            <div class="mt-8 flex items-center gap-3 bg-white/10 rounded-2xl p-4">
                <div class="h-10 w-10 rounded-xl bg-amber-400 flex items-center justify-center flex-shrink-0">
                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-white text-sm font-semibold">Trusted & Secure</div>
                    <div class="text-indigo-200 text-xs">Bank-grade encryption on all transactions</div>
                </div>
            </div>
        </div>

        {{-- Stats --}}
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
                    <div class="text-xl font-bold font-outfit text-white">5%</div>
                    <div class="text-xs text-indigo-200">Airtime Discount</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Right Form Panel ── --}}
    <div class="flex-1 flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-16 xl:px-24 bg-white dark:bg-vtu-dark overflow-y-auto">

        {{-- Mobile logo --}}
        <div class="lg:hidden mb-6 flex items-center space-x-2">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-vtu-primary to-vtu-secondary flex items-center justify-center shadow-md shadow-indigo-500/20">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <span class="text-xl font-bold font-outfit tracking-tight bg-gradient-to-r from-vtu-primary to-vtu-secondary bg-clip-text text-transparent">PayPulse</span>
        </div>

        <div class="w-full max-w-md mx-auto">

            <div class="mb-7">
                <h1 class="text-2xl sm:text-3xl font-bold font-outfit text-slate-900 dark:text-white">Create your free account</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-vtu-primary hover:text-indigo-700 transition-colors">Sign in</a>
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4" novalidate>
                @csrf

                {{-- Full Name --}}
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Full Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                            autocomplete="name" placeholder="John Doe" required
                            class="w-full pl-11 pr-4 py-3.5 rounded-xl border text-sm transition-all duration-200 focus:outline-none focus:ring-2 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 {{ $errors->has('name') ? 'border-red-400 focus:ring-red-400/30 focus:border-red-400' : 'border-slate-200 dark:border-slate-700 focus:ring-vtu-primary/30 focus:border-vtu-primary' }}">
                    </div>
                    @error('name')
                        <p class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                            <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

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

                {{-- Phone --}}
                <div>
                    <label for="phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Phone Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                            autocomplete="tel" placeholder="08012345678" required
                            class="w-full pl-11 pr-4 py-3.5 rounded-xl border text-sm transition-all duration-200 focus:outline-none focus:ring-2 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 {{ $errors->has('phone') ? 'border-red-400 focus:ring-red-400/30 focus:border-red-400' : 'border-slate-200 dark:border-slate-700 focus:ring-vtu-primary/30 focus:border-vtu-primary' }}">
                    </div>
                    @error('phone')
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
                            autocomplete="new-password" placeholder="Min. 8 characters" required
                            oninput="checkStrength(this.value)"
                            class="w-full pl-11 pr-12 py-3.5 rounded-xl border text-sm transition-all duration-200 focus:outline-none focus:ring-2 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 {{ $errors->has('password') ? 'border-red-400 focus:ring-red-400/30 focus:border-red-400' : 'border-slate-200 dark:border-slate-700 focus:ring-vtu-primary/30 focus:border-vtu-primary' }}">
                        <button type="button" onclick="togglePassword('password','eye-pass')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                            <svg id="eye-pass" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    {{-- Strength bar --}}
                    <div class="mt-2 flex gap-1.5" id="strength-bars">
                        <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700 transition-all duration-300" id="bar1"></div>
                        <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700 transition-all duration-300" id="bar2"></div>
                        <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700 transition-all duration-300" id="bar3"></div>
                        <div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700 transition-all duration-300" id="bar4"></div>
                    </div>
                    <p id="strength-text" class="mt-1 text-xs text-slate-400 dark:text-slate-500"></p>
                    @error('password')
                        <p class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                            <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            autocomplete="new-password" placeholder="Re-enter your password" required
                            class="w-full pl-11 pr-12 py-3.5 rounded-xl border text-sm transition-all duration-200 focus:outline-none focus:ring-2 bg-slate-50 dark:bg-slate-800/50 text-slate-900 dark:text-white placeholder-slate-400 border-slate-200 dark:border-slate-700 focus:ring-vtu-primary/30 focus:border-vtu-primary">
                        <button type="button" onclick="togglePassword('password_confirmation','eye-confirm')" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                            <svg id="eye-confirm" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Terms --}}
                <div>
                    <label class="flex items-start gap-3 cursor-pointer select-none group">
                        <input type="checkbox" name="terms" id="terms" value="1"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-vtu-primary accent-vtu-primary focus:ring-vtu-primary/30 flex-shrink-0">
                        <span class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            I agree to PayPulse's
                            <a href="#" class="font-semibold text-vtu-primary hover:text-indigo-700 transition-colors">Terms of Service</a>
                            and
                            <a href="#" class="font-semibold text-vtu-primary hover:text-indigo-700 transition-colors">Privacy Policy</a>
                        </span>
                    </label>
                    @error('terms')
                        <p class="mt-1.5 flex items-center gap-1 text-xs text-red-500">
                            <svg class="h-3.5 w-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full py-3.5 px-6 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-vtu-primary to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 shadow-lg shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30 hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-vtu-primary/50">
                    Create My Free Account
                </button>
            </form>
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

    function checkStrength(val) {
        var bars  = ['bar1','bar2','bar3','bar4'];
        var text  = document.getElementById('strength-text');
        var score = 0;

        if (val.length >= 8)  score++;
        if (val.length >= 12) score++;
        if (/[0-9]/.test(val) && /[a-zA-Z]/.test(val)) score++;
        if (/[^a-zA-Z0-9]/.test(val)) score++;

        var colors = ['bg-red-400','bg-orange-400','bg-yellow-400','bg-emerald-500'];
        var labels = ['','Weak','Fair','Good','Strong'];

        bars.forEach(function(id, i) {
            var el = document.getElementById(id);
            el.className = 'h-1 flex-1 rounded-full transition-all duration-300 ' +
                (i < score ? colors[score - 1] : 'bg-slate-200 dark:bg-slate-700');
        });

        text.textContent = val.length ? labels[score] : '';
        text.className = 'mt-1 text-xs transition-colors ' +
            (['','text-red-500','text-orange-500','text-yellow-500','text-emerald-500'][score] || 'text-slate-400');
    }
</script>
@endsection

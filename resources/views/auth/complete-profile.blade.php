@extends('layouts.auth')

@section('title', 'Complete Your Profile')

@section('content')
<div class="min-h-screen flex flex-col justify-center items-center px-4 py-10 sm:px-6 lg:px-8">
    <div class="w-full max-w-lg">

        {{-- Top Branding / Logo --}}
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center mb-3">
                <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-indigo-500 via-vtu-primary to-cyan-500 flex items-center justify-center shadow-lg shadow-indigo-500/25 text-white">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 mb-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Final Step • Account Setup
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold font-outfit text-slate-900 dark:text-white">
                Complete Your Profile
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                Welcome, <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $user->name }}</span>! Set your phone number &amp; transaction PIN to start using your account.
            </p>
        </div>

        {{-- Connected Google Account Chip --}}
        <div class="mb-5 p-3.5 rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700/80 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($user->avatar)
                <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                @else
                <div class="h-10 w-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-sm text-slate-700 dark:text-slate-300">
                    {{ $user->initials() }}
                </div>
                @endif
                <div>
                    <div class="text-xs font-medium text-slate-400 dark:text-slate-400">Connected Google Account</div>
                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate max-w-[210px] sm:max-w-xs">
                        {{ $user->email }}
                    </div>
                </div>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Verified
            </span>
        </div>

        {{-- Flash / Error Messages --}}
        @if(session('info'))
        <div class="mb-5 rounded-2xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 p-3.5 flex items-start gap-3">
            <svg class="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed">{{ session('info') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-5 rounded-2xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 p-4">
            <div class="flex items-center gap-2 text-sm font-semibold text-red-700 dark:text-red-400 mb-1">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Please correct the following:</span>
            </div>
            <ul class="list-disc list-inside text-xs text-red-600 dark:text-red-400 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Form Card --}}
        <div class="rounded-3xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700/80 shadow-xl shadow-slate-200/50 dark:shadow-none p-6 sm:p-8">
            <form method="POST" action="{{ route('profile.complete.store') }}" id="complete-profile-form" class="space-y-6">
                @csrf

                {{-- Phone Number Input --}}
                @if(empty($user->phone))
                <div>
                    <label for="phone" class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                        Nigerian Phone Number <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700">
                                🇳🇬 +234
                            </span>
                        </div>
                        <input type="tel"
                               name="phone"
                               id="phone"
                               value="{{ old('phone') }}"
                               placeholder="08012345678"
                               maxlength="11"
                               inputmode="numeric"
                               required
                               class="w-full pl-24 pr-4 py-3 rounded-xl border @error('phone') border-red-400 focus:border-red-500 @else border-slate-300 dark:border-slate-600 focus:border-vtu-primary @enderror bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:outline-none focus:ring-4 focus:ring-vtu-primary/10 transition-all font-mono tracking-wider">
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Used for transaction receipts, airtime top-ups, and account notifications.
                    </p>
                </div>
                @else
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                    <div class="text-xs text-slate-400">Phone Number</div>
                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 font-mono">{{ $user->phone }}</div>
                </div>
                @endif

                {{-- Transaction PIN Setup --}}
                @if(!$user->hasPinSet())
                <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60">
                    {{-- Choose PIN --}}
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                Choose 4-Digit Transaction PIN <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] text-slate-400">4 numbers</span>
                        </div>
                        <div class="flex justify-center gap-3 sm:gap-4" id="pin-inputs">
                            @for($i = 0; $i < 4; $i++)
                            <input type="password"
                                   inputmode="numeric"
                                   maxlength="1"
                                   pattern="[0-9]*"
                                   autocomplete="off"
                                   class="pin-digit w-12 h-14 sm:w-14 sm:h-14 text-center text-2xl font-bold rounded-2xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-vtu-primary focus:ring-4 focus:ring-vtu-primary/10 transition-all"
                                   data-group="pin"/>
                            @endfor
                        </div>
                        <input type="hidden" name="pin" id="pin-hidden"/>
                    </div>

                    {{-- Confirm PIN --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200">
                                Confirm 4-Digit Transaction PIN <span class="text-red-500">*</span>
                            </label>
                            <span id="pin-match-msg" class="text-xs font-semibold hidden"></span>
                        </div>
                        <div class="flex justify-center gap-3 sm:gap-4" id="confirm-inputs">
                            @for($i = 0; $i < 4; $i++)
                            <input type="password"
                                   inputmode="numeric"
                                   maxlength="1"
                                   pattern="[0-9]*"
                                   autocomplete="off"
                                   class="pin-confirm w-12 h-14 sm:w-14 sm:h-14 text-center text-2xl font-bold rounded-2xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-vtu-primary focus:ring-4 focus:ring-vtu-primary/10 transition-all"
                                   data-group="confirm"/>
                            @endfor
                        </div>
                        <input type="hidden" name="pin_confirmation" id="confirm-hidden"/>
                    </div>
                </div>
                @endif

                {{-- Referral Code Section --}}
                <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60">
                    @if(!empty($user->referred_by))
                    <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50/60 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 text-xs">
                        <span class="text-slate-600 dark:text-slate-400">Referral Code Applied:</span>
                        <span class="font-mono font-bold text-vtu-primary dark:text-indigo-400">{{ $user->referred_by }}</span>
                    </div>
                    @else
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="referral_code" class="block text-xs font-semibold text-slate-600 dark:text-slate-300">
                                Referral Code <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <span class="text-[11px] text-slate-400">Did someone invite you?</span>
                        </div>
                        <input type="text"
                               name="referral_code"
                               id="referral_code"
                               value="{{ old('referral_code', $refCode) }}"
                               placeholder="e.g. AB12CD34"
                               maxlength="10"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 text-xs font-mono uppercase focus:outline-none focus:border-vtu-primary focus:ring-4 focus:ring-vtu-primary/10 transition-all">
                    </div>
                    @endif
                </div>

                {{-- Security Note Box --}}
                <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 p-3.5 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2.5">
                    <svg class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Your PIN is encrypted with one-way hashing. You will need it to authorize all transactions and transfers.</span>
                </div>

                {{-- Submit Button --}}
                <button type="submit"
                        id="submit-btn"
                        class="w-full py-3.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-vtu-primary to-vtu-secondary text-white transition-all shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:scale-[1.01] active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed disabled:scale-100 flex items-center justify-center gap-2">
                    <span>Complete Profile &amp; Continue</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>
        </div>

        {{-- Switch Account / Log Out --}}
        <div class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
            <span>Signed into the wrong account?</span>
            <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                @csrf
                <button type="submit" class="font-semibold text-vtu-primary hover:underline">
                    Sign out and switch account
                </button>
            </form>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    const phoneInput    = document.getElementById('phone');
    const pinDigits     = document.querySelectorAll('[data-group="pin"]');
    const confirmDigits = document.querySelectorAll('[data-group="confirm"]');
    const pinHidden     = document.getElementById('pin-hidden');
    const confirmHidden = document.getElementById('confirm-hidden');
    const submitBtn     = document.getElementById('submit-btn');
    const matchMsg      = document.getElementById('pin-match-msg');
    const hasPinSet     = {{ $user->hasPinSet() ? 'true' : 'false' }};
    const hasPhone      = {{ !empty($user->phone) ? 'true' : 'false' }};

    // Filter non-numeric characters on phone input
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
            checkValidity();
        });
    }

    function initGroup(inputs, hiddenInput) {
        inputs.forEach((input, idx) => {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
                if (this.value && idx < inputs.length - 1) {
                    inputs[idx + 1].focus();
                }
                sync(inputs, hiddenInput);
                checkMatch();
                checkValidity();
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) {
                    inputs[idx - 1].focus();
                }
            });

            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 4);
                paste.split('').forEach((ch, i) => { if (inputs[i]) inputs[i].value = ch; });
                const last = Math.min(paste.length, inputs.length - 1);
                if (inputs[last]) inputs[last].focus();
                sync(inputs, hiddenInput);
                checkMatch();
                checkValidity();
            });
        });
    }

    function sync(inputs, hidden) {
        if (hidden) {
            hidden.value = Array.from(inputs).map(d => d.value).join('');
        }
    }

    function checkMatch() {
        if (!pinHidden || !confirmHidden || !matchMsg) return;

        const pin     = pinHidden.value;
        const confirm = confirmHidden.value;

        if (pin.length < 4 || confirm.length < 4) {
            matchMsg.classList.add('hidden');
            return;
        }

        matchMsg.classList.remove('hidden');
        if (pin === confirm) {
            matchMsg.textContent = '✓ PINs match';
            matchMsg.className = 'text-xs font-semibold text-emerald-600 dark:text-emerald-400';
            confirmDigits.forEach(d => {
                d.classList.remove('border-red-400', 'border-slate-300', 'dark:border-slate-600');
                d.classList.add('border-emerald-500');
            });
        } else {
            matchMsg.textContent = '✗ PINs do not match';
            matchMsg.className = 'text-xs font-semibold text-red-500';
            confirmDigits.forEach(d => {
                d.classList.remove('border-emerald-500', 'border-slate-300', 'dark:border-slate-600');
                d.classList.add('border-red-400');
            });
        }
    }

    function checkValidity() {
        let valid = true;

        if (!hasPhone && phoneInput) {
            const phoneVal = phoneInput.value.trim();
            const phoneRegex = /^0[789]\d{9}$/;
            if (!phoneRegex.test(phoneVal)) {
                valid = false;
            }
        }

        if (!hasPinSet && pinHidden && confirmHidden) {
            const p = pinHidden.value;
            const c = confirmHidden.value;
            if (p.length !== 4 || c.length !== 4 || p !== c) {
                valid = false;
            }
        }

        if (submitBtn) {
            submitBtn.disabled = !valid;
        }
    }

    if (pinDigits.length > 0 && pinHidden) {
        initGroup(pinDigits, pinHidden);
    }
    if (confirmDigits.length > 0 && confirmHidden) {
        initGroup(confirmDigits, confirmHidden);
    }

    // Auto-focus first input on load
    if (phoneInput && !hasPhone) {
        phoneInput.focus();
    } else if (pinDigits.length > 0 && !hasPinSet) {
        pinDigits[0].focus();
    }

    checkValidity();
</script>
@endsection

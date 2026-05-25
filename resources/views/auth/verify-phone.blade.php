@extends('layouts.auth')

@section('title', 'Verify Phone – PayPulse')

@section('content')
<div class="w-full max-w-md mx-auto">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-indigo-100 dark:bg-indigo-500/10 mb-4">
            <svg class="h-8 w-8 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                      d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Verify your phone</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            We sent a 6-digit code to<br>
            <strong class="text-slate-700 dark:text-slate-300">{{ $phone }}</strong>
        </p>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="mb-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 px-4 py-3 text-sm text-red-700 dark:text-red-400">
        {{ session('error') }}
    </div>
    @endif

    <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm p-6 space-y-5">

        <form method="POST" action="{{ route('verification.phone.confirm') }}" class="space-y-5">
            @csrf

            {{-- OTP input --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3 text-center">
                    Enter 6-digit OTP
                </label>
                <div class="flex justify-center gap-2" id="otp-grid">
                    @for($i = 0; $i < 6; $i++)
                    <input type="tel"
                           inputmode="numeric"
                           maxlength="1"
                           pattern="[0-9]*"
                           class="otp-digit w-11 h-12 text-center text-xl font-bold rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-vtu-primary transition-colors"
                           autocomplete="one-time-code"/>
                    @endfor
                </div>
                <input type="hidden" name="otp" id="otp-hidden"/>
                @error('otp')
                <p class="mt-2 text-xs text-red-500 text-center">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-xl text-sm font-semibold bg-vtu-primary hover:bg-indigo-600 text-white transition-colors shadow-md shadow-indigo-500/20">
                Verify Phone Number
            </button>
        </form>

        <div class="text-center text-sm text-slate-500 dark:text-slate-400">
            Didn't receive the code?
            <form method="POST" action="{{ route('verification.phone.send') }}" class="inline">
                @csrf
                <button type="submit" class="font-semibold text-vtu-primary hover:text-indigo-700 transition-colors ml-1">
                    Resend OTP
                </button>
            </form>
        </div>

        <div class="text-center">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
                    Sign out
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-advance OTP inputs
    const digits = document.querySelectorAll('.otp-digit');
    const hidden = document.getElementById('otp-hidden');

    digits.forEach((input, idx) => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/\D/, '');
            if (this.value && idx < digits.length - 1) {
                digits[idx + 1].focus();
            }
            syncHidden();
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                digits[idx - 1].focus();
            }
        });

        // Handle paste
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            paste.split('').forEach((ch, i) => {
                if (digits[i]) digits[i].value = ch;
            });
            if (digits[paste.length - 1]) digits[paste.length - 1].focus();
            syncHidden();
        });
    });

    function syncHidden() {
        hidden.value = Array.from(digits).map(d => d.value).join('');
    }

    // Focus first input
    digits[0]?.focus();
</script>
@endsection

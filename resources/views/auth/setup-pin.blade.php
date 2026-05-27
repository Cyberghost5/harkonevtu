@extends('layouts.auth')

@section('title', 'Set Up PIN')

@section('content')
<div class="w-full max-w-md mx-auto">
    <div class="text-center mb-8">
        <div class="relative inline-flex items-center justify-center mb-4">
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center shadow-lg shadow-indigo-500/25">
                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
        </div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Create your Transaction PIN</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Set a 4-digit PIN to secure transactions on your account.
        </p>
    </div>

    {{-- Errors --}}
    @if($errors->any())
    <div class="mb-5 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 px-4 py-3 text-sm text-red-700 dark:text-red-400">
        {{ $errors->first() }}
    </div>
    @endif

    <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm p-6">
        <form method="POST" action="{{ route('pin.store') }}" class="space-y-7" id="pin-form">
            @csrf

            {{-- Create PIN --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3 text-center">
                    Choose a 4-digit PIN
                </label>
                <div class="flex justify-center gap-3" id="pin-inputs">
                    @for($i = 0; $i < 4; $i++)
                    <div class="relative">
                        <input type="password"
                               inputmode="numeric"
                               maxlength="1"
                               pattern="[0-9]*"
                               class="pin-digit w-14 h-14 text-center text-2xl font-bold rounded-2xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-vtu-primary focus:ring-4 focus:ring-vtu-primary/10 transition-all"
                               data-group="pin"/>
                    </div>
                    @endfor
                </div>
                <input type="hidden" name="pin" id="pin-hidden"/>
            </div>

            {{-- Confirm PIN --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3 text-center">
                    Confirm your PIN
                </label>
                <div class="flex justify-center gap-3" id="confirm-inputs">
                    @for($i = 0; $i < 4; $i++)
                    <input type="password"
                           inputmode="numeric"
                           maxlength="1"
                           pattern="[0-9]*"
                           class="pin-confirm w-14 h-14 text-center text-2xl font-bold rounded-2xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-vtu-primary focus:ring-4 focus:ring-vtu-primary/10 transition-all"
                           data-group="confirm"/>
                    @endfor
                </div>
                <input type="hidden" name="pin_confirmation" id="confirm-hidden"/>
            </div>

            {{-- Match indicator --}}
            <div id="pin-match-msg" class="hidden text-xs text-center font-medium"></div>

            <button type="submit"
                    id="submit-btn"
                    class="w-full py-3.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-vtu-primary to-vtu-secondary text-white transition-all shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:scale-[1.01] disabled:opacity-50 disabled:cursor-not-allowed disabled:scale-100"
                    disabled>
                Create PIN &amp; Continue
            </button>
        </form>

        <div class="mt-5 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 p-3.5">
            <div class="flex gap-2.5 text-xs text-amber-700 dark:text-amber-400">
                <svg class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p>Keep your PIN private. You'll need it to approve every transaction. Do not share it with anyone.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const pinDigits    = document.querySelectorAll('[data-group="pin"]');
    const confirmDigits = document.querySelectorAll('[data-group="confirm"]');
    const pinHidden    = document.getElementById('pin-hidden');
    const confirmHidden = document.getElementById('confirm-hidden');
    const submitBtn    = document.getElementById('submit-btn');
    const matchMsg     = document.getElementById('pin-match-msg');

    function initGroup(inputs, hiddenInput) {
        inputs.forEach((input, idx) => {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/\D/, '');
                if (this.value && idx < inputs.length - 1) {
                    inputs[idx + 1].focus();
                }
                sync(inputs, hiddenInput);
                checkMatch();
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
                inputs[last].focus();
                sync(inputs, hiddenInput);
                checkMatch();
            });
        });
    }

    function sync(inputs, hidden) {
        hidden.value = Array.from(inputs).map(d => d.value).join('');
    }

    function checkMatch() {
        const pin     = pinHidden.value;
        const confirm = confirmHidden.value;

        if (pin.length < 4 || confirm.length < 4) {
            matchMsg.className = 'hidden text-xs text-center font-medium';
            submitBtn.disabled = true;
            return;
        }

        if (pin === confirm) {
            matchMsg.textContent = '✓ PINs match';
            matchMsg.className = 'text-xs text-center font-medium text-emerald-600 dark:text-emerald-400';
            submitBtn.disabled = false;

            // Highlight confirm inputs green
            confirmDigits.forEach(d => {
                d.classList.remove('border-red-400', 'border-slate-300', 'dark:border-slate-600');
                d.classList.add('border-emerald-400');
            });
        } else {
            matchMsg.textContent = '✗ PINs do not match';
            matchMsg.className = 'text-xs text-center font-medium text-red-500';
            submitBtn.disabled = true;

            confirmDigits.forEach(d => {
                d.classList.remove('border-emerald-400', 'border-slate-300', 'dark:border-slate-600');
                d.classList.add('border-red-400');
            });
        }
    }

    initGroup(pinDigits, pinHidden);
    initGroup(confirmDigits, confirmHidden);

    pinDigits[0]?.focus();
</script>
@endsection

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Transaction PIN</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 flex items-center justify-center p-4">
<div class="w-full max-w-md">

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        {{-- Header --}}
        <div class="px-8 pt-8 pb-6 text-center border-b border-slate-100">
            <div class="h-14 w-14 rounded-2xl mx-auto mb-4 flex items-center justify-center"
                 style="background: linear-gradient(135deg, #22c55e, #16a34a)">
                <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-slate-900">Set New Transaction PIN</h1>
            <p class="mt-1 text-sm text-slate-500">Enter and confirm your new 4-digit PIN.</p>
        </div>

        {{-- Form --}}
        <div class="px-8 py-7">
            @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
                <svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <ul class="space-y-0.5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif

            <form method="POST" action="{{ route('settings.pin.reset.submit', ['token' => $token]) }}" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">New PIN</label>
                    <input type="password" name="new_pin" maxlength="4" inputmode="numeric" autofocus required
                           class="w-full px-4 py-3 text-center text-xl font-bold tracking-[0.8em] border border-slate-200 rounded-xl bg-slate-50 text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-400/30 focus:border-green-400 transition-colors placeholder:tracking-normal placeholder:text-base placeholder:font-normal"
                           placeholder="••••">
                    @error('new_pin')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm New PIN</label>
                    <input type="password" name="confirm_pin" maxlength="4" inputmode="numeric" required
                           class="w-full px-4 py-3 text-center text-xl font-bold tracking-[0.8em] border border-slate-200 rounded-xl bg-slate-50 text-slate-900 focus:outline-none focus:ring-2 focus:ring-green-400/30 focus:border-green-400 transition-colors placeholder:tracking-normal placeholder:text-base placeholder:font-normal"
                           placeholder="••••">
                    @error('confirm_pin')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                        class="w-full py-3 text-sm font-bold text-white rounded-xl bg-green-500 hover:bg-green-600 transition-colors shadow-md shadow-green-500/25">
                    Save New PIN
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-xs text-slate-400 mt-4">
        <a href="{{ route('login') }}" class="hover:text-slate-600 transition-colors">← Back to Login</a>
    </p>
</div>
</body>
</html>

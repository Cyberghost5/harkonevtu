@extends('layouts.admin')

@section('title', 'Email Settings')
@section('heading', 'Email Settings')
@section('subheading', 'Edit everything email pertaining to ' . \App\Models\AppSetting::get('site_name', 'PayPulse'))

@section('content')

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

    {{-- Card header --}}
    <div class="px-6 pt-5 pb-4 border-b border-slate-100">
        <h3 class="text-base font-bold text-slate-800">{{ \App\Models\AppSetting::get('site_name', 'PayPulse') }} Email Settings</h3>
        <p class="text-xs text-slate-400 mt-0.5">{{ \App\Models\AppSetting::get('site_name', 'PayPulse') }} Email Settings</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.email.update') }}">
        @csrf
        <div class="p-6 space-y-4">

            {{-- Host --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Host</label>
                <input type="text" name="mail_host" value="{{ $s['mail_host'] ?? '' }}"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
            </div>

            {{-- Username --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Username</label>
                <input type="text" name="mail_username" value="{{ $s['mail_username'] ?? '' }}"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
            </div>

            {{-- Password --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" id="mail-password" name="mail_password" value="{{ $s['mail_password'] ?? '' }}"
                           class="w-full px-3 py-2.5 pr-10 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg id="eye-icon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Port No. --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Port No.</label>
                <input type="text" name="mail_port" value="{{ $s['mail_port'] ?? '465' }}"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
            </div>

            {{-- Set From --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Set From</label>
                <input type="email" name="mail_from_address" value="{{ $s['mail_from_address'] ?? '' }}"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
            </div>

            {{-- Set Reply To --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Set Reply To</label>
                <input type="email" name="mail_reply_to" value="{{ $s['mail_reply_to'] ?? '' }}"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
            </div>

            {{-- Buttons --}}
            <div class="pt-2 flex items-center gap-3 flex-wrap">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90"
                        style="background:#4CAF50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Save
                </button>
            </div>

        </div>
    </form>

    {{-- Send Test Email (separate action) --}}
    <div class="px-6 pb-6 -mt-2 flex justify-end">
        <form method="POST" action="{{ route('admin.settings.email.test') }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90"
                    style="background:#e6a817">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Send Test Email
            </button>
        </form>
    </div>

</div>

@endsection

@section('scripts')
<script>
function togglePassword() {
    var input = document.getElementById('mail-password');
    var icon  = document.getElementById('eye-icon');
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

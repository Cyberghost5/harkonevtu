@extends('layouts.admin')

@section('title', 'General Settings')
@section('heading', 'General Settings')
@section('subheading', 'Edit everything pertaining to ' . \App\Models\AppSetting::get('site_name', 'PayPulse'))

@section('content')

<form method="POST" action="{{ route('admin.settings.general.update') }}" enctype="multipart/form-data">
    @csrf

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

        {{-- Card header --}}
        <div class="px-6 pt-5 pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-800">{{ \App\Models\AppSetting::get('site_name', 'PayPulse') }} General Settings</h3>
            <p class="text-xs text-slate-400 mt-0.5">{{ \App\Models\AppSetting::get('site_name', 'PayPulse') }} General Settings</p>
        </div>

        <div class="p-6 space-y-5">

            {{-- Site Name + Site URL --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Site Name</label>
                    <input type="text" name="site_name" value="{{ $s['site_name'] ?? '' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Site URL</label>
                    <input type="text" name="site_url" value="{{ $s['site_url'] ?? '' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
                </div>
            </div>

            {{-- Site Description --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Site Description</label>
                <textarea name="site_description" rows="3"
                          class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors resize-none">{{ $s['site_description'] ?? '' }}</textarea>
            </div>

            {{-- Site Keywords --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Site Keywords</label>
                <textarea name="site_keywords" rows="3"
                          class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors resize-none">{{ $s['site_keywords'] ?? '' }}</textarea>
            </div>

            {{-- Location + Copyright --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Location</label>
                    <input type="text" name="location" value="{{ $s['location'] ?? '' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Copyright</label>
                    <input type="text" name="copyright" value="{{ $s['copyright'] ?? '' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
                </div>
            </div>

            {{-- Admin Email + Favicon --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Admin Email</label>
                    <input type="email" name="admin_email" value="{{ $s['admin_email'] ?? '' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Favicon</label>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden flex-shrink-0" id="favicon-wrap">
                            @if(!empty($s['favicon']))
                            <img src="{{ Storage::url($s['favicon']) }}" class="w-full h-full object-contain" alt="favicon">
                            @else
                            <svg class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @endif
                        </div>
                        <label for="favicon-input" class="cursor-pointer w-7 h-7 rounded-full border border-slate-200 bg-white flex items-center justify-center hover:bg-slate-50 transition-colors shadow-sm" title="Upload favicon">
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </label>
                        <input type="file" id="favicon-input" name="favicon" accept="image/*" class="hidden" onchange="previewFile(this,'favicon-wrap')">
                    </div>
                </div>
            </div>

            {{-- Logo1 + Logo2 --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Logo1</label>
                    <div class="flex items-center gap-3">
                        <div class="h-10 min-w-[40px] max-w-[140px] px-2 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden" id="logo1-wrap">
                            @if(!empty($s['logo1']))
                            <img src="{{ Storage::url($s['logo1']) }}" class="h-full max-w-full object-contain" alt="logo1">
                            @else
                            <svg class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @endif
                        </div>
                        <label for="logo1-input" class="cursor-pointer w-7 h-7 rounded-full border border-slate-200 bg-white flex items-center justify-center hover:bg-slate-50 transition-colors shadow-sm" title="Upload logo1">
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </label>
                        <input type="file" id="logo1-input" name="logo1" accept="image/*" class="hidden" onchange="previewFile(this,'logo1-wrap')">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Logo2</label>
                    <div class="flex items-center gap-3">
                        <div class="h-10 min-w-[40px] max-w-[180px] px-2 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden" id="logo2-wrap">
                            @if(!empty($s['logo2']))
                            <img src="{{ Storage::url($s['logo2']) }}" class="h-full max-w-full object-contain" alt="logo2">
                            @else
                            <svg class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @endif
                        </div>
                        <label for="logo2-input" class="cursor-pointer w-7 h-7 rounded-full border border-slate-200 bg-white flex items-center justify-center hover:bg-slate-50 transition-colors shadow-sm" title="Upload logo2">
                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </label>
                        <input type="file" id="logo2-input" name="logo2" accept="image/*" class="hidden" onchange="previewFile(this,'logo2-wrap')">
                    </div>
                </div>
            </div>

            {{-- Email Verification + OTP Verification --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email Verification for users [Signup]</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="email_verification" value="0"
                                   {{ ($s['email_verification'] ?? '1') == '0' ? 'checked' : '' }}
                                   class="w-4 h-4 accent-green-600">
                            <span class="text-sm text-slate-600">Off</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="email_verification" value="1"
                                   {{ ($s['email_verification'] ?? '1') == '1' ? 'checked' : '' }}
                                   class="w-4 h-4 accent-green-600">
                            <span class="text-sm text-slate-600">On</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">OTP Verification for users [Signin]</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="otp_verification" value="0"
                                   {{ ($s['otp_verification'] ?? '0') == '0' ? 'checked' : '' }}
                                   class="w-4 h-4 accent-green-600">
                            <span class="text-sm text-slate-600">Off</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="otp_verification" value="1"
                                   {{ ($s['otp_verification'] ?? '0') == '1' ? 'checked' : '' }}
                                   class="w-4 h-4 accent-green-600">
                            <span class="text-sm text-slate-600">On</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Theme Color + App Version --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Theme Color</label>
                    <input type="color" name="theme_color" value="{{ $s['theme_color'] ?? '#1b3a1b' }}"
                           class="h-10 w-full rounded-lg border border-slate-200 cursor-pointer p-1 bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">App Version</label>
                    <input type="text" name="app_version" value="{{ $s['app_version'] ?? '1.0.0' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 transition-colors">
                </div>
            </div>

            {{-- Save button --}}
            <div class="pt-2">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90"
                        style="background:#4CAF50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Save
                </button>
            </div>

        </div>
    </div>

</form>

@endsection

@section('scripts')
<script>
function previewFile(input, wrapId) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var wrap = document.getElementById(wrapId);
        wrap.innerHTML = '<img src="' + e.target.result + '" class="h-full max-w-full object-contain" alt="preview">';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
@endsection

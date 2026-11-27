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
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Site URL</label>
                    <input type="text" name="site_url" value="{{ $s['site_url'] ?? '' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                </div>
            </div>

            {{-- Landing Page Layout Mode --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Homepage / Landing Page Layout Mode</label>
                <select name="landing_page_layout" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    <option value="vtu_default" {{ ($s['landing_page_layout'] ?? 'vtu_default') === 'vtu_default' ? 'selected' : '' }}>Standard VTU Portal Landing Page</option>
                    <option value="company_profile" {{ ($s['landing_page_layout'] ?? 'vtu_default') === 'company_profile' ? 'selected' : '' }}>Company Profile</option>
                </select>
                <p class="text-xs text-slate-400 mt-1">Select which page template is displayed when users visit your main homepage (/)</p>
            </div>

            {{-- Site Description --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Site Description</label>
                <textarea name="site_description" rows="3"
                          class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors resize-none">{{ $s['site_description'] ?? '' }}</textarea>
            </div>

            {{-- Site Keywords --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Site Keywords</label>
                <textarea name="site_keywords" rows="3"
                          class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors resize-none">{{ $s['site_keywords'] ?? '' }}</textarea>
            </div>

            {{-- Location + Copyright --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Location</label>
                    <input type="text" name="location" value="{{ $s['location'] ?? '' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Copyright</label>
                    <input type="text" name="copyright" value="{{ $s['copyright'] ?? '' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                </div>
            </div>

            {{-- Admin Email + Favicon --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Admin Email</label>
                    <input type="email" name="admin_email" value="{{ $s['admin_email'] ?? '' }}"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
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
                                   class="w-4 h-4" style="accent-color:{{ $themeColor }}">
                            <span class="text-sm text-slate-600">Off</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="email_verification" value="1"
                                   {{ ($s['email_verification'] ?? '1') == '1' ? 'checked' : '' }}
                                   class="w-4 h-4" style="accent-color:{{ $themeColor }}">
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
                                   class="w-4 h-4" style="accent-color:{{ $themeColor }}">
                            <span class="text-sm text-slate-600">Off</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="otp_verification" value="1"
                                   {{ ($s['otp_verification'] ?? '0') == '1' ? 'checked' : '' }}
                                   class="w-4 h-4" style="accent-color:{{ $themeColor }}">
                            <span class="text-sm text-slate-600">On</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Theme Color --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Theme Color</label>
                <input type="color" name="theme_color" value="{{ $s['theme_color'] ?? '#4caf50' }}"
                       class="h-10 w-full rounded-lg border border-slate-200 cursor-pointer p-1 bg-white">
            </div>

            {{-- Mobile App Control & Versioning --}}
            <div class="border-t border-slate-100 pt-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Mobile App Control & Versioning (API Config)
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Latest App Version</label>
                        <input type="text" name="app_version" value="{{ $s['app_version'] ?? '4.0.0' }}"
                               placeholder="4.0.0"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                        <p class="text-xs text-slate-500 mt-1">e.g. 4.0.0 or 4.0.1. Users with lower version numbers will be prompted to update.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Minimum Supported Version</label>
                        <input type="text" name="app_min_version" value="{{ $s['app_min_version'] ?? '1.0.0' }}"
                               placeholder="1.0.0"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                        <p class="text-xs text-slate-500 mt-1">Users below this version are strictly forced to update.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Force Update Mobile App</label>
                        <select name="app_force_update" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                            <option value="0" {{ ($s['app_force_update'] ?? '0') == '0' ? 'selected' : '' }}>No (Optional Update)</option>
                            <option value="1" {{ ($s['app_force_update'] ?? '0') == '1' ? 'selected' : '' }}>Yes (Enforce Update)</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">When set to Yes, only devices running a version lower than Latest App Version are locked to the update screen.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">App Maintenance Mode</label>
                        <select name="app_maintenance_mode" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                            <option value="0" {{ ($s['app_maintenance_mode'] ?? '0') == '0' ? 'selected' : '' }}>Disabled (App Active)</option>
                            <option value="1" {{ ($s['app_maintenance_mode'] ?? '0') == '1' ? 'selected' : '' }}>Enabled (Under Maintenance)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">App Maintenance Notice</label>
                        <input type="text" name="app_maintenance_message" value="{{ $s['app_maintenance_message'] ?? 'Platform is under routine maintenance. Please check back shortly.' }}"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">WhatsApp Community / Group Link</label>
                        <input type="url" name="whatsapp_group_link" value="{{ $s['whatsapp_group_link'] ?? '' }}"
                               placeholder="https://chat.whatsapp.com/..."
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Telegram Channel Link</label>
                        <input type="url" name="telegram_channel_link" value="{{ $s['telegram_channel_link'] ?? '' }}"
                               placeholder="https://t.me/..."
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-500" viewBox="0 0 24 24" fill="currentColor"><path d="M3.18 23.61c.28.15.6.18.92.08l10.4-5.99-2.32-2.33-9 8.24zM.54 1.55C.2 1.88 0 2.4 0 3.05v17.88c0 .65.2 1.18.55 1.51l.08.07 10.02-10.02v-.24L.62 1.49l-.08.06zM20.19 10.6l-2.85-1.65-2.54 2.54 2.54 2.55 2.87-1.65c.82-.47.82-1.32-.02-1.79zM3.18.39l9 8.24 2.32-2.32L4.1.31c-.36-.2-.7-.18-.92.08z"/></svg>
                            Google Play Store Link
                        </label>
                        <input type="url" name="play_store_url" value="{{ $s['play_store_url'] ?? '' }}"
                               placeholder="https://play.google.com/store/apps/details?id=..."
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                        <p class="text-xs text-slate-500 mt-1">Direct link to your app on the Google Play Store.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-800 dark:text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.62-.76 1.05-1.81.93-2.87-.9.04-2 .6-2.65 1.36-.57.65-1.07 1.72-.94 2.76 1.01.08 2.04-.49 2.66-1.25z"/></svg>
                            Apple App Store Link
                        </label>
                        <input type="url" name="app_store_url" value="{{ $s['app_store_url'] ?? '' }}"
                               placeholder="https://apps.apple.com/app/..."
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                        <p class="text-xs text-slate-500 mt-1">Direct link to your app on the Apple App Store.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Direct APK Download Link
                        </label>
                        <input type="url" name="apk_download_url" value="{{ $s['apk_download_url'] ?? '' }}"
                               placeholder="https://yourdomain.com/downloads/app.apk"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                        <p class="text-xs text-slate-500 mt-1">Optional direct download link for users installing Android APK.</p>
                    </div>
                </div>
            </div>

            {{-- Agent & Security Settings --}}
            <div>
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Agent & Security Settings</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Agent Upgrade Fee (₦)</label>
                        <input type="number" name="agent_upgrade_fee" value="{{ $s['agent_upgrade_fee'] ?? '5000' }}" min="0" step="any"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">KYC Verification Fee (₦)</label>
                        <input type="number" name="kyc_fee" value="{{ $s['kyc_fee'] ?? '0' }}" min="0" step="any"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Session Idle Timeout (Minutes)</label>
                        <input type="number" name="session_idle_timeout" value="{{ $s['session_idle_timeout'] ?? '5' }}" min="1" max="1440"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                </div>
            </div>

            {{-- Support Contact Details --}}
            <div>
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Support Contact Details</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">WhatsApp Number</label>
                        <input type="text" name="support_whatsapp" value="{{ $s['support_whatsapp'] ?? '' }}"
                               placeholder="e.g. 2348012345678"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Support Phone</label>
                        <input type="text" name="support_phone" value="{{ $s['support_phone'] ?? '' }}"
                               placeholder="e.g. 08012345678"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Support Email</label>
                        <input type="email" name="support_email" value="{{ $s['support_email'] ?? '' }}"
                               placeholder="support@yourdomain.com"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Support Hours</label>
                        <input type="text" name="support_hours" value="{{ $s['support_hours'] ?? '8am – 6pm' }}"
                               placeholder="e.g. 8am – 6pm"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Ticket URL <span class="text-xs text-slate-400">(optional)</span></label>
                        <input type="url" name="support_ticket_url" value="{{ $s['support_ticket_url'] ?? '' }}"
                               placeholder="https://"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 transition-colors">
                    </div>
                </div>
            </div>

            {{-- Save button --}}
            <div class="pt-2">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90"
                        style="background:{{ $themeColor }}">
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

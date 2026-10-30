@extends('layouts.admin')

@section('title', 'Email Notifications')
@section('heading', 'Email Notifications')
@section('subheading', 'Compose and broadcast email campaigns to your users')

@section('content')

<style>
    .dark .admin-card {
        background-color: #0f172a !important;
        border-color: #1e293b !important;
    }
    .dark .admin-card h3,
    .dark .admin-card h4 {
        color: #ffffff !important;
    }
    .dark .admin-card p,
    .dark .admin-card span.text-slate-400 {
        color: #64748b !important;
    }
    .dark .admin-card label {
        color: #94a3b8 !important;
    }
    .dark .admin-card select,
    .dark .admin-card input,
    .dark .admin-card textarea {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
</style>

{{-- Stat Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="admin-card bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Users</div>
        <div class="text-2xl font-bold text-slate-800 dark:text-white mt-1">{{ number_format($totalUsers) }}</div>
    </div>
    <div class="admin-card bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Agents</div>
        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($agentCount) }}</div>
    </div>
    <div class="admin-card bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Verified</div>
        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($verifiedCount) }}</div>
    </div>
    <div class="admin-card bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Active Users</div>
        <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ number_format($activeCount) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Compose Email Form --}}
    <div class="lg:col-span-1">
        <div class="admin-card bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden sticky top-6">
            <div class="px-6 pt-5 pb-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Compose Email Campaign
                </h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Send branded emails using your site's design template</p>
            </div>

            @php
                $smtpConfigured = \App\Services\EmailNotificationService::isConfigured();
            @endphp
            @if(!$smtpConfigured)
            <div class="mx-6 mt-4 p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-xl flex items-start gap-2.5 text-xs text-amber-800 dark:text-amber-300">
                <svg class="w-4 h-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <span class="font-bold">SMTP Not Configured:</span> Please enter your mail host and credentials under <a href="{{ route('admin.settings.email') }}" class="underline font-semibold hover:text-amber-900 dark:hover:text-amber-200">Settings &rarr; Email Settings</a> before sending email campaigns.
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('admin.notifications.email.send') }}" class="p-6 space-y-4">
                @csrf

                {{-- Template Presets Selector --}}
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 rounded-xl">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-200 mb-1 flex items-center justify-between">
                        <span>Load Template Preset</span>
                        <span class="text-[10px] text-vtu-primary font-normal cursor-pointer hover:underline" onclick="loadEmailTemplate('blank')">Clear Form</span>
                    </label>
                    <select id="template_preset" onchange="loadEmailTemplate(this.value)"
                            class="w-full px-3 py-2 text-xs border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        <option value="">-- Choose an example template --</option>
                        <option value="promo">Special Promotion / Discount Announcement</option>
                        <option value="maintenance">Scheduled System Maintenance Notice</option>
                        <option value="features">New Features & Speed Enhancements</option>
                        <option value="security">Account Security & PIN Protection Advisory</option>
                    </select>
                </div>

                {{-- Subject --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Email Subject</label>
                    <input type="text" name="subject" id="email_subject" required placeholder="e.g. Important Update / New Feature Alert"
                           value="{{ old('subject') }}"
                           class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                </div>

                {{-- Target Audience --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Target Audience</label>
                    <select name="target_audience" id="target_audience" required onchange="toggleAudienceFields(this.value)"
                            class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        <option value="all">All Users (Broadcast)</option>
                        <option value="filter">Filter by Group</option>
                        <option value="users">Specific User(s)</option>
                    </select>
                </div>

                {{-- Filter Type --}}
                <div id="filter_type_wrapper" class="hidden">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Filter Group</label>
                    <select name="filter_type" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        <option value="verified">Email Verified Users ({{ number_format($verifiedCount) }})</option>
                        <option value="active">Active Users ({{ number_format($activeCount) }})</option>
                        <option value="agents">Agents Only ({{ number_format($agentCount) }})</option>
                        <option value="inactive">Inactive Users</option>
                    </select>
                </div>

                {{-- Specific Users --}}
                <div id="users_wrapper" class="hidden">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">User IDs, Usernames, or Emails</label>
                    <input type="text" name="user_ids" placeholder="e.g. 1, 4, john_doe, user@mail.com"
                           class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    <p class="text-[11px] text-slate-400 mt-1">Separate multiple targets with commas</p>
                </div>

                {{-- Email Body --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Email Body</label>
                    <textarea name="body" id="email_body" rows="7" required
                              placeholder="Write your message here. Leave a blank line between paragraphs. Recipients will be greeted by name."
                              class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 resize-none">{{ old('body') }}</textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Tip: Double-press Enter to start a new paragraph in the email.</p>
                </div>

                {{-- CTA Button (optional) --}}
                <div class="border-t border-slate-100 dark:border-slate-800 pt-4 space-y-3">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Call-to-Action Button <span class="font-normal text-slate-400">(optional)</span></p>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Button Label</label>
                        <input type="text" name="action_label" id="action_label" placeholder="e.g. Visit Dashboard / Learn More"
                               value="{{ old('action_label') }}"
                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Button URL</label>
                        <input type="url" name="action_url" id="action_url" placeholder="https://your-site.com/page"
                               value="{{ old('action_url') }}"
                               class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 text-sm font-semibold text-white bg-vtu-primary hover:opacity-90 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Send Email Campaign
                </button>
            </form>
        </div>
    </div>

    {{-- Email Campaign History --}}
    <div class="lg:col-span-2">
        <div class="admin-card bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 pt-5 pb-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-800 dark:text-white">Email Campaign History</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Records of past email campaigns dispatched by admins</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-6 py-3">Subject & Preview</th>
                            <th class="px-4 py-3">Target</th>
                            <th class="px-4 py-3">Recipients</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Sent By</th>
                            <th class="px-4 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($campaigns as $campaign)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800 dark:text-white">{{ $campaign->subject }}</div>
                                <div class="text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2 max-w-xs">{{ $campaign->body }}</div>
                                @if($campaign->action_url)
                                <a href="{{ $campaign->action_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-blue-500 hover:underline mt-1">
                                    <span class="inline-block bg-vtu-primary/10 text-vtu-primary px-2 py-0.5 rounded-full font-semibold">{{ $campaign->action_label ?? 'CTA' }}</span>
                                </a>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ ucfirst($campaign->target_audience) }}
                                    @if($campaign->filter_type) ({{ ucfirst($campaign->filter_type) }}) @endif
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap font-medium">
                                {{ number_format($campaign->recipient_count) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($campaign->status === 'sent')
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    Sent
                                </span>
                                @elseif($campaign->status === 'partial')
                                <div>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                                        Partial
                                    </span>
                                    @if(!empty($campaign->extra_data['error']))
                                    <div class="text-[10px] text-amber-500 max-w-xs mt-1 truncate" title="{{ $campaign->extra_data['error'] }}">
                                        {{ $campaign->extra_data['error'] }}
                                    </div>
                                    @endif
                                </div>
                                @else
                                <div>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                                        Failed
                                    </span>
                                    @if(!empty($campaign->extra_data['error']))
                                    <div class="text-[10px] text-red-500 max-w-xs mt-1 truncate" title="{{ $campaign->extra_data['error'] }}">
                                        {{ $campaign->extra_data['error'] }}
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap font-medium text-slate-600 dark:text-slate-400">
                                {{ $campaign->admin->name ?? 'Admin' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-slate-400">
                                {{ $campaign->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                No email campaigns sent yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($campaigns->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
                {{ $campaigns->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

<script>
function toggleAudienceFields(val) {
    const filterWrapper = document.getElementById('filter_type_wrapper');
    const usersWrapper  = document.getElementById('users_wrapper');

    filterWrapper.classList.add('hidden');
    usersWrapper.classList.add('hidden');

    if (val === 'filter') {
        filterWrapper.classList.remove('hidden');
    } else if (val === 'users') {
        usersWrapper.classList.remove('hidden');
    }
}

const emailTemplates = {
    promo: {
        subject: "Special Offer: Discounted Data & Airtime Bundles Available Now!",
        body: "We are thrilled to bring you exclusive discounted rates across all mobile network data and airtime purchases on our platform!\n\nEnjoy automated instant recharges, maximum uptime, and discounted prices on MTN, Glo, Airtel, and 9mobile.\n\nTake advantage of this limited-time offer directly from your dashboard and fund your wallet with ease.",
        action_label: "Recharge & Save Now",
        action_url: "{{ url('/dashboard') }}"
    },
    maintenance: {
        subject: "Notice: Scheduled Upstream Network Maintenance",
        body: "Please be informed that routine server optimization and upstream service upgrades are scheduled to take place shortly.\n\nDuring this period, you may experience brief intermittent delays on selected network top-ups. Our engineering team is closely monitoring all routes to ensure minimal impact.\n\nAll wallet balances remain safe and secure, and normal speeds will resume immediately following completion.",
        action_label: "View Dashboard Status",
        action_url: "{{ url('/dashboard') }}"
    },
    features: {
        subject: "New Platform Enhancements & Faster Delivery Speeds",
        body: "We have upgraded our core engines to provide you with faster automated delivery on all airtime, data, electricity, cable TV, and exam PIN purchases.\n\nEnjoy automated dedicated virtual bank account funding with instant wallet crediting, improved transaction reports, and live delivery updates.\n\nLog in today to explore all the improvements designed to make your bill payment experience seamless.",
        action_label: "Explore New Features",
        action_url: "{{ url('/dashboard') }}"
    },
    security: {
        subject: "Security Advisory: Keeping Your Account Safe & Secure",
        body: "Your security is our highest priority. We want to remind you of essential safety practices to keep your funds and personal information secure.\n\nNever disclose your transaction PIN, account password, or login OTP to anyone—including individuals claiming to represent our customer support team.\n\nIf you ever notice any unrecognized activity or suspect your PIN may be compromised, please change your PIN immediately via your account settings.",
        action_label: "Review Security Settings",
        action_url: "{{ url('/settings') }}"
    }
};

function loadEmailTemplate(key) {
    if (!key || key === 'blank') {
        document.getElementById('email_subject').value = '';
        document.getElementById('email_body').value = '';
        document.getElementById('action_label').value = '';
        document.getElementById('action_url').value = '';
        document.getElementById('template_preset').value = '';
        return;
    }
    const t = emailTemplates[key];
    if (t) {
        document.getElementById('email_subject').value = t.subject;
        document.getElementById('email_body').value = t.body;
        document.getElementById('action_label').value = t.action_label;
        document.getElementById('action_url').value = t.action_url;
    }
}
</script>

@endsection

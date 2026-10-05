@extends('layouts.admin')

@section('title', 'Push Notifications')
@section('heading', 'Push Notifications')
@section('subheading', 'Send real-time web & mobile push notifications to your users')

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
        <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Web Users</div>
        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($webUsersCount) }}</div>
    </div>
    <div class="admin-card bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Android Users</div>
        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($androidUsersCount) }}</div>
    </div>
    <div class="admin-card bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">iOS Users</div>
        <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-1">{{ number_format($iosUsersCount) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Send Push Notification Form --}}
    <div class="lg:col-span-1">
        <div class="admin-card bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden sticky top-6">
            <div class="px-6 pt-5 pb-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-vtu-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Send Push Notification
                </h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Broadcast instantly to web browsers and mobile apps</p>
            </div>

            <form method="POST" action="{{ route('admin.notifications.send') }}" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Notification Title</label>
                    <input type="text" name="title" required placeholder="e.g. Flash Promo / Maintenance Alert"
                           class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Target Audience</label>
                    <select name="target_audience" id="target_audience" required onchange="toggleAudienceFields(this.value)"
                            class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        <option value="all">All Users (Broadcasting)</option>
                        <option value="device_type">Filter by Device Type</option>
                        <option value="users">Specific User(s)</option>
                    </select>
                </div>

                <div id="device_type_wrapper" class="hidden">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Select Device Type</label>
                    <select name="device_type" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        <option value="web">Web Browser Push</option>
                        <option value="android">Android Mobile Apps</option>
                        <option value="ios">iOS Mobile Apps</option>
                    </select>
                </div>

                <div id="users_wrapper" class="hidden">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">User IDs, Usernames, or Emails</label>
                    <input type="text" name="user_ids" placeholder="e.g. 1, 4, john_doe, user@mail.com (comma separated)"
                           class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    <p class="text-[11px] text-slate-400 mt-1">Separate multiple targets with commas</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Notification Body / Message</label>
                    <textarea name="message" rows="4" required placeholder="Type your push notification content here..."
                              class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Action URL (Optional)</label>
                    <input type="url" name="action_url" placeholder="https://example.com/promo"
                           class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    <p class="text-[11px] text-slate-400 mt-1">Users will be directed here when tapping the notification</p>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 text-sm font-semibold text-white bg-vtu-primary hover:opacity-90 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Send Push Notification
                </button>
            </form>
        </div>
    </div>

    {{-- Notification History Log --}}
    <div class="lg:col-span-2">
        <div class="admin-card bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 pt-5 pb-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Push Notification Log History</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Records of past notifications dispatched by admins</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-6 py-3">Title & Content</th>
                            <th class="px-4 py-3">Target</th>
                            <th class="px-4 py-3">Recipients</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Sent By</th>
                            <th class="px-6 py-3 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($notifications as $notif)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800 dark:text-white">{{ $notif->title }}</div>
                                <div class="text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">{{ $notif->message }}</div>
                                @if(!empty($notif->extra_data['url']))
                                <a href="{{ $notif->extra_data['url'] }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-blue-500 hover:underline mt-1">
                                    {{ $notif->extra_data['url'] }}
                                </a>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ ucfirst($notif->target_audience) }}
                                    @if($notif->device_type) ({{ strtoupper($notif->device_type) }}) @endif
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap font-medium">
                                {{ number_format($notif->recipient_count) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($notif->status === 'sent')
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    Sent
                                </span>
                                @else
                                <div>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400" title="{{ $notif->extra_data['error'] ?? 'Delivery failed' }}">
                                        Failed
                                    </span>
                                    @if(!empty($notif->extra_data['error']))
                                    <div class="text-[10px] text-red-500 max-w-xs mt-1 truncate" title="{{ $notif->extra_data['error'] }}">
                                        {{ $notif->extra_data['error'] }}
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap font-medium text-slate-600 dark:text-slate-400">
                                {{ $notif->admin->name ?? 'Admin' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-slate-400">
                                {{ $notif->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                No push notifications sent yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
                {{ $notifications->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

<script>
function toggleAudienceFields(val) {
    const deviceWrapper = document.getElementById('device_type_wrapper');
    const usersWrapper = document.getElementById('users_wrapper');

    deviceWrapper.classList.add('hidden');
    usersWrapper.classList.add('hidden');

    if (val === 'device_type') {
        deviceWrapper.classList.remove('hidden');
    } else if (val === 'users') {
        usersWrapper.classList.remove('hidden');
    }
}
</script>

@endsection

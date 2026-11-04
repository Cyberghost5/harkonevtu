@extends('layouts.admin')

@section('title', 'Manage Administrators')
@section('heading', 'Administrators')
@section('subheading', 'Manage staff administrators and control dashboard privileges')

@section('content')

{{-- Super Admin Notice Banner --}}
<div class="mb-5 p-4 rounded-2xl bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/20 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="h-9 w-9 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center flex-shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs font-bold text-amber-500 uppercase tracking-wider">Super Administrator Authority</p>
            <p class="text-xs text-slate-600 dark:text-slate-400">
                You are logged in with the <strong>Super Admin</strong> role. Super administrator accounts are protected and isolated from the standard staff administrator directory.
            </p>
        </div>
    </div>
    <a href="{{ route('admin.admins.create') }}" class="px-4 py-2 text-xs font-semibold text-white bg-vtu-primary rounded-xl hover:bg-indigo-700 transition-colors shadow-sm flex-shrink-0">
        + Add New Admin
    </a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.admins.index') }}"
      class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm mb-5 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Search Administrator</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, username, phone..."
               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Status</label>
        <select name="status" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="active" {{ request('status')==='active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status')==='inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    <button type="submit" class="px-4 py-2 text-sm font-medium bg-vtu-primary text-white rounded-xl hover:bg-indigo-700 transition-colors">Filter</button>
    @if(request()->hasAny(['search','status']))
    <a href="{{ route('admin.admins.index') }}" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-white">Clear</a>
    @endif
</form>

{{-- Admins Table --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 select-none">
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Admin User</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Contact</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Role</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Created At</th>
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($admins as $admin)
                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors">
                    {{-- User Column --}}
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            @if($admin->avatar)
                                <img src="{{ $admin->avatar }}" class="w-9 h-9 rounded-full object-cover flex-shrink-0" alt="">
                            @else
                                <div class="w-9 h-9 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold flex items-center justify-center text-xs flex-shrink-0">
                                    {{ $admin->initials() }}
                                </div>
                            @endif
                            <div>
                                <a href="{{ route('admin.users.show', $admin->id) }}" class="font-semibold text-slate-900 dark:text-white hover:text-vtu-primary transition-colors">
                                    {{ $admin->name }}
                                </a>
                                <p class="text-xs text-slate-400">@<span>{{ $admin->username }}</span></p>
                            </div>
                        </div>
                    </td>

                    {{-- Contact Column --}}
                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-400">
                        <p>{{ $admin->email }}</p>
                        <p class="text-slate-400">{{ $admin->phone }}</p>
                    </td>

                    {{-- Role Column --}}
                    <td class="px-5 py-3.5 text-xs">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                            Staff Admin
                        </span>
                    </td>

                    {{-- Status Column --}}
                    <td class="px-5 py-3.5 text-xs">
                        @if($admin->isActive())
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Inactive
                            </span>
                        @endif
                    </td>

                    {{-- Created At --}}
                    <td class="px-5 py-3.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ $admin->created_at->format('M d, Y') }}
                        <p class="text-[10px] text-slate-400">{{ $admin->created_at->format('h:i A') }}</p>
                    </td>

                    {{-- Actions --}}
                    <td class="px-5 py-3.5 text-right space-x-2">
                        <a href="{{ route('admin.users.show', $admin->id) }}"
                           class="text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-vtu-primary transition-colors">
                            View
                        </a>
                        <form method="POST" action="{{ route('admin.admins.demote', $admin->id) }}" class="inline"
                              onsubmit="return confirm('Are you sure you want to demote {{ $admin->name }} to a regular user? They will lose all admin panel privileges.');">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-amber-600 hover:text-amber-700 transition-colors">
                                Demote to User
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-slate-400 text-xs">
                        No staff administrators found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($admins->hasPages())
    <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800">
        {{ $admins->links() }}
    </div>
    @endif
</div>

@endsection

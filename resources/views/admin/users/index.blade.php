@extends('layouts.admin')

@section('title', 'Users')
@section('heading', 'Users')
@section('subheading', 'Manage registered users')

@section('content')

<div class="flex justify-between items-center mb-5">
    <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Registered Accounts</h2>
    <a href="{{ route('admin.users.create') }}" class="px-4 py-2 text-xs font-semibold text-white bg-vtu-primary rounded-xl hover:bg-indigo-700 transition-colors">
        + Add New User
    </a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.users.index') }}"
      class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm mb-5 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[180px]">
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, username, phone..."
               class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Type</label>
        <select name="type" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Types</option>
            <option value="user" {{ request('type')==='user' ? 'selected' : '' }}>User</option>
            <option value="agent" {{ request('type')==='agent' ? 'selected' : '' }}>Agent</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Status</label>
        <select name="status" class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">All Status</option>
            <option value="active" {{ request('status')==='active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status')==='inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
    <button type="submit" class="px-4 py-2 text-sm font-medium bg-vtu-primary text-white rounded-xl hover:bg-indigo-700 transition-colors">Filter</button>
    @if(request()->hasAny(['search','type','status']))
    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-white">Clear</a>
    @endif
</form>

{{-- Table --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">User</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Contact</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Type</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Balance</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Joined</th>
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                @forelse($users as $user)
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-xl bg-gradient-to-tr from-vtu-primary to-vtu-secondary flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ $user->initials() }}
                            </div>
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white text-xs">{{ $user->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $user->username }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <p class="text-xs text-slate-700 dark:text-slate-300">{{ $user->email }}</p>
                        <p class="text-[10px] text-slate-400">{{ $user->phone }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full
                            {{ $user->isAgent() ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400' }}">
                            {{ ucfirst($user->user_type) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 font-semibold text-slate-900 dark:text-white text-xs">
                        ₦{{ number_format($user->wallet?->balance ?? 0, 2) }}
                    </td>
                    <td class="px-5 py-3">
                        @if($user->is_active)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Active
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Inactive
                        </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-400">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2.5">
                            <a href="{{ route('admin.users.show', $user) }}"
                               class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                                View
                            </a>
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="text-xs font-medium text-vtu-primary hover:underline">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-slate-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800">
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection

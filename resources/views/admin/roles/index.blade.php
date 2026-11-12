@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('heading', 'Roles & Permissions')
@section('subheading', 'Configure administrative roles and granular CRUD privileges for staff members')

@section('content')

{{-- Top Notice Banner --}}
<div class="mb-5 p-4 rounded-2xl bg-gradient-to-r from-indigo-500/10 via-indigo-500/5 to-transparent border border-indigo-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-indigo-500/20 text-indigo-500 flex items-center justify-center flex-shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">Role-Based Access Control (RBAC)</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Create specific operational roles (e.g. <em>Manager, Support Staff, Auditor</em>) and assign exact <strong>Create, Read, Update, Delete</strong> rights for each module.
            </p>
        </div>
    </div>
    @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccess('roles', 'create'))
    <a href="{{ route('admin.roles.create') }}" class="px-4 py-2.5 text-xs font-semibold text-white bg-vtu-primary rounded-xl hover:bg-indigo-700 transition-colors shadow-sm flex-shrink-0 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create New Role
    </a>
    @endif
</div>

{{-- Roles Table --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 select-none">
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Role Name</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Role Type</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Permissions Count</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Assigned Staff</th>
                    <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Description</th>
                    <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($roles as $role)
                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors">
                    {{-- Role Name --}}
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold {{ $role->is_system ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300' }}">
                                {{ substr($role->name, 0, 2) }}
                            </div>
                            <div>
                                <span class="font-semibold text-slate-900 dark:text-white block">
                                    {{ $role->name }}
                                </span>
                                <span class="text-[11px] font-mono text-slate-400">slug: {{ $role->slug }}</span>
                            </div>
                        </div>
                    </td>

                    {{-- Type --}}
                    <td class="px-5 py-4 text-xs">
                        @if($role->is_system)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/30">
                                System Role
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                                Custom Staff Role
                            </span>
                        @endif
                    </td>

                    {{-- Permissions Count --}}
                    <td class="px-5 py-4 text-xs">
                        @if($role->slug === 'super-admin')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                All (Full Access)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                <strong>{{ $role->permissions_count }}</strong> permissions
                            </span>
                        @endif
                    </td>

                    {{-- Assigned Users --}}
                    <td class="px-5 py-4 text-xs text-slate-600 dark:text-slate-400">
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $role->users_count }}</span> admin{{ $role->users_count === 1 ? '' : 's' }}
                    </td>

                    {{-- Description --}}
                    <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400 max-w-xs truncate">
                        {{ $role->description ?? '—' }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-5 py-4 text-right space-x-2">
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->canAccess('roles', 'update'))
                        <a href="{{ route('admin.roles.edit', $role->id) }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Configure
                        </a>
                        @endif

                        @if(!$role->is_system && (auth()->user()->isSuperAdmin() || auth()->user()->canAccess('roles', 'delete')))
                        <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" class="inline"
                              onsubmit="return confirm('Are you sure you want to delete the role \'{{ $role->name }}\'? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-rose-600 dark:text-rose-400 hover:text-rose-800 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-slate-400 text-xs">
                        No roles found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

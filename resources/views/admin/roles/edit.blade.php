@extends('layouts.admin')

@section('title', 'Edit Role - ' . $role->name)
@section('heading', 'Edit Role: ' . $role->name)
@section('subheading', 'Update role details and customize CRUD privileges')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    @if($role->is_system && $role->slug === 'super-admin')
    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center gap-3">
        <svg class="w-6 h-6 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-xs text-amber-700 dark:text-amber-300">
            <strong>System Notice:</strong> The <strong>Super Administrator</strong> role automatically bypasses all permission checks in the application and retains full access at all times.
        </p>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.roles.update', $role->id) }}" id="role-form">
        @csrf
        @method('PATCH')

        {{-- Role Basic Info Card --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm mb-6">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Role Details
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                        Role Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" required
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                        Description
                    </label>
                    <input type="text" name="description" value="{{ old('description', $role->description) }}" placeholder="Brief summary of duties and responsibilities"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                    @error('description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Permissions Toolbar --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm mb-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">Permission Matrix</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Configure which Create, Read, Update, and Delete operations this role can perform.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="selectAllPermissions(true)"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-colors">
                    Select All
                </button>
                <button type="button" onclick="selectAllPermissions(false)"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    Deselect All
                </button>
            </div>
        </div>

        {{-- Permissions Matrix Cards by Module --}}
        <div class="space-y-6">
            @php
                $moduleLabels = [
                    'dashboard'       => 'Dashboard & Analytics',
                    'users'           => 'Users Management',
                    'transactions'    => 'Transactions & Orders',
                    'funding'         => 'Wallet Funding Requests',
                    'airtime_to_cash' => 'Airtime to Cash',
                    'vouchers'        => 'Recharge Voucher Printing',
                    'epins'           => 'Exam PINs (WAEC / NECO / JAMB)',
                    'coupons'         => 'Coupons & Discounts',
                    'bulksms'         => 'Bulk SMS Campaigns',
                    'reports'         => 'Financial Reports & Exports',
                    'settings'        => 'System & Provider Settings',
                    'api_logs'        => 'API Diagnostics & Logs',
                    'admins'          => 'Staff Administrator Management',
                    'roles'           => 'Roles & Permissions (RBAC)',
                ];

                $actionBadges = [
                    'create'        => ['label' => 'CREATE', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/30'],
                    'read'          => ['label' => 'READ',   'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border border-blue-500/30'],
                    'update'        => ['label' => 'UPDATE', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-500/30'],
                    'delete'        => ['label' => 'DELETE', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30'],
                    'export'        => ['label' => 'EXPORT', 'class' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-300 border border-cyan-500/30'],
                    'wallet_adjust' => ['label' => 'ADJUST', 'class' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-500/30'],
                    'impersonate'   => ['label' => 'LOGIN',  'class' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300 border border-orange-500/30'],
                    'api_keys'      => ['label' => 'KEYS',   'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30'],
                ];
            @endphp

            @foreach($permissions as $module => $modulePermissions)
            @php
                $modulePermIds = $modulePermissions->pluck('id')->toArray();
                $allModuleChecked = count(array_diff($modulePermIds, old('permissions', $rolePermissionIds))) === 0;
            @endphp
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden" data-module="{{ $module }}">
                {{-- Module Card Header --}}
                <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-vtu-primary"></span>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                            {{ $moduleLabels[$module] ?? ucfirst(str_replace('_', ' ', $module)) }}
                        </h4>
                        <span class="text-[11px] text-slate-400">({{ count($modulePermissions) }} actions)</span>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        <input type="checkbox" onchange="toggleModulePermissions('{{ $module }}', this.checked)" class="module-master-toggle rounded border-slate-300 text-vtu-primary focus:ring-vtu-primary" {{ $allModuleChecked ? 'checked' : '' }}>
                        <span>Select Module</span>
                    </label>
                </div>

                {{-- Module Permissions Grid --}}
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($modulePermissions as $p)
                    @php
                        $badge = $actionBadges[$p->action] ?? ['label' => strtoupper($p->action), 'class' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'];
                        $isChecked = in_array($p->id, old('permissions', $rolePermissionIds));
                    @endphp
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors cursor-pointer select-none">
                        <input type="checkbox" name="permissions[]" value="{{ $p->id }}"
                               class="perm-checkbox module-perm-{{ $module }} mt-1 rounded border-slate-300 text-vtu-primary focus:ring-vtu-primary"
                               {{ $isChecked ? 'checked' : '' }}>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $p->name }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">{{ $p->description }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        {{-- Form Actions Bar --}}
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-800 mt-6">
            <a href="{{ route('admin.roles.index') }}"
               class="px-5 py-2.5 text-sm font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-semibold text-white bg-vtu-primary rounded-xl hover:bg-indigo-700 transition-colors shadow-sm">
                Save Changes
            </button>
        </div>

    </form>
</div>

<script>
    function selectAllPermissions(check) {
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            cb.checked = check;
        });
        document.querySelectorAll('.module-master-toggle').forEach(mt => {
            mt.checked = check;
        });
    }

    function toggleModulePermissions(module, check) {
        document.querySelectorAll('.module-perm-' + module).forEach(cb => {
            cb.checked = check;
        });
    }
</script>
@endsection

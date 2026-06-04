@extends('layouts.admin')

@section('title', 'Cable Plan Settings')
@section('heading', 'Cable Plan Settings')
@section('subheading', 'Manage cable TV subscription plans')

@section('content')

@if(session('success'))
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    {{ session('error') }}
</div>
@endif

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-800">Cable TV Plans</h3>
            <p class="text-xs text-slate-400 mt-0.5">{{ $plans->count() }} plan(s) across {{ $providers->count() }} provider(s)</p>
        </div>
        <button onclick="openAddModal()"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90"
                style="background:{{ $themeColor }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Plan
        </button>
    </div>

    {{-- Provider Tabs --}}
    <div class="px-6 pt-4 border-b border-slate-100 flex items-center gap-1 flex-wrap">
        <button onclick="filterPlans('all')" id="tab-all"
                class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 border-green-500 text-green-600 bg-green-50 transition-colors">
            All ({{ $plans->count() }})
        </button>
        @foreach($providers as $prov)
        <button onclick="filterPlans({{ $prov->id }})" id="tab-{{ $prov->id }}"
                class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-colors">
            {{ $prov->name }} ({{ $plans->where('cable_provider_id', $prov->id)->count() }})
        </button>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <th class="px-4 py-3 text-left w-10">#</th>
                    <th class="px-4 py-3 text-left">Provider</th>
                    <th class="px-4 py-3 text-left">Plan Name</th>
                    <th class="px-4 py-3 text-left">VTPass ID</th>
                    <th class="px-4 py-3 text-left">EasyAccess ID</th>
                    <th class="px-4 py-3 text-right">Amount (₦)</th>
                    <th class="px-4 py-3 text-center">Sort</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="plansTableBody">
                @forelse($plans as $i => $plan)
                <tr class="hover:bg-slate-50 transition-colors plan-row" data-provider="{{ $plan->cable_provider_id }}">
                    <td class="px-4 py-2.5 text-slate-400 text-xs">{{ $i + 1 }}</td>
                    <td class="px-4 py-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                            {{ $plan->provider->name ?? '-' }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 font-medium text-slate-800">{{ $plan->name }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $plan->vtpass_id }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $plan->easyaccess_id ?? '-' }}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-slate-700">{{ number_format($plan->amount, 0) }}</td>
                    <td class="px-4 py-2.5 text-center text-slate-500">{{ $plan->sort_order }}</td>
                    <td class="px-4 py-2.5 text-center">
                        @if($plan->enabled)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Off</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <button onclick="openEditModal(
                                {{ $plan->id }},
                                {{ $plan->cable_provider_id }},
                                {{ json_encode($plan->name) }},
                                {{ json_encode($plan->vtpass_id) }},
                                {{ json_encode($plan->easyaccess_id ?? '') }},
                                {{ json_encode($plan->payscribe_id ?? '') }},
                                {{ $plan->amount }},
                                {{ $plan->sort_order }},
                                {{ $plan->enabled ? 'true' : 'false' }}
                            )"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </button>
                            <form method="POST" action="{{ route('admin.settings.cable-plans.destroy', $plan->id) }}"
                                  onsubmit="return confirm('Delete this plan?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-6 py-10 text-center text-slate-400 text-sm">No cable plans found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Add Modal ────────────────────────────────────────────────────────── --}}
<div id="addModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h4 class="text-base font-bold text-slate-800">Add Cable Plan</h4>
            <button onclick="closeModal('addModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.settings.cable-plans.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Provider <span class="text-red-500">*</span></label>
                    <select name="cable_provider_id" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                        <option value="">- Select Provider -</option>
                        @foreach($providers as $prov)
                        <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. DStv Compact"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">VTPass ID <span class="text-red-500">*</span></label>
                    <input type="text" name="vtpass_id" required placeholder="e.g. compact"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 font-mono text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">EasyAccess ID</label>
                    <input type="text" name="easyaccess_id" placeholder="e.g. 93"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 font-mono text-xs">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Payscribe ID</label>
                    <input type="text" name="payscribe_id" placeholder="e.g. WDdqUUgrMVBtaFVOL0p2..."
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 font-mono text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Amount (₦) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" required step="0.01" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="0" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div class="col-span-2 flex items-center gap-2">
                    <input type="checkbox" name="enabled" value="1" id="add_enabled" checked class="rounded border-slate-300 text-green-500">
                    <label for="add_enabled" class="text-sm text-slate-600">Enabled</label>
                </div>
            </div>
            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeModal('addModal')"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90"
                        style="background:{{ $themeColor }}">Add Plan</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Modal ───────────────────────────────────────────────────────── --}}
<div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h4 class="text-base font-bold text-slate-800">Edit Cable Plan</h4>
            <button onclick="closeModal('editModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editForm" method="POST" action="" class="px-6 py-5 space-y-4">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Provider <span class="text-red-500">*</span></label>
                    <select id="edit_cable_provider_id" name="cable_provider_id" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                        @foreach($providers as $prov)
                        <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" id="edit_name" name="name" required
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">VTPass ID <span class="text-red-500">*</span></label>
                    <input type="text" id="edit_vtpass_id" name="vtpass_id" required
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 font-mono text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">EasyAccess ID</label>
                    <input type="text" id="edit_easyaccess_id" name="easyaccess_id"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 font-mono text-xs">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Payscribe ID</label>
                    <input type="text" id="edit_payscribe_id" name="payscribe_id"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 font-mono text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Amount (₦) <span class="text-red-500">*</span></label>
                    <input type="number" id="edit_amount" name="amount" required step="0.01" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Sort Order</label>
                    <input type="number" id="edit_sort_order" name="sort_order" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div class="col-span-2 flex items-center gap-2">
                    <input type="checkbox" id="edit_enabled" name="enabled" value="1" class="rounded border-slate-300 text-green-500">
                    <label for="edit_enabled" class="text-sm text-slate-600">Enabled</label>
                </div>
            </div>
            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeModal('editModal')"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90"
                        style="background:{{ $themeColor }}">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const baseUrl = '{{ url("admin/settings/cable-plans") }}';

    function openAddModal()  { document.getElementById('addModal').classList.remove('hidden'); }
    function closeModal(id)  { document.getElementById(id).classList.add('hidden'); }

    function openEditModal(id, providerId, name, vtpassId, easyaccessId, payscribeId, amount, sortOrder, enabled) {
        document.getElementById('edit_cable_provider_id').value = providerId;
        document.getElementById('edit_name').value              = name;
        document.getElementById('edit_vtpass_id').value         = vtpassId;
        document.getElementById('edit_easyaccess_id').value     = easyaccessId;
        document.getElementById('edit_payscribe_id').value      = payscribeId;
        document.getElementById('edit_amount').value            = amount;
        document.getElementById('edit_sort_order').value        = sortOrder;
        document.getElementById('edit_enabled').checked         = enabled;
        document.getElementById('editForm').action              = baseUrl + '/' + id;
        document.getElementById('editModal').classList.remove('hidden');
    }

    let activeTab = 'all';
    function filterPlans(providerId) {
        activeTab = providerId;
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-green-500','text-green-600','bg-green-50');
            btn.classList.add('border-transparent','text-slate-500');
        });
        const active = document.getElementById('tab-' + providerId);
        if (active) {
            active.classList.add('border-green-500','text-green-600','bg-green-50');
            active.classList.remove('border-transparent','text-slate-500');
        }
        document.querySelectorAll('.plan-row').forEach(row => {
            if (providerId === 'all' || row.dataset.provider == providerId) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    }

    // Close on backdrop click
    ['addModal','editModal'].forEach(id => {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) closeModal(id);
        });
    });
</script>
@endsection

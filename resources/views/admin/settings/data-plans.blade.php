@extends('layouts.admin')

@section('title', 'Data Plan Settings')
@section('heading', 'Data Plan Settings')
@section('subheading', 'Manage data bundle plans per network')

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

@php
    $netColors = [
        'mtn'     => 'bg-yellow-400 text-yellow-900',
        'airtel'  => 'bg-red-500 text-white',
        'glo'     => 'bg-green-600 text-white',
        'etisalat'=> 'bg-green-400 text-green-900',
    ];
    $typeLabels = [
        'sme'        => 'SME',
        'gifting'    => 'Gifting',
        'cg'         => 'Corp. Gifting',
        'awoof'      => 'AWOOF',
        'cheap_data' => 'Cheap Data',
    ];
@endphp

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

    {{-- Network Tabs --}}
    <div class="flex items-stretch border-b border-slate-100">
        @foreach($networks as $key => $label)
        <a href="{{ route('admin.settings.data-plans', $key) }}"
           class="flex-1 text-center py-3 text-sm font-semibold transition-colors
                  {{ $network === $key
                       ? 'border-b-2 border-green-500 text-green-600 bg-green-50'
                       : 'text-slate-500 hover:bg-slate-50 border-b-2 border-transparent' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- Subheader: data-type filter + New button --}}
    <div class="px-6 py-3 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-1 flex-wrap">
            <button onclick="filterType('all')" id="dtype-all"
                    class="dtype-btn px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-600 transition-colors">
                All ({{ $plans->count() }})
            </button>
            @foreach($dataTypes as $dt)
            <button onclick="filterType('{{ $dt }}')" id="dtype-{{ $dt }}"
                    class="dtype-btn px-3 py-1.5 text-xs font-medium rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                {{ $typeLabels[$dt] ?? strtoupper($dt) }} ({{ $plans->where('data_type', $dt)->count() }})
            </button>
            @endforeach
        </div>
        <button onclick="openAddModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90 flex-shrink-0"
                style="background:{{ $themeColor }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Plan
        </button>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <th class="px-4 py-3 text-left w-10">#</th>
                    <th class="px-4 py-3 text-left">Plan Name</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Validity</th>
                    <th class="px-4 py-3 text-left">VTPass ID</th>
                    <th class="px-4 py-3 text-left">Clubkonnect ID</th>
                    <th class="px-4 py-3 text-left">EasyAccess ID</th>
                    <th class="px-4 py-3 text-right">Amount (₦)</th>
                    <th class="px-4 py-3 text-right">Agent (₦)</th>
                    <th class="px-4 py-3 text-center">Sort</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="plansBody">
                @forelse($plans as $i => $plan)
                <tr class="hover:bg-slate-50 transition-colors plan-row" data-type="{{ $plan->data_type }}">
                    <td class="px-4 py-2.5 text-slate-400 text-xs">{{ $i + 1 }}</td>
                    <td class="px-4 py-2.5 font-medium text-slate-800 whitespace-nowrap">{{ $plan->plan_name }}</td>
                    <td class="px-4 py-2.5">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                            {{ $typeLabels[$plan->data_type] ?? strtoupper($plan->data_type) }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-slate-500">{{ $plan->validity ?? '-' }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $plan->vtpass_id ?? '-' }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $plan->clubkonnect_id ?? '-' }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $plan->easyaccess_id ?? '-' }}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-slate-700">{{ number_format($plan->amount, 0) }}</td>
                    <td class="px-4 py-2.5 text-right text-slate-600">{{ $plan->amount_agent ? number_format($plan->amount_agent, 0) : '-' }}</td>
                    <td class="px-4 py-2.5 text-center text-slate-500 text-xs">{{ $plan->sort_order }}</td>
                    <td class="px-4 py-2.5 text-center">
                        @if($plan->enabled)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">On</span>
                        @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Off</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center gap-1.5">
                            <button onclick="openEditModal({{ json_encode($plan->toArray()) }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </button>
                            <form method="POST" action="{{ route('admin.settings.data-plans.destroy', $plan->id) }}"
                                  onsubmit="return confirm('Delete this plan?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Del
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="12" class="px-6 py-10 text-center text-slate-400 text-sm">No data plans for this network.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Add Modal ────────────────────────────────────────────────────────── --}}
<div id="addModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <h4 class="text-base font-bold text-slate-800">Add Data Plan</h4>
            <button onclick="closeModal('addModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.settings.data-plans.store') }}" class="px-6 py-5">
            @csrf
            <input type="hidden" name="network_key" value="{{ $network }}">
            <div class="grid grid-cols-2 gap-4">

                {{-- Core Fields --}}
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" name="plan_name" required placeholder="e.g. MTN 1GB Daily"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Data Type <span class="text-red-500">*</span></label>
                    <select name="data_type" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                        <option value="">- Select -</option>
                        <option value="sme">SME</option>
                        <option value="gifting">Gifting</option>
                        <option value="cg">Corp. Gifting</option>
                        <option value="awoof">AWOOF</option>
                        <option value="cheap_data">Cheap Data</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Validity</label>
                    <input type="text" name="validity" placeholder="e.g. 30 days"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Amount (₦) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" required step="0.01" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Agent Amount (₦)</label>
                    <input type="number" name="amount_agent" step="0.01" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>

                {{-- API Provider IDs --}}
                <div class="col-span-2 pt-2">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">API Provider IDs</p>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['vtpass_id'=>'VTPass','clubkonnect_id'=>'Clubkonnect','easyaccess_id'=>'EasyAccess','aabaxztech_id'=>'Aabaxztech','legitdataway_id'=>'LegitDataway','globacom_id'=>'Globacom','autopilot_id'=>'AutoPilot','merrybills_product_id'=>'Merrybills Product','merrybills_id'=>'Merrybills ID'] as $field => $lbl)
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ $lbl }}</label>
                            <input type="text" name="{{ $field }}" placeholder="-"
                                   class="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white font-mono focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                        </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="0" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" checked class="rounded border-slate-300 text-green-500">
                        <span class="text-sm text-slate-600">Enabled</span>
                    </label>
                </div>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-100 flex justify-end gap-3">
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
<div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <h4 class="text-base font-bold text-slate-800">Edit Data Plan</h4>
            <button onclick="closeModal('editModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editForm" method="POST" action="" class="px-6 py-5">
            @csrf @method('PATCH')
            <input type="hidden" id="edit_network_key" name="network_key" value="{{ $network }}">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" id="edit_plan_name" name="plan_name" required
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Data Type <span class="text-red-500">*</span></label>
                    <select id="edit_data_type" name="data_type" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                        <option value="sme">SME</option>
                        <option value="gifting">Gifting</option>
                        <option value="cg">Corp. Gifting</option>
                        <option value="awoof">AWOOF</option>
                        <option value="cheap_data">Cheap Data</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Validity</label>
                    <input type="text" id="edit_validity" name="validity"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Amount (₦) <span class="text-red-500">*</span></label>
                    <input type="number" id="edit_amount" name="amount" required step="0.01" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Agent Amount (₦)</label>
                    <input type="number" id="edit_amount_agent" name="amount_agent" step="0.01" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>

                <div class="col-span-2 pt-2">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">API Provider IDs</p>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(['vtpass_id'=>'VTPass','clubkonnect_id'=>'Clubkonnect','easyaccess_id'=>'EasyAccess','aabaxztech_id'=>'Aabaxztech','legitdataway_id'=>'LegitDataway','globacom_id'=>'Globacom','autopilot_id'=>'AutoPilot','merrybills_product_id'=>'Merrybills Product','merrybills_id'=>'Merrybills ID'] as $field => $lbl)
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ $lbl }}</label>
                            <input type="text" id="edit_{{ $field }}" name="{{ $field }}"
                                   class="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white font-mono focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                        </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Sort Order</label>
                    <input type="number" id="edit_sort_order" name="sort_order" min="0"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit_enabled" name="enabled" value="1" class="rounded border-slate-300 text-green-500">
                        <span class="text-sm text-slate-600">Enabled</span>
                    </label>
                </div>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-100 flex justify-end gap-3">
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
    const baseUrl = '{{ url("admin/settings/data-plans") }}';

    function openAddModal()  { document.getElementById('addModal').classList.remove('hidden'); }
    function closeModal(id)  { document.getElementById(id).classList.add('hidden'); }

    function openEditModal(plan) {
        const fields = ['plan_name','data_type','validity','amount','amount_agent',
                        'vtpass_id','clubkonnect_id','easyaccess_id','aabaxztech_id',
                        'legitdataway_id','globacom_id','autopilot_id',
                        'merrybills_product_id','merrybills_id','sort_order'];
        fields.forEach(f => {
            const el = document.getElementById('edit_' + f);
            if (el) el.value = plan[f] ?? '';
        });
        document.getElementById('edit_enabled').checked = plan.enabled == 1 || plan.enabled === true;
        document.getElementById('edit_network_key').value = plan.network_key;
        document.getElementById('editForm').action = baseUrl + '/' + plan.id;
        document.getElementById('editModal').classList.remove('hidden');
    }

    let activeType = 'all';
    function filterType(type) {
        activeType = type;
        document.querySelectorAll('.dtype-btn').forEach(btn => {
            btn.classList.remove('bg-green-50','text-green-600');
            btn.classList.add('text-slate-500');
        });
        const active = document.getElementById('dtype-' + type);
        if (active) {
            active.classList.add('bg-green-50','text-green-600');
            active.classList.remove('text-slate-500');
        }
        document.querySelectorAll('.plan-row').forEach(row => {
            if (type === 'all' || row.dataset.type === type) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
    }

    ['addModal','editModal'].forEach(id => {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) closeModal(id);
        });
    });
</script>
@endsection

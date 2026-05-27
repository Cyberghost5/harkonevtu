@extends('layouts.admin')

@section('title', 'Account Settings')
@section('heading', 'Account Settings')
@section('subheading', 'Manage bank accounts displayed to users for manual payment')

@section('content')

{{-- Flash messages --}}
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

    {{-- Header row --}}
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-800">Bank Accounts</h3>
            <p class="text-xs text-slate-400 mt-0.5">{{ $accounts->count() }} account(s) configured</p>
        </div>
        <button onclick="openAddModal()"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-sm transition-opacity hover:opacity-90"
                style="background:{{ $themeColor }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New
        </button>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-3 text-left w-10">#</th>
                    <th class="px-6 py-3 text-left">Bank Name</th>
                    <th class="px-6 py-3 text-left">Account Number</th>
                    <th class="px-6 py-3 text-left">Name of Account</th>
                    <th class="px-6 py-3 text-left">Short Code</th>
                    <th class="px-6 py-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($accounts as $i => $account)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-3 text-slate-400">{{ $i + 1 }}</td>
                    <td class="px-6 py-3 font-medium text-slate-800">{{ $account->bank_name }}</td>
                    <td class="px-6 py-3 text-slate-600 font-mono">{{ $account->account_number }}</td>
                    <td class="px-6 py-3 text-slate-600">{{ $account->account_name }}</td>
                    <td class="px-6 py-3 text-slate-500">{{ $account->short_code ?? '—' }}</td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            <button onclick="openEditModal({{ $account->id }}, {{ json_encode($account->bank_name) }}, {{ json_encode($account->account_number) }}, {{ json_encode($account->account_name) }}, {{ json_encode($account->short_code ?? '') }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </button>
                            <form method="POST" action="{{ route('admin.settings.accounts.destroy', $account->id) }}"
                                  onsubmit="return confirm('Delete this account?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-slate-400 text-sm">
                        No bank accounts configured yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Add Modal ────────────────────────────────────────────────────────── --}}
<div id="addModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h4 class="text-base font-bold text-slate-800">Add Bank Account</h4>
            <button onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.settings.accounts.store') }}" class="px-6 py-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Bank Name <span class="text-red-500">*</span></label>
                <input type="text" name="bank_name" required
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500"
                       placeholder="e.g. Zenith Bank">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Account Number <span class="text-red-500">*</span></label>
                <input type="text" name="account_number" required maxlength="20"
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 font-mono"
                       placeholder="0123456789">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Name of Account <span class="text-red-500">*</span></label>
                <input type="text" name="account_name" required
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500"
                       placeholder="e.g. PayPulse Limited">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Short Code</label>
                <input type="text" name="short_code" maxlength="20"
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500"
                       placeholder="e.g. ZENITH">
            </div>
            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeAddModal()"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90"
                        style="background:{{ $themeColor }}">
                    Add Account
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Edit Modal ───────────────────────────────────────────────────────── --}}
<div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h4 class="text-base font-bold text-slate-800">Edit Bank Account</h4>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editForm" method="POST" action="" class="px-6 py-5 space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Bank Name <span class="text-red-500">*</span></label>
                <input type="text" id="edit_bank_name" name="bank_name" required
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Account Number <span class="text-red-500">*</span></label>
                <input type="text" id="edit_account_number" name="account_number" required maxlength="20"
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500 font-mono">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Name of Account <span class="text-red-500">*</span></label>
                <input type="text" id="edit_account_name" name="account_name" required
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Short Code</label>
                <input type="text" id="edit_short_code" name="short_code" maxlength="20"
                       class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500/30 focus:border-green-500">
            </div>
            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white rounded-xl transition-opacity hover:opacity-90"
                        style="background:{{ $themeColor }}">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const baseUrl = '{{ url("admin/settings/accounts") }}';

    function openAddModal()  { document.getElementById('addModal').classList.remove('hidden'); }
    function closeAddModal() { document.getElementById('addModal').classList.add('hidden'); }
    function closeEditModal(){ document.getElementById('editModal').classList.add('hidden'); }

    function openEditModal(id, bankName, acctNum, acctName, shortCode) {
        document.getElementById('edit_bank_name').value      = bankName;
        document.getElementById('edit_account_number').value = acctNum;
        document.getElementById('edit_account_name').value   = acctName;
        document.getElementById('edit_short_code').value     = shortCode;
        document.getElementById('editForm').action           = baseUrl + '/' + id;
        document.getElementById('editModal').classList.remove('hidden');
    }

    // Close modals on backdrop click
    ['addModal','editModal'].forEach(id => {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
    });
</script>
@endsection

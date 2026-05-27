@extends('layouts.dashboard')

@section('title', 'Manual Funding')

@section('content')

<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold font-outfit text-slate-900 dark:text-white">Manual Bank Transfer</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
        Transfer to our account, upload your proof, and we'll credit your wallet within minutes.
    </p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

    {{-- ── Left: Form ─────────────────────────────────────────────────────── --}}
    <div class="xl:col-span-3 space-y-5">

        {{-- Bank Account Card --}}
        @if($settings['bank_account_number'] ?? false)
        <div class="rounded-2xl bg-gradient-to-br from-vtu-primary to-indigo-700 p-5 text-white shadow-xl shadow-indigo-500/20 relative overflow-hidden">
            <div class="absolute -top-8 -right-8 h-32 w-32 rounded-full bg-white/10 blur-2xl"></div>
            <p class="text-xs font-semibold uppercase tracking-wider text-white/70 mb-4">Transfer to this account</p>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-white/70">Bank</span>
                    <span class="text-sm font-semibold">{{ $settings['bank_name'] ?: 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-white/70">Account Name</span>
                    <span class="text-sm font-semibold">{{ $settings['bank_account_name'] ?: 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-white/70">Account Number</span>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold font-outfit tracking-widest" id="acc-num">{{ $settings['bank_account_number'] }}</span>
                        <button onclick="copyAccNumber()" class="text-white/70 hover:text-white" title="Copy">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 p-5">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Bank details not yet configured</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">The admin has not added a bank account. Please check back shortly or use card funding.</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Upload Form --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Submit Proof of Payment</h2>
            </div>

            <form method="POST" action="{{ route('wallet.fund.manual.submit') }}" enctype="multipart/form-data" class="p-6 space-y-5">
                @csrf

                {{-- Amount --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Amount Transferred (₦)</label>
                    <input type="number" name="amount" min="500" max="1000000" value="{{ old('amount') }}"
                           placeholder="Enter the exact amount you transferred"
                           class="w-full px-4 py-3 rounded-xl border @error('amount') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 focus:border-vtu-primary transition-all">
                    @error('amount')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                {{-- Bank Reference / Narration --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Bank Reference / Narration</label>
                    <input type="text" name="bank_reference" value="{{ old('bank_reference') }}"
                           placeholder="e.g. Transfer from GTBank ending 4521"
                           class="w-full px-4 py-3 rounded-xl border @error('bank_reference') border-rose-400 @else border-slate-200 dark:border-slate-700 @enderror bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 focus:border-vtu-primary transition-all">
                    @error('bank_reference')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                {{-- Proof Image Upload --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Upload Proof (screenshot)</label>
                    <label for="proof-upload"
                           class="flex flex-col items-center justify-center w-full h-36 rounded-xl border-2 border-dashed
                                  @error('proof_image') border-rose-400 bg-rose-50 dark:bg-rose-500/5 @else border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 @enderror
                                  cursor-pointer hover:border-vtu-primary transition-colors group">
                        <div id="upload-placeholder" class="text-center px-4">
                            <svg class="h-8 w-8 text-slate-400 group-hover:text-vtu-primary mx-auto mb-2 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Click to upload or drag & drop</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">JPG, JPEG, PNG · Max 3 MB</p>
                        </div>
                        <div id="upload-preview" class="hidden text-center px-4">
                            <img id="preview-img" src="#" alt="Preview" class="h-20 w-auto rounded-lg mx-auto mb-1 object-contain">
                            <p id="preview-name" class="text-xs text-vtu-primary font-medium truncate max-w-xs"></p>
                        </div>
                        <input id="proof-upload" type="file" name="proof_image" accept="image/jpg,image/jpeg,image/png" class="hidden" onchange="previewFile(this)">
                    </label>
                    @error('proof_image')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>

                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-vtu-primary to-indigo-700 text-white font-semibold text-sm hover:from-indigo-700 hover:to-vtu-primary transition-all duration-200 shadow-lg shadow-indigo-500/20">
                    Submit Funding Request
                </button>

            </form>
        </div>

    </div>

    {{-- ── Right: Previous Requests ────────────────────────────────────────── --}}
    <div class="xl:col-span-2">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">My Requests</h2>
                <span class="text-xs text-slate-400">{{ $previousRequests->total() }} total</span>
            </div>

            @if ($previousRequests->isEmpty())
                <div class="flex flex-col items-center justify-center py-14 px-6 text-center">
                    <div class="h-12 w-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">No requests yet</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($previousRequests as $req)
                    <li class="px-5 py-3.5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">₦{{ number_format((float) $req->amount, 0) }}</span>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded
                                {{ $req->status === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400'
                                    : ($req->status === 'rejected' ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400') }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">Ref: {{ $req->bank_reference ?? '-' }}</p>
                        <p class="text-xs text-slate-400">{{ $req->created_at->format('d M Y, h:ia') }}</p>
                        @if($req->admin_note)
                        <p class="mt-1 text-xs text-rose-500 dark:text-rose-400 italic">Note: {{ $req->admin_note }}</p>
                        @endif
                    </li>
                    @endforeach
                </ul>

                @if ($previousRequests->hasPages())
                <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $previousRequests->links('pagination::simple-tailwind') }}
                </div>
                @endif
            @endif
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
    function copyAccNumber() {
        const num = document.getElementById('acc-num')?.textContent?.trim();
        if (!num) return;
        navigator.clipboard.writeText(num).then(() => {
            // Brief visual feedback
            const btn = event.currentTarget;
            btn.innerHTML = '<svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            setTimeout(() => {
                btn.innerHTML = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
            }, 1500);
        });
    }

    function previewFile(input) {
        if (!input.files || !input.files[0]) return;
        const file   = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('upload-placeholder').classList.add('hidden');
            document.getElementById('upload-preview').classList.remove('hidden');
            document.getElementById('preview-img').src  = e.target.result;
            document.getElementById('preview-name').textContent = file.name;
        };
        reader.readAsDataURL(file);
    }
</script>
@endsection

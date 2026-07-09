@extends('layouts.dashboard')

@section('title', 'KYC Verification')

@section('content')
@php
    $kycColors = [
        'pending'   => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400',
        'submitted' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
        'verified'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400',
        'rejected'  => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400',
    ];
    $status = $user->kyc_status ?? 'pending';
    $kycBadge = $kycColors[$status] ?? $kycColors['pending'];
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">KYC Verification</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Verify your identity using QoreID (NIN or BVN) to increase transaction limits and secure your account.
        </p>
    </div>

    {{-- ── 1. VERIFIED STATE ── --}}
    @if($status === 'verified')
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 text-center space-y-4 shadow-sm">
        <div class="mx-auto h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div class="space-y-1">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Identity Verified</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Your account identity verification is active and fully approved.</p>
        </div>
        <div class="pt-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $kycBadge }}">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Verified Account
            </span>
        </div>
    </div>

    {{-- ── 2. SUBMITTED / PENDING STATE ── --}}
    @elseif($status === 'submitted')
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 text-center space-y-4 shadow-sm">
        <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
            <svg class="h-8 w-8 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3 3L22 4"/>
            </svg>
        </div>
        <div class="space-y-1">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Verification Under Review</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">Your submission has been received and is being processed by the system.</p>
        </div>
        <div class="pt-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $kycBadge }}">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>Pending Review
            </span>
        </div>
    </div>

    {{-- ── 3. UNVERIFIED OR REJECTED FORM STATE ── --}}
    @else
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200 font-outfit">Identity Setup Form</h3>
            @if($status === 'rejected')
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400">
                Previous Verification Rejected
            </span>
            @endif
        </div>

        <form id="kyc-form" class="p-6 space-y-4">
            @csrf

            {{-- Error alerts --}}
            <div id="kyc-error-alert" class="hidden flex items-start gap-3 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 px-4 py-3 text-sm text-rose-700 dark:text-rose-400">
                <svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span id="kyc-error-message"></span>
            </div>

            {{-- Success Alert --}}
            <div id="kyc-success-alert" class="hidden flex items-center gap-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Verification completed successfully! Reloading page...</span>
            </div>

            {{-- Read-only name notice --}}
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-3.5 border border-slate-100 dark:border-slate-850 text-xs text-slate-500 dark:text-slate-400 space-y-1">
                <span class="font-semibold text-slate-700 dark:text-slate-350">Name on Profile:</span>
                <span class="text-sm font-bold text-slate-900 dark:text-white block mt-0.5">{{ $user->name }}</span>
                <p class="text-[10px] text-slate-400">This name will automatically be retrieved from your profile to verify your ID card.</p>
            </div>

            @php $kycFee = (float) \App\Models\AppSetting::get('kyc_fee', 0); @endphp
            @if($kycFee > 0)
            <div class="bg-indigo-50 dark:bg-indigo-950/40 rounded-xl p-3.5 border border-indigo-150 dark:border-indigo-900/50 text-xs text-indigo-700 dark:text-indigo-300 flex items-center justify-between">
                <span class="font-medium">Verification Fee:</span>
                <span class="text-sm font-bold">₦{{ number_format($kycFee, 2) }}</span>
            </div>
            @endif

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-1">
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">ID Type</label>
                    <select name="id_type" required
                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                        <option value="bvn">BVN</option>
                        <option value="nin">NIN</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">ID Number (11 digits)</label>
                    <input type="text" name="id_number" required placeholder="e.g. 12345678901" maxlength="11" pattern="\d{11}"
                           class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-vtu-primary/30">
                </div>
            </div>

            <button type="submit" id="kyc-submit-btn"
                    class="w-full py-2.5 text-sm font-semibold text-white bg-vtu-primary rounded-xl hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2">
                <span>Submit Verification</span>
            </button>
        </form>

    </div>
    @endif

</div>
@endsection

@section('scripts')
@if($status !== 'verified' && $status !== 'submitted')
<script>
    document.getElementById('kyc-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const form = e.target;
        const btn = document.getElementById('kyc-submit-btn');
        const errAlert = document.getElementById('kyc-error-alert');
        const errMsg = document.getElementById('kyc-error-message');
        const successAlert = document.getElementById('kyc-success-alert');

        errAlert.classList.add('hidden');
        errMsg.textContent = '';
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span>Verifying identity...</span>
        `;

        const formData = new FormData(form);

        try {
            const res = await fetch("{{ route('kyc.submit') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json();

            if (res.ok && data.status) {
                successAlert.classList.remove('hidden');
                form.reset();
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                errAlert.classList.remove('hidden');
                errMsg.textContent = data.message || 'Identity verification failed. Please try again.';
                btn.disabled = false;
                btn.innerHTML = `<span>Submit Verification</span>`;
            }
        } catch (err) {
            console.error('KYC submission error:', err);
            errAlert.classList.remove('hidden');
            errMsg.textContent = 'A network or system error occurred. Please try again later.';
            btn.disabled = false;
            btn.innerHTML = `<span>Submit Verification</span>`;
        }
    });
</script>
@endif
@endsection

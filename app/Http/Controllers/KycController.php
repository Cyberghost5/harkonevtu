<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\WalletTransaction;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KycController extends Controller
{
    public function __construct(
        protected KycService $kycService
    ) {}

    /**
     * Render the KYC status or submission page.
     */
    public function index(): View
    {
        $user = auth()->user();
        return view('kyc.index', compact('user'));
    }

    /**
     * Handle identity verification form submissions.
     */
    public function submit(Request $request): JsonResponse
    {
        $user = auth()->user();
        $reference = 'KYC-' . strtoupper(uniqid());

        if ($user->kyc_status === 'verified') {
            return response()->json([
                'status'  => false,
                'message' => 'Your identity is already verified.'
            ], 422);
        }

        $request->validate([
            'id_type'   => ['required', Rule::in(['bvn', 'nin'])],
            'id_number' => ['required', 'string', 'numeric', 'digits:11'],
        ]);

        $parts = explode(' ', trim($user->name), 2);
        $firstname = trim($parts[0] ?? '');
        $lastname  = trim($parts[1] ?? '');

        if (empty($firstname) || empty($lastname)) {
            return response()->json([
                'status'  => false,
                'message' => 'Please update your full name (First and Last Name separated by a space) in profile settings before verifying KYC.'
            ], 422);
        }

        $fee = (float) AppSetting::get('kyc_fee', 0);
        $wallet = $user->wallet;

        if ($fee > 0) {
            if (!$wallet || $wallet->balance < $fee) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Insufficient wallet balance. You need ₦' . number_format($fee, 2) . ' in your account to verify your KYC.'
                ], 422);
            }

            DB::transaction(function () use ($user, $wallet, $fee, $request, $reference) {
                $before = $wallet->balance;
                $wallet->decrement('balance', $fee);
                $wallet->increment('total_spent', $fee);

                WalletTransaction::create([
                    'user_id'        => $user->id,
                    'wallet_id'      => $wallet->id,
                    'type'           => 'debit',
                    'amount'         => $fee,
                    'balance_before' => $before,
                    'balance_after'  => $wallet->balance,
                    'description'    => 'KYC Verification Fee (' . strtoupper($request->id_type) . ')',
                    'reference'      => $reference,
                    'status'         => 'success',
                ]);
            });
        }

        // Delegate identity verification to unified KycService (PayScribe or QoreID)
        $result = $this->kycService->verifyIdentity(
            $request->id_type,
            $request->id_number,
            $firstname,
            $lastname,
            $user
        );

        if ($result['status']) {
            $user->update(['kyc_status' => 'verified']);

            // Auto-generate Dedicated Virtual Accounts (DVA) if BVN was submitted
            $dvaAccounts = [];
            if ($request->id_type === 'bvn') {
                try {
                    $dvaResult = \App\Services\VirtualAccountService::generateDva($user, $request->id_number);
                    $dvaAccounts = $dvaResult['accounts'] ?? [];
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('DVA auto-generation on KYC error: ' . $e->getMessage());
                }
            }

            return response()->json([
                'status'       => true,
                'message'      => $result['message'] ?? 'Identity verified successfully.',
                'provider'     => $result['provider'] ?? 'kyc',
                'fee_charged'  => $fee,
                'dva_accounts' => $dvaAccounts,
                'data'         => $result['data'] ?? null,
            ]);
        }

        $user->update(['kyc_status' => 'rejected']);
        return response()->json([
            'status'   => false,
            'message'  => $result['message'] ?? 'Identity verification failed.',
            'provider' => $result['provider'] ?? 'kyc',
        ], 422);
    }
}

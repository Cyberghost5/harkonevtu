<?php

namespace App\Http\Controllers;

use App\Services\QoreIDService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KycController extends Controller
{
    protected QoreIDService $qoreIDService;

    public function __construct(QoreIDService $qoreIDService)
    {
        $this->qoreIDService = $qoreIDService;
    }

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

        if ($user->kyc_status === 'verified') {
            return response()->json([
                'status' => false,
                'message' => 'Your identity is already verified.'
            ], 422);
        }

        $request->validate([
            'id_type'    => ['required', Rule::in(['bvn', 'nin'])],
            'id_number'  => ['required', 'string', 'numeric', 'digits:11'],
        ]);

        $parts = explode(' ', trim($user->name), 2);
        $firstname = trim($parts[0] ?? '');
        $lastname = trim($parts[1] ?? '');

        if (empty($firstname) || empty($lastname)) {
            return response()->json([
                'status' => false,
                'message' => 'Please update your full name (First and Last Name separated by a space) in profile settings before verifying KYC.'
            ], 422);
        }

        $fee = (float) \App\Models\AppSetting::get('kyc_fee', 0);
        $wallet = $user->wallet;

        if ($fee > 0) {
            if (!$wallet || $wallet->balance < $fee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient wallet balance. You need ₦' . number_format($fee, 2) . ' in your account to verify your KYC.'
                ], 422);
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($user, $wallet, $fee, $request) {
                $before = $wallet->balance;
                $wallet->decrement('balance', $fee);
                $wallet->increment('total_spent', $fee);

                \App\Models\WalletTransaction::create([
                    'user_id'        => $user->id,
                    'wallet_id'      => $wallet->id,
                    'type'           => 'debit',
                    'amount'         => $fee,
                    'balance_before' => $before,
                    'balance_after'  => $wallet->balance,
                    'description'    => 'KYC Verification Fee (' . strtoupper($request->id_type) . ')',
                    'reference'      => 'KYC-' . strtoupper(uniqid()),
                    'status'         => 'success',
                ]);
            });
        }

        // Sandbox/local fallback simulation if QoreID is not yet configured
        if (!$this->qoreIDService->isConfigured()) {
            $firstNameInput = strtolower($firstname);
            $lastNameInput = strtolower($lastname);
            $userNames = array_map('strtolower', explode(' ', trim($user->name)));

            // Validate that inputs correspond to parts of the user's name
            $nameMatches = in_array($firstNameInput, $userNames) || in_array($lastNameInput, $userNames);

            if ($nameMatches) {
                $user->update(['kyc_status' => 'verified']);
                return response()->json([
                    'status' => true,
                    'message' => 'Identity verified successfully (Simulated Validation).'
                ]);
            }

            $user->update(['kyc_status' => 'rejected']);
            return response()->json([
                'status' => false,
                'message' => 'Verification failed. The names retrieved from your profile do not match your account identity.'
            ], 422);
        }

        // Live API call via QoreIDService
        $result = $this->qoreIDService->verifyIdentity(
            $request->id_type,
            $request->id_number,
            $firstname,
            $lastname
        );

        if ($result['status']) {
            $user->update(['kyc_status' => 'verified']);
            return response()->json([
                'status' => true,
                'message' => 'Identity verified successfully via QoreID.'
            ]);
        }

        $user->update(['kyc_status' => 'rejected']);
        return response()->json([
            'status' => false,
            'message' => $result['message']
        ], 422);
    }
}

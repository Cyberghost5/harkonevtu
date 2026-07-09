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
            'firstname'  => ['required', 'string', 'max:100'],
            'lastname'   => ['required', 'string', 'max:100'],
        ]);

        // Sandbox/local fallback simulation if QoreID is not yet configured
        if (!$this->qoreIDService->isConfigured()) {
            $firstNameInput = strtolower(trim($request->firstname));
            $lastNameInput = strtolower(trim($request->lastname));
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
                'message' => 'Verification failed. The names provided do not match your account profile.'
            ], 422);
        }

        // Live API call via QoreIDService
        $result = $this->qoreIDService->verifyIdentity(
            $request->id_type,
            $request->id_number,
            $request->firstname,
            $request->lastname
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

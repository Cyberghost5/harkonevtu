<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PinController extends Controller
{
    public function showSetup(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasPinSet()) {
            return redirect()->route('dashboard');
        }

        return view('auth.setup-pin', compact('user'));
    }

    public function storePin(Request $request): RedirectResponse
    {
        $request->validate([
            'pin'              => ['required', 'digits:4'],
            'pin_confirmation' => ['required', 'same:pin'],
        ], [
            'pin.digits'              => 'PIN must be exactly 4 digits.',
            'pin_confirmation.same'   => 'PINs do not match. Please try again.',
        ]);

        $user = auth()->user();

        if ($user->hasPinSet()) {
            return redirect()->route('dashboard');
        }

        $user->transaction_pin = Hash::make($request->pin);
        $user->save();

        return redirect()->route('dashboard')
            ->with('success', 'Transaction PIN created successfully. Your account is now fully set up!');
    }

    /**
     * AJAX: verify the user's transaction PIN before a sensitive operation.
     * Returns JSON - used by the global PIN confirmation modal.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => ['required', 'digits:4'],
        ]);

        $user = auth()->user();

        if (!$user->verifyPin($request->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect PIN. Please try again.',
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}

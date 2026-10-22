<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileCompletionController extends Controller
{
    /**
     * Show the profile completion form for users missing phone or PIN.
     */
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // If user already has both phone and PIN, redirect to dashboard
        if (!empty($user->phone) && $user->hasPinSet()) {
            return redirect()->route('dashboard');
        }

        return view('auth.complete-profile', [
            'user'     => $user,
            'refCode'  => session('ref_code') ?: $user->referred_by,
            'siteName' => AppSetting::get('site_name', config('app.name')),
        ]);
    }

    /**
     * Store the phone number and 4-digit transaction PIN.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $rules = [];
        $messages = [];

        // Validate phone if missing
        if (empty($user->phone)) {
            $rules['phone'] = [
                'required',
                'string',
                'regex:/^0[789]\d{9}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ];
            $messages['phone.required'] = 'Please enter your phone number.';
            $messages['phone.regex']    = 'Please enter a valid 11-digit Nigerian phone number (e.g. 08012345678).';
            $messages['phone.unique']   = 'This phone number is already registered to another account.';
        }

        // Validate PIN if not set
        if (!$user->hasPinSet()) {
            $rules['pin'] = ['required', 'digits:4'];
            $rules['pin_confirmation'] = ['required', 'same:pin'];
            $messages['pin.required']              = 'Please choose a 4-digit transaction PIN.';
            $messages['pin.digits']                = 'Transaction PIN must be exactly 4 digits.';
            $messages['pin_confirmation.required'] = 'Please confirm your transaction PIN.';
            $messages['pin_confirmation.same']     = 'Transaction PINs do not match. Please try again.';
        }

        // Optional referral code if user does not already have a referrer
        if (empty($user->referred_by)) {
            $rules['referral_code'] = [
                'nullable',
                'string',
                'max:10',
                Rule::exists('users', 'referral_code')->where(fn ($q) => $q->where('id', '!=', $user->id)),
            ];
            $messages['referral_code.exists'] = 'The referral code entered does not exist or is invalid.';
        }

        $validated = $request->validate($rules, $messages);

        if (empty($user->phone) && isset($validated['phone'])) {
            $user->phone = $validated['phone'];
            // If OTP verification is not required by admin, mark phone as verified
            if (AppSetting::get('otp_verification', '0') === '0') {
                $user->phone_verified_at = now();
            }
        }

        if (!$user->hasPinSet() && isset($validated['pin'])) {
            $user->transaction_pin = Hash::make($validated['pin']);
        }

        if (empty($user->referred_by)) {
            $ref = $request->input('referral_code') ?: session('ref_code');
            if (!empty($ref)) {
                $refCode = strtoupper(trim($ref));
                $validReferrer = User::where('referral_code', $refCode)->where('id', '!=', $user->id)->first();
                if ($validReferrer) {
                    $user->referred_by = $validReferrer->referral_code;
                }
            }
        }

        // Ensure user has their own referral code
        if (empty($user->referral_code)) {
            do {
                $myRef = strtoupper(Str::random(8));
            } while (User::where('referral_code', $myRef)->exists());
            $user->referral_code = $myRef;
        }

        $user->save();
        session()->forget('ref_code');

        $siteName = AppSetting::get('site_name', config('app.name'));

        return redirect()->route('dashboard')
            ->with('success', 'Profile completed successfully! Welcome to ' . $siteName . '.');
    }
}

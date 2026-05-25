<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VerificationController extends Controller
{
    // ─── Email ────────────────────────────────────────────────────────────────

    public function notice(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email', compact('user'));
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->fulfill(); // marks email as verified + fires Verified event

        return redirect()->route('dashboard')->with('success', 'Email verified successfully. Welcome to PayPulse!');
    }

    public function resendEmail(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'A new verification link has been sent to your email address.');
    }

    // ─── Phone ────────────────────────────────────────────────────────────────

    public function showPhoneVerification(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('dashboard');
        }

        // Auto-send OTP on first load if none exists
        if (!Cache::has('phone_otp_' . $user->id)) {
            $this->dispatchOtp($user);
        }

        return view('auth.verify-phone', ['user' => $user, 'phone' => $user->phone]);
    }

    public function sendPhoneOtp(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('dashboard');
        }

        // Rate limit: 1 OTP per 60 seconds
        $rateLimitKey = 'otp_rate_' . $user->id;
        if (Cache::has($rateLimitKey)) {
            return back()->with('error', 'Please wait before requesting another OTP.');
        }

        $this->dispatchOtp($user);
        Cache::put($rateLimitKey, true, 60);

        return back()->with('success', 'A new OTP has been sent to ' . $user->phone);
    }

    public function verifyPhone(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $user   = auth()->user();
        $stored = Cache::get('phone_otp_' . $user->id);

        if (!$stored || $stored !== $request->otp) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.']);
        }

        $user->phone_verified_at = now();
        $user->save();

        Cache::forget('phone_otp_' . $user->id);

        return redirect()->route('dashboard')
            ->with('success', 'Phone number verified successfully!');
    }

    // ─── OTP Dispatch ─────────────────────────────────────────────────────────

    private function dispatchOtp(\App\Models\User $user): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 10 minutes
        Cache::put('phone_otp_' . $user->id, $otp, now()->addMinutes(10));

        // In production, replace this with your SMS provider (e.g. Termii, Twilio)
        Log::info('[PayPulse OTP] Phone: ' . $user->phone . ' | OTP: ' . $otp);

        // TODO: Send via SMS provider
        // Http::post('https://api.ng.termii.com/api/sms/send', [...]);
    }
}

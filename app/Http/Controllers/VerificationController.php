<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

        return redirect()->route('dashboard')->with('success', 'Email verified successfully. Welcome to ' . \App\Models\AppSetting::get('site_name', config('app.name')) . '!');
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

        // Always log the OTP locally for reference/fallback
        Log::info('[PayPulse OTP] Phone: ' . $user->phone . ' | OTP: ' . $otp);

        $apiKey = AppSetting::get('bulksms_api_key');
        $sender = AppSetting::get('bulksms_sender') ?: AppSetting::get('site_name', 'PayPulse');
        if (strlen($sender) > 11) {
            $sender = substr($sender, 0, 11);
        }

        if ($apiKey) {
            $phone = $user->phone;
            // Format phone to international (e.g. 23480...)
            if (str_starts_with($phone, '0')) {
                $phone = '234' . substr($phone, 1);
            } elseif (str_starts_with($phone, '+')) {
                $phone = substr($phone, 1);
            }

            $message = "Your " . AppSetting::get('site_name', 'PayPulse') . " verification code is: " . $otp;

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])->post('https://www.bulksmsnigeria.com/api/v2/sms', [
                    'from' => $sender,
                    'to' => $phone,
                    'body' => $message,
                ]);

                if ($response->failed() || ($response->json('status') !== 'success' && $response->json('data.status') !== 'success')) {
                    Log::error('BulkSMS Nigeria OTP delivery failed', [
                        'status' => $response->status(),
                        'response' => $response->json(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('BulkSMS Nigeria API request exception', [
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::warning('BulkSMS API key not set. Logged OTP: ' . $otp);
        }
    }
}

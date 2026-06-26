<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        // If admin has disabled email verification, pass through
        if (AppSetting::get('email_verification', '1') !== '1') {
            return $next($request);
        }

        $user = $request->user();

        if (!$user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your email address is not verified.'], 403);
            }
            return redirect()->route('verification.notice');
        }

        // If phone/OTP verification is enabled, enforce it
        if (AppSetting::get('otp_verification', '0') === '1') {
            if (!$user->hasVerifiedPhone()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your phone number is not verified.',
                        'requires_phone_verification' => true
                    ], 403);
                }
                return redirect()->route('verification.phone')
                    ->with('info', 'Please verify your phone number to continue.');
            }
        }

        return $next($request);
    }
}

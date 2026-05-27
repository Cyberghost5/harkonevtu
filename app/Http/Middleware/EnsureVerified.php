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

        return $next($request);
    }
}

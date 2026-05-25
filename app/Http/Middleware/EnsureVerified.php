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
        $required = AppSetting::get('require_verification', 'none');

        if ($required === 'none') {
            return $next($request);
        }

        $user = $request->user();

        if ($required === 'email' && !$user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your email address is not verified.'], 403);
            }
            return redirect()->route('verification.notice');
        }

        if ($required === 'phone' && !$user->hasVerifiedPhone()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your phone number is not verified.'], 403);
            }
            return redirect()->route('verification.phone');
        }

        return $next($request);
    }
}

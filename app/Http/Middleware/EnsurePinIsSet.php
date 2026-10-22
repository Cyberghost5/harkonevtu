<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePinIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (empty($user->phone)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'                      => false,
                    'message'                     => 'Profile completion required: phone number is missing.',
                    'requires_profile_completion' => true,
                    'is_phone_set'                => false,
                    'is_pin_set'                  => $user->hasPinSet(),
                ], 403);
            }
            return redirect()->route('profile.complete')
                ->with('info', 'Please complete your profile by providing your phone number and transaction PIN.');
        }

        if (!$user->hasPinSet()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'             => false,
                    'message'            => 'Transaction PIN not set.',
                    'requires_pin_setup' => true,
                    'is_phone_set'       => true,
                    'is_pin_set'         => false,
                ], 403);
            }
            return redirect()->route('pin.setup')
                ->with('info', 'Please set up your transaction PIN to continue.');
        }

        return $next($request);
    }
}

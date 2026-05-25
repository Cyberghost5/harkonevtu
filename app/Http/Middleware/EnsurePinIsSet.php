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

        if (!$user->hasPinSet()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message'       => 'Transaction PIN not set.',
                    'requires_pin_setup' => true,
                ], 403);
            }
            return redirect()->route('pin.setup')
                ->with('info', 'Please set up your transaction PIN to continue.');
        }

        return $next($request);
    }
}

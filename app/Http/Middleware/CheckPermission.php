<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Must be an admin
        if (!$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['status' => false, 'message' => 'Unauthorized. Admin privileges required.'], 403);
            }
            abort(403, 'Unauthorized. Admin privileges required.');
        }

        // Super Admin bypasses all checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // If no specific permissions were passed, allow basic admin access
        if (empty($permissions)) {
            return $next($request);
        }

        // Check if user has at least one of the required permissions
        foreach ($permissions as $perm) {
            $subPerms = explode('|', $perm);
            foreach ($subPerms as $sp) {
                if ($user->hasPermission(trim($sp))) {
                    return $next($request);
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => false,
                'message' => 'You do not have permission to access this resource or perform this action.',
            ], 403);
        }

        return redirect()->route('admin.dashboard')->with('error', 'You do not have permission to access that section.');
    }
}

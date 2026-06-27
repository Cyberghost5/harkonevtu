<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AppSetting;

class EnsureNotLocked
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $currentRoute = $request->route() ? $request->route()->getName() : null;
            $excludedRoutes = [
                'lockscreen',
                'lockscreen.unlock',
                'lockscreen.biometric.options',
                'lockscreen.biometric.verify',
                'logout',
            ];

            if (in_array($currentRoute, $excludedRoutes)) {
                return $next($request);
            }

            // 1. Check if explicitly marked as locked
            if (session('session_locked')) {
                return redirect()->route('lockscreen');
            }

            // 2. Check for inactivity timeout
            $lastActivity = session('last_activity');
            if ($lastActivity) {
                try {
                    $lastActivity = \Carbon\Carbon::parse($lastActivity);
                } catch (\Exception $e) {
                    $lastActivity = null;
                }
            }
            $timeoutMinutes = (int) AppSetting::get('session_idle_timeout', 5);

            if ($lastActivity) {
                $elapsedMinutes = (now()->timestamp - $lastActivity->timestamp) / 60;

                if ($elapsedMinutes >= $timeoutMinutes) {
                    session(['session_locked' => true]);
                    return redirect()->route('lockscreen');
                }
            }

            // Update activity time
            session(['last_activity' => now()]);
        }

        return $next($request);
    }
}

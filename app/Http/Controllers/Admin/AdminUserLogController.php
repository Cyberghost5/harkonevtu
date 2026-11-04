<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLoginLog;
use Illuminate\Http\Request;

class AdminUserLogController extends Controller
{
    public function index(Request $request)
    {
        // Stealth Filter: Completely exclude all Super Admin logs and identifiers
        $superAdminUsers = User::where('is_super_admin', true)->get(['id', 'email', 'username']);
        $superAdminUserIds = $superAdminUsers->pluck('id')->all();
        $superAdminIdentifiers = $superAdminUsers
            ->flatMap(fn($u) => [$u->email, $u->username])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $applyStealthFilter = function ($q) use ($superAdminUserIds, $superAdminIdentifiers) {
            if (!empty($superAdminUserIds)) {
                $q->where(function ($sub) use ($superAdminUserIds) {
                    $sub->whereNotIn('user_id', $superAdminUserIds)
                        ->orWhereNull('user_id');
                });
            }
            $q->whereDoesntHave('user', fn($uq) => $uq->where('is_super_admin', true));
            if (!empty($superAdminIdentifiers)) {
                $q->whereNotIn('email_or_username', $superAdminIdentifiers);
            }
        };

        $query = UserLoginLog::with('user')
            ->where($applyStealthFilter)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('email_or_username', 'like', "%$s%")
                  ->orWhere('ip_address', 'like', "%$s%")
                  ->orWhereHas('user', function ($uq) use ($s) {
                      $uq->where('name', 'like', "%$s%")
                        ->orWhere('email', 'like', "%$s%")
                        ->orWhere('username', 'like', "%$s%");
                  });
            });
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(30)->withQueryString();

        // Summary metrics (filtered to exclude super admin activity)
        $todayCount  = UserLoginLog::where($applyStealthFilter)->whereDate('created_at', today())->count();
        $webCount    = UserLoginLog::where($applyStealthFilter)->where('channel', 'web')->count();
        $mobileCount = UserLoginLog::where($applyStealthFilter)->where('channel', 'mobile')->count();
        $failedCount = UserLoginLog::where($applyStealthFilter)->where('status', 'failed')->count();

        return view('admin.user-logs.index', compact('logs', 'todayCount', 'webCount', 'mobileCount', 'failedCount'));
    }
}

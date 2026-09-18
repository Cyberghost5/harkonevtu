<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserLoginLog;
use Illuminate\Http\Request;

class AdminUserLogController extends Controller
{
    public function index(Request $request)
    {
        $query = UserLoginLog::with('user')->orderByDesc('created_at');

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

        // Summary metrics
        $todayCount  = UserLoginLog::whereDate('created_at', today())->count();
        $webCount    = UserLoginLog::where('channel', 'web')->count();
        $mobileCount = UserLoginLog::where('channel', 'mobile')->count();
        $failedCount = UserLoginLog::where('status', 'failed')->count();

        return view('admin.user-logs.index', compact('logs', 'todayCount', 'webCount', 'mobileCount', 'failedCount'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Http\Request;

class AdminApiLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiLog::with('user')->orderByDesc('created_at');

        if ($request->filled('service')) {
            $query->where('service', $request->service);
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->filled('status')) {
            $query->where('success', $request->status === 'success');
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                  ->orWhere('endpoint', 'like', "%$s%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(30)->withQueryString();

        $services  = ApiLog::select('service')->distinct()->pluck('service');
        $providers = ApiLog::select('provider')->distinct()->pluck('provider');

        return view('admin.api-logs.index', compact('logs', 'services', 'providers'));
    }
}

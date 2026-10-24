<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\FundingRequest;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'     => User::where('is_admin', false)->count(),
            'total_admins'    => User::where('is_admin', true)->count(),
            'active_users'    => User::where('is_admin', false)->where('is_active', true)->count(),
            'total_revenue'   => (float) ServiceTransaction::where('status', 'success')->sum('amount'),
            'today_revenue'   => (float) ServiceTransaction::where('status', 'success')
                                     ->where('created_at', '>=', now()->startOfDay())->sum('amount'),
            'users_balance'   => (float) \App\Models\Wallet::whereHas('user', function ($q) {
                                     $q->where('is_admin', false);
                                 })->sum('balance'),
            'pending_funding' => FundingRequest::where('status', 'pending')->count(),
            'total_tx'        => ServiceTransaction::count(),
            'today_tx'        => ServiceTransaction::where('created_at', '>=', now()->startOfDay())->count(),
            'failed_tx'       => ServiceTransaction::where('status', 'failed')
                                     ->where('created_at', '>=', now()->startOfDay())->count(),
        ];

        // Revenue by service (All-time, matching "Total Spendings / All-time platform spending" on the card)
        $revenueByService = ServiceTransaction::where('status', 'success')
            ->select('service_type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('service_type')
            ->get()
            ->keyBy('service_type');

        // Recent transactions
        $recentTransactions = ServiceTransaction::with('user')
            ->latest()->take(8)->get();

        // Recent users (latest 8)
        $recentUsers = User::where('is_admin', false)
            ->latest()->take(8)->get();

        // Daily revenue for last 7 days
        $dailyRevenue = ServiceTransaction::where('status', 'success')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        return view('admin.dashboard', compact(
            'stats', 'revenueByService', 'recentTransactions', 'recentUsers', 'dailyRevenue'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Defensive: ensure wallet exists for users created before wallet system
        if (!$user->wallet) {
            $user->wallet()->create([
                'balance'      => 0.00,
                'total_funded' => 0.00,
                'total_spent'  => 0.00,
            ]);
            $user->refresh();
        }

        $wallet         = $user->wallet;
        $totalReferrals = User::where('referred_by', $user->referral_code)->count();
        $recentTx       = $wallet->transactions()->latest()->take(5)->get();

        return view('dashboard', compact('user', 'wallet', 'totalReferrals', 'recentTx'));
    }
}

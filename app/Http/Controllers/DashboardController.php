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

        // Collect references of all refund credits so we can hide both the
        // refund entry itself and the matching orphaned failed debit.
        $refundedOriginalRefs = $wallet->transactions()
            ->where('reference', 'like', 'REFUND_%')
            ->pluck('reference')
            ->map(fn ($r) => substr($r, 7)) // strip "REFUND_" prefix
            ->values()
            ->all();

        $recentTx = $wallet->transactions()
            ->where('reference', 'not like', 'REFUND_%')          // hide refund credits
            ->when($refundedOriginalRefs, fn ($q) =>
                $q->whereNotIn('reference', $refundedOriginalRefs) // hide orphaned failed debits
            )
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('user', 'wallet', 'totalReferrals', 'recentTx'));
    }
}

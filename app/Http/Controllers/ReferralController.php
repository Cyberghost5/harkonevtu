<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(): View
    {
        $user     = auth()->user();
        $settings = AppSetting::getMany([
            'referral_commission',
            'referral_min_withdrawal',
            'referral_min_total_spent',
        ]);

        $referrals = User::where('referred_by', $user->referral_code)
            ->with('wallet')
            ->latest()
            ->get();

        $referralLink    = url('/register?ref=' . $user->referral_code);
        $referralBalance = (float) ($user->wallet->referral_balance ?? 0);
        $minWithdrawal   = (float) ($settings['referral_min_withdrawal'] ?? 0);
        $canWithdraw     = $referralBalance >= $minWithdrawal && $minWithdrawal > 0;

        return view('referral.index', compact(
            'user',
            'settings',
            'referrals',
            'referralLink',
            'referralBalance',
            'minWithdrawal',
            'canWithdraw',
        ));
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $user            = auth()->user();
        $referralBalance = (float) ($user->wallet->referral_balance ?? 0);
        $minWithdrawal   = (float) AppSetting::get('referral_min_withdrawal', 0);

        if ($referralBalance <= 0) {
            return back()->with('error', 'You have no referral balance to withdraw.');
        }

        if ($referralBalance < $minWithdrawal) {
            return back()->with('error',
                'Minimum withdrawal is ₦' . number_format($minWithdrawal, 2) .
                '. Your balance is ₦' . number_format($referralBalance, 2) . '.'
            );
        }

        try {
            DB::transaction(function () use ($user, $referralBalance) {
                $user->wallet->withdrawReferral($referralBalance);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Withdrawal failed. Please try again.');
        }

        return back()->with('success',
            '₦' . number_format($referralBalance, 2) . ' has been moved to your main wallet.'
        );
    }
}

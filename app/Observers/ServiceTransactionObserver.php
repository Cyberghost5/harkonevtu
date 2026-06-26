<?php

namespace App\Observers;

use App\Models\AppSetting;
use App\Models\ServiceTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceTransactionObserver
{
    /**
     * After every successful service transaction, check whether the referred
     * user's total_spent has now crossed the threshold that unlocks the referrer's
     * one-time commission.
     */
    public function created(ServiceTransaction $transaction): void
    {
        // Dispatch push notification to user on success
        if ($transaction->status === 'success') {
            $formattedService = match($transaction->service_type) {
                'epin' => 'Exam Pin',
                'cable' => 'Cable TV',
                default => ucfirst($transaction->service_type)
            };
            $title = $formattedService . ' Purchase Success';
            $message = "Your purchase of " . $formattedService . " (₦" . number_format((float) $transaction->amount, 2) . ") for " . $transaction->recipient . " was successful.";
            
            \App\Services\OneSignalService::sendNotificationToUser((string) $transaction->user_id, $title, $message);
        }

        $user = $transaction->user ?? User::find($transaction->user_id);

        if (!$user || !$user->referred_by || $user->referral_commission_paid) {
            return; // not referred, or commission already paid
        }

        $minSpent  = (float) AppSetting::get('referral_min_total_spent', 0);
        $commission = (float) AppSetting::get('referral_commission', 0);

        if ($minSpent <= 0 || $commission <= 0) {
            return; // referral program not configured
        }

        $totalSpent = (float) ($user->wallet?->total_spent ?? 0);

        if ($totalSpent < $minSpent) {
            return; // threshold not yet reached
        }

        // Find the referrer by their referral_code
        $referrer = User::where('referral_code', $user->referred_by)->first();

        if (!$referrer || !$referrer->wallet) {
            return;
        }

        try {
            DB::transaction(function () use ($user, $referrer, $commission) {
                // Credit referrer's referral balance
                $referrer->wallet->creditReferral($commission);

                // Mark commission as paid so it never fires again for this user
                $user->referral_commission_paid = true;
                $user->saveQuietly();
            });
        } catch (\Throwable $e) {
            Log::error('Referral commission credit failed', [
                'referred_user_id' => $user->id,
                'referrer_id'      => $referrer->id,
                'commission'       => $commission,
                'error'            => $e->getMessage(),
            ]);
        }
    }
}

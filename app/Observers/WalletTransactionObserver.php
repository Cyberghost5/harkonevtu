<?php

namespace App\Observers;

use App\Models\WalletTransaction;
use App\Services\OneSignalService;

class WalletTransactionObserver
{
    /**
     * Handle the WalletTransaction "created" event.
     */
    public function created(WalletTransaction $transaction): void
    {
        $amount = (float) $transaction->amount;
        $formattedAmount = '₦' . number_format($amount, 2);
        $formattedBalance = '₦' . number_format((float) $transaction->balance_after, 2);

        $title = '';
        $message = '';

        if ($transaction->type === 'credit') {
            $title = 'Wallet Credited';
            $message = "Your wallet has been credited with {$formattedAmount}. New balance: {$formattedBalance}.";
        } elseif ($transaction->type === 'debit') {
            // Service purchases are handled separately with specific service details
            if (isset($transaction->metadata['service'])) {
                return;
            }
            $title = 'Wallet Debited';
            $message = "Your wallet has been debited with {$formattedAmount}. New balance: {$formattedBalance}. Details: {$transaction->description}";
        } else {
            return;
        }

        OneSignalService::sendNotificationToUser((string) $transaction->user_id, $title, $message);
    }
}

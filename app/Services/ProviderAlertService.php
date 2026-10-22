<?php

namespace App\Services;

use App\Mail\ProviderLowBalanceAlertMail;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProviderAlertService
{
    /**
     * Regular expression patterns that indicate an upstream provider's
     * balance, float, or wallet is exhausted or insufficient.
     */
    protected static array $lowBalancePatterns = [
        '/insufficient\s+(funds?|balance|credit|amount)/i',
        '/(low|depleted)\s+(wallet|account|provider|available)?\s*(balance|credit|funds?)/i',
        '/available\s*\+\s*facility\s+balance/i',
        '/facility\s+balance/i',
        '/balance\s+is\s+(too\s+)?(low|zero|insufficient)/i',
        '/wallet\s+balance\s+is\s+(less\s+than|below|zero|insufficient)/i',
        '/below\s+(minimum|required)\s+balance/i',
        '/not\s+enough\s+(funds?|balance|credit)/i',
        '/partner\s+balance.*(low|insufficient)/i',
        '/insufficient\s+account\s+balance/i',
    ];

    /**
     * Detect if a provider error message or payload represents an insufficient funds issue.
     */
    public static function isLowBalance(mixed $rawMessage): bool
    {
        if (empty($rawMessage)) {
            return false;
        }

        $textToInspect = '';

        if (is_string($rawMessage)) {
            $textToInspect = $rawMessage;
        } elseif (is_array($rawMessage) || is_object($rawMessage)) {
            $textToInspect = json_encode($rawMessage, JSON_UNESCAPED_SLASHES);
        }

        foreach (self::$lowBalancePatterns as $pattern) {
            if (preg_match($pattern, $textToInspect)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a user-friendly, non-confusing error message for the customer.
     */
    public static function getUserFriendlyMessage(bool $refunded = true): string
    {
        if ($refunded) {
            return 'This service is temporarily unavailable due to a provider gateway issue. Please try again shortly. Your wallet balance has been refunded.';
        }

        return 'This service is temporarily unavailable due to a provider gateway issue. Please try again shortly. Your wallet has not been charged.';
    }

    /**
     * Dispatch an urgent Push Notification and Email to all admins alerting them
     * that a customer purchase failed because the provider float/balance is empty.
     */
    public static function notifyLowBalance(array $details): void
    {
        try {
            $service   = (string) ($details['service'] ?? 'VTU Service');
            $provider  = (string) ($details['provider'] ?? 'Provider');
            $amount    = (float)  ($details['amount'] ?? 0);
            $recipient = (string) ($details['recipient'] ?? 'N/A');
            $reference = (string) ($details['reference'] ?? 'N/A');
            $rawError  = $details['raw_error'] ?? 'Insufficient Funds';

            // Throttle duplicate alerts per provider + service to prevent alert storms (3 minutes throttle)
            $cacheKey = 'provider_low_balance_alert_' . md5(strtolower("{$provider}_{$service}"));
            if (Cache::has($cacheKey)) {
                Log::warning("Provider low balance alert throttled for {$provider} - {$service}", [
                    'provider'  => $provider,
                    'service'   => $service,
                    'reference' => $reference,
                    'amount'    => $amount,
                ]);
                return;
            }

            // Lock for 3 minutes (180 seconds)
            Cache::put($cacheKey, true, now()->addMinutes(3));

            // 1. Send OneSignal Push Notification to all admins
            $adminIds = User::where('is_admin', true)->pluck('id')->all();
            if (!empty($adminIds)) {
                $pushTitle = '⚠️ Low Provider Balance Alert!';
                $pushBody  = "Provider '{$provider}' has insufficient funds for {$service} (₦" . number_format($amount, 2) . '). Please top up your provider wallet.';

                OneSignalService::sendNotificationToUsers($adminIds, $pushTitle, $pushBody, [
                    'type'      => 'provider_low_balance',
                    'service'   => $service,
                    'provider'  => $provider,
                    'amount'    => $amount,
                    'reference' => $reference,
                ]);
            }

            // 2. Prepare customer details for the admin email
            $userObj = $details['user'] ?? auth()->user();
            $customerName    = 'Customer';
            $customerEmail   = 'N/A';
            $customerPhone   = 'N/A';
            $customerId      = 'N/A';
            $customerBalance = 0.0;

            if ($userObj) {
                if (is_object($userObj)) {
                    $customerName    = method_exists($userObj, 'displayName') ? $userObj->displayName() : ($userObj->name ?? 'Customer');
                    $customerEmail   = $userObj->email ?? 'N/A';
                    $customerPhone   = $userObj->phone ?? 'N/A';
                    $customerId      = (string) ($userObj->id ?? 'N/A');
                    if (method_exists($userObj, 'wallet') && $userObj->wallet) {
                        $customerBalance = (float) $userObj->wallet->balance;
                    } elseif (isset($userObj->balance)) {
                        $customerBalance = (float) $userObj->balance;
                    }
                } elseif (is_array($userObj)) {
                    $customerName    = $userObj['name'] ?? 'Customer';
                    $customerEmail   = $userObj['email'] ?? 'N/A';
                    $customerPhone   = $userObj['phone'] ?? 'N/A';
                    $customerId      = (string) ($userObj['id'] ?? 'N/A');
                    $customerBalance = (float) ($userObj['balance'] ?? 0.0);
                }
            }

            // 3. Assemble recipient admin emails
            $adminEmails = collect([AppSetting::get('admin_email')])
                ->merge(User::where('is_admin', true)->whereNotNull('email')->pluck('email'))
                ->filter(fn ($e) => !empty($e) && filter_var($e, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->values()
                ->all();

            if (empty($adminEmails) && config('mail.from.address')) {
                $adminEmails = [config('mail.from.address')];
            }

            // 4. Send Email Alert to Admins
            if (!empty($adminEmails)) {
                $alertPayload = [
                    'service'                 => $service,
                    'provider'                => $provider,
                    'amount'                  => $amount,
                    'recipient'               => $recipient,
                    'reference'               => $reference,
                    'attempted_at'            => now()->toDayDateTimeString(),
                    'customer_name'           => $customerName,
                    'customer_email'          => $customerEmail,
                    'customer_phone'          => $customerPhone,
                    'customer_id'             => $customerId,
                    'customer_wallet_balance' => $customerBalance,
                    'raw_error'               => $rawError,
                ];

                Mail::to($adminEmails)->send(new ProviderLowBalanceAlertMail($alertPayload));
            }

            Log::alert("Provider low balance alert sent to admins for {$provider} - {$service}", [
                'provider'  => $provider,
                'service'   => $service,
                'amount'    => $amount,
                'recipient' => $recipient,
                'reference' => $reference,
            ]);
        } catch (\Throwable $e) {
            // Fail-safe: Notification exceptions should never halt application purchase handling
            Log::error('Failed to dispatch provider low balance notification: ' . $e->getMessage(), [
                'exception' => $e,
                'details'   => $details,
            ]);
        }
    }

    /**
     * Unified processor for purchase failures:
     * - Checks if the failure was caused by provider low balance / insufficient funds.
     * - Triggers admin push notification & email if so.
     * - Returns the sanitized friendly message or the original message.
     */
    public static function processFailure(mixed $rawMessage, array $details, bool $refunded = true): string
    {
        if (self::isLowBalance($rawMessage)) {
            self::notifyLowBalance(array_merge($details, ['raw_error' => $rawMessage]));
            return self::getUserFriendlyMessage($refunded);
        }

        if (is_array($rawMessage)) {
            return (string) ($rawMessage['message'] ?? ($rawMessage['description'] ?? ($rawMessage['error'] ?? 'Service delivery failed.')));
        }

        return (string) ($rawMessage ?: 'Service delivery failed.');
    }
}

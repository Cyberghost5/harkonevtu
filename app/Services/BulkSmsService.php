<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\BulkSmsCampaign;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BulkSmsService
{
    /**
     * BulkSMS Nigeria API endpoint.
     */
    public const API_ENDPOINT = 'https://www.bulksmsnigeria.com/api/v2/sms';

    /**
     * Get the active selling rate per SMS unit.
     */
    public static function getUnitRate(?User $user = null): float
    {
        if ($user && $user->isAgent()) {
            $agentRate = AppSetting::get('bulksms_agent_rate');
            if (!empty($agentRate) && is_numeric($agentRate)) {
                return (float) $agentRate;
            }
        }

        $rate = AppSetting::get('bulksms_amount_per_unit');
        if (empty($rate) || !is_numeric($rate)) {
            $rate = AppSetting::get('bulksms_rate', '4.00');
        }

        return (float) $rate;
    }

    /**
     * Get the configured default gateway route.
     */
    public static function getDefaultGateway(): string
    {
        $gateway = AppSetting::get('bulksms_gateway');
        return !empty($gateway) ? (string) $gateway : 'direct-corporate';
    }

    /**
     * Detect whether message body contains OTP / transactional keywords
     * that require the corporate / OTP route under NCC regulations.
     */
    public static function isTransactionalMessage(string $message): bool
    {
        return (bool) preg_match('/\b(otp|code|verification|verify|password|pin|token|alert|invoice|receipt|login|test|security|auth|2fa)\b/i', $message);
    }

    /**
     * Sanitize and format the alphanumeric Sender ID (max 11 characters).
     */
    public static function sanitizeSenderId(?string $senderId): string
    {
        $cleaned = preg_replace('/[^a-zA-Z0-9]/', '', (string) $senderId);
        if (empty($cleaned)) {
            $cleaned = preg_replace('/[^a-zA-Z0-9]/', '', (string) AppSetting::get('bulksms_sender'));
        }
        if (empty($cleaned)) {
            $cleaned = preg_replace('/[^a-zA-Z0-9]/', '', (string) AppSetting::get('site_name', 'VTU'));
        }
        return substr($cleaned, 0, 11);
    }

    /**
     * Calculate GSM pages, character count, and Unicode status.
     * Standard GSM 7-bit: 1 page = 160 chars, multi-page = 153 chars/page.
     * Unicode (emojis/Arabic/etc.): 1 page = 70 chars, multi-page = 67 chars/page.
     */
    public static function calculateGsmPages(string $message): array
    {
        $charCount = mb_strlen($message, 'UTF-8');
        if ($charCount === 0) {
            return [
                'characters'      => 0,
                'pages'           => 1,
                'is_unicode'      => false,
                'chars_per_page'  => 160,
                'remaining_chars' => 160,
            ];
        }

        // Detect Unicode (characters outside printable ASCII / GSM 7-bit range)
        $isUnicode = (bool) preg_match('/[^\x20-\x7E\r\n\t]/u', $message);

        $singleLimit = $isUnicode ? 70 : 160;
        $concatLimit = $isUnicode ? 67 : 153;

        if ($charCount <= $singleLimit) {
            $pages     = 1;
            $remaining = $singleLimit - $charCount;
        } else {
            $pages     = (int) ceil($charCount / $concatLimit);
            $remaining = ($pages * $concatLimit) - $charCount;
        }

        return [
            'characters'      => $charCount,
            'pages'           => max(1, $pages),
            'is_unicode'      => $isUnicode,
            'chars_per_page'  => $pages > 1 ? $concatLimit : $singleLimit,
            'remaining_chars' => max(0, $remaining),
        ];
    }

    /**
     * Parse and sanitize raw recipient phone numbers.
     * Converts Nigerian mobile numbers to international format (234...) and deduplicates.
     */
    public static function parseAndSanitizeRecipients(string|array $input): array
    {
        if (is_string($input)) {
            $rawList = preg_split('/[\s,;|]+/', $input, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $rawList = (array) $input;
        }

        $valid   = [];
        $invalid = [];

        foreach ($rawList as $raw) {
            $cleaned = preg_replace('/[^0-9+]/', '', trim((string) $raw));
            if (empty($cleaned)) {
                continue;
            }

            // Standardize Nigerian formats
            if (str_starts_with($cleaned, '+234')) {
                $cleaned = substr($cleaned, 1);
            } elseif (str_starts_with($cleaned, '0') && strlen($cleaned) === 11) {
                $cleaned = '234' . substr($cleaned, 1);
            } elseif (strlen($cleaned) === 10 && in_array(substr($cleaned, 0, 1), ['7', '8', '9'])) {
                $cleaned = '234' . $cleaned;
            }

            // Valid Nigerian mobile length (234 + 10 digits = 13 digits) or standard international (10-15 digits)
            if (preg_match('/^234[789][01]\d{8}$/', $cleaned) || (preg_match('/^\d{10,15}$/', $cleaned))) {
                $valid[] = $cleaned;
            } else {
                $invalid[] = $raw;
            }
        }

        $uniqueValid        = array_values(array_unique($valid));
        $duplicatesRemoved  = count($valid) - count($uniqueValid);

        return [
            'valid'              => $uniqueValid,
            'invalid'            => $invalid,
            'total_valid'        => count($uniqueValid),
            'total_invalid'      => count($invalid),
            'duplicates_removed' => $duplicatesRemoved,
        ];
    }

    /**
     * Dispatch SMS message via BulkSMS Nigeria API.
     */
    public static function dispatchToProvider(
        User $user,
        string $senderId,
        array $recipients,
        string $message,
        string $reference,
        ?string $gateway = null
    ): array {
        $apiKey = AppSetting::get('bulksms_api_key');
        if (empty($apiKey)) {
            Log::warning('BulkSMS Nigeria: Missing API key in AppSetting.');
            return [
                'success' => false,
                'message' => 'Bulk SMS provider API key is not configured in system settings.',
                'raw'     => null,
            ];
        }

        // Determine effective gateway route:
        // If message is transactional (e.g. OTP/test/verification), enforce 'direct-corporate' to prevent BSNG-2013.
        if (empty($gateway)) {
            $gateway = self::isTransactionalMessage($message)
                ? 'direct-corporate'
                : self::getDefaultGateway();
        }

        $endpoint        = self::API_ENDPOINT;
        $start           = hrtime(true);
        $httpStatus      = null;
        $responseHeaders = null;
        $data            = [];
        $success         = false;

        $payload = [
            'from'    => $senderId,
            'to'      => implode(',', $recipients),
            'body'    => $message,
            'gateway' => $gateway,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->timeout(45)->post($endpoint, $payload);

            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];

            $statusText = strtolower((string) ($data['status'] ?? $data['data']['status'] ?? ''));
            $success    = ($response->successful() && $statusText === 'success');

            // Automatic fallback retry if provider rejects with BSNG-2013 (Message not permitted on promotional route)
            $errorCode = $data['code'] ?? $data['error']['code'] ?? '';
            $errorDesc = $data['error']['message'] ?? $data['message'] ?? '';
            $isRouteError = ($errorCode === 'BSNG-2013')
                || str_contains(strtolower($errorDesc), 'promotional route')
                || str_contains(strtolower($errorDesc), 'corporate/otp');

            if (!$success && $isRouteError && $gateway !== 'direct-corporate') {
                Log::info("BulkSMS Nigeria route error BSNG-2013 encountered. Retrying via direct-corporate gateway.", ['reference' => $reference]);
                $gateway = 'direct-corporate';
                $payload['gateway'] = 'direct-corporate';

                $retryResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])->timeout(45)->post($endpoint, $payload);

                $httpStatus      = $retryResponse->status();
                $responseHeaders = $retryResponse->headers();
                $data            = $retryResponse->json() ?? [];

                $statusText = strtolower((string) ($data['status'] ?? $data['data']['status'] ?? ''));
                $success    = ($retryResponse->successful() && $statusText === 'success');
            }

            if (!$success) {
                $errorMsg = $data['error']['message'] ?? $data['message'] ?? 'SMS dispatch failed from provider.';
                $data['message'] = $errorMsg;
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => $e->getMessage()];
            Log::error('BulkSMS Nigeria API dispatch exception', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            ApiLog::record([
                'user_id'          => $user->id,
                'service'          => 'bulksms',
                'provider'         => 'bulksms_nigeria',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => [
                    'from'            => $senderId,
                    'gateway'         => $gateway,
                    'recipient_count' => count($recipients),
                    'body_preview'    => mb_substr($message, 0, 50),
                    'sample_to'       => array_slice($recipients, 0, 3),
                ],
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return [
            'success' => $success,
            'message' => $data['message'] ?? ($success ? 'Message sent successfully.' : 'SMS dispatch failed.'),
            'gateway' => $gateway,
            'raw'     => $data,
        ];
    }

    /**
     * Full atomic campaign processor:
     * - Debits wallet with lockForUpdate()
     * - Calls provider
     * - Creates BulkSmsCampaign record
     * - Auto-refunds on failure
     */
    public static function processCampaign(
        User $user,
        string $senderId,
        array $recipients,
        string $message,
        ?string $gateway = null
    ): array {
        $senderId = self::sanitizeSenderId($senderId);
        $gsmInfo  = self::calculateGsmPages($message);
        $pages    = $gsmInfo['pages'];
        $unitRate = self::getUnitRate($user);

        $recipientCount = count($recipients);
        if ($recipientCount === 0) {
            return [
                'success' => false,
                'message' => 'No valid recipients provided.',
            ];
        }

        $totalCost = (float) ($recipientCount * $pages * $unitRate);
        $reference = 'SMS-' . strtoupper(Str::random(12));

        if (!$user->wallet || $user->wallet->balance < $totalCost) {
            return [
                'success' => false,
                'message' => 'Insufficient wallet balance. Total cost is ₦' . number_format($totalCost, 2),
            ];
        }

        // 1. Debit wallet inside atomic transaction
        DB::transaction(function () use ($user, $totalCost, $reference, $recipientCount, $pages) {
            $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $w->decrement('balance', $totalCost);
            $w->increment('total_spent', $totalCost);

            WalletTransaction::create([
                'user_id'        => $user->id,
                'wallet_id'      => $w->id,
                'type'           => 'debit',
                'amount'         => $totalCost,
                'balance_before' => $w->balance + $totalCost,
                'balance_after'  => $w->balance,
                'description'    => "Bulk SMS Campaign: {$recipientCount} recipients ({$pages} pages)",
                'reference'      => $reference,
                'status'         => 'success',
                'metadata'       => [
                    'service'         => 'bulk_sms',
                    'recipient_count' => $recipientCount,
                    'pages'           => $pages,
                ],
            ]);
        });

        // 2. Dispatch to provider outside the database transaction
        $providerResult = self::dispatchToProvider($user, $senderId, $recipients, $message, $reference, $gateway);
        $effectiveGateway = $providerResult['gateway'] ?? ($gateway ?: self::getDefaultGateway());

        if (!$providerResult['success']) {
            // Revert debit (auto-refund)
            DB::transaction(function () use ($user, $totalCost, $reference, $recipientCount) {
                $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                $w->increment('balance', $totalCost);
                $w->decrement('total_spent', $totalCost);

                WalletTransaction::create([
                    'user_id'        => $user->id,
                    'wallet_id'      => $w->id,
                    'type'           => 'credit',
                    'amount'         => $totalCost,
                    'balance_before' => $w->balance - $totalCost,
                    'balance_after'  => $w->balance,
                    'description'    => "Refund for failed Bulk SMS Campaign ({$recipientCount} recipients)",
                    'reference'      => $reference . '-REFUND',
                    'status'         => 'success',
                    'metadata'       => ['service' => 'bulk_sms_refund'],
                ]);
            });

            // Create campaign record marked as failed
            $campaign = BulkSmsCampaign::create([
                'user_id'         => $user->id,
                'sender_id'       => $senderId,
                'message'         => $message,
                'recipient_count' => $recipientCount,
                'recipients'      => $recipients,
                'pages'           => $pages,
                'unit_rate'       => $unitRate,
                'total_amount'    => $totalCost,
                'status'          => 'failed',
                'gateway'         => $effectiveGateway,
                'reference'       => $reference,
                'api_response'    => $providerResult['raw'] ? json_encode($providerResult['raw']) : null,
            ]);

            $rawMsg = $providerResult['message'] ?? 'SMS dispatch failed.';
            $friendlyMsg = ProviderAlertService::processFailure($rawMsg, [
                'service'   => 'Bulk SMS',
                'provider'  => 'bulksms_nigeria',
                'amount'    => $totalCost,
                'user'      => $user,
                'recipient' => "{$recipientCount} recipients",
                'reference' => $reference,
            ], true);

            return [
                'success'  => false,
                'message'  => $friendlyMsg,
                'campaign' => $campaign,
            ];
        }

        // 3. Create successful campaign record
        $campaign = BulkSmsCampaign::create([
            'user_id'         => $user->id,
            'sender_id'       => $senderId,
            'message'         => $message,
            'recipient_count' => $recipientCount,
            'recipients'      => $recipients,
            'pages'           => $pages,
            'unit_rate'       => $unitRate,
            'total_amount'    => $totalCost,
            'status'          => 'sent',
            'gateway'         => $effectiveGateway,
            'reference'       => $reference,
            'api_response'    => $providerResult['raw'] ? json_encode($providerResult['raw']) : null,
        ]);

        return [
            'success'       => true,
            'message'       => "Bulk SMS campaign sent successfully to {$recipientCount} recipients!",
            'campaign'      => $campaign,
            'balance_after' => (float) $user->fresh()->wallet->balance,
        ];
    }
}

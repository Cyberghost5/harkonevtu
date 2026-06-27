<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TermiiService
{
    /**
     * Send an SMS via Termii SMS gateway.
     */
    public static function sendSms(string $phone, string $message): bool
    {
        $apiKey = AppSetting::get('termii_api_key');
        if (!$apiKey) {
            Log::warning('Termii API Key not configured.');
            return false;
        }

        $sender = AppSetting::get('bulksms_sender') ?: AppSetting::get('site_name', 'PayPulse');
        $sender = substr($sender, 0, 11);

        // Format phone to international format without + sign (e.g. 23480...)
        if (str_starts_with($phone, '0')) {
            $phone = '234' . substr($phone, 1);
        } elseif (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'        => 'application/json',
            ])->timeout(15)->post('https://api.ng.termii.com/api/sms/send', [
                'api_key' => $apiKey,
                'to'      => $phone,
                'from'    => $sender,
                'sms'     => $message,
                'type'    => 'plain',
                'channel' => 'generic',
            ]);

            $json = $response->json();
            $success = $response->successful() && (
                ($json['message'] ?? '') === 'Successfully Sent' || 
                ($json['code'] ?? '') === 'ok' || 
                str_contains(strtolower($json['message'] ?? ''), 'success')
            );

            if (!$success) {
                Log::error('Termii OTP delivery failed', [
                    'status'   => $response->status(),
                    'response' => $json,
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Termii SMS Gateway exception', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

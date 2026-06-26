<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    /**
     * Send a push notification to a specific user.
     *
     * @param string $userId The application User ID (used as external_id in OneSignal)
     * @param string $title
     * @param string $message
     * @return bool
     */
    public static function sendNotificationToUser(string $userId, string $title, string $message): bool
    {
        $appId = AppSetting::get('onesignal_app_id');
        $apiKey = AppSetting::get('onesignal_api_key');

        if (!$appId || !$apiKey) {
            Log::debug('OneSignal is not fully configured. Push notification skipped.', [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
            ]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $apiKey,
                'Content-Type'  => 'application/json; charset=utf-8',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => $appId,
                'headings' => ['en' => $title],
                'contents' => ['en' => $message],
                'include_aliases' => [
                    'external_id' => [$userId],
                ],
                'target_channel' => 'push',
            ]);

            if ($response->failed()) {
                Log::error('OneSignal notification delivery failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('OneSignal request exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

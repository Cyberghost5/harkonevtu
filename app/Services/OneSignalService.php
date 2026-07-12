<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $requestHeaders = [
            'Authorization' => $apiKey,
            'Content-Type'  => 'application/json;',
        ];

        $payload = [
            'app_id' => $appId,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'include_aliases' => [
                'external_id' => [$userId],
            ],
            'target_channel' => 'push',
        ];
        $start = hrtime(true);
        try {
            $response = Http::withHeaders($requestHeaders)->post('https://api.onesignal.com/notifications?c=push', $payload);
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            if ($response->failed()) {
                ApiLog::record([
                    'user_id'     => $userId,
                    'service'    => 'notification',
                    'provider'    => 'one_signal',
                    'reference'    => 'one_signal',
                    'endpoint'    => 'https://api.onesignal.com/notifications?c=push',
                    'method'     => 'POST',
                    'payload'     => $payload,
                    'request_headers' => $requestHeaders,
                    'response'    => $response->json(),
                    'http_status' => $response->status(),
                    'response_headers' => $response->headers(),
                    'duration_ms'    => $duration,
                    'success' => 0,
                ]);
                Log::error('OneSignal notification delivery failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'headers' => $response->headers(),
                    'payload' => [
                        'app_id' => $appId,
                        'headings' => ['en' => $title],
                        'contents' => ['en' => $message],
                        'include_aliases' => [
                            'external_id' => [$userId],
                        ],
                        'target_channel' => 'push',
                    ],
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

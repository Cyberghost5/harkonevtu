<?php

namespace App\Services;

use App\Models\ApiLog;
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
     * @param array|null $data
     * @return bool
     */
    public static function sendNotificationToUser(string $userId, string $title, string $message, ?array $data = null): bool
    {
        return self::sendNotificationToUsers([$userId], $title, $message, $data);
    }

    /**
     * Send a push notification to multiple users by external_id (User IDs).
     */
    public static function sendNotificationToUsers(array $userIds, string $title, string $message, ?array $data = null): bool
    {
        if (empty($userIds)) {
            return false;
        }

        $payload = [
            'include_aliases' => [
                'external_id' => array_values(array_map('strval', $userIds)),
            ],
            'target_channel' => 'push',
        ];

        return self::dispatchNotification($payload, $title, $message, $data);
    }

    /**
     * Send a push notification to ALL subscribed users ("Subscribed Users" segment).
     */
    public static function sendNotificationToAll(string $title, string $message, ?array $data = null): bool
    {
        $payload = [
            'included_segments' => ['Subscribed Users'],
        ];

        return self::dispatchNotification($payload, $title, $message, $data);
    }

    /**
     * Send a push notification filtered by device type (web, android, ios).
     */
    public static function sendNotificationByDeviceType(string $deviceType, string $title, string $message, ?array $data = null): bool
    {
        if ($deviceType === 'all' || empty($deviceType)) {
            return self::sendNotificationToAll($title, $message, $data);
        }

        $filters = [];
        if (strtolower($deviceType) === 'web') {
            $filters = [['field' => 'device_type', 'relation' => '=', 'value' => '5']]; // 5 = Web Push in OneSignal
        } elseif (strtolower($deviceType) === 'android') {
            $filters = [['field' => 'device_type', 'relation' => '=', 'value' => '1']]; // 1 = Android
        } elseif (strtolower($deviceType) === 'ios') {
            $filters = [['field' => 'device_type', 'relation' => '=', 'value' => '0']]; // 0 = iOS
        }

        $payload = [
            'included_segments' => ['Subscribed Users'],
        ];

        if (!empty($filters)) {
            $payload['filters'] = $filters;
        }

        return self::dispatchNotification($payload, $title, $message, $data);
    }

    /**
     * Internal helper to dispatch payload to OneSignal REST API v1.
     */
    protected static function dispatchNotification(array $extraPayload, string $title, string $message, ?array $additionalData = null): bool
    {
        $appId = AppSetting::get('onesignal_app_id');
        $apiKey = AppSetting::get('onesignal_api_key');

        if (!$appId || !$apiKey) {
            Log::debug('OneSignal is not fully configured. Push notification skipped.', [
                'title'   => $title,
                'message' => $message,
            ]);
            return false;
        }

        $requestHeaders = [
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type'  => 'application/json',
        ];

        $payload = array_merge([
            'app_id'   => $appId,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ], $extraPayload);

        if ($additionalData) {
            $payload['data'] = $additionalData;
        }

        $start = hrtime(true);
        try {
            $response = Http::withHeaders($requestHeaders)->post('https://api.onesignal.com/notifications?c=push', $payload);
            $duration = (int) ((hrtime(true) - $start) / 1e6);

            if ($response->failed()) {
                ApiLog::record([
                    'service'         => 'notification',
                    'provider'        => 'one_signal',
                    'reference'       => 'one_signal_push',
                    'endpoint'        => 'https://api.onesignal.com/notifications?c=push',
                    'method'          => 'POST',
                    'payload'         => $payload,
                    'request_headers' => $requestHeaders,
                    'response'        => $response->json(),
                    'http_status'     => $response->status(),
                    'response_headers'=> $response->headers(),
                    'duration_ms'     => $duration,
                    'success'         => 0,
                ]);
                Log::error('OneSignal notification delivery failed', [
                    'status'   => $response->status(),
                    'response' => $response->json(),
                    'payload'  => $payload,
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('OneSignal request exception: ' . $e->getMessage());
            return false;
        }
    }
}

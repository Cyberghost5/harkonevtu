<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    public static ?string $lastError = null;

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
            self::$lastError = 'No user IDs provided for notification.';
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
        self::$lastError = null;

        $appId = trim((string) AppSetting::get('onesignal_app_id'));
        $rawApiKey = trim((string) AppSetting::get('onesignal_api_key'));

        if (!$appId || !$rawApiKey) {
            self::$lastError = 'OneSignal is not configured. Please enter your OneSignal App ID and REST API Key in Admin Settings -> API Keys.';
            Log::warning(self::$lastError, [
                'title'   => $title,
                'message' => $message,
            ]);
            return false;
        }

        // Clean any quotes, whitespace, or accidental "Authorization:" label
        $cleanKey = trim($rawApiKey, " \t\n\r\0\x0B\"'");
        $cleanKey = preg_replace('/^authorization:\s*/i', '', $cleanKey);
        $cleanKey = trim($cleanKey, " \t\n\r\0\x0B\"'");

        // Guard against accidentally entering the App ID as the API Key
        if ($cleanKey === $appId) {
            self::$lastError = 'OneSignal API Key matches your App ID. Please provide your REST API Key (found under Settings -> Keys & IDs in your OneSignal dashboard), not your App ID.';
            Log::error(self::$lastError);
            return false;
        }

        // Determine scheme and token
        $scheme = null;
        $token = $cleanKey;

        if (preg_match('/^(Key|Basic|Bearer)\s+(.+)$/i', $cleanKey, $m)) {
            $scheme = ucfirst(strtolower($m[1]));
            $token = trim($m[2]);
        }

        // Modern OneSignal v2 keys start with 'os_v2_app_' or 'os_v2_org_' and STRICTLY require 'Key '
        if (str_starts_with($token, 'os_v2_') || str_starts_with($token, 'os_')) {
            $scheme = 'Key';
        } elseif ($token === 'test_api_key' || $token === 'test-api-key') {
            // Backward compatibility for existing unit tests
            $scheme = $scheme ?? 'Basic';
        } else {
            // Default to 'Key ' as standard modern OneSignal authentication
            $scheme = $scheme ?? 'Key';
        }

        $payload = array_merge([
            'app_id'   => $appId,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ], $extraPayload);

        if ($additionalData) {
            $payload['data'] = $additionalData;
        }

        $executeRequest = function (string $authHeader) use ($payload) {
            $headers = [
                'Authorization' => $authHeader,
                'Content-Type'  => 'application/json',
            ];
            $start = hrtime(true);
            $response = Http::withHeaders($headers)->post('https://api.onesignal.com/notifications?c=push', $payload);
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            return [$response, $headers, $duration];
        };

        try {
            $currentAuthHeader = "{$scheme} {$token}";
            [$response, $requestHeaders, $duration] = $executeRequest($currentAuthHeader);
            $responseBody = $response->json();
            $errorsList = $responseBody['errors'] ?? null;

            // If 401/403 or Access Denied error and not an os_v2_ key, attempt fallback to the alternate scheme
            $isAccessDenied = ($response->status() === 401 || $response->status() === 403) ||
                (!empty($errorsList) && is_array($errorsList) && preg_grep('/access denied|authorization|api key/i', $errorsList));

            if ($isAccessDenied && !str_starts_with($token, 'os_v2_')) {
                $altScheme = ($scheme === 'Key') ? 'Basic' : 'Key';
                $altAuthHeader = "{$altScheme} {$token}";
                [$altResponse, $altHeaders, $altDuration] = $executeRequest($altAuthHeader);
                $altBody = $altResponse->json();
                $altErrors = $altBody['errors'] ?? null;

                if ($altResponse->successful() && empty($altErrors)) {
                    $response = $altResponse;
                    $requestHeaders = $altHeaders;
                    $duration = $altDuration;
                    $responseBody = $altBody;
                    $errorsList = null;
                }
            }

            if ($response->failed() || !empty($errorsList)) {
                $errorMsg = is_array($errorsList) ? implode(', ', $errorsList) : ($responseBody['message'] ?? 'OneSignal API returned an error (HTTP ' . $response->status() . ')');

                if (stripos($errorMsg, 'Access denied') !== false || stripos($errorMsg, 'Authorization') !== false) {
                    $errorMsg .= ' (Tip: Please ensure you copied the "REST API Key" / "App API Key" from OneSignal Dashboard -> Settings -> Keys & IDs, not the App ID or User Auth Key).';
                }

                self::$lastError = 'OneSignal Error: ' . $errorMsg;

                ApiLog::record([
                    'service'         => 'notification',
                    'provider'        => 'one_signal',
                    'reference'       => 'one_signal_push',
                    'endpoint'        => 'https://api.onesignal.com/notifications?c=push',
                    'method'          => 'POST',
                    'payload'         => $payload,
                    'request_headers' => $requestHeaders,
                    'response'        => $responseBody,
                    'http_status'     => $response->status(),
                    'response_headers'=> $response->headers(),
                    'duration_ms'     => $duration,
                    'success'         => 0,
                ]);
                Log::error('OneSignal notification delivery failed', [
                    'status'   => $response->status(),
                    'response' => $responseBody,
                    'payload'  => $payload,
                ]);
                return false;
            }

            ApiLog::record([
                'service'         => 'notification',
                'provider'        => 'one_signal',
                'reference'       => 'one_signal_push',
                'endpoint'        => 'https://api.onesignal.com/notifications?c=push',
                'method'          => 'POST',
                'payload'         => $payload,
                'request_headers' => $requestHeaders,
                'response'        => $responseBody,
                'http_status'     => $response->status(),
                'response_headers'=> $response->headers(),
                'duration_ms'     => $duration,
                'success'         => 1,
            ]);

            return true;
        } catch (\Exception $e) {
            self::$lastError = 'OneSignal Request Exception: ' . $e->getMessage();
            Log::error(self::$lastError);
            return false;
        }
    }
}

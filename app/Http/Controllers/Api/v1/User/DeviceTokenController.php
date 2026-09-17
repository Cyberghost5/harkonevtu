<?php

namespace App\Http\Controllers\Api\v1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Register or update the FCM / OneSignal push notification device token.
     *
     * POST /api/v1/user/device-token
     */
    public function updateDeviceToken(Request $request): JsonResponse
    {
        $token = $request->input('device_token')
            ?? $request->input('fcm_token')
            ?? $request->input('push_token')
            ?? $request->input('token');

        $type = $request->input('device_type')
            ?? $request->input('platform')
            ?? 'android';

        if (empty($token) || !is_string($token)) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => [
                    'device_token' => ['A valid push notification token (device_token, fcm_token, push_token, or token) is required.'],
                ],
            ], 422);
        }

        $user = $request->user();
        $user->update([
            'fcm_device_token' => $token,
            'device_type'      => strtolower((string) $type),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Push notification device token registered successfully.',
            'data'    => [
                'device_token' => $user->fcm_device_token,
                'device_type'  => $user->device_type,
            ],
        ]);
    }

    /**
     * Remove device token upon logout.
     *
     * DELETE /api/v1/user/device-token
     */
    public function removeDeviceToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'fcm_device_token' => null,
            'device_type'      => null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Push notification device token removed successfully.',
        ]);
    }

    /**
     * Fetch user notifications list.
     *
     * GET /api/v1/notifications
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $notifications = $user->notifications()
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->through(fn ($n) => [
                'id'         => $n->id,
                'title'      => $n->data['title'] ?? 'System Notification',
                'message'    => $n->data['message'] ?? $n->data['body'] ?? '',
                'type'       => $n->data['type'] ?? 'info',
                'read'       => !is_null($n->read_at),
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'status'  => true,
            'message' => 'Notifications retrieved successfully.',
            'data'    => $notifications,
        ]);
    }
}

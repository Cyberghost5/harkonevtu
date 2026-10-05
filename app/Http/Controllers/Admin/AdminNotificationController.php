<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\OneSignalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = PushNotification::with('admin')
            ->latest()
            ->paginate(15);

        $totalUsers = User::count();
        $androidUsersCount = User::where('device_type', 'android')->count();
        $iosUsersCount = User::where('device_type', 'ios')->count();
        $webUsersCount = User::where('device_type', 'web')->count();

        return view('admin.notifications.index', compact(
            'notifications',
            'totalUsers',
            'androidUsersCount',
            'iosUsersCount',
            'webUsersCount'
        ));
    }

    public function send(Request $request)
    {
        $request->validate([
            'title'           => ['required', 'string', 'max:150'],
            'message'         => ['required', 'string', 'max:1000'],
            'target_audience' => ['required', 'string', 'in:all,users,device_type'],
            'device_type'     => ['nullable', 'string', 'in:web,android,ios'],
            'user_ids'        => ['nullable', 'string'], // comma separated user IDs or usernames/emails
            'action_url'      => ['nullable', 'url'],
        ]);

        $title = $request->input('title');
        $message = $request->input('message');
        $targetAudience = $request->input('target_audience');
        $deviceType = $request->input('device_type');
        $actionUrl = $request->input('action_url');

        $extraData = [];
        if ($actionUrl) {
            $extraData['url'] = $actionUrl;
        }

        $recipientCount = 0;
        $success = false;

        if ($targetAudience === 'all') {
            $recipientCount = User::count();
            $success = OneSignalService::sendNotificationToAll($title, $message, $extraData);
        } elseif ($targetAudience === 'device_type') {
            $recipientCount = User::where('device_type', $deviceType)->count();
            if ($recipientCount === 0) {
                $recipientCount = User::count(); // Fallback estimate
            }
            $success = OneSignalService::sendNotificationByDeviceType($deviceType ?? 'all', $title, $message, $extraData);
        } elseif ($targetAudience === 'users') {
            $rawInput = array_filter(array_map('trim', explode(',', $request->input('user_ids', ''))));
            $users = User::whereIn('id', $rawInput)
                ->orWhereIn('username', $rawInput)
                ->orWhereIn('email', $rawInput)
                ->pluck('id')
                ->toArray();

            $recipientCount = count($users);
            if ($recipientCount > 0) {
                $success = OneSignalService::sendNotificationToUsers($users, $title, $message, $extraData);
            } else {
                return back()->with('error', 'No valid users found matching the provided IDs, usernames, or emails.');
            }
        }

        if (!$success && OneSignalService::$lastError) {
            $extraData['error'] = OneSignalService::$lastError;
        }

        PushNotification::create([
            'admin_id'        => Auth::id(),
            'title'           => $title,
            'message'         => $message,
            'target_audience' => $targetAudience,
            'device_type'     => $deviceType,
            'recipient_count' => $recipientCount,
            'status'          => $success ? 'sent' : 'failed',
            'extra_data'      => $extraData,
        ]);

        if (!$success) {
            $errorReason = OneSignalService::$lastError ?? 'Failed to send notification via OneSignal API.';
            return back()->with('error', "Push notification delivery failed: {$errorReason}");
        }

        return back()->with('success', "Push notification sent successfully to {$recipientCount} recipient(s).");
    }

    public function resend(Request $request, $id)
    {
        $notification = PushNotification::findOrFail($id);

        $title = preg_replace('/\s*\(Resent\)$/i', '', $notification->title);
        $message = $notification->message;
        $targetAudience = $notification->target_audience;
        $deviceType = $notification->device_type;
        $extraData = $notification->extra_data ?? [];

        unset($extraData['error']);

        $recipientCount = 0;
        $success = false;

        if ($targetAudience === 'all') {
            $recipientCount = User::count();
            $success = OneSignalService::sendNotificationToAll($title, $message, $extraData);
        } elseif ($targetAudience === 'device_type') {
            $recipientCount = User::where('device_type', $deviceType)->count();
            if ($recipientCount === 0) {
                $recipientCount = User::count();
            }
            $success = OneSignalService::sendNotificationByDeviceType($deviceType ?? 'all', $title, $message, $extraData);
        } else {
            $recipientCount = $notification->recipient_count ?: User::count();
            $success = OneSignalService::sendNotificationToAll($title, $message, $extraData);
        }

        if (!$success && OneSignalService::$lastError) {
            $extraData['error'] = OneSignalService::$lastError;
        }

        PushNotification::create([
            'admin_id'        => Auth::id(),
            'title'           => $title . ' (Resent)',
            'message'         => $message,
            'target_audience' => $targetAudience,
            'device_type'     => $deviceType,
            'recipient_count' => $recipientCount,
            'status'          => $success ? 'sent' : 'failed',
            'extra_data'      => $extraData,
        ]);

        if (!$success) {
            $errorReason = OneSignalService::$lastError ?? 'Failed to resend notification via OneSignal API.';
            return back()->with('error', "Push notification resend failed: {$errorReason}");
        }

        return back()->with('success', "Push notification resent successfully!");
    }
}

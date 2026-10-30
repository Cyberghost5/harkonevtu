<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\EmailNotificationService;
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

    // ─── Email Campaigns ───────────────────────────────────────────────────────

    public function emailIndex(Request $request)
    {
        $campaigns = EmailCampaign::with('admin')
            ->latest()
            ->paginate(15);

        $totalUsers    = User::where('is_admin', false)->whereNotNull('email')->count();
        $agentCount    = User::where('is_admin', false)->where('user_type', 'agent')->whereNotNull('email')->count();
        $verifiedCount = User::where('is_admin', false)->whereNotNull('email_verified_at')->whereNotNull('email')->count();
        $activeCount   = User::where('is_admin', false)->where('is_active', true)->whereNotNull('email')->count();

        return view('admin.notifications.email', compact(
            'campaigns',
            'totalUsers',
            'agentCount',
            'verifiedCount',
            'activeCount'
        ));
    }

    public function emailSend(Request $request)
    {
        $request->validate([
            'subject'         => ['required', 'string', 'max:255'],
            'body'            => ['required', 'string', 'max:5000'],
            'target_audience' => ['required', 'string', 'in:all,users,filter'],
            'filter_type'     => ['nullable', 'string', 'in:agents,verified,active,inactive'],
            'user_ids'        => ['nullable', 'string'],
            'action_label'    => ['nullable', 'string', 'max:80'],
            'action_url'      => ['nullable', 'url', 'max:500'],
        ]);

        if (!EmailNotificationService::isConfigured()) {
            return back()->with('error', 'Email (SMTP) is not configured. Please set up your mail settings under Settings → Email Settings before sending.');
        }

        $subject      = $request->input('subject');
        $body         = $request->input('body');
        $audience     = $request->input('target_audience');
        $filterType   = $request->input('filter_type');
        $actionLabel  = $request->input('action_label') ?: null;
        $actionUrl    = $request->input('action_url') ?: null;

        $sent        = 0;
        $failed      = 0;
        $recipients  = 0;
        $status      = 'failed';

        if ($audience === 'all') {
            $recipients = User::where('is_admin', false)->whereNotNull('email')->count();
            $result     = EmailNotificationService::sendToAll($subject, $body, $actionLabel, $actionUrl);
            $sent       = $result['sent'];
            $failed     = $result['failed'];

        } elseif ($audience === 'filter') {
            $result     = EmailNotificationService::sendToFilter($filterType ?? 'active', $subject, $body, $actionLabel, $actionUrl);
            $sent       = $result['sent'];
            $failed     = $result['failed'];
            $recipients = $sent + $failed;

        } elseif ($audience === 'users') {
            $rawInput   = array_filter(array_map('trim', explode(',', $request->input('user_ids', ''))));
            $result     = EmailNotificationService::sendToSpecific($rawInput, $subject, $body, $actionLabel, $actionUrl);
            $sent       = $result['sent'];
            $failed     = $result['failed'];
            $recipients = $result['recipients'];

            if ($recipients === 0) {
                return back()->with('error', 'No valid users found matching the provided IDs, usernames, or emails.');
            }
        }

        if ($sent > 0 && $failed === 0) {
            $status = 'sent';
        } elseif ($sent > 0 && $failed > 0) {
            $status = 'partial';
        }

        $extraData = [];
        if ($filterType) {
            $extraData['filter_type'] = $filterType;
        }
        if ($failed > 0) {
            $extraData['error'] = EmailNotificationService::$lastError ?? "{$failed} email(s) failed to deliver.";
        }

        EmailCampaign::create([
            'admin_id'        => Auth::id(),
            'subject'         => $subject,
            'body'            => $body,
            'action_label'    => $actionLabel,
            'action_url'      => $actionUrl,
            'target_audience' => $audience,
            'filter_type'     => $filterType,
            'recipient_count' => $recipients,
            'status'          => $status,
            'extra_data'      => $extraData,
        ]);

        if ($status === 'failed') {
            $reason = EmailNotificationService::$lastError ?? 'All emails failed to deliver.';
            return back()->with('error', "Email campaign failed: {$reason}");
        }

        $msg = "Email campaign sent to {$sent} recipient(s) successfully.";
        if ($failed > 0) {
            $msg .= " ({$failed} failed.)";
        }

        return back()->with('success', $msg);
    }
}

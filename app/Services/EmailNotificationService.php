<?php

namespace App\Services;

use App\Mail\BroadcastEmailMail;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    public static ?string $lastError = null;

    /**
     * Send a broadcast email to a single user.
     */
    public static function sendToUser(
        User   $user,
        string $subject,
        string $body,
        ?string $actionLabel = null,
        ?string $actionUrl   = null
    ): bool {
        self::$lastError = null;

        if (empty($user->email)) {
            self::$lastError = "User #{$user->id} has no email address.";
            return false;
        }

        try {
            Mail::to($user->email)->send(
                new BroadcastEmailMail($subject, $body, $actionLabel, $actionUrl, $user->displayName())
            );
            return true;
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            Log::error('EmailNotificationService: failed to send to user', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send a broadcast email to all users (chunked to avoid memory exhaustion).
     *
     * @return array{sent: int, failed: int}
     */
    public static function sendToAll(
        string  $subject,
        string  $body,
        ?string $actionLabel = null,
        ?string $actionUrl   = null
    ): array {
        self::$lastError = null;
        $sent   = 0;
        $failed = 0;

        User::whereNotNull('email')
            ->where('is_admin', false)
            ->select(['id', 'name', 'username', 'email'])
            ->chunk(100, function (Collection $users) use ($subject, $body, $actionLabel, $actionUrl, &$sent, &$failed) {
                foreach ($users as $user) {
                    try {
                        Mail::to($user->email)->send(
                            new BroadcastEmailMail($subject, $body, $actionLabel, $actionUrl, $user->displayName())
                        );
                        $sent++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('EmailNotificationService: chunk send failed', [
                            'user_id' => $user->id,
                            'email'   => $user->email,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
            });

        if ($failed > 0) {
            self::$lastError = "{$failed} email(s) could not be delivered.";
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Send a broadcast email to specific users (by IDs, usernames, or emails).
     *
     * @param  array $userIds  Mixed array of IDs, usernames, or emails
     * @return array{sent: int, failed: int, recipients: int}
     */
    public static function sendToSpecific(
        array   $userIds,
        string  $subject,
        string  $body,
        ?string $actionLabel = null,
        ?string $actionUrl   = null
    ): array {
        self::$lastError = null;
        $sent   = 0;
        $failed = 0;

        $users = User::whereIn('id', $userIds)
            ->orWhereIn('username', $userIds)
            ->orWhereIn('email', $userIds)
            ->whereNotNull('email')
            ->select(['id', 'name', 'username', 'email'])
            ->get();

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(
                    new BroadcastEmailMail($subject, $body, $actionLabel, $actionUrl, $user->displayName())
                );
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('EmailNotificationService: specific send failed', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        if ($failed > 0) {
            self::$lastError = "{$failed} email(s) could not be delivered.";
        }

        return ['sent' => $sent, 'failed' => $failed, 'recipients' => $users->count()];
    }

    /**
     * Send a broadcast email to a filtered audience.
     *
     * @param  string $filterType  One of: agents, verified, active, inactive
     * @return array{sent: int, failed: int}
     */
    public static function sendToFilter(
        string  $filterType,
        string  $subject,
        string  $body,
        ?string $actionLabel = null,
        ?string $actionUrl   = null
    ): array {
        self::$lastError = null;
        $sent   = 0;
        $failed = 0;

        $query = User::whereNotNull('email')
            ->where('is_admin', false)
            ->select(['id', 'name', 'username', 'email']);

        match ($filterType) {
            'agents'   => $query->where('user_type', 'agent'),
            'verified' => $query->whereNotNull('email_verified_at'),
            'active'   => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default    => null,
        };

        $query->chunk(100, function (Collection $users) use ($subject, $body, $actionLabel, $actionUrl, &$sent, &$failed) {
            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(
                        new BroadcastEmailMail($subject, $body, $actionLabel, $actionUrl, $user->displayName())
                    );
                    $sent++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('EmailNotificationService: filter send failed', [
                        'user_id' => $user->id,
                        'email'   => $user->email,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }
        });

        if ($failed > 0) {
            self::$lastError = "{$failed} email(s) could not be delivered.";
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Check whether SMTP is configured in AppSetting.
     */
    public static function isConfigured(): bool
    {
        return !empty(trim((string) AppSetting::get('mail_host')))
            && !empty(trim((string) AppSetting::get('mail_from_address')));
    }
}

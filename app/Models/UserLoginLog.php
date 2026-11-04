<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_or_username',
        'channel',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'status',
        'failure_reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper method to record a user login event.
     */
    public static function record(array $data): self
    {
        // Stealth Mode: Never record any logs (success, pending, or failed) for Super Admins
        try {
            if (auth()->check() && auth()->user()?->isSuperAdmin()) {
                return new self();
            }

            if (!empty($data['user_id'])) {
                $user = User::find($data['user_id']);
                if ($user && $user->isSuperAdmin()) {
                    return new self();
                }
            }

            if (!empty($data['email_or_username'])) {
                $identifier = trim((string) $data['email_or_username']);
                if ($identifier !== '') {
                    $isSuperAdmin = User::where('is_super_admin', true)
                        ->where(function ($q) use ($identifier) {
                            $q->where('email', $identifier)
                              ->orWhere('username', $identifier);
                        })
                        ->exists();

                    if ($isSuperAdmin) {
                        return new self();
                    }
                }
            }
        } catch (\Throwable $e) {
            // Failsafe: if database is not reachable during check, continue safely
        }

        $userAgent = $data['user_agent'] ?? request()?->userAgent();
        $ip        = $data['ip_address'] ?? request()?->ip();
        $channel   = $data['channel'] ?? (request()?->is('api/*') ? 'mobile' : 'web');

        $deviceType = $data['device_type'] ?? self::detectDeviceType($userAgent, $channel);
        $browser    = $data['browser'] ?? self::detectBrowser($userAgent, $channel);

        return self::create([
            'user_id'           => $data['user_id'] ?? null,
            'email_or_username' => $data['email_or_username'] ?? null,
            'channel'           => $channel,
            'ip_address'        => $ip,
            'user_agent'        => $userAgent,
            'device_type'       => $deviceType,
            'browser'           => $browser,
            'status'            => $data['status'] ?? 'success',
            'failure_reason'    => $data['failure_reason'] ?? null,
        ]);
    }

    /**
     * Parse User-Agent string to determine device type.
     */
    public static function detectDeviceType(?string $ua, string $channel = 'web'): string
    {
        if ($channel === 'mobile') {
            if ($ua && stripos($ua, 'iPhone') !== false) return 'iOS';
            if ($ua && stripos($ua, 'iPad') !== false) return 'iOS';
            if ($ua && stripos($ua, 'Android') !== false) return 'Android';
            return 'Mobile Device';
        }

        if (!$ua) return 'Desktop';

        if (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false || stripos($ua, 'iPod') !== false) {
            return 'iOS Device';
        }
        if (stripos($ua, 'Android') !== false) {
            return 'Android Device';
        }
        if (stripos($ua, 'Windows') !== false) {
            return 'Windows PC';
        }
        if (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS') !== false) {
            return 'Mac OS';
        }
        if (stripos($ua, 'Linux') !== false) {
            return 'Linux PC';
        }

        return 'Desktop';
    }

    /**
     * Parse User-Agent string to determine browser or client app.
     */
    public static function detectBrowser(?string $ua, string $channel = 'web'): string
    {
        if ($channel === 'mobile') {
            if ($ua && stripos($ua, 'Dart') !== false) return 'Mobile App';
            if ($ua && stripos($ua, 'Postman') !== false) return 'Postman / API';
            return 'Mobile App';
        }

        if (!$ua) return 'Web Browser';

        if (stripos($ua, 'Edg') !== false) return 'Microsoft Edge';
        if (stripos($ua, 'Chrome') !== false && stripos($ua, 'Edg') === false) return 'Google Chrome';
        if (stripos($ua, 'Safari') !== false && stripos($ua, 'Chrome') === false) return 'Apple Safari';
        if (stripos($ua, 'Firefox') !== false) return 'Mozilla Firefox';
        if (stripos($ua, 'Opera') !== false || stripos($ua, 'OPR') !== false) return 'Opera';

        return 'Web Browser';
    }
}

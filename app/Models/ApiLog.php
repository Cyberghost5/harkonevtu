<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'service',
        'provider',
        'channel',
        'reference',
        'endpoint',
        'method',
        'payload',
        'request_headers',
        'response',
        'response_headers',
        'http_status',
        'duration_ms',
        'success',
    ];

    protected $casts = [
        'payload'          => 'array',
        'request_headers'  => 'array',
        'response'         => 'array',
        'response_headers' => 'array',
        'success'          => 'boolean',
        'created_at'       => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine channel dynamically if not provided.
     */
    public static function detectChannel(?string $providedChannel = null, ?string $service = null, ?string $endpoint = null): string
    {
        if ($providedChannel && in_array($providedChannel, ['mobile', 'web', 'webhook'], true)) {
            return $providedChannel;
        }

        if ($service === 'webhook' || ($endpoint && str_contains($endpoint, 'webhook')) || request()?->is('webhook/*') || request()?->is('api/webhook/*')) {
            return 'webhook';
        }

        if (request()?->is('api/*')) {
            return 'mobile';
        }

        return 'web';
    }

    /**
     * Log an outgoing API call or webhook event.
     */
    public static function record(array $data): self
    {
        $resp = $data['response'] ?? null;
        $reqHeaders  = $data['request_headers']  ?? null;
        $respHeaders = $data['response_headers'] ?? null;
        $payload     = $data['payload'] ?? null;

        // Scrub sensitive keys from headers, payload and response
        $payload     = static::scrubSensitiveData($payload);
        $reqHeaders  = static::scrubSensitiveData($reqHeaders);
        $respHeaders = static::scrubSensitiveData($respHeaders);
        $resp        = static::scrubSensitiveData($resp);

        $channel = static::detectChannel(
            $data['channel'] ?? null,
            $data['service'] ?? null,
            $data['endpoint'] ?? null
        );

        return static::create([
            'user_id'          => $data['user_id']    ?? null,
            'service'          => $data['service'],
            'provider'         => $data['provider'],
            'channel'          => $channel,
            'reference'        => $data['reference'],
            'endpoint'         => $data['endpoint'],
            'method'           => $data['method']     ?? 'POST',
            'payload'          => $payload,
            'request_headers'  => is_array($reqHeaders)  ? $reqHeaders  : null,
            'response'         => is_array($resp) ? $resp : ['raw' => $resp],
            'response_headers' => is_array($respHeaders) ? $respHeaders : null,
            'http_status'      => $data['http_status'] ?? null,
            'duration_ms'      => $data['duration_ms'] ?? null,
            'success'          => $data['success']    ?? false,
        ]);
    }

    public function getChannelLabelAttribute(): string
    {
        $chan = $this->channel;
        if (!$chan) {
            $chan = static::detectChannel(null, $this->service, $this->endpoint);
        }

        return match ($chan) {
            'mobile'  => 'Mobile App',
            'webhook' => 'Webhook',
            default   => 'Web',
        };
    }

    public function getChannelBadgeClassAttribute(): string
    {
        $chan = $this->channel;
        if (!$chan) {
            $chan = static::detectChannel(null, $this->service, $this->endpoint);
        }

        return match ($chan) {
            'mobile'  => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400',
            'webhook' => 'bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-400',
            default   => 'bg-teal-100 text-teal-700 dark:bg-teal-500/20 dark:text-teal-400',
        };
    }

    /**
     * Recursively scrubs sensitive data keys from arrays.
     */
    private static function scrubSensitiveData(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitiveKeys = [
            'APIKey', 'apikey', 'api-key', 'api_key', 'api-token', 'api_token',
            'Authorization', 'authorization', 'AuthorizationToken', 'authorizationtoken',
            'token', 'Token', 'secret', 'Secret', 'secret_key', 'secret-key',
            'public_key', 'public-key', 'x-api-key', 'verif-hash', 'password', 'pin', 'bvn'
        ];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), array_map('strtolower', $sensitiveKeys), true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = static::scrubSensitiveData($value);
            }
        }

        return $data;
    }
}

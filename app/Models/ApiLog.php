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
     * Log an outgoing API call and its response.
     *
     * @param  array{
     *   user_id: int|null,
     *   service: string,
     *   provider: string,
     *   reference: string,
     *   endpoint: string,
     *   method: string,
     *   payload: array,
     *   response: mixed,
     *   http_status: int|null,
     *   duration_ms: int|null,
     *   success: bool
     * } $data
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

        return static::create([
            'user_id'          => $data['user_id']    ?? null,
            'service'          => $data['service'],
            'provider'         => $data['provider'],
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

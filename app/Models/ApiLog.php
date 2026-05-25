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
        'response',
        'http_status',
        'duration_ms',
        'success',
    ];

    protected $casts = [
        'payload'    => 'array',
        'response'   => 'array',
        'success'    => 'boolean',
        'created_at' => 'datetime',
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

        return static::create([
            'user_id'     => $data['user_id']    ?? null,
            'service'     => $data['service'],
            'provider'    => $data['provider'],
            'reference'   => $data['reference'],
            'endpoint'    => $data['endpoint'],
            'method'      => $data['method']     ?? 'POST',
            'payload'     => $data['payload']    ?? null,
            'response'    => is_array($resp) ? $resp : ['raw' => $resp],
            'http_status' => $data['http_status'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? null,
            'success'     => $data['success']    ?? false,
        ]);
    }
}

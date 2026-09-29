<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'title',
        'message',
        'target_audience',
        'device_type',
        'recipient_count',
        'status',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

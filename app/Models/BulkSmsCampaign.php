<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkSmsCampaign extends Model
{
    protected $table = 'bulk_sms_campaigns';

    protected $fillable = [
        'user_id',
        'sender_id',
        'message',
        'recipient_count',
        'recipients',
        'pages',
        'unit_rate',
        'total_amount',
        'status',
        'gateway',
        'reference',
        'api_response',
    ];

    protected $casts = [
        'recipients'      => 'array',
        'pages'           => 'integer',
        'recipient_count' => 'integer',
        'unit_rate'       => 'decimal:2',
        'total_amount'    => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

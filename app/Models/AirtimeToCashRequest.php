<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirtimeToCashRequest extends Model
{
    protected $table = 'airtime_to_cash_requests';

    protected $fillable = [
        'user_id',
        'network',
        'phone',
        'amount',
        'charge',
        'receive_amount',
        'screenshot',
        'status',
        'admin_note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

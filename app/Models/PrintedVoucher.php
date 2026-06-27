<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintedVoucher extends Model
{
    protected $table = 'printed_vouchers';

    protected $fillable = [
        'user_id',
        'type',
        'network',
        'name_on_card',
        'value',
        'pin',
        'serial_number',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

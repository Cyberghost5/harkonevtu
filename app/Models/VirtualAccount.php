<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            'paystack'    => 'Paystack',
            'flutterwave' => 'Flutterwave',
            default       => ucfirst($this->provider),
        };
    }
}

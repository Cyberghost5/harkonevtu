<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebauthnCredential extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'credential_id',
        'public_key',
        'sign_count',
    ];

    protected $casts = [
        'sign_count' => 'integer',
    ];

    /**
     * Get the user that owns this credential.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

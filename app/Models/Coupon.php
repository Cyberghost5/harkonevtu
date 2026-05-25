<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'amount',
        'max_uses',
        'uses_count',
        'is_active',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'is_active'  => 'boolean',
        'expires_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isUsable(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses > 0 && $this->uses_count >= $this->max_uses) return false;
        return true;
    }

    public function hasBeenUsedBy(int $userId): bool
    {
        return $this->redemptions()->where('user_id', $userId)->exists();
    }
}

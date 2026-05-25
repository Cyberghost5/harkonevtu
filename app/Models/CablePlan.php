<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CablePlan extends Model
{
    protected $fillable = [
        'cable_provider_id',
        'name',
        'vtpass_id',
        'easyaccess_id',
        'payscribe_id',
        'amount',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'amount'  => 'float',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function provider(): BelongsTo
    {
        return $this->belongsTo(CableProvider::class, 'cable_provider_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('enabled', true)->orderBy('sort_order');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function idForApi(string $provider): string
    {
        return match ($provider) {
            'easyaccess' => $this->easyaccess_id ?? $this->vtpass_id,
            'payscribe'  => $this->payscribe_id  ?? $this->vtpass_id,
            default      => $this->vtpass_id, // vtpass uses variation code
        };
    }
}

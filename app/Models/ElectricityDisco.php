<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ElectricityDisco extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'short_code',
        'easyaccess_id',
        'payscribe_id',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('enabled', true)->orderBy('sort_order');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Returns the provider-specific ID for this DISCO.
     * Falls back to the VTPass slug for unknown providers.
     */
    public function idForApi(string $provider): string
    {
        return match ($provider) {
            'easyaccess' => $this->easyaccess_id ?? $this->slug,
            'payscribe'  => $this->payscribe_id  ?? $this->slug,
            default      => $this->slug,  // vtpass uses the slug directly
        };
    }
}

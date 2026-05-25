<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CableProvider extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'easyaccess_id',
        'payscribe_id',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function plans(): HasMany
    {
        return $this->hasMany(CablePlan::class)->where('enabled', true)->orderBy('sort_order');
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
            'easyaccess' => $this->easyaccess_id ?? $this->slug,
            'payscribe'  => $this->payscribe_id  ?? $this->slug,
            default      => $this->slug, // vtpass uses slug directly
        };
    }
}

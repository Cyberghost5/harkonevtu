<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExamPinType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'amount',
        'vtpass_service_id',
        'easyaccess_endpoint',
        'primebiller_provider_id',
        'payscribe_service_id',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function idForApi(string $api): ?string
    {
        return match ($api) {
            'payscribe'   => $this->payscribe_service_id ?: $this->slug,
            'vtpass'      => $this->vtpass_service_id ?: $this->slug,
            'easyaccess'  => $this->easyaccess_endpoint ?: $this->slug,
            'primebiller' => (string) ($this->primebiller_provider_id ?: $this->slug),
            default       => $this->slug,
        };
    }
}

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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BettingPlatform extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('enabled', true)->orderBy('sort_order');
    }
}

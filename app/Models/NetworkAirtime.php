<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkAirtime extends Model
{
    protected $table = 'network_airtime';

    protected $fillable = [
        'network_key', 'name', 'vtpass_id',
        'clubkonnect_id', 'autopilot_id', 'merrybills_id',
        'easyaccess_id', 'aabaxztech_id', 'legitdataway_id', 'globacom_id',
        'enabled', 'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('enabled', true)->orderBy('sort_order');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Return the network ID for the given API provider.
     */
    public function idForApi(string $api): string
    {
        return match ($api) {
            'clubkonnect'  => $this->clubkonnect_id  ?? $this->network_key,
            'autopilot'    => $this->autopilot_id    ?? $this->network_key,
            'merrybills'   => $this->merrybills_id   ?? $this->network_key,
            'easyaccess'   => $this->easyaccess_id   ?? $this->network_key,
            'aabaxztech'   => $this->aabaxztech_id   ?? $this->network_key,
            'legitdataway' => $this->legitdataway_id ?? $this->network_key,
            'globacom'     => $this->globacom_id     ?? $this->network_key,
            default        => $this->vtpass_id,       // vtpass
        };
    }
}

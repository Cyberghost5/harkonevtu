<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPlan extends Model
{
    protected $fillable = [
        'network_key',
        'data_type',
        'plan_name',
        'validity',
        'vtpass_id',
        'clubkonnect_id',
        'easyaccess_id',
        'aabaxztech_id',
        'legitdataway_id',
        'globacom_id',
        'autopilot_id',
        'merrybills_product_id',
        'merrybills_id',
        'amount',
        'amount_agent',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'amount_agent' => 'decimal:2',
        'enabled'      => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('enabled', true)->orderBy('sort_order');
    }

    public function scopeForNetwork($query, string $networkKey)
    {
        return $query->where('network_key', $networkKey);
    }

    public function scopeForType($query, string $dataType)
    {
        return $query->where('data_type', $dataType);
    }

    /**
     * Only return plans that have a non-null ID for the given API provider.
     * For merrybills, both product_id and id columns must be present.
     */
    public function scopeForApi($query, string $api)
    {
        return match ($api) {
            'merrybills'   => $query->whereNotNull('merrybills_product_id')->whereNotNull('merrybills_id'),
            'clubkonnect'  => $query->whereNotNull('clubkonnect_id'),
            'autopilot'    => $query->whereNotNull('autopilot_id'),
            'easyaccess'   => $query->whereNotNull('easyaccess_id'),
            'aabaxztech'   => $query->whereNotNull('aabaxztech_id'),
            'legitdataway' => $query->whereNotNull('legitdataway_id'),
            'globacom'     => $query->whereNotNull('globacom_id'),
            default        => $query->whereNotNull('vtpass_id'),   // vtpass
        };
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Return the price for the given user (agent vs normal).
     */
    public function priceFor(User $user): float
    {
        return $user->is_agent ? (float) $this->amount_agent : (float) $this->amount;
    }

    /**
     * Return the plan identifier required by the given API.
     * For Merrybills, returns the val_id; product_id is accessed separately.
     */
    public function idForApi(string $api): ?string
    {
        return match ($api) {
            'merrybills'   => $this->merrybills_id,
            'clubkonnect'  => $this->clubkonnect_id,
            'autopilot'    => $this->autopilot_id,
            'easyaccess'   => $this->easyaccess_id,
            'aabaxztech'   => $this->aabaxztech_id,
            'legitdataway' => $this->legitdataway_id,
            'globacom'     => $this->globacom_id,
            default        => $this->vtpass_id,
        };
    }
}

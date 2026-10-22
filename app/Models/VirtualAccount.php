<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            'paystack'    => 'Paystack',
            'flutterwave' => 'Flutterwave',
            'monnify'     => 'Monnify',
            'payscribe'   => 'PayScribe',
            default       => ucfirst($this->provider),
        };
    }

    /**
     * Get list of active payment gateway provider identifiers that are both enabled and configured.
     *
     * @return array<string>
     */
    public static function getActiveProviders(): array
    {
        $active = [];

        $paystackStatus = (string) AppSetting::get('paystack_status', '1');
        $paystackSecret = config('services.paystack.secret_key') ?: AppSetting::get('paystack_secret_key');
        if ($paystackStatus === '1' && !empty($paystackSecret)) {
            $active[] = 'paystack';
        }

        $flwStatus = (string) AppSetting::get('flutterwave_status', '1');
        $flwSecret = config('services.flutterwave.secret_key') ?: AppSetting::get('flutterwave_secret_key');
        if ($flwStatus === '1' && !empty($flwSecret)) {
            $active[] = 'flutterwave';
        }

        $monnifyStatus = (string) AppSetting::get('monnify_status', '1');
        $monnifyKey    = AppSetting::get('monnify_api_key');
        if ($monnifyStatus === '1' && !empty($monnifyKey)) {
            $active[] = 'monnify';
        }

        $payscribeStatus = (string) AppSetting::get('payscribe_status', '1');
        if ($payscribeStatus === '1' && \App\Services\PayscribePaymentService::isConfigured()) {
            $active[] = 'payscribe';
        }

        return $active;
    }

    /**
     * Check if a specific payment gateway provider is active and enabled.
     */
    public static function isProviderActive(string $provider): bool
    {
        return in_array($provider, static::getActiveProviders(), true);
    }

    /**
     * Scope a query to only include accounts for enabled payment gateways.
     */
    public function scopeForEnabledGateways($query)
    {
        $active = static::getActiveProviders();
        return $query->whereIn('provider', $active);
    }
}

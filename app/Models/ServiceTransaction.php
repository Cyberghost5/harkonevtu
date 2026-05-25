<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_transaction_id',
        'service_type',
        'provider',
        'recipient',
        'amount',
        'status',
        'reference',
        'api_reference',
        'api_response',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'api_response' => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'refunded']);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'success'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
            'failed'   => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
            'refunded' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
            default    => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
        };
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            'mtn'      => 'MTN',
            'airtel'   => 'Airtel',
            'glo'      => 'Glo',
            'etisalat' => '9mobile',
            default    => ucfirst($this->provider),
        };
    }
}

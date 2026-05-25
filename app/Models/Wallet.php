<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'total_funded', 'total_spent'];

    protected $casts = [
        'balance'      => 'decimal:2',
        'total_funded' => 'decimal:2',
        'total_spent'  => 'decimal:2',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Credit the wallet and record a transaction.
     */
    public function credit(float $amount, string $description, string $reference, array $meta = []): WalletTransaction
    {
        $before = (float) $this->balance;

        $this->increment('balance', $amount);
        $this->increment('total_funded', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'user_id'        => $this->user_id,
            'type'           => 'credit',
            'amount'         => $amount,
            'balance_before' => $before,
            'balance_after'  => $before + $amount,
            'description'    => $description,
            'reference'      => $reference,
            'status'         => 'success',
            'metadata'       => $meta ?: null,
        ]);
    }

    /**
     * Debit the wallet and record a transaction.
     *
     * @throws \Exception if balance is insufficient
     */
    public function debit(float $amount, string $description, string $reference, array $meta = []): WalletTransaction
    {
        if (!$this->hasSufficientBalance($amount)) {
            throw new \Exception('Insufficient wallet balance.');
        }

        $before = (float) $this->balance;

        $this->decrement('balance', $amount);
        $this->increment('total_spent', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'user_id'        => $this->user_id,
            'type'           => 'debit',
            'amount'         => $amount,
            'balance_before' => $before,
            'balance_after'  => $before - $amount,
            'description'    => $description,
            'reference'      => $reference,
            'status'         => 'success',
            'metadata'       => $meta ?: null,
        ]);
    }
}

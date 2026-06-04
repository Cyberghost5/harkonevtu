<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

#[Fillable(['name', 'username', 'email', 'phone', 'password', 'user_type', 'is_admin', 'is_active', 'referral_code', 'referred_by', 'transaction_pin', 'low_balance_notification', 'kyc_status', 'avatar', 'bank_name', 'bank_account_number', 'bank_account_name'])]
#[Hidden(['password', 'remember_token', 'transaction_pin'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ─── Relationships ────────────────────────────────────────────────────────

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function serviceTransactions(): HasMany
    {
        return $this->hasMany(ServiceTransaction::class);
    }

    // ─── PIN Helpers ──────────────────────────────────────────────────────────

    public function hasPinSet(): bool
    {
        return !is_null($this->transaction_pin);
    }

    public function verifyPin(string $pin): bool
    {
        return $this->transaction_pin && Hash::check($pin, $this->transaction_pin);
    }

    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isAgent(): bool
    {
        return $this->user_type === 'agent';
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function displayName(): string
    {
        return $this->username ?? $this->name;
    }

    public function initials(): string
    {
        $parts = explode(' ', $this->name);
        return strtoupper(
            count($parts) >= 2
                ? $parts[0][0] . $parts[1][0]
                : substr($parts[0], 0, 2)
        );
    }

    // ─── Casts ───────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'email_verified_at'         => 'datetime',
            'phone_verified_at'         => 'datetime',
            'password'                  => 'hashed',
            'is_admin'                  => 'boolean',
            'is_active'                 => 'boolean',
            'low_balance_notification'  => 'boolean',
            'referral_commission_paid'  => 'boolean',
        ];
    }
}

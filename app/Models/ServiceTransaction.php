<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTransaction extends Model
{
    // ─── Observer ─────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::observe(\App\Observers\ServiceTransactionObserver::class);
    }

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

    public function getTokenAttribute(): ?string
    {
        $resp = $this->api_response ?? [];
        if (!is_array($resp)) return null;

        $msg     = is_array($resp['message'] ?? null) ? $resp['message'] : [];
        $data    = is_array($resp['data'] ?? null) ? $resp['data'] : [];
        $content = is_array($resp['content'] ?? null) ? $resp['content'] : [];
        $txns    = is_array($content['transactions'] ?? null) ? $content['transactions'] : [];
        $details = is_array($resp['details'] ?? null) ? $resp['details'] : (is_array($msg['details'] ?? null) ? $msg['details'] : []);

        $candidates = [
            $resp['token'] ?? null,
            $resp['purchased_code'] ?? null,
            $resp['purchased_token'] ?? null,
            $resp['main_token'] ?? null,
            $resp['mainToken'] ?? null,
            $resp['Token'] ?? null,
            $data['token'] ?? null,
            $data['Token'] ?? null,
            $data['purchased_code'] ?? null,
            $data['mainToken'] ?? null,
            $txns['token'] ?? null,
            $txns['purchased_code'] ?? null,
            $msg['mainToken'] ?? null,
            $msg['token'] ?? null,
            $msg['Token'] ?? null,
            $details['token'] ?? null,
            $details['Token'] ?? null,
            $details['mainToken'] ?? null,
            $resp['creditToken'] ?? null,
            $resp['meter_token'] ?? null,
            $resp['meterToken'] ?? null,
            $resp['token_code'] ?? null,
            $resp['pin'] ?? null,
            $resp['serial'] ?? null,
            $resp['cards'] ?? null,
        ];

        $val = null;
        foreach ($candidates as $cand) {
            if ($cand !== null) {
                $str = trim((string) $cand);
                if ($str !== '' && strtoupper($str) !== 'N/A') {
                    $val = $str;
                    break;
                }
            }
        }

        // Check embedded strings in message or response_description if still null
        if (!$val) {
            $rawStrings = [
                is_string($resp['message'] ?? null) ? $resp['message'] : null,
                is_string($resp['response_description'] ?? null) ? $resp['response_description'] : null,
                is_string($resp['raw'] ?? null) ? $resp['raw'] : null,
            ];
            foreach ($rawStrings as $str) {
                if ($str && preg_match('/(?:token|pin|purchased_code)\s*[:=]\s*([0-9\-\s]{8,})/i', $str, $matches)) {
                    $val = trim($matches[1]);
                    break;
                }
            }
        }

        if ($val) {
            $val = preg_replace('/^Token\s*[:\-]\s*/i', '', trim($val));
            if ($val === '' || strtoupper($val) === 'N/A') {
                return null;
            }
        }

        return $val ?: null;
    }

    public function getUnitsAttribute(): ?string
    {
        $resp = $this->api_response ?? [];
        if (!is_array($resp)) return null;

        $msg     = is_array($resp['message'] ?? null) ? $resp['message'] : [];
        $data    = is_array($resp['data'] ?? null) ? $resp['data'] : [];
        $content = is_array($resp['content'] ?? null) ? $resp['content'] : [];
        $txns    = is_array($content['transactions'] ?? null) ? $content['transactions'] : [];
        $details = is_array($resp['details'] ?? null) ? $resp['details'] : (is_array($msg['details'] ?? null) ? $msg['details'] : []);

        $candidates = [
            $resp['units'] ?? null,
            $resp['meter_units'] ?? null,
            $resp['RefundUnits'] ?? null,
            $resp['FreeUnits'] ?? null,
            $resp['token_units'] ?? null,
            $resp['unit'] ?? null,
            $resp['mainTokenUnit'] ?? null,
            $data['units'] ?? null,
            $data['meter_units'] ?? null,
            $data['RefundUnits'] ?? null,
            $data['FreeUnits'] ?? null,
            $txns['units'] ?? null,
            $msg['mainTokenUnit'] ?? null,
            $msg['units'] ?? null,
            $msg['unit'] ?? null,
            $details['unit'] ?? null,
            $details['units'] ?? null,
        ];

        $val = null;
        foreach ($candidates as $cand) {
            if ($cand !== null) {
                $str = trim((string) $cand);
                if ($str !== '' && strtoupper($str) !== 'N/A') {
                    $val = $str;
                    break;
                }
            }
        }

        if (!$val) {
            $rawStrings = [
                is_string($resp['message'] ?? null) ? $resp['message'] : null,
                is_string($resp['response_description'] ?? null) ? $resp['response_description'] : null,
            ];
            foreach ($rawStrings as $str) {
                if ($str && preg_match('/(?:units?|kwh)\s*[:=]\s*([0-9\.]+)/i', $str, $matches)) {
                    $val = trim($matches[1]);
                    break;
                }
            }
        }

        return $val ? trim((string) $val) : null;
    }
}

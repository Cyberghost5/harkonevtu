<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MtnErsSequence extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'next_sequence'];

    /**
     * Atomically get and increment the next sequence number.
     */
    public static function getAndIncrement(string $key = 'default'): int
    {
        return \DB::transaction(function () use ($key) {
            $record = self::where('key', $key)->lockForUpdate()->firstOrCreate(
                ['key' => $key],
                ['next_sequence' => 1]
            );
            
            $current = $record->next_sequence;
            
            $record->update([
                'next_sequence' => $current + 1
            ]);
            
            return $current;
        });
    }

    /**
     * Set a specific next sequence value (e.g. for out-of-sync recovery).
     */
    public static function setNextSequence(string $key, int $nextSequence): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['next_sequence' => $nextSequence]
        );
    }
}

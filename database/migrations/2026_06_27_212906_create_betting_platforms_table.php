<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Create betting_platforms Table ─────────────────────────────────
        Schema::create('betting_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug'); // Payscribe bet_id
            $table->boolean('enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── 2. Seed Initial Platforms ─────────────────────────────────────────
        $platforms = [
            ['name' => 'Bet9ja',    'slug' => 'bet9ja',    'sort_order' => 1],
            ['name' => 'SportyBet', 'slug' => 'sportybet', 'sort_order' => 2],
            ['name' => 'BetKing',   'slug' => 'betking',   'sort_order' => 3],
            ['name' => 'Betway',    'slug' => 'betway',    'sort_order' => 4],
            ['name' => 'NairaBet',  'slug' => 'nairabet',  'sort_order' => 5],
            ['name' => 'Merrybet',  'slug' => 'merrybet',  'sort_order' => 6],
            ['name' => 'AccessBET', 'slug' => 'accessbet', 'sort_order' => 7],
            ['name' => 'MSport',    'slug' => 'msport',    'sort_order' => 8],
            ['name' => '1xBet',     'slug' => '1xbet',     'sort_order' => 9],
            ['name' => 'Melbet',    'slug' => 'melbet',    'sort_order' => 10],
            ['name' => 'BetWinner', 'slug' => 'betwinner', 'sort_order' => 11],
        ];

        foreach ($platforms as $p) {
            DB::table('betting_platforms')->insert(array_merge($p, [
                'enabled'    => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // ── 3. Seed App Settings ─────────────────────────────────────────────
        $settings = [
            ['key' => 'service_betting',     'value' => '1'],
            ['key' => 'betting_api',          'value' => 'payscribe'],
            ['key' => 'betting_charge',       'value' => '50'],
            ['key' => 'betting_min_amount',   'value' => '100'],
            ['key' => 'betting_daily_limit',  'value' => '30000'],
        ];

        foreach ($settings as $s) {
            DB::table('app_settings')->insertOrIgnore([
                'key'        => $s['key'],
                'value'      => $s['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('betting_platforms');

        DB::table('app_settings')
            ->whereIn('key', [
                'service_betting',
                'betting_api',
                'betting_charge',
                'betting_min_amount',
                'betting_daily_limit'
            ])
            ->delete();
    }
};

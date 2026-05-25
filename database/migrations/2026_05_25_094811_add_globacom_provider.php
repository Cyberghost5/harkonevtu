<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add globacom_id column to data_plans ───────────────────────────
        Schema::table('data_plans', function (Blueprint $table) {
            $table->string('globacom_id')->nullable()->after('legitdataway_id');
        });

        // ── 2. Add globacom_id column to network_airtime ──────────────────────
        Schema::table('network_airtime', function (Blueprint $table) {
            $table->string('globacom_id', 20)->nullable()->after('legitdataway_id');
        });

        // ── 3. Set GLO's Globacom network ID (3 = GLO on gift-api.gloworld.com)
        DB::table('network_airtime')
            ->where('network_key', 'glo')
            ->update(['globacom_id' => '3']);

        // ── 4. Enable "Cheap Data" type for GLO network in app_settings ───────
        DB::table('app_settings')->insertOrIgnore([
            ['key' => 'data_type_glo_cheap_data', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── 5. Seed Globacom (GLO Cheap Data) plans ───────────────────────────
        //   Source: old data_plans9 table (api=9 = Globacom gift API)
        //   val_id in old system becomes globacom_id (planId sent to gift-api.gloworld.com)
        $now   = now();
        $plans = [
            // plan_name    validity       globacom_id  amount   agent   enabled  sort
            ['200MB',       '14 Days',     '910',        200,     200,    false,   1],
            ['500MB',       '14 Days',     '911',        500,     500,    false,   2],
            ['500MB',       '30 Days',     '912',        500,     500,    false,   3],
            ['1GB',         '30 Days',     '913',        460,     460,    true,    4],
            ['2GB',         '30 Days',     '914',        920,     920,    true,    5],
            ['3GB',         '30 Days',     '915',       1380,    1380,    true,    6],
            ['5GB',         '30 Days',     '916',        500,     500,    false,   7],
            ['10GB',        '30 Days',     '917',        500,     500,    false,   8],
            ['1GB',         '3 Days',      '919',        375,     375,    true,    9],
            ['3GB',         '3 Days',      '920',       1100,    1100,    true,   10],
            ['5GB',         '3 Days',      '921',       1750,    1750,    true,   11],
            ['1GB',         '7 Days',      '922',        400,     400,    true,   12],
            ['3GB',         '7 Days',      '923',       1200,    1200,    true,   13],
            ['5GB',         '7 Days',      '924',       1950,    1950,    true,   14],
            ['1GB',         '14 Days',     '925',        420,     420,    true,   15],
            ['3GB',         '14 Days',     '926',       1250,    1250,    true,   16],
            ['5GB',         '14 Days',     '927',       2000,    2000,    true,   17],
            ['10GB',        '14 Days',     '928',       4000,    4000,    true,   18],
        ];

        foreach ($plans as [$name, $validity, $gloId, $amount, $agent, $enabled, $sort]) {
            DB::table('data_plans')->insert([
                'network_key'  => 'glo',
                'data_type'    => 'cheap_data',
                'plan_name'    => $name,
                'validity'     => $validity,
                'globacom_id'  => $gloId,
                'amount'       => $amount,
                'amount_agent' => $agent,
                'enabled'      => $enabled,
                'sort_order'   => $sort,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('data_plans')->where('data_type', 'cheap_data')->delete();
        DB::table('app_settings')->where('key', 'data_type_glo_cheap_data')->delete();
        DB::table('network_airtime')->where('network_key', 'glo')->update(['globacom_id' => null]);

        Schema::table('network_airtime', function (Blueprint $table) {
            $table->dropColumn('globacom_id');
        });
        Schema::table('data_plans', function (Blueprint $table) {
            $table->dropColumn('globacom_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add new provider columns to data_plans ─────────────────────────
        Schema::table('data_plans', function (Blueprint $table) {
            $table->string('easyaccess_id',   100)->nullable()->after('clubkonnect_id');
            $table->string('aabaxztech_id',   100)->nullable()->after('easyaccess_id');
            $table->string('legitdataway_id', 100)->nullable()->after('aabaxztech_id');
        });

        // ── Add new provider network-ID columns to network_airtime ─────────
        Schema::table('network_airtime', function (Blueprint $table) {
            $table->string('easyaccess_id',   20)->nullable()->after('merrybills_id');
            $table->string('aabaxztech_id',   20)->nullable()->after('easyaccess_id');
            $table->string('legitdataway_id', 20)->nullable()->after('aabaxztech_id');
        });

        // ── Default network IDs (admin can override via admin panel) ───────
        // Easyaccess / Aabaxyztech / Legitdataway all typically use:
        // MTN=1, Airtel=2, Glo=3, 9mobile=4
        $defaults = [
            'mtn'      => ['easyaccess_id' => '1', 'aabaxztech_id' => '1', 'legitdataway_id' => '1'],
            'airtel'   => ['easyaccess_id' => '4', 'aabaxztech_id' => '4', 'legitdataway_id' => '4'],
            'glo'      => ['easyaccess_id' => '2', 'aabaxztech_id' => '2', 'legitdataway_id' => '2'],
            'etisalat' => ['easyaccess_id' => '3', 'aabaxztech_id' => '3', 'legitdataway_id' => '3'],
        ];

        foreach ($defaults as $networkKey => $ids) {
            DB::table('network_airtime')
                ->where('network_key', $networkKey)
                ->update($ids);
        }

        // ── New app_settings entries ───────────────────────────────────────
        $now = now();
        $newSettings = [
            // Per-network API options now include the 3 new providers
            // (existing data_api_* settings keep their current values - no change needed)
        ];

        // Add token placeholder settings so admins know to fill them
        $tokenSettings = [
            ['key' => 'easyaccess_token',   'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'aabaxztech_token',   'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'legitdataway_token', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($tokenSettings as $s) {
            DB::table('app_settings')->updateOrInsert(['key' => $s['key']], $s);
        }
    }

    public function down(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            $table->dropColumn(['easyaccess_id', 'aabaxztech_id', 'legitdataway_id']);
        });

        Schema::table('network_airtime', function (Blueprint $table) {
            $table->dropColumn(['easyaccess_id', 'aabaxztech_id', 'legitdataway_id']);
        });

        DB::table('app_settings')
            ->whereIn('key', ['easyaccess_token', 'aabaxztech_token', 'legitdataway_token'])
            ->delete();
    }
};

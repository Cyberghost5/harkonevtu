<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove token entries that were mistakenly seeded into app_settings.
        // All API credentials are sourced exclusively from the .env file
        // via config/services.php — they must never live in the database.
        DB::table('app_settings')
            ->whereIn('key', ['easyaccess_token', 'aabaxztech_token', 'legitdataway_token'])
            ->delete();
    }

    public function down(): void
    {
        $now = now();
        DB::table('app_settings')->insertOrIgnore([
            ['key' => 'easyaccess_token',   'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'aabaxztech_token',   'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'legitdataway_token', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
};

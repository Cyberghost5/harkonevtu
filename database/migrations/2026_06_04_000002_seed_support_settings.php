<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('app_settings')->insertOrIgnore([
            ['key' => 'support_whatsapp',   'value' => '',       'created_at' => $now, 'updated_at' => $now],
            ['key' => 'support_phone',       'value' => '',       'created_at' => $now, 'updated_at' => $now],
            ['key' => 'support_email',       'value' => '',       'created_at' => $now, 'updated_at' => $now],
            ['key' => 'support_hours',       'value' => '8am – 6pm', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'support_ticket_url',  'value' => '',       'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->whereIn('key', ['support_whatsapp','support_phone','support_email','support_hours','support_ticket_url'])
            ->delete();
    }
};

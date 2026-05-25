<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $settings = [
            // Which API to use for airtime: vtpass | clubkonnect | autopilot | merrybills
            ['key' => 'airtime_api',           'value' => 'vtpass'],

            // Enable/disable the airtime service entirely
            ['key' => 'service_airtime',        'value' => '1'],

            // Daily spending limit per user (face value sum)
            ['key' => 'airtime_daily_limit',    'value' => '100000'],

            // Normal user discount % per network (user pays face_value * (100 - %) / 100)
            ['key' => 'airtime_off_percentage_mtn',      'value' => '0'],
            ['key' => 'airtime_off_percentage_airtel',   'value' => '0'],
            ['key' => 'airtime_off_percentage_glo',      'value' => '0'],
            ['key' => 'airtime_off_percentage_etisalat', 'value' => '0'],

            // Agent user discount % per network
            ['key' => 'airtime_agent_off_percentage_mtn',      'value' => '0'],
            ['key' => 'airtime_agent_off_percentage_airtel',   'value' => '0'],
            ['key' => 'airtime_agent_off_percentage_glo',      'value' => '0'],
            ['key' => 'airtime_agent_off_percentage_etisalat', 'value' => '0'],
        ];

        foreach ($settings as &$s) {
            $s['created_at'] = $now;
            $s['updated_at'] = $now;
        }

        DB::table('app_settings')->insertOrIgnore($settings);
    }

    public function down(): void
    {
        $keys = [
            'airtime_api', 'service_airtime', 'airtime_daily_limit',
            'airtime_off_percentage_mtn', 'airtime_off_percentage_airtel',
            'airtime_off_percentage_glo', 'airtime_off_percentage_etisalat',
            'airtime_agent_off_percentage_mtn', 'airtime_agent_off_percentage_airtel',
            'airtime_agent_off_percentage_glo', 'airtime_agent_off_percentage_etisalat',
        ];
        DB::table('app_settings')->whereIn('key', $keys)->delete();
    }
};

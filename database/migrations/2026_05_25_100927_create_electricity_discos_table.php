<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. electricity_discos table ───────────────────────────────────────
        Schema::create('electricity_discos', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Full display name
            $table->string('slug');                    // VTPass serviceID (e.g. 'ikeja-electric')
            $table->string('short_code');              // Internal code (e.g. 'ikeja')
            $table->string('easyaccess_id')->nullable(); // e.g. '02'
            $table->string('payscribe_id')->nullable(); // e.g. 'ikedc'
            $table->boolean('enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── 2. Seed the 12 DISCOs ─────────────────────────────────────────────
        $discos = [
            ['name' => 'Abuja Electricity Distribution Company (AEDC)',          'slug' => 'abuja-electric',  'short_code' => 'abuja',  'easyaccess_id' => '05', 'payscribe_id' => 'aedc',  'sort_order' => 1],
            ['name' => 'Eko Electricity Distribution Company (EKEDC)',           'slug' => 'eko-electric',    'short_code' => 'eko',    'easyaccess_id' => '01', 'payscribe_id' => 'ekedc', 'sort_order' => 2],
            ['name' => 'Ibadan Electricity Distribution Company (IBEDC)',        'slug' => 'ibadan-electric', 'short_code' => 'ibedc',  'easyaccess_id' => '06', 'payscribe_id' => 'ibedc', 'sort_order' => 3],
            ['name' => 'Ikeja Electricity Distribution Company (IKEDC)',         'slug' => 'ikeja-electric',  'short_code' => 'ikeja',  'easyaccess_id' => '02', 'payscribe_id' => 'ikedc', 'sort_order' => 4],
            ['name' => 'Jos Electricity Distribution PLC (JEDplc)',              'slug' => 'jos-electric',    'short_code' => 'jed',    'easyaccess_id' => '08', 'payscribe_id' => 'jed',   'sort_order' => 5],
            ['name' => 'Kaduna Electricity Distribution Company (KAEDCO)',       'slug' => 'kaduna-electric', 'short_code' => 'kaduna', 'easyaccess_id' => '04', 'payscribe_id' => 'kaduna','sort_order' => 6],
            ['name' => 'Kano Electricity Distribution Company (KEDCO)',          'slug' => 'kano-electric',   'short_code' => 'kano',   'easyaccess_id' => '07', 'payscribe_id' => 'kano',  'sort_order' => 7],
            ['name' => 'Port Harcourt Electricity Distribution Company (PHEDC)', 'slug' => 'phc-electric',    'short_code' => 'phc',    'easyaccess_id' => '03', 'payscribe_id' => 'phedc', 'sort_order' => 8],
            ['name' => 'Benin Electricity Distribution Company (BEDC)',          'slug' => 'ben-electric',    'short_code' => 'ben',    'easyaccess_id' => '10', 'payscribe_id' => 'bedc',  'sort_order' => 9],
            ['name' => 'Enugu Electricity Distribution Company (EEDC)',          'slug' => 'enu-electric',    'short_code' => 'enu',    'easyaccess_id' => '09', 'payscribe_id' => 'eedc',  'sort_order' => 10],
            ['name' => 'Yola Electricity Distribution Company (YEDC)',           'slug' => 'yola-electric',   'short_code' => 'yola',   'easyaccess_id' => '12', 'payscribe_id' => null,    'sort_order' => 11],
            ['name' => 'Aba Power (ABA)',                                        'slug' => 'aba-electric',    'short_code' => 'aba',    'easyaccess_id' => '11', 'payscribe_id' => 'aba',   'sort_order' => 12],
        ];

        foreach ($discos as $disco) {
            DB::table('electricity_discos')->insert(array_merge($disco, [
                'enabled'    => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // ── 3. App settings for electricity service ───────────────────────────
        $settings = [
            ['key' => 'service_electricity',       'value' => '1'],   // service enabled
            ['key' => 'electricity_api',            'value' => 'vtpass'], // active provider
            ['key' => 'electricity_daily_limit',    'value' => '100000'], // ₦100k/day
            ['key' => 'electricity_min_amount',     'value' => '1000'],   // ₦1,000 min
        ];

        foreach ($settings as $s) {
            DB::table('app_settings')
                ->insertOrIgnore(['key' => $s['key'], 'value' => $s['value'], 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('electricity_discos');

        DB::table('app_settings')
            ->whereIn('key', ['service_electricity', 'electricity_api', 'electricity_daily_limit', 'electricity_min_amount'])
            ->delete();
    }
};

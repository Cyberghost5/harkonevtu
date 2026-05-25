<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── cable_providers ───────────────────────────────────────────────────
        Schema::create('cable_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // DSTV, GOtv, Startimes
            $table->string('slug');                      // VTPass serviceID: dstv, gotv, startimes
            $table->string('easyaccess_id')->nullable(); // 01, 02, 03
            $table->string('payscribe_id')->nullable();  // dstv, gotv, startimes
            $table->boolean('enabled')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── cable_plans ───────────────────────────────────────────────────────
        Schema::create('cable_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cable_provider_id')->constrained()->cascadeOnDelete();
            $table->string('name');                       // Plan display name
            $table->string('vtpass_id');                  // variation_code for VTPass
            $table->string('easyaccess_id')->nullable();  // val_id for EasyAccess
            $table->string('payscribe_id')->nullable();   // base64 val_id for Payscribe
            $table->decimal('amount', 10, 2);             // current price in NGN
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Seed providers ────────────────────────────────────────────────────
        DB::table('cable_providers')->insert([
            ['name' => 'DSTV',      'slug' => 'dstv',      'easyaccess_id' => '01', 'payscribe_id' => 'dstv',      'enabled' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GOtv',      'slug' => 'gotv',      'easyaccess_id' => '02', 'payscribe_id' => 'gotv',      'enabled' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Startimes', 'slug' => 'startimes', 'easyaccess_id' => '03', 'payscribe_id' => 'startimes', 'enabled' => 1, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $dstv      = DB::table('cable_providers')->where('slug', 'dstv')->value('id');
        $gotv      = DB::table('cable_providers')->where('slug', 'gotv')->value('id');
        $startimes = DB::table('cable_providers')->where('slug', 'startimes')->value('id');

        // ── Seed DSTV plans ───────────────────────────────────────────────────
        DB::table('cable_plans')->insert([
            ['cable_provider_id' => $dstv, 'name' => 'DStv Padi',                  'vtpass_id' => 'padi',           'easyaccess_id' => '90',  'payscribe_id' => 'RWpqNXRDckJTNHVpeUN3QU11dlM2QT09', 'amount' => 3600,  'enabled' => 1, 'sort_order' => 1,  'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $dstv, 'name' => 'DStv Yanga',                 'vtpass_id' => 'yanga',          'easyaccess_id' => '91',  'payscribe_id' => 'cklmYzI3RzY2aVQ3L0xjMTM1Qkd3Zz09', 'amount' => 5100,  'enabled' => 1, 'sort_order' => 2,  'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $dstv, 'name' => 'DStv Confam',                'vtpass_id' => 'confam',         'easyaccess_id' => '92',  'payscribe_id' => 'TnpRK0N5Z2hwbElEa0srdjJXQnBUdz09', 'amount' => 9300,  'enabled' => 1, 'sort_order' => 3,  'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $dstv, 'name' => 'DStv Compact',               'vtpass_id' => 'compact',        'easyaccess_id' => '93',  'payscribe_id' => 'WDdqUUgrMVBtaFVOL0p2Wk01dm5SUT09', 'amount' => 15700, 'enabled' => 1, 'sort_order' => 4,  'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $dstv, 'name' => 'DStv Compact Plus',          'vtpass_id' => 'compact1',       'easyaccess_id' => '105', 'payscribe_id' => 'VmFrcm9tQW9JcHNmTFJhS293VFE5QT09', 'amount' => 25000, 'enabled' => 1, 'sort_order' => 5,  'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $dstv, 'name' => 'DStv Premium',               'vtpass_id' => 'premium',        'easyaccess_id' => '106', 'payscribe_id' => 'TnNSdkxhdVdmMG1SUTBaajVIOGhYQT09', 'amount' => 37000, 'enabled' => 1, 'sort_order' => 6,  'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $dstv, 'name' => 'DStv Padi + ExtraView',      'vtpass_id' => 'padi-extra',     'easyaccess_id' => '107', 'payscribe_id' => 'amRMUDBOa05pYkV1VXNiMmpqUmFnZz09', 'amount' => 8600,  'enabled' => 1, 'sort_order' => 7,  'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $dstv, 'name' => 'DStv Yanga + ExtraView',     'vtpass_id' => 'yanga-extra',    'easyaccess_id' => '108', 'payscribe_id' => 'a3pTUkk3Y2pJM3E5VTIxajdwbWVTQT09', 'amount' => 10100, 'enabled' => 1, 'sort_order' => 8,  'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $dstv, 'name' => 'DStv Compact + ExtraView',   'vtpass_id' => 'compact-extra',  'easyaccess_id' => '110', 'payscribe_id' => 'RDJhb0xweVR6VjNLY0kyT2hhWkVsQT09', 'amount' => 20700, 'enabled' => 1, 'sort_order' => 9,  'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $dstv, 'name' => 'DStv Premium + ExtraView',   'vtpass_id' => 'premium-extra',  'easyaccess_id' => '111', 'payscribe_id' => 'STc5enQvSVBab2RXR1J0ZU80bkhrQT09', 'amount' => 42000, 'enabled' => 1, 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Seed GOtv plans ───────────────────────────────────────────────────
        DB::table('cable_plans')->insert([
            ['cable_provider_id' => $gotv, 'name' => 'GOtv Smallie',           'vtpass_id' => 'gotv-smallie',           'easyaccess_id' => '94',  'payscribe_id' => 'R2xmQWtmOUNkMGJVcU1sUTZjYzBPQT09', 'amount' => 1900,  'enabled' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $gotv, 'name' => 'GOtv Jinja',             'vtpass_id' => 'gotv-jinja',             'easyaccess_id' => '97',  'payscribe_id' => 'RnhPVlV6dkhmTiswVUdxUDVPODZyZz09', 'amount' => 3900,  'enabled' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $gotv, 'name' => 'GOtv Jolli',             'vtpass_id' => 'gotv-jolli',             'easyaccess_id' => '96',  'payscribe_id' => 'OGNqYVdDaXgwOHdRN1ZmbFhLWmppQT09', 'amount' => 5800,  'enabled' => 1, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $gotv, 'name' => 'GOtv Max',               'vtpass_id' => 'gotv-max',               'easyaccess_id' => '95',  'payscribe_id' => 'MzdwNG5xMjFmM0JOTWtiN3dnUk1adz09', 'amount' => 8500,  'enabled' => 1, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $gotv, 'name' => 'GOtv Supa',              'vtpass_id' => 'gotv-supa',              'easyaccess_id' => '112', 'payscribe_id' => 'NUlXaTAyZmJTcUM4SUVHRDFrZFdIdz09', 'amount' => 11400, 'enabled' => 1, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $gotv, 'name' => 'GOtv Supa Plus',         'vtpass_id' => 'gotv-supa-plus',         'easyaccess_id' => '113', 'payscribe_id' => 'TllBcmlmUHlDcDFDZmdGWnBnQVJFdz09', 'amount' => 16800, 'enabled' => 1, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $gotv, 'name' => 'GOtv Smallie Quarterly', 'vtpass_id' => 'gotv-smallie-quarterly', 'easyaccess_id' => '98',  'payscribe_id' => 'azkxa3p6VmZWRFVIM1pLSzhZaG1UQT09', 'amount' => 5100,  'enabled' => 1, 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $gotv, 'name' => 'GOtv Smallie Yearly',    'vtpass_id' => 'gotv-smallie-yearly',    'easyaccess_id' => '99',  'payscribe_id' => 'bWlZR0FZWmJsS1VLYWxWdTRMeU9YUT09', 'amount' => 15000, 'enabled' => 1, 'sort_order' => 8, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Seed Startimes plans ──────────────────────────────────────────────
        DB::table('cable_plans')->insert([
            ['cable_provider_id' => $startimes, 'name' => 'Startimes Nova',    'vtpass_id' => 'nova',    'easyaccess_id' => '100', 'payscribe_id' => 'T3NHTGkvMFJpTEh0NDhBQlJOQm44QT09', 'amount' => 1900, 'enabled' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $startimes, 'name' => 'Startimes Basic',   'vtpass_id' => 'basic',   'easyaccess_id' => '101', 'payscribe_id' => 'b3hkOGFtaFBZMzE4dXFkNm8wTDRDUT09', 'amount' => 3300, 'enabled' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $startimes, 'name' => 'Startimes Smart',   'vtpass_id' => 'smart',   'easyaccess_id' => '102', 'payscribe_id' => null,                                   'amount' => 4200, 'enabled' => 1, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $startimes, 'name' => 'Startimes Classic', 'vtpass_id' => 'classic', 'easyaccess_id' => '103', 'payscribe_id' => 'ZFBNNUtMZUdwK1VEcXYxSlZlUGtrdz09', 'amount' => 5000, 'enabled' => 1, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['cable_provider_id' => $startimes, 'name' => 'Startimes Super',   'vtpass_id' => 'super',   'easyaccess_id' => '104', 'payscribe_id' => 'TVRqdk5yYU1kdGpBa3ordzJsemdBQT09', 'amount' => 8200, 'enabled' => 1, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── Seed app_settings ─────────────────────────────────────────────────
        $now = now();
        $settings = [
            ['key' => 'service_cable',        'value' => '1',       'created_at' => $now, 'updated_at' => $now],
            ['key' => 'cable_api',            'value' => 'vtpass',  'created_at' => $now, 'updated_at' => $now],
            ['key' => 'cable_daily_limit',    'value' => '100000',  'created_at' => $now, 'updated_at' => $now],
        ];
        foreach ($settings as $s) {
            DB::table('app_settings')->updateOrInsert(['key' => $s['key']], $s);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cable_plans');
        Schema::dropIfExists('cable_providers');
        DB::table('app_settings')->whereIn('key', [
            'service_cable', 'cable_api', 'cable_daily_limit',
        ])->delete();
    }
};

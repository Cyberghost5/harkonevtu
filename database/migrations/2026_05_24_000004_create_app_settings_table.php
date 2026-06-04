<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed defaults
        $now = now();
        DB::table('app_settings')->insert([
            ['key' => 'active_gateway',            'value' => 'paystack', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'transaction_charge_type',   'value' => 'flat',     'created_at' => $now, 'updated_at' => $now],
            ['key' => 'transaction_charge_value',  'value' => '0',        'created_at' => $now, 'updated_at' => $now],
            ['key' => 'bank_name',                 'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'bank_account_name',         'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'bank_account_number',       'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'site_name',                 'value' => 'KlassPay', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'site_description',          'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'site_keywords',             'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'admin_email',              'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'copyright',                'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'location',                 'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'favicon',                  'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'logo1',                    'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'logo2',                    'value' => '',         'created_at' => $now, 'updated_at' => $now],
            ['key' => 'theme_color',              'value' => '#4caf50',  'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};

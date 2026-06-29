<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_airtime', function (Blueprint $table) {
            $table->id();
            $table->string('network_key')->unique();          // mtn, airtel, glo, etisalat
            $table->string('name');                           // MTN, Airtel, Glo, 9mobile
            $table->string('vtpass_id');                      // VTpass serviceID
            $table->string('clubkonnect_id')->nullable();     // Clubkonnect MobileNetwork param
            $table->string('autopilot_id')->nullable();       // Autopilot network param
            $table->string('merrybills_id')->nullable();      // Merrybills network_id
            $table->boolean('enabled')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default networks
        DB::table('network_airtime')->insert([
            [
                'network_key'     => 'mtn',
                'name'            => 'MTN',
                'vtpass_id'       => 'mtn',
                'clubkonnect_id'  => '01',
                'autopilot_id'    => '1',
                'merrybills_id'   => 'MTN',
                'enabled'         => true,
                'sort_order'      => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'network_key'     => 'airtel',
                'name'            => 'Airtel',
                'vtpass_id'       => 'airtel',
                'clubkonnect_id'  => '04',
                'autopilot_id'    => '2',
                'merrybills_id'   => 'AIRTEL',
                'enabled'         => true,
                'sort_order'      => 2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'network_key'     => 'glo',
                'name'            => 'Glo',
                'vtpass_id'       => 'glo',
                'clubkonnect_id'  => '02',
                'autopilot_id'    => '3',
                'merrybills_id'   => 'GLO',
                'enabled'         => true,
                'sort_order'      => 3,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'network_key'     => 'etisalat',
                'name'            => '9mobile',
                'vtpass_id'       => 'etisalat',
                'clubkonnect_id'  => '03',
                'autopilot_id'    => '4',
                'merrybills_id'   => '9MOBILE',
                'enabled'         => true,
                'sort_order'      => 4,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('network_airtime');
    }
};

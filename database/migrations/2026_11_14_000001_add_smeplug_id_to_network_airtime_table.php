<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('network_airtime') && !Schema::hasColumn('network_airtime', 'smeplug_id')) {
            Schema::table('network_airtime', function (Blueprint $table) {
                $table->string('smeplug_id', 20)->nullable()->after('payscribe_id');
            });
        }

        $defaults = [
            'mtn'      => '1',
            'airtel'   => '2',
            'etisalat' => '3',
            'glo'      => '4',
        ];

        if (Schema::hasTable('network_airtime') && Schema::hasColumn('network_airtime', 'smeplug_id')) {
            foreach ($defaults as $networkKey => $code) {
                DB::table('network_airtime')
                    ->where('network_key', $networkKey)
                    ->update(['smeplug_id' => $code]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('network_airtime') && Schema::hasColumn('network_airtime', 'smeplug_id')) {
            Schema::table('network_airtime', function (Blueprint $table) {
                $table->dropColumn('smeplug_id');
            });
        }
    }
};

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
        if (!Schema::hasColumn('network_airtime', 'payscribe_id')) {
            Schema::table('network_airtime', function (Blueprint $table) {
                $table->string('payscribe_id', 20)->nullable()->after('globacom_id');
            });
        }

        $defaults = [
            'mtn'      => 'mtn',
            'airtel'   => 'airtel',
            'glo'      => 'glo',
            'etisalat' => '9mobile',
        ];

        foreach ($defaults as $networkKey => $code) {
            DB::table('network_airtime')
                ->where('network_key', $networkKey)
                ->update(['payscribe_id' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('network_airtime', 'payscribe_id')) {
            Schema::table('network_airtime', function (Blueprint $table) {
                $table->dropColumn('payscribe_id');
            });
        }
    }
};

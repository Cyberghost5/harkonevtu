<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            $table->string('glo_ers_id')->nullable()->after('globacom_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            $table->dropColumn('glo_ers_id');
        });
    }
};

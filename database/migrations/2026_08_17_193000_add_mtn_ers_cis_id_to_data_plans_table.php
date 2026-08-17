<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            $table->string('mtn_ers_cis_id')->nullable()->after('mtn_ers_id');
        });
    }

    public function down(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            $table->dropColumn('mtn_ers_cis_id');
        });
    }
};

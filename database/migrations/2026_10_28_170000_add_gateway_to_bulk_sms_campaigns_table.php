<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_sms_campaigns', function (Blueprint $table) {
            $table->string('gateway', 32)->nullable()->default('direct-corporate')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bulk_sms_campaigns', function (Blueprint $table) {
            $table->dropColumn('gateway');
        });
    }
};

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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'fcm_device_token')) {
                $table->string('fcm_device_token', 500)->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('users', 'device_type')) {
                $table->string('device_type', 20)->nullable()->after('fcm_device_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fcm_device_token')) {
                $table->dropColumn('fcm_device_token');
            }
            if (Schema::hasColumn('users', 'device_type')) {
                $table->dropColumn('device_type');
            }
        });
    }
};

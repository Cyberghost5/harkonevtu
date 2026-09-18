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
        Schema::table('api_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('api_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('channel');
            }
            if (!Schema::hasColumn('api_logs', 'device_info')) {
                $table->string('device_info')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('api_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('device_info');
            }
            if (!Schema::hasColumn('api_logs', 'location')) {
                $table->string('location')->nullable()->after('user_agent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'device_info', 'user_agent', 'location']);
        });
    }
};

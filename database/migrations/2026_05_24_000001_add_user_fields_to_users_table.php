<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('user_type', 10)->default('normal')->after('phone'); // normal | agent
            $table->string('referral_code', 10)->nullable()->unique()->after('user_type');
            $table->string('referred_by', 10)->nullable()->after('referral_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'user_type', 'referral_code', 'referred_by']);
        });
    }
};

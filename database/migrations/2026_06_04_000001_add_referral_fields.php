<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separate referral earnings balance on the wallet
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('referral_balance', 12, 2)->default(0.00)->after('total_spent');
        });

        // Flag so commission is only ever paid once per referred user
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('referral_commission_paid')->default(false)->after('referred_by');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('referral_balance');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('referral_commission_paid');
        });
    }
};

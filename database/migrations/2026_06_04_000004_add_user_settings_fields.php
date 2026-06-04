<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('low_balance_notification')->default(false)->after('referral_commission_paid');
            $table->enum('kyc_status', ['pending', 'submitted', 'verified', 'rejected'])->default('pending')->after('low_balance_notification');
            $table->string('avatar')->nullable()->after('kyc_status');
            $table->string('api_token', 80)->nullable()->unique()->after('avatar');
            $table->string('bank_name')->nullable()->after('api_token');
            $table->string('bank_account_number', 20)->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'low_balance_notification',
                'kyc_status',
                'avatar',
                'api_token',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
            ]);
        });
    }
};

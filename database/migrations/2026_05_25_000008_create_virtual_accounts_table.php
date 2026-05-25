<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');        // paystack | flutterwave
            $table->string('bank_name');
            $table->string('bank_code');       // wema-bank | titan-paystack | flutterwave_dva
            $table->string('account_number');
            $table->string('account_name');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'bank_code']);
            $table->index('account_number');   // for webhook lookups
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_accounts');
    }
};

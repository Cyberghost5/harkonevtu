<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();

            $table->string('service_type');    // airtime, data, electricity, cable, exam_pin
            $table->string('provider');        // mtn, airtel, glo, etisalat, dstv, etc.
            $table->string('recipient');       // phone number / meter number / smart card number
            $table->decimal('amount', 12, 2);

            $table->string('status');          // pending | success | failed | refunded
            $table->string('reference')->unique();    // our internal reference
            $table->string('api_reference')->nullable(); // provider's transaction ID
            $table->json('api_response')->nullable();    // full provider response

            $table->timestamps();

            $table->index(['user_id', 'service_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_transactions');
    }
};

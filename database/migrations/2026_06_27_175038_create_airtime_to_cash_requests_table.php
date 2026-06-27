<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airtime_to_cash_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('network');
            $table->string('phone');
            $table->decimal('amount', 15, 2);
            $table->decimal('charge', 15, 2);
            $table->decimal('receive_amount', 15, 2);
            $table->string('screenshot')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airtime_to_cash_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sender_id', 11);
            $table->text('message');
            $table->unsignedInteger('recipient_count');
            $table->longText('recipients')->nullable();
            $table->unsignedTinyInteger('pages')->default(1);
            $table->decimal('unit_rate', 8, 2)->default(4.00);
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('pending'); // pending | sent | failed | partial
            $table->string('reference')->unique();
            $table->text('api_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_sms_campaigns');
    }
};

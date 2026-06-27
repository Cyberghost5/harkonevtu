<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printed_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // airtime | data
            $table->string('network'); // mtn | airtel | glo | 9mobile
            $table->string('name_on_card')->nullable(); // custom business name
            $table->decimal('value', 10, 2); // face value
            $table->string('pin');
            $table->string('serial_number');
            $table->string('status')->default('unused'); // unused | used
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printed_vouchers');
    }
};

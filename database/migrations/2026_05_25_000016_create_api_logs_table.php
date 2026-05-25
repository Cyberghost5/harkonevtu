<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service', 50);           // airtime, data, electricity, etc.
            $table->string('provider', 50);           // vtpass, clubkonnect, autopilot, merrybills
            $table->string('reference', 100)->index();
            $table->string('endpoint', 500);
            $table->string('method', 10)->default('POST');
            $table->json('payload')->nullable();      // what we sent
            $table->json('response')->nullable();     // what we got back
            $table->smallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};

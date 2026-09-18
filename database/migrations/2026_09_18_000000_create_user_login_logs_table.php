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
        Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email_or_username')->nullable();
            $table->string('channel')->default('web'); // web, mobile
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type')->default('Unknown'); // Android, iOS, Windows, Mac, Linux, Mobile, Desktop
            $table->string('browser')->default('Unknown'); // Chrome, Safari, Firefox, Edge, Mobile App, etc.
            $table->string('status')->default('success'); // success, failed, otp_pending
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['channel', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_login_logs');
    }
};

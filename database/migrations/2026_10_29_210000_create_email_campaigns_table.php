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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('action_label')->nullable(); // e.g. "Visit Dashboard"
            $table->string('action_url')->nullable();   // optional CTA link
            $table->string('target_audience')->default('all'); // all, users, filter
            $table->string('filter_type')->nullable();  // agents, verified, active
            $table->integer('recipient_count')->default(0);
            $table->string('status')->default('sent'); // sent, partial, failed
            $table->json('extra_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};

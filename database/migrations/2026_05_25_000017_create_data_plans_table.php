<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_plans', function (Blueprint $table) {
            $table->id();
            $table->string('network_key', 20);          // mtn, glo, airtel, etisalat
            $table->string('data_type', 30);            // sme, gifting, cg, awoof
            $table->string('plan_name', 120);           // e.g. "1GB 30 Days"
            $table->string('validity', 60);             // e.g. "30 Days"
            // Provider-specific plan identifiers
            $table->string('vtpass_id', 100)->nullable();           // variation_code
            $table->string('clubkonnect_id', 100)->nullable();
            $table->string('autopilot_id', 120)->nullable();        // val_id from data_plans8
            $table->string('merrybills_product_id', 30)->nullable();// product_id (network catalogue)
            $table->string('merrybills_id', 30)->nullable();        // val_id within catalogue
            // Pricing
            $table->decimal('amount', 10, 2);           // user price
            $table->decimal('amount_agent', 10, 2);     // agent price
            // Status
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['network_key', 'data_type', 'enabled'], 'data_plans_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_plans');
    }
};

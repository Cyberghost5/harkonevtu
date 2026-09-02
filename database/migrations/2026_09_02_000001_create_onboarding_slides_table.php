<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed 3 default onboarding slides
        DB::table('onboarding_slides')->insert([
            [
                'title'       => 'Instant Airtime & Cheap Data',
                'description' => 'Top up airtime and buy SME & Gifting data bundles instantly across MTN, Airtel, Glo, and 9mobile at wholesale prices.',
                'image'       => null,
                'sort_order'  => 1,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => 'Pay Utilities & Cable TV',
                'description' => 'Pay electricity bills (Prepaid/Postpaid) and renew DSTV, GOtv, and StarTimes subscriptions with zero hassle and instant token generation.',
                'image'       => null,
                'sort_order'  => 2,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'title'       => '24/7 Automated Wallet Funding',
                'description' => 'Get dedicated virtual bank accounts for instant automated wallet funding anytime, day or night.',
                'image'       => null,
                'sort_order'  => 3,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_slides');
    }
};

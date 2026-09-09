<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure data_plans table structure is complete
        if (!Schema::hasTable('data_plans')) {
            Schema::create('data_plans', function (Blueprint $table) {
                $table->id();
                $table->string('network_key', 20);
                $table->string('data_type', 30);
                $table->string('plan_name', 120);
                $table->string('validity', 60);
                $table->string('vtpass_id', 100)->nullable();
                $table->string('clubkonnect_id', 100)->nullable();
                $table->string('easyaccess_id', 100)->nullable();
                $table->string('aabaxztech_id', 100)->nullable();
                $table->string('legitdataway_id', 100)->nullable();
                $table->string('globacom_id', 255)->nullable();
                $table->string('glo_ers_id', 255)->nullable();
                $table->string('autopilot_id', 120)->nullable();
                $table->string('merrybills_product_id', 30)->nullable();
                $table->string('merrybills_id', 30)->nullable();
                $table->string('mtn_ers_id', 255)->nullable();
                $table->string('mtn_ers_cis_id', 255)->nullable();
                $table->decimal('amount', 10, 2);
                $table->decimal('amount_agent', 10, 2);
                $table->boolean('enabled')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['network_key', 'data_type', 'enabled'], 'data_plans_lookup');
            });
        } else {
            Schema::table('data_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('data_plans', 'easyaccess_id')) {
                    $table->string('easyaccess_id', 100)->nullable()->after('clubkonnect_id');
                }
                if (!Schema::hasColumn('data_plans', 'aabaxztech_id')) {
                    $table->string('aabaxztech_id', 100)->nullable()->after('easyaccess_id');
                }
                if (!Schema::hasColumn('data_plans', 'legitdataway_id')) {
                    $table->string('legitdataway_id', 100)->nullable()->after('aabaxztech_id');
                }
                if (!Schema::hasColumn('data_plans', 'globacom_id')) {
                    $table->string('globacom_id', 255)->nullable()->after('legitdataway_id');
                }
                if (!Schema::hasColumn('data_plans', 'glo_ers_id')) {
                    $table->string('glo_ers_id', 255)->nullable()->after('globacom_id');
                }
                if (!Schema::hasColumn('data_plans', 'mtn_ers_id')) {
                    $table->string('mtn_ers_id', 255)->nullable()->after('merrybills_id');
                }
                if (!Schema::hasColumn('data_plans', 'mtn_ers_cis_id')) {
                    $table->string('mtn_ers_cis_id', 255)->nullable()->after('mtn_ers_id');
                }
            });
        }

        // 2. Load approved data plans from docs/data_plans.sql and seed table
        $sqlPath = base_path('docs/data_plans.sql');
        if (file_exists($sqlPath)) {
            $sqlContent = file_get_contents($sqlPath);

            if (preg_match('/INSERT INTO [`"]data_plans[`"].*?;/s', $sqlContent, $matches)) {
                // Convert INSERT INTO to REPLACE INTO so existing primary keys/records are overwritten
                $replaceSql = preg_replace('/^INSERT INTO/i', 'REPLACE INTO', trim($matches[0]));

                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::table('data_plans')->truncate();
                DB::unprepared($replaceSql);
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

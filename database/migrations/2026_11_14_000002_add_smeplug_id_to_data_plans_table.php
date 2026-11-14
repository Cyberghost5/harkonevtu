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
        if (Schema::hasTable('data_plans') && !Schema::hasColumn('data_plans', 'smeplug_id')) {
            Schema::table('data_plans', function (Blueprint $table) {
                $table->string('smeplug_id', 120)->nullable()->after('payscribe_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('data_plans') && Schema::hasColumn('data_plans', 'smeplug_id')) {
            Schema::table('data_plans', function (Blueprint $table) {
                $table->dropColumn('smeplug_id');
            });
        }
    }
};

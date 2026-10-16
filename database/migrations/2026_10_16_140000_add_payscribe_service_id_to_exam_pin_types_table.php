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
        if (Schema::hasTable('exam_pin_types') && !Schema::hasColumn('exam_pin_types', 'payscribe_service_id')) {
            Schema::table('exam_pin_types', function (Blueprint $table) {
                $table->string('payscribe_service_id', 50)->nullable()->after('primebiller_provider_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('exam_pin_types') && Schema::hasColumn('exam_pin_types', 'payscribe_service_id')) {
            Schema::table('exam_pin_types', function (Blueprint $table) {
                $table->dropColumn('payscribe_service_id');
            });
        }
    }
};

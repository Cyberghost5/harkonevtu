<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('user')->after('user_type');
            }
            if (!Schema::hasColumn('users', 'is_super_admin')) {
                $table->boolean('is_super_admin')->default(false)->after('is_admin');
            }
            $table->index(['role', 'is_super_admin']);
        });

        // Backfill existing users cleanly
        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);
        DB::table('users')->where('user_type', 'agent')->where('is_admin', false)->update(['role' => 'agent']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role') && Schema::hasColumn('users', 'is_super_admin')) {
                $table->dropIndex(['role', 'is_super_admin']);
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'is_super_admin')) {
                $table->dropColumn('is_super_admin');
            }
        });
    }
};

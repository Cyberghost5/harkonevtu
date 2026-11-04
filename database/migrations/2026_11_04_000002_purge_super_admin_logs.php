<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('user_login_logs')) {
            return;
        }

        // 1. Purge logs belonging to any super admin user IDs
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_super_admin')) {
            $superAdminUsers = DB::table('users')->where('is_super_admin', true)->get(['id', 'email', 'username']);

            $superAdminIds = $superAdminUsers->pluck('id')->all();
            if (!empty($superAdminIds)) {
                DB::table('user_login_logs')->whereIn('user_id', $superAdminIds)->delete();
            }

            $superAdminIdentifiers = $superAdminUsers
                ->flatMap(fn($u) => array_filter([$u->email ?? null, $u->username ?? null]))
                ->unique()
                ->values()
                ->all();

            if (!empty($superAdminIdentifiers)) {
                DB::table('user_login_logs')->whereIn('email_or_username', $superAdminIdentifiers)->delete();
            }
        }

        // 2. Also ensure default seeded superadmin email/username logs are purged
        DB::table('user_login_logs')
            ->whereIn('email_or_username', [
                'superadmin@klasspay.com.ng',
                'superadmin',
                env('SUPER_ADMIN_EMAIL', 'superadmin@klasspay.com.ng'),
                env('SUPER_ADMIN_USERNAME', 'superadmin'),
            ])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op (purged logs cannot be restored)
    }
};

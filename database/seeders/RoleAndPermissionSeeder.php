<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ─── 1. Define All System Permissions ─────────────────────────────────
        $permissions = [
            // Dashboard
            ['module' => 'dashboard',       'action' => 'read',          'name' => 'View Dashboard',            'description' => 'Access the main admin dashboard and overview stats'],

            // Users Management
            ['module' => 'users',           'action' => 'read',          'name' => 'View Users',                'description' => 'View user list and account details'],
            ['module' => 'users',           'action' => 'create',        'name' => 'Create Users',              'description' => 'Register new user accounts from admin'],
            ['module' => 'users',           'action' => 'update',        'name' => 'Edit Users',                'description' => 'Update user details, verify status, and ban/unban'],
            ['module' => 'users',           'action' => 'delete',        'name' => 'Delete Users',              'description' => 'Delete user accounts'],
            ['module' => 'users',           'action' => 'wallet_adjust', 'name' => 'Adjust User Wallet',        'description' => 'Directly credit or debit user wallet balances'],
            ['module' => 'users',           'action' => 'impersonate',   'name' => 'Impersonate Users',         'description' => 'Log in to user accounts as admin'],

            // Transactions & Orders
            ['module' => 'transactions',    'action' => 'read',          'name' => 'View Transactions',         'description' => 'View service transactions and purchases'],
            ['module' => 'transactions',    'action' => 'update',        'name' => 'Manage Transactions',       'description' => 'Retry failed transactions, refund, or edit status'],
            ['module' => 'transactions',    'action' => 'export',        'name' => 'Export Transactions',       'description' => 'Export transaction records to CSV/Excel'],

            // Wallet Funding
            ['module' => 'funding',         'action' => 'read',          'name' => 'View Funding Requests',     'description' => 'View manual funding submissions and gateway payments'],
            ['module' => 'funding',         'action' => 'update',        'name' => 'Approve/Reject Funding',    'description' => 'Approve or decline manual bank transfer funding requests'],
            ['module' => 'funding',         'action' => 'create',        'name' => 'Manual Credit Funding',     'description' => 'Initiate manual credits to user wallets'],

            // Airtime to Cash
            ['module' => 'airtime_to_cash', 'action' => 'read',          'name' => 'View Airtime to Cash',      'description' => 'View airtime-to-cash conversion requests'],
            ['module' => 'airtime_to_cash', 'action' => 'update',        'name' => 'Process Airtime to Cash',   'description' => 'Approve, reject, or mark airtime-to-cash transfers paid'],

            // Voucher Printing
            ['module' => 'vouchers',        'action' => 'read',          'name' => 'View Vouchers',             'description' => 'View generated recharge vouchers and PINs'],
            ['module' => 'vouchers',        'action' => 'create',        'name' => 'Generate Vouchers',         'description' => 'Generate new airtime and data recharge vouchers'],
            ['module' => 'vouchers',        'action' => 'delete',        'name' => 'Delete Vouchers',           'description' => 'Delete or revoke printed vouchers'],

            // Exam PINs
            ['module' => 'epins',           'action' => 'read',          'name' => 'View Exam PINs',            'description' => 'View purchased WAEC, NECO, NABTEB, JAMB PINs'],
            ['module' => 'epins',           'action' => 'create',        'name' => 'Purchase Exam PINs',        'description' => 'Order exam result checker tokens from admin'],

            // Coupons
            ['module' => 'coupons',         'action' => 'read',          'name' => 'View Coupons',              'description' => 'View discount and gift coupons'],
            ['module' => 'coupons',         'action' => 'create',        'name' => 'Create Coupons',            'description' => 'Generate promotional discount and wallet bonus coupons'],
            ['module' => 'coupons',         'action' => 'update',        'name' => 'Edit Coupons',              'description' => 'Edit coupon parameters, limits, and expiry'],
            ['module' => 'coupons',         'action' => 'delete',        'name' => 'Delete Coupons',            'description' => 'Delete coupons'],

            // Bulk SMS
            ['module' => 'bulksms',         'action' => 'read',          'name' => 'View Bulk SMS',             'description' => 'View bulk SMS campaigns and delivery logs'],
            ['module' => 'bulksms',         'action' => 'create',        'name' => 'Send Bulk SMS',             'description' => 'Broadcast SMS messages from admin'],
            ['module' => 'bulksms',         'action' => 'delete',        'name' => 'Delete SMS Logs',           'description' => 'Purge SMS campaign logs'],

            // Reports & Analytics
            ['module' => 'reports',         'action' => 'read',          'name' => 'View Reports',              'description' => 'View financial reports, revenue summaries, and charts'],
            ['module' => 'reports',         'action' => 'export',        'name' => 'Export Reports',            'description' => 'Export financial statements and performance metrics'],

            // Settings
            ['module' => 'settings',        'action' => 'read',          'name' => 'View Settings',             'description' => 'View system configuration, prices, and bank accounts'],
            ['module' => 'settings',        'action' => 'update',        'name' => 'Update Settings',           'description' => 'Modify site configurations, services, and prices'],
            ['module' => 'settings',        'action' => 'api_keys',      'name' => 'Manage API Keys',           'description' => 'Access and update sensitive third-party provider API keys'],

            // API Logs
            ['module' => 'api_logs',        'action' => 'read',          'name' => 'View API Logs',             'description' => 'Inspect raw API request and response logs'],

            // Staff Administrators
            ['module' => 'admins',          'action' => 'read',          'name' => 'View Administrators',       'description' => 'View list of staff administrators'],
            ['module' => 'admins',          'action' => 'create',        'name' => 'Create Administrators',     'description' => 'Add new staff administrator accounts'],
            ['module' => 'admins',          'action' => 'update',        'name' => 'Edit Administrators',       'description' => 'Edit staff administrator details and passwords'],
            ['module' => 'admins',          'action' => 'delete',        'name' => 'Demote Administrators',     'description' => 'Demote or remove administrator accounts'],

            // Roles & Permissions Management
            ['module' => 'roles',           'action' => 'read',          'name' => 'View Roles',                'description' => 'View administrative roles and their assigned permissions'],
            ['module' => 'roles',           'action' => 'create',        'name' => 'Create Roles',              'description' => 'Create new custom staff roles'],
            ['module' => 'roles',           'action' => 'update',        'name' => 'Edit Roles',                'description' => 'Edit roles and configure granular CRUD permissions'],
            ['module' => 'roles',           'action' => 'delete',        'name' => 'Delete Roles',              'description' => 'Delete non-system roles'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $p) {
            $slug = "{$p['module']}.{$p['action']}";
            $permission = Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'        => $p['name'],
                    'module'      => $p['module'],
                    'action'      => $p['action'],
                    'description' => $p['description'],
                ]
            );
            $createdPermissions[$slug] = $permission->id;
        }

        // ─── 2. Create Default System Roles ───────────────────────────────────

        // Role 1: Super Administrator
        $superAdminRole = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name'        => 'Super Administrator',
                'description' => 'Full and unrestricted access to every feature, setting, API key, and staff management.',
                'is_system'   => true,
            ]
        );
        $superAdminRole->permissions()->sync(array_values($createdPermissions));

        // Role 2: Manager
        $managerRole = Role::updateOrCreate(
            ['slug' => 'manager'],
            [
                'name'        => 'Manager',
                'description' => 'Comprehensive management of daily business operations, user accounts, transactions, wallet approvals, and service generation.',
                'is_system'   => true,
            ]
        );
        $managerSlugs = [
            'dashboard.read',
            'users.read', 'users.create', 'users.update', 'users.wallet_adjust',
            'transactions.read', 'transactions.update', 'transactions.export',
            'funding.read', 'funding.update', 'funding.create',
            'airtime_to_cash.read', 'airtime_to_cash.update',
            'vouchers.read', 'vouchers.create',
            'epins.read', 'epins.create',
            'coupons.read', 'coupons.create', 'coupons.update',
            'bulksms.read', 'bulksms.create',
            'reports.read', 'reports.export',
            'settings.read',
            'api_logs.read',
            'roles.read',
        ];
        $managerIds = array_values(array_intersect_key($createdPermissions, array_flip($managerSlugs)));
        $managerRole->permissions()->sync($managerIds);

        // Role 3: Customer Support
        $supportRole = Role::updateOrCreate(
            ['slug' => 'support'],
            [
                'name'        => 'Customer Support',
                'description' => 'Assists customers by viewing user account profiles, transaction receipts, funding logs, and API statuses without altering funds or system settings.',
                'is_system'   => true,
            ]
        );
        $supportSlugs = [
            'dashboard.read',
            'users.read',
            'transactions.read',
            'funding.read',
            'airtime_to_cash.read',
            'vouchers.read',
            'epins.read',
            'api_logs.read',
        ];
        $supportIds = array_values(array_intersect_key($createdPermissions, array_flip($supportSlugs)));
        $supportRole->permissions()->sync($supportIds);

        // Role 4: Accountant / Finance
        $accountantRole = Role::updateOrCreate(
            ['slug' => 'accountant'],
            [
                'name'        => 'Accountant / Finance',
                'description' => 'Oversees wallet funding approvals, transaction accounting, airtime-to-cash conversions, and financial performance reports.',
                'is_system'   => true,
            ]
        );
        $accountantSlugs = [
            'dashboard.read',
            'transactions.read', 'transactions.export',
            'funding.read', 'funding.update',
            'airtime_to_cash.read', 'airtime_to_cash.update',
            'reports.read', 'reports.export',
        ];
        $accountantIds = array_values(array_intersect_key($createdPermissions, array_flip($accountantSlugs)));
        $accountantRole->permissions()->sync($accountantIds);

        // ─── 3. Assign Super Admin Role to Existing Super Admin Users ─────────
        User::where('is_super_admin', true)->update(['role_id' => $superAdminRole->id]);

        // Assign Manager role to existing staff admins without a role
        User::where('is_admin', true)
            ->where('is_super_admin', false)
            ->whereNull('role_id')
            ->update(['role_id' => $managerRole->id]);
    }
}

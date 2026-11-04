<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@klasspay.com.ng');
        $username = env('SUPER_ADMIN_USERNAME', 'superadmin');
        $password = env('SUPER_ADMIN_PASSWORD', 'SuperAdmin@12345');
        $pin = env('SUPER_ADMIN_PIN', '0000');

        $superAdmin = User::updateOrCreate(
            ['email' => $email],
            [
                'name'                     => 'Super Administrator',
                'username'                 => $username,
                'phone'                    => '08000000001',
                'password'                 => Hash::make($password),
                'transaction_pin'          => Hash::make($pin),
                'user_type'                => 'normal',
                'role'                     => 'super_admin',
                'is_admin'                 => true,
                'is_super_admin'           => true,
                'is_active'                => true,
                'email_verified_at'        => now(),
                'phone_verified_at'        => now(),
                'low_balance_notification' => false,
            ]
        );

        Wallet::firstOrCreate(
            ['user_id' => $superAdmin->id],
            [
                'balance'      => 0.00,
                'total_funded' => 0.00,
                'total_spent'  => 0.00,
            ]
        );

        $this->command?->info("Super Admin user ready: [Email: {$email} | Username: {$username}]");
    }
}

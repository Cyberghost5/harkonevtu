<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAndSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
    }

    public function test_non_admin_cannot_access_admin_search(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('admin.search', ['q' => 'test']));
        $response->assertStatus(403);

        $liveResponse = $this->getJson(route('admin.search.live', ['q' => 'test']));
        $liveResponse->assertStatus(403);
    }

    public function test_admin_dashboard_users_balance_excludes_admin_wallets(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
        Wallet::create([
            'user_id' => $admin->id,
            'balance' => 50000.00,
        ]);

        $regularUser = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);
        Wallet::create([
            'user_id' => $regularUser->id,
            'balance' => 12500.00,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Verify users_balance in stats is exactly 12500, NOT 62500
        $response->assertViewHas('stats', function ($stats) {
            return $stats['users_balance'] === 12500.00;
        });
    }

    public function test_admin_search_live_returns_users_and_transactions(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Chinedu Okeke',
            'email' => 'chinedu@example.com',
            'phone' => '08012345678',
            'is_admin' => false,
            'is_active' => true,
        ]);
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 3000.00,
        ]);

        $tx = ServiceTransaction::create([
            'user_id' => $user->id,
            'provider' => 'vtpass',
            'reference' => 'TXN-OK-998877',
            'service_type' => 'airtime',
            'amount' => 1000.00,
            'status' => 'success',
            'recipient' => '08012345678',
        ]);

        $this->actingAs($admin);

        // Search for user
        $userSearch = $this->getJson(route('admin.search.live', ['q' => 'Chinedu']));
        $userSearch->assertStatus(200);
        $userSearch->assertJsonFragment(['name' => 'Chinedu Okeke']);

        // Search for transaction reference
        $txSearch = $this->getJson(route('admin.search.live', ['q' => 'OK-998877']));
        $txSearch->assertStatus(200);
        $txSearch->assertJsonFragment(['reference' => 'TXN-OK-998877']);
    }

    public function test_admin_search_full_page_renders(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Aisha Bello',
            'email' => 'aisha@example.com',
            'phone' => '08098765432',
            'is_admin' => false,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.search', ['q' => 'Aisha']));
        $response->assertStatus(200);
        $response->assertSee('Aisha Bello');
        $response->assertSee('Global Search');
    }

    public function test_admin_api_logs_page_links_user_to_profile(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Abubakar Danladi',
            'email' => 'abubakar@example.com',
            'phone' => '08022233344',
            'is_admin' => false,
        ]);

        $log = \App\Models\ApiLog::create([
            'user_id'     => $user->id,
            'service'     => 'data',
            'provider'    => 'payscribe',
            'channel'     => 'mobile',
            'reference'   => 'DAT202610221555050',
            'endpoint'    => 'https://api.payscribe.ng/api/v1/data/vend',
            'method'      => 'POST',
            'payload'     => ['network' => 'airtel'],
            'response'    => ['status' => false],
            'http_status' => 403,
            'duration_ms' => 872,
            'success'     => false,
            'created_at'  => now(),
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.api-logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Abubakar Danladi');
        $response->assertSee(route('admin.users.show', $user));
    }
}

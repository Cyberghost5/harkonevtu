<?php

namespace Tests\Feature;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Models\VirtualAccount;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTransactionDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
    }

    public function test_non_admin_cannot_access_transaction_details(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $walletTx = WalletTransaction::create([
            'user_id' => $user->id,
            'reference' => 'WTX_TEST_12345',
            'type' => 'credit',
            'amount' => 5000.00,
            'balance_before' => 0.00,
            'balance_after' => 5000.00,
            'description' => 'Wallet funding test',
            'status' => 'success',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('admin.transactions.show', ['type' => 'wallet', 'id' => $walletTx->id]));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_wallet_transaction_details_and_api_telemetry(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'name' => 'Suraiya Dala',
            'email' => 'suraiya@example.com',
            'phone' => '08012345678',
            'is_admin' => false,
            'is_active' => true,
            'kyc_status' => 'verified',
            'bvn' => '12345678901',
        ]);

        $wallet = Wallet::create([
            'user_id' => $customer->id,
            'balance' => 10000.00,
            'total_funded' => 15000.00,
            'total_spent' => 5000.00,
        ]);

        VirtualAccount::create([
            'user_id' => $customer->id,
            'account_number' => '1234567890',
            'bank_name' => '9PSB / Wema',
            'account_name' => 'Suraiya Dala - VTU',
            'provider' => 'payscribe',
            'status' => 'active',
        ]);

        $walletTx = WalletTransaction::create([
            'user_id' => $customer->id,
            'reference' => 'b1f8a9a0-c338-48f8-9b28-16fb5333e6a2',
            'type' => 'credit',
            'amount' => 10000.00,
            'balance_before' => 0.00,
            'balance_after' => 10000.00,
            'description' => 'Wallet funding via Payscribe_dva',
            'status' => 'success',
            'metadata' => [
                'gateway' => 'payscribe',
                'channel' => 'dedicated_virtual_account',
                'trans_id' => 'PSC_TX_987654321',
                'session_id' => 'SESS_11223344',
                'charge' => 50.00,
            ],
        ]);

        // Mock an associated ApiLog
        ApiLog::create([
            'user_id' => $customer->id,
            'reference' => 'b1f8a9a0-c338-48f8-9b28-16fb5333e6a2',
            'provider' => 'payscribe',
            'service' => 'webhook',
            'channel' => 'webhook',
            'endpoint' => '/webhook/payscribe',
            'method' => 'POST',
            'http_status' => 200,
            'success' => true,
            'payload' => [
                'event' => 'collection.successful',
                'reference' => 'b1f8a9a0-c338-48f8-9b28-16fb5333e6a2',
                'amount' => 10000,
                'account_number' => '1234567890',
            ],
            'response' => [
                'status' => 'success',
                'message' => 'Wallet funded successfully',
            ],
            'ip_address' => '102.129.144.50',
            'location' => 'Lagos, Nigeria',
            'device_info' => 'PayScribe Webhook Server',
            'duration_ms' => 45.2,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.transactions.show', ['type' => 'wallet', 'id' => $walletTx->id]));
        $response->assertStatus(200);

        // Assert transaction details
        $response->assertSee('b1f8a9a0-c338-48f8-9b28-16fb5333e6a2');
        $response->assertSee('10,000.00');
        $response->assertSee('Wallet funding via Payscribe_dva');

        // Assert customer user details
        $response->assertSee('Suraiya Dala');
        $response->assertSee('suraiya@example.com');
        $response->assertSee('08012345678');
        $response->assertSee('1234567890'); // virtual account

        // Assert API request telemetry
        $response->assertSee('API Request &amp; Telemetry Logs', false);
        $response->assertSee('/webhook/payscribe');
        $response->assertSee('collection.successful');
        $response->assertSee('102.129.144.50');
    }

    public function test_admin_can_view_service_transaction_details_and_tokens(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $customer = User::factory()->create([
            'name' => 'Abubakar VTU',
            'email' => 'abubakar@example.com',
            'phone' => '08037412343',
            'is_admin' => false,
            'is_active' => true,
        ]);

        $serviceTx = ServiceTransaction::create([
            'user_id' => $customer->id,
            'reference' => 'AIR20261023200517NBRTXHSX',
            'api_reference' => 'PSC_AIR_123456',
            'service_type' => 'airtime',
            'provider' => 'payscribe',
            'recipient' => '08037412343',
            'amount' => 196.00,
            'status' => 'success',
            'api_response' => [
                'status' => 'success',
                'message' => 'Airtime delivered',
                'network' => 'MTN',
            ],
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.transactions.show', ['type' => 'service', 'id' => $serviceTx->id]));
        $response->assertStatus(200);
        $response->assertSee('AIR20261023200517NBRTXHSX');
        $response->assertSee('PSC_AIR_123456');
        $response->assertSee('08037412343');
        $response->assertSee('196.00');
        $response->assertSee('Abubakar VTU');
    }

    public function test_view_by_reference_route_redirects_correctly(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $walletTx = WalletTransaction::create([
            'user_id' => $user->id,
            'reference' => 'REF_DIRECT_REDIRECT_WALLET',
            'type' => 'credit',
            'amount' => 500.00,
            'balance_before' => 0.00,
            'balance_after' => 500.00,
            'description' => 'Test',
            'status' => 'success',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.transactions.by-reference', ['reference' => 'REF_DIRECT_REDIRECT_WALLET']));
        $response->assertRedirect(route('admin.transactions.show', ['type' => 'wallet', 'id' => $walletTx->id]));
    }

    public function test_api_logs_page_links_reference_to_transaction_details_when_it_exists(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        // 1. Transaction that exists
        $walletTx = WalletTransaction::create([
            'user_id' => $user->id,
            'reference' => 'PSC_1792848523',
            'type' => 'credit',
            'amount' => 5000.00,
            'balance_before' => 0.00,
            'balance_after' => 5000.00,
            'description' => 'Wallet funding via Payscribe_dva',
            'status' => 'success',
        ]);

        $logWithTx = ApiLog::create([
            'user_id' => $user->id,
            'reference' => 'PSC_1792848523',
            'provider' => 'payscribe',
            'service' => 'webhook',
            'channel' => 'webhook',
            'endpoint' => '/webhook/payscribe',
            'method' => 'POST',
            'http_status' => 200,
            'success' => true,
            'payload' => ['event' => 'collection.successful'],
            'response' => ['status' => 'ok'],
        ]);

        // 2. Log without a transaction
        $logWithoutTx = ApiLog::create([
            'user_id' => $user->id,
            'reference' => 'NON_EXISTENT_REF_9999',
            'provider' => 'payscribe',
            'service' => 'auth',
            'channel' => 'web',
            'endpoint' => '/api/auth/token',
            'method' => 'POST',
            'http_status' => 200,
            'success' => true,
            'payload' => [],
            'response' => [],
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.api-logs.index'));
        $response->assertStatus(200);

        // Should link to the wallet transaction details
        $expectedUrl = route('admin.transactions.show', ['type' => 'wallet', 'id' => $walletTx->id]);
        $response->assertSee($expectedUrl);
        $response->assertSee('PSC_1792848523');

        // Non-existent reference should be rendered as plain text
        $response->assertSee('NON_EXISTENT_REF_9');
        $response->assertDontSee(route('admin.transactions.show', ['type' => 'wallet', 'id' => 9999]));
    }

    public function test_api_logs_page_links_reference_when_matched_via_payload(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $walletTx = WalletTransaction::create([
            'user_id' => $user->id,
            'reference' => 'WTX_PAYLOAD_MATCH_99',
            'type' => 'credit',
            'amount' => 1000.00,
            'balance_before' => 0.00,
            'balance_after' => 1000.00,
            'description' => 'Test',
            'status' => 'success',
        ]);

        $log = ApiLog::create([
            'user_id' => $user->id,
            'reference' => 'PSC_WEBHOOK_PAYLOAD_TEST',
            'provider' => 'payscribe',
            'service' => 'webhook',
            'channel' => 'webhook',
            'endpoint' => '/webhook/payscribe',
            'method' => 'POST',
            'http_status' => 200,
            'success' => true,
            'payload' => [
                'trans_id' => 'WTX_PAYLOAD_MATCH_99',
                'amount' => 1000,
            ],
            'response' => ['status' => 'ok'],
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.api-logs.index'));
        $response->assertStatus(200);

        $expectedUrl = route('admin.transactions.show', ['type' => 'wallet', 'id' => $walletTx->id]);
        $response->assertSee($expectedUrl);
    }
}

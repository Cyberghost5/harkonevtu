<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use App\Models\BettingPlatform;
use App\Models\ServiceTransaction;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
        AppSetting::set('payscribe_secret_key', 'test_payscribe_key');

        // Seed settings
        AppSetting::set('service_betting', '1');
        AppSetting::set('betting_charge', '50');
        AppSetting::set('betting_min_amount', '100');
        AppSetting::set('betting_daily_limit', '30000');

        // Create a platform
        BettingPlatform::create([
            'name' => 'Bet9ja',
            'slug' => 'bet9ja',
            'enabled' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_cannot_access_betting_if_service_disabled(): void
    {
        AppSetting::set('service_betting', '0');

        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 5000]);

        $this->actingAs($user);

        $response = $this->get(route('services.betting'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Betting funding service is currently disabled.');
    }

    public function test_customer_validation_lookup(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 5000]);

        $this->actingAs($user);

        // Mock lookup API
        Http::fake([
            'https://api.payscribe.ng/api/v1/betting/lookup/*' => Http::response([
                'status' => true,
                'status_code' => 200,
                'details' => [
                    'customer_name' => 'John Doe'
                ]
            ], 200)
        ]);

        $response = $this->postJson(route('services.betting.validate'), [
            'platform' => 'bet9ja',
            'customer_id' => '1234567',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'customer_name' => 'John Doe'
        ]);
    }

    public function test_cannot_purchase_with_incorrect_pin(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 5000]);

        $this->actingAs($user);

        $response = $this->post(route('services.betting.purchase'), [
            'platform' => 'bet9ja',
            'customer_id' => '1234567',
            'customer_name' => 'John Doe',
            'amount' => '500',
            'pin' => '9999', // wrong pin
        ]);

        $response->assertSessionHas('error', 'Transaction PIN is incorrect.');
        $this->assertEquals(5000.00, $user->wallet->balance); // Unchanged
    }

    public function test_cannot_purchase_less_than_min_amount(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 5000]);

        $this->actingAs($user);

        $response = $this->post(route('services.betting.purchase'), [
            'platform' => 'bet9ja',
            'customer_id' => '1234567',
            'customer_name' => 'John Doe',
            'amount' => '50', // less than min 100
            'pin' => '1234',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_daily_betting_aggregate_limit(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 40000]);

        // Record ₦29,000 successful betting transactions today
        ServiceTransaction::create([
            'user_id' => $user->id,
            'service_type' => 'betting',
            'provider' => 'payscribe',
            'recipient' => '1234567',
            'amount' => 29000,
            'status' => 'success',
            'reference' => 'BET-OLD1',
        ]);

        $this->actingAs($user);

        // Attempting to fund ₦2,000 (total today would be 31,000 > 30,000 limit)
        $response = $this->post(route('services.betting.purchase'), [
            'platform' => 'bet9ja',
            'customer_id' => '1234567',
            'customer_name' => 'John Doe',
            'amount' => '2000',
            'pin' => '1234',
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue(str_contains(session('error'), 'Daily betting limit is ₦30,000.00'));
    }

    public function test_successful_betting_purchase(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $wallet = $user->wallet()->create(['balance' => 5000]);

        $this->actingAs($user);

        // Mock Successful Vend
        Http::fake([
            'https://api.payscribe.ng/api/v1/betting/vend' => Http::response([
                'status' => true,
                'message' => [
                    'description' => 'Transaction Successful',
                    'transaction_id' => 'TX-PAYS-999',
                    'details' => [
                        'bet_id' => 'bet9ja-api-ref'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->post(route('services.betting.purchase'), [
            'platform' => 'bet9ja',
            'customer_id' => '1234567',
            'customer_name' => 'John Doe',
            'amount' => '1000',
            'pin' => '1234',
        ]);

        $response->assertRedirect(route('services.betting'));
        $response->assertSessionHas('success', 'Betting wallet funded successfully!');

        // Wallet debited: 1000 + 50 charge = 1050
        $this->assertEquals(3950.00, $wallet->fresh()->balance);

        // Transaction logged
        $this->assertDatabaseHas('service_transactions', [
            'user_id' => $user->id,
            'service_type' => 'betting',
            'recipient' => '1234567',
            'amount' => 1000.00,
            'status' => 'success',
            'provider' => 'payscribe',
        ]);

        // Wallet transaction logged
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => 1050.00,
            'status' => 'success',
        ]);
    }
}

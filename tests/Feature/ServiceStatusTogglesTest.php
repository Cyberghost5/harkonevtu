<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceStatusTogglesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $this->user->wallet()->create(['balance' => 2000.00]);
    }

    public function test_recharge_card_printing_toggle_blocks_access(): void
    {
        // 1. Enabled by default
        AppSetting::set('service_recharge_card_printing', '1');

        $this->actingAs($this->user);
        $response = $this->get(route('services.print-pins'));
        $response->assertStatus(200);

        // 2. Disable service
        AppSetting::set('service_recharge_card_printing', '0');

        // Requesting page redirects to dashboard with error alert
        $response = $this->get(route('services.print-pins'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Recharge Card printing is currently disabled.');

        // API request gets 503 JSON
        $response = $this->postJson(route('services.print-pins.generate'), [
            'type' => 'airtime',
            'network' => 'mtn',
            'value' => 100,
            'quantity' => 1,
        ]);
        $response->assertStatus(503);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Recharge Card printing is currently disabled.');
    }

    public function test_funding_methods_toggles_block_access(): void
    {
        $this->actingAs($this->user);

        // Disable all payment/funding methods
        AppSetting::set('service_funding_gateway', '0');
        AppSetting::set('service_funding_auto_bank', '0');
        AppSetting::set('service_funding_manual', '0');
        AppSetting::set('service_funding_coupon', '0');

        // ATM/Card Gateway
        $response = $this->get(route('wallet.fund.gateway'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Card / ATM Funding is currently disabled.');

        // Auto Bank DVA
        $response = $this->get(route('wallet.fund.auto'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Auto Bank Transfer (DVA) is currently disabled.');

        // Manual Bank Transfer
        $response = $this->get(route('wallet.fund.manual'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Manual Bank Funding is currently disabled.');

        // Coupon Code
        $response = $this->get(route('wallet.fund.coupon'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Coupon Funding is currently disabled.');
    }

    public function test_auto_bank_funding_more_accounts_button_visibility(): void
    {
        $this->actingAs($this->user);

        // Enable auto bank transfer
        AppSetting::set('service_funding_auto_bank', '1');

        // Case 1: No accounts at all -> empty state (should show main generate button)
        $response = $this->get(route('wallet.fund.auto'));
        $response->assertStatus(200);
        $response->assertSee('Generate Virtual Accounts');
        $response->assertDontSee('Generate More Accounts');

        // Case 2: Configured Paystack and Flutterwave, but user only generated Paystack (2 accounts)
        AppSetting::set('paystack_secret_key', 'mock_sk');
        AppSetting::set('flutterwave_secret_key', 'mock_flw_sk');
        
        \App\Models\VirtualAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'paystack',
            'bank_name' => 'Wema Bank',
            'bank_code' => 'wema-bank',
            'account_number' => '1234567890',
            'account_name' => $this->user->name,
        ]);
        \App\Models\VirtualAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'paystack',
            'bank_name' => 'Titan Trust',
            'bank_code' => 'titan-paystack',
            'account_number' => '1234567891',
            'account_name' => $this->user->name,
        ]);

        // Has 2 accounts from Paystack. Flutterwave is enabled but missing.
        // It should see the "Generate More Accounts" button.
        $response = $this->get(route('wallet.fund.auto'));
        $response->assertStatus(200);
        $response->assertSee('Generate More Accounts');

        // Case 3: Let's also add Flutterwave account, so we have 3 accounts.
        \App\Models\VirtualAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'flutterwave',
            'bank_name' => 'Sterling Bank',
            'bank_code' => 'flutterwave_dva',
            'account_number' => '1234567892',
            'account_name' => $this->user->name,
        ]);

        // Total count is 3. Paystack and Flutterwave are enabled and have accounts.
        // "Generate More Accounts" button should NOT be visible.
        $response = $this->get(route('wallet.fund.auto'));
        $response->assertStatus(200);
        $response->assertDontSee('Generate More Accounts');

        // Case 4: Enable Monnify but it is missing.
        // Now canGenerateMore should be true again! (Even though count is 3)
        AppSetting::set('monnify_api_key', 'mock_monnify_key');
        $response = $this->get(route('wallet.fund.auto'));
        $response->assertStatus(200);
        $response->assertSee('Generate More Accounts');
    }
}

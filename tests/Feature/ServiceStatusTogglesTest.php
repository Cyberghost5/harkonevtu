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
}

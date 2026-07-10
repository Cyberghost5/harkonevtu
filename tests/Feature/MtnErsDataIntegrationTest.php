<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\DataPlan;
use App\Models\NetworkAirtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtnErsDataIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected NetworkAirtime $mtn;
    protected DataPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $this->user->wallet()->create(['balance' => 3000.00]);

        $this->mtn = NetworkAirtime::updateOrCreate(
            ['network_key' => 'mtn'],
            [
                'name' => 'MTN',
                'vtpass_id' => 'mtn',
                'enabled' => true,
            ]
        );

        // Seed an MTN Data Plan configured specifically for ERS SOAP API
        $this->plan = DataPlan::create([
            'network_key' => 'mtn',
            'data_type' => 'sme',
            'plan_name' => 'MTN SME 1GB',
            'validity' => '30 Days',
            'amount' => 350.00,
            'amount_agent' => 340.00,
            'mtn_ers_id' => '9', // ERS tariffTypeId
            'enabled' => true,
        ]);

        AppSetting::set('mtn_ers_originator_msisdn', '09062058470');
    }

    public function test_mtn_data_purchase_routes_to_ers_soap_sandbox_success(): void
    {
        AppSetting::set('data_api_mtn', 'mtn_ers');
        AppSetting::set('mtn_ers_mode', 'sandbox');

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.data.purchase'), [
            'network_key' => 'mtn',
            'data_type' => 'sme',
            'plan_id' => $this->plan->id,
            'phone' => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->user->wallet->refresh();
        // 3000 - 350 = 2650
        $this->assertEquals(2650.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id' => $this->user->id,
            'service_type' => 'data',
            'provider' => 'mtn',
            'recipient' => '08031112233',
            'amount' => 350.00,
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service' => 'data',
            'provider' => 'mtn_ers',
            'success' => true,
        ]);
    }

    public function test_mtn_data_purchase_routes_to_ers_soap_sandbox_failure_and_refunds(): void
    {
        AppSetting::set('data_api_mtn', 'mtn_ers');
        AppSetting::set('mtn_ers_mode', 'sandbox');

        $this->actingAs($this->user);

        // Phone number containing '9999' triggers simulated mock error "Insufficient Airtime"
        $response = $this->postJson(route('services.data.purchase'), [
            'network_key' => 'mtn',
            'data_type' => 'sme',
            'plan_id' => $this->plan->id,
            'phone' => '08039999233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        // Refunded immediately, so balance should still be 3000.00
        $this->assertEquals(3000.00, $this->user->wallet->balance);

        // Transaction is NOT recorded since it failed and refunded
        $this->assertDatabaseMissing('service_transactions', [
            'user_id' => $this->user->id,
            'service_type' => 'data',
        ]);

        // API logs still record the failed attempt
        $this->assertDatabaseHas('api_logs', [
            'service' => 'data',
            'provider' => 'mtn_ers',
            'success' => false,
        ]);
    }
}

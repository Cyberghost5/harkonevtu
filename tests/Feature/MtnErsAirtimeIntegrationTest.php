<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\NetworkAirtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtnErsAirtimeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected NetworkAirtime $mtn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $this->user->wallet()->create(['balance' => 2000.00]);

        $this->mtn = NetworkAirtime::updateOrCreate(
            ['network_key' => 'mtn'],
            [
                'name' => 'MTN',
                'vtpass_id' => 'mtn',
                'enabled' => true,
            ]
        );
    }

    public function test_mtn_airtime_purchase_routes_to_ers_soap_sandbox_success(): void
    {
        AppSetting::set('airtime_net_mtn', 'mtn_ers');
        AppSetting::set('mtn_ers_mode', 'sandbox');

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.airtime.purchase'), [
            'network' => 'mtn',
            'amount' => 500,
            'phone' => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->user->wallet->refresh();
        // Default discount is 0%, so 500 should be debited
        $this->assertEquals(1500.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id' => $this->user->id,
            'service_type' => 'airtime',
            'provider' => 'mtn',
            'recipient' => '08031112233',
            'amount' => 500.00,
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service' => 'airtime',
            'provider' => 'mtn_ers',
            'success' => true,
        ]);
    }

    public function test_mtn_airtime_purchase_routes_to_ers_soap_sandbox_failure_and_refunds(): void
    {
        AppSetting::set('airtime_net_mtn', 'mtn_ers');
        AppSetting::set('mtn_ers_mode', 'sandbox');

        $this->actingAs($this->user);

        // Phone number containing '9999' triggers simulated mock error "Insufficient Airtime"
        $response = $this->postJson(route('services.airtime.purchase'), [
            'network' => 'mtn',
            'amount' => 500,
            'phone' => '08039999233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        // Refunded immediately, so balance should still be 2000.00
        $this->assertEquals(2000.00, $this->user->wallet->balance);

        // Transaction is NOT recorded since it failed and refunded
        $this->assertDatabaseMissing('service_transactions', [
            'user_id' => $this->user->id,
            'service_type' => 'airtime',
        ]);

        // API logs still record the failed attempt
        $this->assertDatabaseHas('api_logs', [
            'service' => 'airtime',
            'provider' => 'mtn_ers',
            'success' => false,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\NetworkAirtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NineMobileEvcAirtimeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected NetworkAirtime $etisalat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $this->user->wallet()->create(['balance' => 2000.00]);

        $this->etisalat = NetworkAirtime::updateOrCreate(
            ['network_key' => 'etisalat'],
            [
                'name' => '9Mobile',
                'vtpass_id' => 'etisalat',
                'enabled' => true,
            ]
        );

        AppSetting::set('nine_mobile_evc_username', 'evcuser');
        AppSetting::set('nine_mobile_evc_password', 'secret');
    }

    public function test_web_9mobile_airtime_purchase_routes_to_evc_soap_sandbox_success(): void
    {
        AppSetting::set('airtime_net_etisalat', 'nine_mobile_evc');
        AppSetting::set('nine_mobile_evc_mode', 'sandbox');

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.airtime.purchase'), [
            'network' => 'etisalat',
            'amount' => 500,
            'phone' => '08091112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->user->wallet->refresh();
        $this->assertEquals(1500.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id' => $this->user->id,
            'service_type' => 'airtime',
            'provider' => 'etisalat',
            'recipient' => '08091112233',
            'amount' => 500.00,
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service' => 'airtime',
            'provider' => 'nine_mobile_evc',
            'success' => true,
        ]);
    }

    public function test_api_v1_9mobile_airtime_purchase_routes_to_evc_soap_sandbox_success(): void
    {
        AppSetting::set('airtime_net_etisalat', 'nine_mobile_evc');
        AppSetting::set('nine_mobile_evc_mode', 'sandbox');

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/airtime/purchase', [
                'network' => 'etisalat',
                'amount' => 200,
                'phone' => '08095556677',
                'pin' => '1234',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);

        $this->user->wallet->refresh();
        $this->assertEquals(1800.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('api_logs', [
            'service' => 'airtime',
            'provider' => 'nine_mobile_evc',
            'success' => true,
        ]);
    }

    public function test_9mobile_airtime_purchase_routes_to_evc_soap_sandbox_failure_and_refunds(): void
    {
        AppSetting::set('airtime_net_etisalat', 'nine_mobile_evc');
        AppSetting::set('nine_mobile_evc_mode', 'sandbox');

        $this->actingAs($this->user);

        // Phone number containing '9999' triggers simulated mock failure
        $response = $this->postJson(route('services.airtime.purchase'), [
            'network' => 'etisalat',
            'amount' => 500,
            'phone' => '08099992233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        $this->assertEquals(2000.00, $this->user->wallet->balance);

        $this->assertDatabaseMissing('service_transactions', [
            'user_id' => $this->user->id,
            'service_type' => 'airtime',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service' => 'airtime',
            'provider' => 'nine_mobile_evc',
            'success' => false,
        ]);
    }
}

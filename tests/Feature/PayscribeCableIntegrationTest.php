<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\CablePlan;
use App\Models\CableProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayscribeCableIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CableProvider $provider;
    protected CablePlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'              => 'Sarah Connor',
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $this->user->wallet()->create(['balance' => 10000.00]);

        $this->provider = CableProvider::create([
            'name'          => 'DSTV',
            'slug'          => 'dstv',
            'easyaccess_id' => '01',
            'payscribe_id'  => 'dstv',
            'sort_order'    => 1,
            'enabled'       => true,
        ]);

        $this->plan = CablePlan::create([
            'cable_provider_id' => $this->provider->id,
            'name'              => 'DStv Padi',
            'vtpass_id'         => 'padi',
            'easyaccess_id'     => '90',
            'payscribe_id'      => 'RWpqNXRDckJTNHVpeUN3QU11dlM2QT09',
            'amount'            => 3600.00,
            'sort_order'        => 1,
            'enabled'           => true,
        ]);

        AppSetting::set('payscribe_secret_key', 'test_payscribe_secret_key');
        AppSetting::set('payscribe_public_key', 'test_payscribe_public_key');
        config(['services.payscribe.secret_key' => 'test_payscribe_secret_key']);
        config(['services.payscribe.public_key' => 'test_payscribe_public_key']);
        AppSetting::set('payscribe_status', '1');
        AppSetting::set('service_cable', '1');
        AppSetting::set('cable_api', 'payscribe');
    }

    public function test_web_validate_card_payscribe_success(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/multichoice/validate' => Http::response([
                'status'      => true,
                'description' => 'Card Validated',
                'message'     => [
                    'details' => [
                        'customer_name' => 'SARAH CONNOR',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.cable.validate'), [
            'provider_id' => $this->provider->id,
            'plan_id'     => $this->plan->id,
            'smartcard'   => '1020304050',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('customer_name', 'SARAH CONNOR');
        $response->assertJsonPath('smartcard', '1020304050');

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'cable_validate',
            'provider' => 'payscribe',
            'success'  => true,
        ]);

        Http::assertSent(function (Request $request) {
            $data = json_decode($request->body(), true);
            return $request->url() === 'https://api.payscribe.ng/api/v1/multichoice/validate'
                && $request->hasHeader('Authorization', 'Bearer test_payscribe_secret_key')
                && ($data['service'] ?? '') === 'dstv'
                && ($data['account'] ?? '') === '1020304050'
                && ($data['plan_id'] ?? '') === 'RWpqNXRDckJTNHVpeUN3QU11dlM2QT09';
        });
    }

    public function test_web_validate_card_payscribe_failure(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/multichoice/validate' => Http::response([
                'status'      => false,
                'description' => 'Smartcard not found on network',
            ], 400),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.cable.validate'), [
            'provider_id' => $this->provider->id,
            'plan_id'     => $this->plan->id,
            'smartcard'   => '0000000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Smartcard not found on network');

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'cable_validate',
            'provider' => 'payscribe',
            'success'  => false,
        ]);
    }

    public function test_web_purchase_cable_payscribe_success(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/multichoice/vend' => Http::response([
                'status'      => true,
                'description' => 'Subscription successful',
                'reference'   => 'PS-CABLE-9988',
                'message'     => [
                    'details' => [
                        'customer_name' => 'SARAH CONNOR',
                        'transid'       => 'PS-CABLE-9988',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.cable.purchase'), [
            'provider_id'     => $this->provider->id,
            'plan_id'         => $this->plan->id,
            'smartcard'       => '1020304050',
            'phone'           => '08012345678',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('balance', '₦6,400.00');

        $this->user->wallet->refresh();
        $this->assertEquals(6400.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'cable',
            'amount'        => 3600.00,
            'status'        => 'success',
            'api_reference' => 'PS-CABLE-9988',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'cable',
            'provider' => 'payscribe',
            'success'  => true,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.payscribe.ng/api/v1/multichoice/vend'
                && $request->hasHeader('Authorization', 'Bearer test_payscribe_secret_key')
                && $request['service'] === 'dstv'
                && $request['plan_id'] === 'RWpqNXRDckJTNHVpeUN3QU11dlM2QT09'
                && $request['account'] === '1020304050';
        });
    }

    public function test_web_purchase_cable_payscribe_failure_and_refunds(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/multichoice/vend' => Http::response([
                'status'      => false,
                'description' => 'Provider account balance insufficient',
            ], 400),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.cable.purchase'), [
            'provider_id'     => $this->provider->id,
            'plan_id'         => $this->plan->id,
            'smartcard'       => '1020304050',
            'phone'           => '08012345678',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        $this->assertEquals(10000.00, $this->user->wallet->balance);

        $this->assertDatabaseMissing('service_transactions', [
            'user_id'      => $this->user->id,
            'service_type' => 'cable',
            'status'       => 'success',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'cable',
            'provider' => 'payscribe',
            'success'  => false,
        ]);
    }

    public function test_api_v1_validate_card_payscribe_success(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/multichoice/validate' => Http::response([
                'status'  => true,
                'message' => [
                    'details' => [
                        'customer_name' => 'API SUBSCRIBER',
                    ],
                ],
            ], 200),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/bills/cable/validate-card', [
                'provider_id' => $this->provider->id,
                'smartcard'   => '1020304050',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.customer_name', 'API SUBSCRIBER');
    }

    public function test_api_v1_purchase_cable_payscribe_success(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/multichoice/vend' => Http::response([
                'status'  => true,
                'message' => [
                    'details' => [
                        'transid' => 'PS-API-CABLE-777',
                    ],
                ],
            ], 200),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/bills/cable/purchase', [
                'provider_id' => $this->provider->id,
                'plan_id'     => $this->plan->id,
                'smartcard'   => '1020304050',
                'phone'       => '08012345678',
                'pin'         => '1234',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);

        $this->user->wallet->refresh();
        $this->assertEquals(6400.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'cable',
            'amount'        => 3600.00,
            'status'        => 'success',
            'api_reference' => 'PS-API-CABLE-777',
        ]);
    }

    public function test_api_v1_purchase_cable_payscribe_failure_and_refunds(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/multichoice/vend' => Http::response([
                'status'  => false,
                'message' => 'Cable operator connection timeout',
            ], 500),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/bills/cable/purchase', [
                'provider_id' => $this->provider->id,
                'plan_id'     => $this->plan->id,
                'smartcard'   => '1020304050',
                'phone'       => '08012345678',
                'pin'         => '1234',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        $this->assertEquals(10000.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'      => $this->user->id,
            'service_type' => 'cable',
            'status'       => 'refunded',
        ]);
    }

    public function test_payscribe_cable_fails_gracefully_when_secret_key_not_configured(): void
    {
        AppSetting::set('payscribe_secret_key', '');
        config(['services.payscribe.secret_key' => '']);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.cable.purchase'), [
            'provider_id'     => $this->provider->id,
            'plan_id'         => $this->plan->id,
            'smartcard'       => '1020304050',
            'phone'           => '08012345678',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);
        $response->assertJsonPath('message', 'Payscribe secret key is not configured.');

        $this->user->wallet->refresh();
        $this->assertEquals(10000.00, $this->user->wallet->balance);
    }
}

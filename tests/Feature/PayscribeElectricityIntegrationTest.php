<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ElectricityDisco;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayscribeElectricityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ElectricityDisco $disco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'              => 'John Doe',
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $this->user->wallet()->create(['balance' => 5000.00]);

        $this->disco = ElectricityDisco::create([
            'name'          => 'Ikeja Electric (IKEDC)',
            'slug'          => 'ikeja-electric',
            'short_code'    => 'ikeja',
            'easyaccess_id' => '02',
            'payscribe_id'  => 'ikedc',
            'sort_order'    => 1,
            'enabled'       => true,
        ]);

        AppSetting::set('payscribe_secret_key', 'test_payscribe_secret_key');
        AppSetting::set('payscribe_public_key', 'test_payscribe_public_key');
        config(['services.payscribe.secret_key' => 'test_payscribe_secret_key']);
        config(['services.payscribe.public_key' => 'test_payscribe_public_key']);
        AppSetting::set('payscribe_status', '1');
        AppSetting::set('service_electricity', '1');
        AppSetting::set('electricity_api', 'payscribe');
        AppSetting::set('electricity_min_amount', '500');
        AppSetting::set('electricity_daily_limit', '50000');
    }

    public function test_web_validate_meter_payscribe_success(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/electricity/validate' => Http::response([
                'status'      => true,
                'description' => 'Meter Validated',
                'message'     => [
                    'details' => [
                        'customer_name' => 'JOHN DOE',
                        'address'       => '123 IKEJA WAY, LAGOS',
                        'meter_number'  => '1122334455',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.electricity.validate'), [
            'disco_id'     => $this->disco->id,
            'meter_type'   => 'prepaid',
            'meter_number' => '1122334455',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('customer_name', 'JOHN DOE');
        $response->assertJsonPath('customer_address', '123 IKEJA WAY, LAGOS');

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'electricity_validate',
            'provider' => 'payscribe',
            'success'  => true,
        ]);

        Http::assertSent(function (Request $request) {
            $data = json_decode($request->body(), true);
            return $request->url() === 'https://api.payscribe.ng/api/v1/electricity/validate'
                && $request->hasHeader('Authorization', 'Bearer test_payscribe_secret_key')
                && ($data['service'] ?? '') === 'ikedc'
                && ($data['meter_number'] ?? '') === '1122334455';
        });
    }

    public function test_web_validate_meter_payscribe_failure(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/electricity/validate' => Http::response([
                'status'      => false,
                'description' => 'Invalid meter number',
            ], 400),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.electricity.validate'), [
            'disco_id'     => $this->disco->id,
            'meter_type'   => 'prepaid',
            'meter_number' => '0000000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Invalid meter number');

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'electricity_validate',
            'provider' => 'payscribe',
            'success'  => false,
        ]);
    }

    public function test_web_purchase_prepaid_electricity_payscribe_success(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/electricity/vend' => Http::response([
                'status'      => true,
                'status_code' => 200,
                'description' => 'Electricity Purchase Successful',
                'message'     => [
                    'details' => [
                        'token'         => '1122-3344-5566-7788',
                        'unit'          => '45.2',
                        'customer_name' => 'JOHN DOE',
                        'transid'       => 'PS-ELEC-123456',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.electricity.purchase'), [
            'disco_id'        => $this->disco->id,
            'meter_type'      => 'prepaid',
            'meter_number'    => '1122334455',
            'amount'          => 1000,
            'phone'           => '08012345678',
            'customer_name'   => 'JOHN DOE',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('token', '1122-3344-5566-7788');
        $response->assertJsonPath('units', '45.2');
        $response->assertJsonPath('balance', '₦4,000.00');

        $this->user->wallet->refresh();
        $this->assertEquals(4000.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'electricity',
            'amount'        => 1000.00,
            'status'        => 'success',
            'api_reference' => 'PS-ELEC-123456',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'electricity',
            'provider' => 'payscribe',
            'success'  => true,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.payscribe.ng/api/v1/electricity/vend'
                && $request->hasHeader('Authorization', 'Bearer test_payscribe_secret_key')
                && $request['service'] === 'ikedc'
                && $request['amount'] === '1000';
        });
    }

    public function test_web_purchase_electricity_payscribe_failure_and_refunds(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/electricity/vend' => Http::response([
                'status'      => false,
                'description' => 'DISCO gateway temporarily down',
            ], 400),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.electricity.purchase'), [
            'disco_id'        => $this->disco->id,
            'meter_type'      => 'prepaid',
            'meter_number'    => '1122334455',
            'amount'          => 1000,
            'phone'           => '08012345678',
            'customer_name'   => 'JOHN DOE',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        $this->assertEquals(5000.00, $this->user->wallet->balance);

        $this->assertDatabaseMissing('service_transactions', [
            'user_id'      => $this->user->id,
            'service_type' => 'electricity',
            'status'       => 'success',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'electricity',
            'provider' => 'payscribe',
            'success'  => false,
        ]);
    }

    public function test_api_v1_validate_meter_payscribe_success(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/electricity/validate' => Http::response([
                'status'  => true,
                'message' => [
                    'details' => [
                        'customer_name' => 'API CUSTOMER',
                        'address'       => 'VICTORIA ISLAND, LAGOS',
                    ],
                ],
            ], 200),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/bills/electricity/validate-meter', [
                'disco_id'     => $this->disco->id,
                'meter_type'   => 'prepaid',
                'meter_number' => '9988776655',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.customer_name', 'API CUSTOMER');
        $response->assertJsonPath('data.address', 'VICTORIA ISLAND, LAGOS');
    }

    public function test_api_v1_purchase_electricity_payscribe_success(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/electricity/vend' => Http::response([
                'status'  => true,
                'message' => [
                    'details' => [
                        'token'   => '8888-9999-0000-1111',
                        'unit'    => '22.8',
                        'transid' => 'PS-API-ELEC-999',
                    ],
                ],
            ], 200),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/bills/electricity/purchase', [
                'disco_id'     => $this->disco->id,
                'meter_type'   => 'prepaid',
                'meter_number' => '1122334455',
                'amount'       => 1500,
                'phone'        => '08012345678',
                'pin'          => '1234',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.token', '8888-9999-0000-1111');
        $response->assertJsonPath('data.units', '22.8');

        $this->user->wallet->refresh();
        $this->assertEquals(3500.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'electricity',
            'amount'        => 1500.00,
            'status'        => 'success',
            'api_reference' => 'PS-API-ELEC-999',
        ]);
    }

    public function test_api_v1_purchase_electricity_payscribe_failure_and_refunds(): void
    {
        Http::fake([
            'https://api.payscribe.ng/api/v1/electricity/vend' => Http::response([
                'status'  => false,
                'message' => 'Connection timeout to DISCO',
            ], 500),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/bills/electricity/purchase', [
                'disco_id'     => $this->disco->id,
                'meter_type'   => 'prepaid',
                'meter_number' => '1122334455',
                'amount'       => 1500,
                'phone'        => '08012345678',
                'pin'          => '1234',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        $this->assertEquals(5000.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'      => $this->user->id,
            'service_type' => 'electricity',
            'status'       => 'refunded',
        ]);
    }

    public function test_payscribe_electricity_fails_gracefully_when_secret_key_not_configured(): void
    {
        AppSetting::set('payscribe_secret_key', '');
        config(['services.payscribe.secret_key' => '']);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.electricity.purchase'), [
            'disco_id'        => $this->disco->id,
            'meter_type'      => 'prepaid',
            'meter_number'    => '1122334455',
            'amount'          => 1000,
            'phone'           => '08012345678',
            'customer_name'   => 'JOHN DOE',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);
        $response->assertJsonPath('message', 'Payscribe secret key is not configured.');

        $this->user->wallet->refresh();
        $this->assertEquals(5000.00, $this->user->wallet->balance);
    }
}

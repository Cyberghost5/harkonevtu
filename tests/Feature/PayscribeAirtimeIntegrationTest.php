<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\NetworkAirtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayscribeAirtimeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected NetworkAirtime $mtn;
    protected NetworkAirtime $etisalat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $this->user->wallet()->create(['balance' => 2000.00]);

        $this->mtn = NetworkAirtime::updateOrCreate(
            ['network_key' => 'mtn'],
            [
                'name'         => 'MTN',
                'vtpass_id'    => 'mtn',
                'payscribe_id' => 'mtn',
                'enabled'      => true,
            ]
        );

        $this->etisalat = NetworkAirtime::updateOrCreate(
            ['network_key' => 'etisalat'],
            [
                'name'         => '9Mobile',
                'vtpass_id'    => 'etisalat',
                'payscribe_id' => '9mobile',
                'enabled'      => true,
            ]
        );

        AppSetting::set('payscribe_secret_key', 'test_payscribe_secret_key');
        AppSetting::set('payscribe_public_key', 'test_payscribe_public_key');
        config(['services.payscribe.secret_key' => 'test_payscribe_secret_key']);
        config(['services.payscribe.public_key' => 'test_payscribe_public_key']);
        AppSetting::set('payscribe_status', '1');
        AppSetting::set('service_airtime', '1');
    }

    public function test_web_airtime_purchase_routes_to_payscribe_success(): void
    {
        AppSetting::set('airtime_api', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/airtime' => Http::response([
                'status'      => true,
                'message'     => 'Airtime Purchase Successful',
                'description' => 'Airtime Purchase Successful',
                'details'     => [
                    'transid'   => 'PS-AIR-998877',
                    'network'   => 'mtn',
                    'amount'    => 500,
                    'recipient' => '08031112233',
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.airtime.purchase'), [
            'network'         => 'mtn',
            'amount'          => 500,
            'phone'           => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('balance', '₦1,500.00');

        $this->user->wallet->refresh();
        $this->assertEquals(1500.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'airtime',
            'provider'      => 'mtn',
            'recipient'     => '08031112233',
            'amount'        => 500.00,
            'status'        => 'success',
            'api_reference' => 'PS-AIR-998877',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'airtime',
            'provider' => 'payscribe',
            'success'  => true,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.payscribe.ng/api/v1/airtime'
                && $request->hasHeader('Authorization', 'Bearer test_payscribe_secret_key')
                && $request['network'] === 'mtn'
                && $request['amount'] === 500
                && $request['recipient'] === '08031112233';
        });
    }

    public function test_web_airtime_purchase_routes_to_payscribe_failure_and_refunds(): void
    {
        AppSetting::set('airtime_api', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/airtime' => Http::response([
                'status'      => false,
                'message'     => 'Insufficient wallet balance',
                'description' => 'Provider balance too low',
            ], 400),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.airtime.purchase'), [
            'network'         => 'mtn',
            'amount'          => 500,
            'phone'           => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        $this->assertEquals(2000.00, $this->user->wallet->balance);

        $this->assertDatabaseMissing('service_transactions', [
            'user_id'      => $this->user->id,
            'service_type' => 'airtime',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'airtime',
            'provider' => 'payscribe',
            'success'  => false,
        ]);
    }

    public function test_api_v1_airtime_purchase_routes_to_payscribe_and_maps_9mobile(): void
    {
        AppSetting::set('airtime_net_etisalat', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/airtime' => Http::response([
                'status'      => true,
                'message'     => 'Airtime sent successfully',
                'description' => 'Airtime sent successfully',
                'details'     => [
                    'transid'   => 'PS-9MOBILE-4455',
                    'network'   => '9mobile',
                    'amount'    => 200,
                    'recipient' => '08095556677',
                ],
            ], 200),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/airtime/purchase', [
                'network' => 'etisalat',
                'amount'  => 200,
                'phone'   => '08095556677',
                'pin'     => '1234',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);

        $this->user->wallet->refresh();
        $this->assertEquals(1800.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'airtime',
            'provider'      => 'etisalat',
            'recipient'     => '08095556677',
            'amount'        => 200.00,
            'status'        => 'success',
            'api_reference' => 'PS-9MOBILE-4455',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'airtime',
            'provider' => 'payscribe',
            'success'  => true,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.payscribe.ng/api/v1/airtime'
                && $request['network'] === '9mobile'
                && $request['amount'] === 200
                && $request['recipient'] === '08095556677';
        });
    }

    public function test_api_v1_airtime_purchase_routes_to_payscribe_failure_and_refunds(): void
    {
        AppSetting::set('airtime_api', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/airtime' => Http::response([
                'status'  => false,
                'message' => 'Network timeout from Telco',
            ], 500),
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/airtime/purchase', [
                'network' => 'mtn',
                'amount'  => 300,
                'phone'   => '08031112233',
                'pin'     => '1234',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        $this->assertEquals(2000.00, $this->user->wallet->balance);

        $this->assertDatabaseMissing('service_transactions', [
            'user_id'      => $this->user->id,
            'service_type' => 'airtime',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'airtime',
            'provider' => 'payscribe',
            'success'  => false,
        ]);
    }

    public function test_payscribe_fails_gracefully_when_secret_key_not_configured(): void
    {
        AppSetting::set('airtime_api', 'payscribe');
        AppSetting::set('payscribe_secret_key', '');
        config(['services.payscribe.secret_key' => '']);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.airtime.purchase'), [
            'network'         => 'mtn',
            'amount'          => 500,
            'phone'           => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);
        $response->assertJsonPath('message', 'Payscribe secret key is not configured.');

        $this->user->wallet->refresh();
        $this->assertEquals(2000.00, $this->user->wallet->balance);
    }
}

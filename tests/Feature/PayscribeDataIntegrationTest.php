<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\DataPlan;
use App\Models\NetworkAirtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayscribeDataIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected NetworkAirtime $mtn;
    protected NetworkAirtime $etisalat;
    protected DataPlan $mtnPlan;
    protected DataPlan $etisalatPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $this->user->wallet()->create(['balance' => 5000.00]);

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

        $this->mtnPlan = DataPlan::create([
            'network_key'  => 'mtn',
            'data_type'    => 'sme',
            'plan_name'    => 'MTN SME 1GB',
            'validity'     => '30 Days',
            'amount'       => 300.00,
            'amount_agent' => 290.00,
            'payscribe_id' => 'PS_MTN_1GB',
            'enabled'      => true,
        ]);

        $this->etisalatPlan = DataPlan::create([
            'network_key'  => 'etisalat',
            'data_type'    => 'gifting',
            'plan_name'    => '9Mobile 1.5GB',
            'validity'     => '30 Days',
            'amount'       => 1000.00,
            'amount_agent' => 950.00,
            'payscribe_id' => 'PS_9MOB_1_5GB',
            'enabled'      => true,
        ]);

        AppSetting::set('payscribe_secret_key', 'test_payscribe_secret_key');
        AppSetting::set('payscribe_public_key', 'test_payscribe_public_key');
        config(['services.payscribe.secret_key' => 'test_payscribe_secret_key']);
        config(['services.payscribe.public_key' => 'test_payscribe_public_key']);
        AppSetting::set('payscribe_status', '1');
        AppSetting::set('service_data', '1');
    }

    public function test_web_data_purchase_routes_to_payscribe_success(): void
    {
        AppSetting::set('data_api_mtn', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/data/vend' => Http::response([
                'status'  => true,
                'message' => 'Data vend successful',
                'data'    => [
                    'trans_id'  => 'PS-DATA-100200',
                    'network'   => 'mtn',
                    'recipient' => '08031112233',
                    'amount'    => 300,
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.data.purchase'), [
            'network_key'     => 'mtn',
            'data_type'       => 'sme',
            'plan_id'         => $this->mtnPlan->id,
            'phone'           => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->user->wallet->refresh();
        $this->assertEquals(4700.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'data',
            'provider'      => 'mtn',
            'recipient'     => '08031112233',
            'amount'        => 300.00,
            'status'        => 'success',
            'api_reference' => 'PS-DATA-100200',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'data',
            'provider' => 'payscribe',
            'success'  => true,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.payscribe.ng/api/v1/data/vend'
                && $request['network'] === 'mtn'
                && ($request['plan'] ?? null) === 'PS_MTN_1GB'
                && $request['recipient'] === '08031112233';
        });
    }

    public function test_api_data_purchase_routes_to_payscribe_success_and_maps_9mobile(): void
    {
        AppSetting::set('data_api_etisalat', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/data/vend' => Http::response([
                'status'  => true,
                'message' => 'Data vend successful',
                'details' => [
                    'transid'   => 'PS-DATA-9MOB-777',
                    'network'   => '9mobile',
                    'recipient' => '08091112233',
                ],
            ], 200),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/data/purchase', [
            'plan_id'         => $this->etisalatPlan->id,
            'phone'           => '08091112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.api_reference', 'PS-DATA-9MOB-777');

        $this->user->wallet->refresh();
        $this->assertEquals(4000.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'data',
            'provider'      => 'etisalat',
            'recipient'     => '08091112233',
            'amount'        => 1000.00,
            'status'        => 'success',
            'api_reference' => 'PS-DATA-9MOB-777',
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.payscribe.ng/api/v1/data/vend'
                && $request['network'] === '9mobile'
                && ($request['plan'] ?? null) === 'PS_9MOB_1_5GB';
        });
    }

    public function test_web_data_purchase_refunds_on_payscribe_failure(): void
    {
        AppSetting::set('data_api_mtn', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/data/vend' => Http::response([
                'status'      => false,
                'description' => 'Vendor route unavailable',
            ], 400),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.data.purchase'), [
            'network_key'     => 'mtn',
            'data_type'       => 'sme',
            'plan_id'         => $this->mtnPlan->id,
            'phone'           => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);

        // Wallet should be restored to initial 5000.00
        $this->user->wallet->refresh();
        $this->assertEquals(5000.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'data',
            'provider' => 'payscribe',
            'success'  => false,
        ]);
    }

    public function test_api_data_purchase_refunds_on_payscribe_failure(): void
    {
        AppSetting::set('data_api_mtn', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/data/vend' => Http::response([
                'status'  => false,
                'message' => 'Network timeout from upstream',
            ], 200),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/data/purchase', [
            'plan_id'         => $this->mtnPlan->id,
            'phone'           => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('refunded', true);

        $this->user->wallet->refresh();
        $this->assertEquals(5000.00, $this->user->wallet->balance);
    }

    public function test_data_purchase_fails_when_plan_missing_payscribe_id(): void
    {
        AppSetting::set('data_api_mtn', 'payscribe');

        $unconfiguredPlan = DataPlan::create([
            'network_key'  => 'mtn',
            'data_type'    => 'sme',
            'plan_name'    => 'MTN Unconfigured Plan',
            'validity'     => '30 Days',
            'amount'       => 200.00,
            'amount_agent' => 190.00,
            'payscribe_id' => null,
            'enabled'      => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.data.purchase'), [
            'network_key'     => 'mtn',
            'data_type'       => 'sme',
            'plan_id'         => $unconfiguredPlan->id,
            'phone'           => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Selected plan is not available.');

        $this->user->wallet->refresh();
        $this->assertEquals(5000.00, $this->user->wallet->balance);
    }

    public function test_data_plans_scope_and_id_for_api_payscribe(): void
    {
        $this->assertEquals('PS_MTN_1GB', $this->mtnPlan->idForApi('payscribe'));

        $plansWithPayscribe = DataPlan::forApi('payscribe')->pluck('id')->all();
        $this->assertContains($this->mtnPlan->id, $plansWithPayscribe);
        $this->assertContains($this->etisalatPlan->id, $plansWithPayscribe);

        $noPayscribe = DataPlan::create([
            'network_key'  => 'mtn',
            'data_type'    => 'sme',
            'plan_name'    => 'MTN Other',
            'validity'     => '30 Days',
            'amount'       => 150.00,
            'amount_agent' => 140.00,
            'payscribe_id' => null,
            'enabled'      => true,
        ]);

        $this->assertNotContains($noPayscribe->id, DataPlan::forApi('payscribe')->pluck('id')->all());
        $this->assertNull($noPayscribe->idForApi('payscribe'));
    }

    public function test_payscribe_appears_in_admin_api_settings_dropdown_lists(): void
    {
        $admin = User::factory()->create([
            'is_admin'          => true,
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.api'));

        $response->assertStatus(200);
        $response->assertViewHas('dataProviders', function ($providers) {
            return in_array('payscribe', $providers);
        });
        $response->assertViewHas('airtimeProviders', function ($providers) {
            return in_array('payscribe', $providers);
        });
        $response->assertViewHas('electricityProviders', function ($providers) {
            return in_array('payscribe', $providers);
        });
        $response->assertViewHas('cableProviders', function ($providers) {
            return in_array('payscribe', $providers);
        });
        $response->assertViewHas('bettingProviders', function ($providers) {
            return in_array('payscribe', $providers);
        });
    }
}

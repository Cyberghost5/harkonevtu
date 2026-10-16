<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ExamPinType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayscribeExamPinIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ExamPinType $waec;
    protected ExamPinType $neco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $this->user->wallet()->create(['balance' => 10000.00]);

        $this->waec = ExamPinType::updateOrCreate(
            ['slug' => 'waec'],
            [
                'name'                 => 'WAEC Result Checker PIN',
                'amount'               => 3600.00,
                'vtpass_service_id'    => 'waec',
                'easyaccess_endpoint'  => '/api/waec_v2.php',
                'payscribe_service_id' => 'waec',
                'instructions'         => 'Check WAEC result online.',
                'is_active'            => true,
            ]
        );

        $this->neco = ExamPinType::updateOrCreate(
            ['slug' => 'neco'],
            [
                'name'                 => 'NECO Result Checker PIN',
                'amount'               => 1400.00,
                'vtpass_service_id'    => 'neco',
                'easyaccess_endpoint'  => '/api/neco_v2.php',
                'payscribe_service_id' => 'neco',
                'instructions'         => 'Check NECO result online.',
                'is_active'            => true,
            ]
        );

        AppSetting::set('payscribe_secret_key', 'test_payscribe_secret_key');
        AppSetting::set('payscribe_public_key', 'test_payscribe_public_key');
        config(['services.payscribe.secret_key' => 'test_payscribe_secret_key']);
        config(['services.payscribe.public_key' => 'test_payscribe_public_key']);
        AppSetting::set('payscribe_status', '1');
        AppSetting::set('service_epins', '1');
    }

    public function test_web_exam_pin_purchase_routes_to_payscribe_success(): void
    {
        AppSetting::set('epins_api', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/education/vend' => Http::response([
                'status'  => true,
                'message' => 'Transaction Successful',
                'data'    => [
                    'trans_id' => 'PS-EPN-998811',
                    'cards'    => [
                        [
                            'pin'    => '112233445566',
                            'serial' => 'WRC12345678',
                        ],
                        [
                            'pin'    => '998877665544',
                            'serial' => 'WRC87654321',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.epins.purchase'), [
            'exam_type_id'    => $this->waec->id,
            'quantity'        => 2,
            'phone'           => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('quantity', 2);
        $response->assertJsonCount(2, 'pins');
        $response->assertJsonPath('pins.0.pin', '112233445566');
        $response->assertJsonPath('pins.0.serial', 'WRC12345678');

        // 10000 - (3600 * 2) = 2800
        $this->user->wallet->refresh();
        $this->assertEquals(2800.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'epin',
            'provider'      => 'waec',
            'recipient'     => '08031112233',
            'amount'        => 7200.00,
            'status'        => 'success',
            'api_reference' => 'PS-EPN-998811',
        ]);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'epin',
            'provider' => 'payscribe',
            'success'  => true,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.payscribe.ng/api/v1/education/vend'
                && $request['service'] === 'waec'
                && $request['quantity'] === 2;
        });
    }

    public function test_api_exam_pin_purchase_routes_to_payscribe_success(): void
    {
        AppSetting::set('epins_api', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/education/vend' => Http::response([
                'status'  => true,
                'message' => 'Transaction Successful',
                'details' => [
                    'transid' => 'PS-EPN-NECO-001',
                    'tokens'  => [
                        [
                            'pin'    => '998822110022',
                            'serial' => 'NEC123000',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/bills/exam-pins/purchase', [
            'exam_type_id'    => $this->neco->id,
            'quantity'        => 1,
            'phone'           => '08091112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.api_reference', 'PS-EPN-NECO-001');
        $response->assertJsonCount(1, 'data.tokens');

        // 10000 - 1400 = 8600
        $this->user->wallet->refresh();
        $this->assertEquals(8600.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('service_transactions', [
            'user_id'       => $this->user->id,
            'service_type'  => 'epin',
            'provider'      => 'neco',
            'amount'        => 1400.00,
            'status'        => 'success',
            'api_reference' => 'PS-EPN-NECO-001',
        ]);
    }

    public function test_web_exam_pin_purchase_refunds_on_payscribe_failure(): void
    {
        AppSetting::set('epins_api', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/education/vend' => Http::response([
                'status'      => false,
                'description' => 'Pin stock exhausted',
            ], 400),
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson(route('services.epins.purchase'), [
            'exam_type_id'    => $this->waec->id,
            'quantity'        => 1,
            'phone'           => '08031112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('refunded', true);

        // Wallet balance restored
        $this->user->wallet->refresh();
        $this->assertEquals(10000.00, $this->user->wallet->balance);

        $this->assertDatabaseHas('api_logs', [
            'service'  => 'epin',
            'provider' => 'payscribe',
            'success'  => false,
        ]);
    }

    public function test_api_exam_pin_purchase_refunds_on_payscribe_failure(): void
    {
        AppSetting::set('epins_api', 'payscribe');

        Http::fake([
            'https://api.payscribe.ng/api/v1/education/vend' => Http::response([
                'status'  => false,
                'message' => 'Provider error',
            ], 200),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/bills/exam-pins/purchase', [
            'exam_type_id'    => $this->neco->id,
            'quantity'        => 1,
            'phone'           => '08091112233',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);

        $this->user->wallet->refresh();
        $this->assertEquals(10000.00, $this->user->wallet->balance);
    }

    public function test_admin_api_settings_includes_payscribe_in_epins_providers(): void
    {
        $admin = User::factory()->create([
            'is_admin'          => true,
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.api'));

        $response->assertStatus(200);
        $response->assertViewHas('epinsProviders', function ($providers) {
            return in_array('payscribe', $providers);
        });
    }
}

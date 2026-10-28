<?php

namespace Tests\Feature;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\PrintedVoucher;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClubkonnectVoucherPrintingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
        AppSetting::set('service_recharge_card_printing', '1');
        AppSetting::set('clubkonnect_api_key', 'test_ck_key');
        AppSetting::set('clubkonnect_user_id', 'CK10001');
        AppSetting::set('clubkonnect_status', '1');
    }

    public function test_admin_settings_includes_clubkonnect_in_airtime_pin_providers(): void
    {
        $admin = User::factory()->create([
            'is_admin'  => true,
            'user_type' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.settings.api'));
        $response->assertStatus(200);
        $response->assertViewHas('airtimePinProviders', function ($providers) {
            return in_array('clubkonnect', $providers);
        });
    }

    public function test_web_voucher_generation_via_clubkonnect_success(): void
    {
        AppSetting::set('airtime_pin_api', 'clubkonnect');

        $user = User::factory()->create([
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $wallet = $user->wallet()->create(['balance' => 1000]);

        Http::fake([
            'https://www.nellobytesystems.com/APIEPINV1.asp*' => Http::response([
                'ORDER_ID' => 'CK-ORD-998877',
                'TXN_EPIN' => [
                    [
                        'transactionid'   => 'TXN001',
                        'mobilenetwork'   => 'MTN',
                        'amount'          => '100',
                        'batchno'         => 'B101',
                        'sno'             => 'SN1234567890',
                        'pin'             => '1111222233334444',
                    ],
                    [
                        'transactionid'   => 'TXN002',
                        'mobilenetwork'   => 'MTN',
                        'amount'          => '100',
                        'batchno'         => 'B101',
                        'sno'             => 'SN1234567891',
                        'pin'             => '5555666677778888',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('services.print-pins.generate'), [
            'type'            => 'airtime',
            'network'         => 'mtn',
            'value'           => 100,
            'quantity'        => 2,
            'name_on_card'    => 'Test Store',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Successfully generated 2 vouchers!');

        // Single request dispatched with Quantity=2
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'APIEPINV1.asp')
                && $request['Quantity'] == 2
                && $request['Value'] == 100
                && $request['MobileNetwork'] == '01';
        });

        // 2 vouchers saved in database
        $this->assertEquals(2, PrintedVoucher::where('user_id', $user->id)->count());

        $first = PrintedVoucher::where('pin', '1111222233334444')->first();
        $this->assertNotNull($first);
        $this->assertEquals('SN1234567890', $first->serial_number);
        $this->assertEquals(100.00, $first->value);

        // Wallet debited 200 (100 * 2)
        $this->assertEquals(800.00, $wallet->fresh()->balance);

        // ApiLog entry created
        $this->assertDatabaseHas('api_logs', [
            'provider' => 'clubkonnect',
            'service'  => 'voucher',
            'success'  => true,
        ]);
    }

    public function test_web_voucher_generation_denominations_validation(): void
    {
        $user = User::factory()->create([
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        // Value 150 is non-standard for airtime
        $response = $this->postJson(route('services.print-pins.generate'), [
            'type'            => 'airtime',
            'network'         => 'mtn',
            'value'           => 150,
            'quantity'        => 1,
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['value']);
    }

    public function test_web_voucher_generation_refunds_on_clubkonnect_failure(): void
    {
        AppSetting::set('airtime_pin_api', 'clubkonnect');

        $user = User::factory()->create([
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $wallet = $user->wallet()->create(['balance' => 1000]);

        Http::fake([
            'https://www.nellobytesystems.com/APIEPINV1.asp*' => Http::response([
                'status'       => 'ORDER_FAILED',
                'statusremark' => 'INSUFFICIENT_WALLET_BALANCE',
            ], 200),
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('services.print-pins.generate'), [
            'type'            => 'airtime',
            'network'         => 'mtn',
            'value'           => 100,
            'quantity'        => 2,
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        // Balance restored back to 1000 via refund
        $this->assertEquals(1000.00, $wallet->fresh()->balance);
        $this->assertEquals(0, PrintedVoucher::count());
    }

    public function test_api_v1_voucher_generation_via_clubkonnect_success(): void
    {
        AppSetting::set('airtime_pin_net_glo', 'clubkonnect');

        $user = User::factory()->create([
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $wallet = $user->wallet()->create(['balance' => 2000]);

        Http::fake([
            'https://www.nellobytesystems.com/APIEPINV1.asp*' => Http::response([
                'ORDER_ID' => 'CK-ORD-GLO-123',
                'TXN_EPIN' => [
                    [
                        'transactionid' => 'GLO001',
                        'mobilenetwork' => 'GLO',
                        'amount'        => '500',
                        'sno'           => 'GLOSN9988',
                        'pin'           => '9999888877776666',
                    ],
                ],
            ], 200),
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/vouchers/generate', [
                'type'            => 'airtime',
                'network'         => 'glo',
                'value'           => 500,
                'quantity'        => 1,
                'name_on_card'    => 'Biz Card',
                'transaction_pin' => '1234',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.network', 'GLO');
        $response->assertJsonPath('data.pins.0.pin', '9999888877776666');
        $response->assertJsonPath('data.pins.0.serial', 'GLOSN9988');

        // Glo network code is '02'
        Http::assertSent(function ($request) {
            return $request['MobileNetwork'] == '02' && $request['Value'] == 500;
        });

        $this->assertEquals(1500.00, $wallet->fresh()->balance);
    }
}

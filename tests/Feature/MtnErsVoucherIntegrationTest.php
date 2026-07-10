<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PrintedVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtnErsVoucherIntegrationTest extends TestCase
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
        $this->user->wallet()->create(['balance' => 5000.00]);

        AppSetting::set('mtn_ers_originator_msisdn', '09062058470');
    }

    public function test_mtn_voucher_generation_routes_to_ers_soap_sandbox_success(): void
    {
        AppSetting::set('epins_api', 'mtn_ers');
        AppSetting::set('mtn_ers_mode', 'sandbox');

        $this->actingAs($this->user);

        // Generate 3 MTN vouchers of value 100
        $response = $this->post(route('services.print-pins.generate'), [
            'type' => 'airtime',
            'network' => 'mtn',
            'value' => 100,
            'quantity' => 3,
            'name_on_card' => 'My Shop',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check wallet debit: 5000 - (100 * 3) = 4700
        $this->user->wallet->refresh();
        $this->assertEquals(4700.00, $this->user->wallet->balance);

        // Verify printed_vouchers contains records with correct ERS mock pins & serials
        $vouchers = PrintedVoucher::where('user_id', $this->user->id)->get();
        $this->assertCount(3, $vouchers);

        foreach ($vouchers as $v) {
            $this->assertEquals('mtn', $v->network);
            $this->assertEquals(100.00, $v->value);
            $this->assertEquals('40692125281574', $v->pin);
            $this->assertEquals('600000000001', $v->serial_number);
        }

        // Assert API logs recorded the three SOAP requests
        $this->assertEquals(3, \App\Models\ApiLog::where('service', 'data') // note: voucher printing is logged as 'data' service or similar in controllers
            ->orWhere('service', 'voucher') // let's count mtn_ers provider log count
            ->where('provider', 'mtn_ers')
            ->count());
    }

    public function test_mtn_voucher_generation_failure_rolls_back_and_refunds(): void
    {
        AppSetting::set('epins_api', 'mtn_ers');
        AppSetting::set('mtn_ers_mode', 'sandbox');
        // Let's set ERS originator to a number containing 9999 to trigger simulated vend failure
        AppSetting::set('mtn_ers_originator_msisdn', '09062059999');

        $this->actingAs($this->user);

        $response = $this->post(route('services.print-pins.generate'), [
            'type' => 'airtime',
            'network' => 'mtn',
            'value' => 100,
            'quantity' => 2,
            'name_on_card' => 'My Shop',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('MTN ERS Voucher generation failed', session('error'));

        // Wallet should be completely refunded (rolled back): balance remains 5000.00
        $this->user->wallet->refresh();
        $this->assertEquals(5000.00, $this->user->wallet->balance);

        // No printed voucher records should be created
        $this->assertCount(0, PrintedVoucher::all());
    }
}

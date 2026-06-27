<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use App\Models\PrintedVoucher;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherPrintingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
    }

    public function test_user_can_generate_airtime_vouchers(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $wallet = $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        $response = $this->post(route('services.print-pins.generate'), [
            'type' => 'airtime',
            'network' => 'mtn',
            'value' => '100',
            'quantity' => '5',
            'name_on_card' => 'Joy Shop',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Successfully generated 5 vouchers!');

        // 5 vouchers generated in database
        $this->assertEquals(5, PrintedVoucher::where('user_id', $user->id)->count());

        $firstVoucher = PrintedVoucher::first();
        $this->assertEquals('airtime', $firstVoucher->type);
        $this->assertEquals('mtn', $firstVoucher->network);
        $this->assertEquals(100.00, $firstVoucher->value);
        $this->assertEquals('Joy Shop', $firstVoucher->name_on_card);
        $this->assertEquals(15, strlen($firstVoucher->pin));
        $this->assertEquals(10, strlen($firstVoucher->serial_number));

        // Wallet balance debited (100 * 5 = 500)
        $this->assertEquals(500.00, $wallet->fresh()->balance);

        // Transaction recorded
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => 500.00,
            'description' => 'Voucher generation: 5x mtn Airtime ₦100.00',
            'status' => 'success',
        ]);
    }

    public function test_voucher_generation_fails_if_insufficient_balance(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $wallet = $user->wallet()->create(['balance' => 100]); // balance is 100

        $this->actingAs($user);

        // Attempting to generate total cost 500
        $response = $this->post(route('services.print-pins.generate'), [
            'type' => 'airtime',
            'network' => 'mtn',
            'value' => '100',
            'quantity' => '5',
            'name_on_card' => 'Joy Shop',
        ]);

        $response->assertSessionHas('error', 'Insufficient wallet balance for this voucher generation.');
        $this->assertEquals(0, PrintedVoucher::count());
        $this->assertEquals(100.00, $wallet->fresh()->balance); // unchanged
    }

    public function test_voucher_printing_slips_view(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 100]);

        $v1 = PrintedVoucher::create([
            'user_id' => $user->id,
            'type' => 'airtime',
            'network' => 'mtn',
            'value' => 100,
            'pin' => '111122223333444',
            'serial_number' => '1000200030',
        ]);

        $v2 = PrintedVoucher::create([
            'user_id' => $user->id,
            'type' => 'airtime',
            'network' => 'mtn',
            'value' => 100,
            'pin' => '555566667777888',
            'serial_number' => '4000500060',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('services.print-pins.print', [
            'ids' => [$v1->id, $v2->id]
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('vouchers');
        $response->assertSee('1111 2222 3333 444');
        $response->assertSee('5555 6666 7777 888');
    }
}

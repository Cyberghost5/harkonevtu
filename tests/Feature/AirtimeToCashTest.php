<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use App\Models\AirtimeToCashRequest;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AirtimeToCashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('airtime2cash_phone', '08030000000');
        AppSetting::set('airtime2cash_tx_charge', '20'); // 20% fee
        AppSetting::set('airtime2cash_min_per_payment', '500');
        AppSetting::set('airtime2cash_max_per_payment', '20000');
        
        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');

        Storage::fake('public');
    }

    public function test_user_can_submit_airtime_to_cash_request(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 100]);

        $this->actingAs($user);

        $file = UploadedFile::fake()->image('proof.png');

        $response = $this->post(route('services.airtime-to-cash.submit'), [
            'network' => 'mtn',
            'phone' => '08031112222',
            'amount' => '1000',
            'screenshot' => $file,
        ]);

        $response->assertRedirect(route('services.airtime-to-cash'));
        $response->assertSessionHas('success', 'Your airtime conversion request has been submitted and is awaiting verification.');

        $this->assertDatabaseHas('airtime_to_cash_requests', [
            'user_id' => $user->id,
            'network' => 'mtn',
            'phone' => '08031112222',
            'amount' => 1000.00,
            'charge' => 200.00, // 20% of 1000
            'receive_amount' => 800.00, // 1000 - 200
            'status' => 'pending',
        ]);

        // Verify screenshot file was stored
        $request = AirtimeToCashRequest::first();
        Storage::disk('public')->assertExists($request->screenshot);
    }

    public function test_user_submission_fails_min_max_validation(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 100]);

        $this->actingAs($user);

        // Less than min (500)
        $response = $this->post(route('services.airtime-to-cash.submit'), [
            'network' => 'mtn',
            'phone' => '08031112222',
            'amount' => '400',
            'screenshot' => UploadedFile::fake()->image('proof.png'),
        ]);
        $response->assertSessionHasErrors(['amount']);

        // Greater than max (20000)
        $responseMax = $this->post(route('services.airtime-to-cash.submit'), [
            'network' => 'mtn',
            'phone' => '08031112222',
            'amount' => '25000',
            'screenshot' => UploadedFile::fake()->image('proof.png'),
        ]);
        $responseMax->assertSessionHasErrors(['amount']);
    }

    public function test_admin_can_approve_request_and_credit_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet()->create(['balance' => 100]);

        $admin = User::factory()->create(['is_admin' => true]);

        $request = AirtimeToCashRequest::create([
            'user_id' => $user->id,
            'network' => 'mtn',
            'phone' => '08031112222',
            'amount' => 2000.00,
            'charge' => 400.00,
            'receive_amount' => 1600.00,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.airtime-to-cash.approve', $request->id), [
            'note' => 'Approved transfer'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Airtime conversion request approved and wallet credited.');

        // Status updated to approved
        $this->assertEquals('approved', $request->fresh()->status);
        $this->assertEquals('Approved transfer', $request->fresh()->admin_note);

        // Wallet credited
        $this->assertEquals(1700.00, $wallet->fresh()->balance);

        // Transaction recorded
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 1600.00,
            'description' => 'Airtime conversion approved: mtn (₦2,000.00)',
            'status' => 'success',
        ]);
    }

    public function test_admin_can_reject_request_without_crediting(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet()->create(['balance' => 100]);

        $admin = User::factory()->create(['is_admin' => true]);

        $request = AirtimeToCashRequest::create([
            'user_id' => $user->id,
            'network' => 'mtn',
            'phone' => '08031112222',
            'amount' => 2000.00,
            'charge' => 400.00,
            'receive_amount' => 1600.00,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.airtime-to-cash.reject', $request->id), [
            'note' => 'Invalid transaction reference'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Airtime conversion request rejected.');

        // Status updated to rejected
        $this->assertEquals('rejected', $request->fresh()->status);
        $this->assertEquals('Invalid transaction reference', $request->fresh()->admin_note);

        // Wallet balance remains unchanged
        $this->assertEquals(100.00, $wallet->fresh()->balance);
    }
}

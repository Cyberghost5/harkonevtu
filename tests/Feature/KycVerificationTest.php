<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KycVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
    }

    public function test_guest_is_redirected_from_kyc_index(): void
    {
        $response = $this->get(route('kyc.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_access_kyc_index(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('kyc.index'));
        $response->assertStatus(200);
        $response->assertSee('KYC Verification');
    }

    public function test_submitting_invalid_kyc_data_fails_validation(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('kyc.submit'), [
            'id_type' => 'invalid_type',
            'id_number' => '12345', // must be 11 digits
            'firstname' => '',
            'lastname' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['id_type', 'id_number', 'firstname', 'lastname']);
    }

    public function test_simulated_kyc_verification_succeeds_if_names_match_registered_profile(): void
    {
        // Set QoreID to unconfigured to trigger sandbox fallback logic
        AppSetting::set('qoreid_client_key', '');
        AppSetting::set('qoreid_secret_key', '');

        $user = User::factory()->create([
            'name' => 'Alice Bob',
            'is_admin' => false,
            'is_active' => true,
            'kyc_status' => 'pending',
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('kyc.submit'), [
            'id_type' => 'nin',
            'id_number' => '12345678901',
            'firstname' => 'Alice',
            'lastname' => 'Smith', // Bob is one of the names, Alice matches
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);

        $user->refresh();
        $this->assertEquals('verified', $user->kyc_status);
    }

    public function test_simulated_kyc_verification_fails_if_names_mismatch_registered_profile(): void
    {
        AppSetting::set('qoreid_client_key', '');
        AppSetting::set('qoreid_secret_key', '');

        $user = User::factory()->create([
            'name' => 'Alice Bob',
            'is_admin' => false,
            'is_active' => true,
            'kyc_status' => 'pending',
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('kyc.submit'), [
            'id_type' => 'nin',
            'id_number' => '12345678901',
            'firstname' => 'John',
            'lastname' => 'Doe',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);

        $user->refresh();
        $this->assertEquals('rejected', $user->kyc_status);
    }

    public function test_live_qoreid_api_verification_flow(): void
    {
        // Configure keys
        AppSetting::set('qoreid_client_key', 'test_client_key');
        AppSetting::set('qoreid_secret_key', 'test_secret_key');
        AppSetting::set('qoreid_mode', 'sandbox');

        $user = User::factory()->create([
            'name' => 'Alice Smith',
            'is_admin' => false,
            'is_active' => true,
            'kyc_status' => 'pending',
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user);

        // Fake QoreID API endpoints
        Http::fake([
            'https://sandbox.qoreid.com/token' => Http::response([
                'accessToken' => 'mock_token_abc123'
            ], 200),
            'https://sandbox.qoreid.com/v1/ng/identities/nin' => Http::response([
                'status' => [
                    'state' => 'VERIFIED'
                ]
            ], 200),
        ]);

        $response = $this->postJson(route('kyc.submit'), [
            'id_type' => 'nin',
            'id_number' => '12345678901',
            'firstname' => 'Alice',
            'lastname' => 'Smith',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);

        $user->refresh();
        $this->assertEquals('verified', $user->kyc_status);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayscribeKycVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
    }

    public function test_admin_can_switch_kyc_provider_to_payscribe(): void
    {
        $admin = User::factory()->create([
            'is_admin'  => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.settings.api-keys.update'), [
            'kyc_provider'         => 'payscribe',
            'payscribe_secret_key' => 'ps_sec_test_123',
        ]);

        $response->assertRedirect();
        $this->assertEquals('payscribe', AppSetting::get('kyc_provider'));
    }

    public function test_payscribe_kyc_bvn_lookup_success_verifies_user(): void
    {
        AppSetting::set('kyc_provider', 'payscribe');
        AppSetting::set('payscribe_secret_key', 'ps_sec_test_123');
        config(['services.payscribe.secret_key' => 'ps_sec_test_123']);

        $user = User::factory()->create([
            'name'              => 'John Doe',
            'is_admin'          => false,
            'is_active'         => true,
            'kyc_status'        => 'pending',
            'transaction_pin'   => bcrypt('1234'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        Http::fake([
            'https://api.payscribe.ng/api/v1/kyc/lookup*' => Http::response([
                'status'      => true,
                'description' => 'Lookup successful.!',
                'message'     => [
                    'details' => [
                        'first_name'         => 'John',
                        'last_name'          => 'Doe',
                        'middle_name'        => 'Adetokunbo',
                        'gender'             => 'male',
                        'phone_number'       => '08012345678',
                        'blacklist'          => false,
                        'transaction_status' => 'success',
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('kyc.submit'), [
            'id_type'   => 'bvn',
            'id_number' => '22392226780',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'   => true,
            'provider' => 'payscribe',
        ]);

        $this->assertEquals('verified', $user->fresh()->kyc_status);

        // Verify ApiLog record was created
        $this->assertDatabaseHas('api_logs', [
            'service'  => 'kyc_verification',
            'provider' => 'payscribe',
            'success'  => 1,
        ]);
    }

    public function test_payscribe_kyc_rejects_on_name_mismatch(): void
    {
        AppSetting::set('kyc_provider', 'payscribe');
        AppSetting::set('payscribe_secret_key', 'ps_sec_test_123');
        config(['services.payscribe.secret_key' => 'ps_sec_test_123']);

        $user = User::factory()->create([
            'name'              => 'Sarah Connor',
            'is_admin'          => false,
            'is_active'         => true,
            'kyc_status'        => 'pending',
            'transaction_pin'   => bcrypt('1234'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        Http::fake([
            'https://api.payscribe.ng/api/v1/kyc/lookup*' => Http::response([
                'status'      => true,
                'description' => 'Lookup successful.!',
                'message'     => [
                    'details' => [
                        'first_name'  => 'Michael',
                        'last_name'   => 'Corleone',
                        'middle_name' => '',
                        'blacklist'   => false,
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('kyc.submit'), [
            'id_type'   => 'bvn',
            'id_number' => '22392226780',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonFragment([
            'message' => 'Identity verification mismatch. The name registered to this ID does not match your profile name.',
        ]);

        $this->assertEquals('rejected', $user->fresh()->kyc_status);
    }

    public function test_payscribe_kyc_rejects_on_blacklisted_identity(): void
    {
        AppSetting::set('kyc_provider', 'payscribe');
        AppSetting::set('payscribe_secret_key', 'ps_sec_test_123');
        config(['services.payscribe.secret_key' => 'ps_sec_test_123']);

        $user = User::factory()->create([
            'name'              => 'John Doe',
            'is_admin'          => false,
            'is_active'         => true,
            'kyc_status'        => 'pending',
            'transaction_pin'   => bcrypt('1234'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        Http::fake([
            'https://api.payscribe.ng/api/v1/kyc/lookup*' => Http::response([
                'status'      => true,
                'description' => 'Lookup successful.!',
                'message'     => [
                    'details' => [
                        'first_name' => 'John',
                        'last_name'  => 'Doe',
                        'blacklist'  => true, // FLAGGED
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('kyc.submit'), [
            'id_type'   => 'bvn',
            'id_number' => '22392226780',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $this->assertStringContainsString('blacklisted', $response->json('message'));
        $this->assertEquals('rejected', $user->fresh()->kyc_status);
    }

    public function test_payscribe_kyc_handles_invalid_bvn_api_error(): void
    {
        AppSetting::set('kyc_provider', 'payscribe');
        AppSetting::set('payscribe_secret_key', 'ps_sec_test_123');
        config(['services.payscribe.secret_key' => 'ps_sec_test_123']);

        $user = User::factory()->create([
            'name'              => 'John Doe',
            'is_admin'          => false,
            'is_active'         => true,
            'kyc_status'        => 'pending',
            'transaction_pin'   => bcrypt('1234'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        Http::fake([
            'https://api.payscribe.ng/api/v1/kyc/lookup*' => Http::response([
                'status'      => false,
                'description' => 'Invalid BVN or Record not found',
                'message'     => 'Invalid BVN',
            ], 400),
        ]);

        $response = $this->actingAs($user)->postJson(route('kyc.submit'), [
            'id_type'   => 'bvn',
            'id_number' => '00000000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $this->assertEquals('rejected', $user->fresh()->kyc_status);
    }

    public function test_api_v1_kyc_submit_works_with_payscribe(): void
    {
        AppSetting::set('kyc_provider', 'payscribe');
        AppSetting::set('payscribe_secret_key', 'ps_sec_test_123');
        config(['services.payscribe.secret_key' => 'ps_sec_test_123']);

        $user = User::factory()->create([
            'name'              => 'Jane Doe',
            'is_admin'          => false,
            'is_active'         => true,
            'kyc_status'        => 'pending',
            'phone_verified_at' => now(),
        ]);

        Http::fake([
            'https://api.payscribe.ng/api/v1/kyc/lookup*' => Http::response([
                'status'      => true,
                'description' => 'Lookup successful.!',
                'message'     => [
                    'details' => [
                        'first_name' => 'Jane',
                        'last_name'  => 'Doe',
                        'blacklist'  => false,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/kyc/submit', [
            'id_type'   => 'bvn',
            'id_number' => '22392226780',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => true,
            'data'    => [
                'kyc_status' => 'verified',
                'provider'   => 'payscribe',
            ],
        ]);
        $this->assertEquals('verified', $user->fresh()->kyc_status);
    }

    public function test_api_v1_kyc_status_returns_active_provider(): void
    {
        AppSetting::set('kyc_provider', 'payscribe');

        $user = User::factory()->create([
            'kyc_status' => 'verified',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/kyc/status');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data'   => [
                'kyc_status'      => 'verified',
                'active_provider' => 'payscribe',
            ],
        ]);
    }
}

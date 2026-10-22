<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::set('google_auth_status', '1');
        AppSetting::set('google_client_id', 'test-client-id');
        AppSetting::set('google_client_secret', 'test-client-secret');
    }

    public function test_google_redirect_url_is_generated_on_web(): void
    {
        $response = $this->get(route('auth.google.redirect'));
        $response->assertStatus(302);
        $this->assertStringContainsString('accounts.google.com', $response->getTargetUrl());
    }

    public function test_google_web_callback_creates_user_with_referral_code_and_redirects_to_profile_complete(): void
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')->andReturn('google-123456');
        $abstractUser->shouldReceive('getEmail')->andReturn('googleuser@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Google User');
        $abstractUser->shouldReceive('getNickname')->andReturn(null);
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('buildProvider')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertDatabaseHas('users', [
            'email'     => 'googleuser@example.com',
            'google_id' => 'google-123456',
            'user_type' => 'normal',
        ]);

        $user = User::where('email', 'googleuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->referral_code);
        $this->assertEquals(8, strlen($user->referral_code));
        $this->assertNotNull($user->wallet);
        $this->assertEquals(0.00, (float) $user->wallet->balance);
        $this->assertEquals(0.00, (float) $user->wallet->total_funded);
        $this->assertEquals(0.00, (float) $user->wallet->total_spent);
        $this->assertEquals('https://lh3.googleusercontent.com/avatar.jpg', $user->avatar);
        $this->assertAuthenticatedAs($user);

        // Since phone and PIN are empty, user must be redirected to profile.complete
        $response->assertRedirect(route('profile.complete'));
    }

    public function test_google_signup_attaches_referrer_from_session(): void
    {
        $referrer = User::factory()->create([
            'referral_code' => 'TOPREF88',
        ]);

        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')->andReturn('google-referral-123');
        $abstractUser->shouldReceive('getEmail')->andReturn('referredgoogle@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Referred User');
        $abstractUser->shouldReceive('getNickname')->andReturn(null);
        $abstractUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('buildProvider')->andReturn($provider);

        // Set referral code in session
        $this->withSession(['ref_code' => 'TOPREF88']);

        $response = $this->get(route('auth.google.callback'));

        $user = User::where('email', 'referredgoogle@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('TOPREF88', $user->referred_by);
        $this->assertNotNull($user->referral_code);
        $response->assertRedirect(route('profile.complete'));
    }

    public function test_google_web_callback_redirects_to_pin_setup_when_phone_set_but_pin_empty(): void
    {
        $user = User::factory()->create([
            'email'           => 'existinggoogle@example.com',
            'phone'           => '08012345678',
            'google_id'       => 'google-78910',
            'transaction_pin' => null,
        ]);

        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')->andReturn('google-78910');
        $abstractUser->shouldReceive('getEmail')->andReturn('existinggoogle@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Existing Google User');
        $abstractUser->shouldReceive('getNickname')->andReturn(null);
        $abstractUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('buildProvider')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('pin.setup'));
    }

    public function test_google_web_callback_redirects_to_dashboard_when_both_phone_and_pin_set(): void
    {
        $user = User::factory()->create([
            'email'           => 'completegoogle@example.com',
            'phone'           => '08098765432',
            'google_id'       => 'google-99900',
            'transaction_pin' => bcrypt('1234'),
        ]);

        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')->andReturn('google-99900');
        $abstractUser->shouldReceive('getEmail')->andReturn('completegoogle@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Complete Google User');
        $abstractUser->shouldReceive('getNickname')->andReturn(null);
        $abstractUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('buildProvider')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_cannot_access_dashboard_before_profile_completion(): void
    {
        $user = User::factory()->create([
            'phone'           => null,
            'transaction_pin' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertRedirect(route('profile.complete'));
    }

    public function test_user_can_submit_complete_profile_screen(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'REFBONUS']);

        $user = User::factory()->create([
            'phone'           => null,
            'transaction_pin' => null,
            'referred_by'     => null,
        ]);

        $response = $this->actingAs($user)->post(route('profile.complete.store'), [
            'phone'                 => '08034567890',
            'pin'                   => '4321',
            'pin_confirmation'      => '4321',
            'referral_code'         => 'REFBONUS',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('08034567890', $user->phone);
        $this->assertTrue($user->verifyPin('4321'));
        $this->assertEquals('REFBONUS', $user->referred_by);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_complete_profile_validates_phone_and_pin(): void
    {
        $user = User::factory()->create([
            'phone'           => null,
            'transaction_pin' => null,
        ]);

        // Invalid Nigerian phone & mismatched pin
        $response = $this->actingAs($user)->post(route('profile.complete.store'), [
            'phone'                 => '12345',
            'pin'                   => '1234',
            'pin_confirmation'      => '9999',
        ]);

        $response->assertSessionHasErrors(['phone', 'pin_confirmation']);
    }

    public function test_mobile_api_google_auth_returns_token_and_profile_completion_flags(): void
    {
        $response = $this->postJson('/api/v1/auth/google', [
            'google_id' => 'mobile-google-999',
            'email'     => 'mobilegoogle@example.com',
            'name'      => 'Mobile Google User',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => true,
            'message' => 'Google authentication successful.',
            'data'    => [
                'is_phone_set'                => false,
                'is_pin_set'                  => false,
                'requires_pin_setup'          => true,
                'requires_profile_completion' => true,
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email'     => 'mobilegoogle@example.com',
            'google_id' => 'mobile-google-999',
            'user_type' => 'normal',
        ]);

        $user = User::where('email', 'mobilegoogle@example.com')->first();
        $this->assertNotNull($user->referral_code);
    }

    public function test_mobile_api_can_complete_profile(): void
    {
        $user = User::factory()->create([
            'phone'           => null,
            'transaction_pin' => null,
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/complete-profile', [
                'phone'                 => '08087654321',
                'pin'                   => '9876',
                'pin_confirmation'      => '9876',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => true,
            'message' => 'Profile completed successfully.',
            'data'    => [
                'is_phone_set'                => true,
                'is_pin_set'                  => true,
                'requires_profile_completion' => false,
                'requires_pin_setup'          => false,
            ],
        ]);

        $user->refresh();
        $this->assertEquals('08087654321', $user->phone);
        $this->assertTrue($user->verifyPin('9876'));
    }
}

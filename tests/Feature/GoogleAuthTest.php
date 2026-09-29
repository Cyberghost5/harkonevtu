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

    public function test_google_web_callback_creates_user_and_redirects_to_pin_setup_when_pin_not_set(): void
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')->andReturn('google-123456');
        $abstractUser->shouldReceive('getEmail')->andReturn('googleuser@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Google User');
        $abstractUser->shouldReceive('getNickname')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('buildProvider')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertDatabaseHas('users', [
            'email'     => 'googleuser@example.com',
            'google_id' => 'google-123456',
        ]);

        $user = User::where('email', 'googleuser@example.com')->first();
        $this->assertNotNull($user->wallet);
        $this->assertAuthenticatedAs($user);

        // PIN is empty so user should be redirected to pin.setup
        $response->assertRedirect(route('pin.setup'));
    }

    public function test_google_web_callback_redirects_to_dashboard_when_pin_already_set(): void
    {
        $user = User::factory()->create([
            'email'           => 'existinggoogle@example.com',
            'google_id'       => 'google-78910',
            'transaction_pin' => bcrypt('1234'),
        ]);

        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')->andReturn('google-78910');
        $abstractUser->shouldReceive('getEmail')->andReturn('existinggoogle@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Existing Google User');
        $abstractUser->shouldReceive('getNickname')->andReturn(null);

        $provider = Mockery::mock('Laravel\Socialite\Two\GoogleProvider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('buildProvider')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_mobile_api_google_auth_returns_token_and_pin_requires_flag(): void
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
                'is_pin_set'         => false,
                'requires_pin_setup' => true,
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email'     => 'mobilegoogle@example.com',
            'google_id' => 'mobile-google-999',
        ]);
    }
}

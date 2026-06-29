<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::set('session_idle_timeout', '5');
    }

    public function test_guest_is_redirected_from_lockscreen(): void
    {
        $response = $this->get(route('lockscreen'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_lockscreen(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        $response = $this->get(route('lockscreen'));
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $this->assertTrue(session('session_locked'));
    }

    public function test_user_cannot_access_dashboard_while_session_locked(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        // Put user in locked state
        $this->withSession(['session_locked' => true]);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('lockscreen'));
    }

    public function test_user_can_unlock_with_correct_password(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => bcrypt('password123'),
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        $this->withSession(['session_locked' => true]);

        $response = $this->post(route('lockscreen.unlock'), [
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertFalse(session('session_locked'));
    }

    public function test_user_cannot_unlock_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => bcrypt('password123'),
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        $this->withSession(['session_locked' => true]);

        $response = $this->post(route('lockscreen.unlock'), [
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(session('session_locked'));
    }

    public function test_inactivity_timeout_locks_session(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        // Simulate activity from 10 minutes ago (idle timeout is 5 minutes)
        $this->withSession(['last_activity' => now()->subMinutes(10)]);

        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('lockscreen'));
        $this->assertTrue(session('session_locked'));
    }

    public function test_biometric_register_options(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        $response = $this->post(route('settings.biometrics.register.options'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'publicKey' => [
                'challenge',
                'rp',
                'user',
                'pubKeyCredParams'
            ]
        ]);

        $this->assertNotNull(session('webauthn_challenge'));
    }

    public function test_biometric_session_bypasses_pin(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        // Put biometric verified timestamp in session
        $this->withSession(['biometric_verified_at' => now()]);

        // Verify that any dummy PIN validates successfully due to biometric session
        $this->assertTrue($user->verifyPin('any_dummy_value'));

        // Assert session token is cleared after use to prevent replay/reuse
        $this->assertNull(session('biometric_verified_at'));
    }
}

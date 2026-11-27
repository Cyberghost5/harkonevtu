<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSessionUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_config_returns_configured_session_idle_timeout(): void
    {
        AppSetting::set('session_idle_timeout', 2);

        $response = $this->getJson('/api/v1/app-config');

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.session_idle_timeout', 2);
    }

    public function test_user_can_unlock_session_with_valid_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'phone_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/user/unlock', [
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Session unlocked successfully.');
    }

    public function test_unlock_session_fails_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'phone_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/user/unlock', [
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Incorrect password. Please try again.');
    }
}

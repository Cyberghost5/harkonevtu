<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
    }

    public function test_non_admin_cannot_impersonate_others(): void
    {
        $user1 = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);

        $user2 = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user1);

        $response = $this->post(route('admin.users.impersonate', $user2));
        $response->assertStatus(403);
        $this->assertEquals($user1->id, auth()->id());
    }

    public function test_admin_can_impersonate_user_and_stop_impersonation(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        // 1. Initiate impersonation
        $response = $this->post(route('admin.users.impersonate', $user));
        $response->assertRedirect(route('dashboard'));

        // Check that auth is now logged in as the user, and impersonator session is set
        $this->assertEquals($user->id, auth()->id());
        $this->assertEquals($admin->id, session('impersonator_id'));

        // 2. Stop impersonation
        $response = $this->post(route('users.impersonate.stop'));
        $response->assertRedirect(route('admin.users.index'));

        // Check that auth is logged back in as the admin, and session is cleared
        $this->assertEquals($admin->id, auth()->id());
        $this->assertFalse(session()->has('impersonator_id'));
    }
}

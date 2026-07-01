<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
    }

    public function test_guest_is_redirected_from_monitoring(): void
    {
        $response = $this->get(route('admin.monitoring'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_is_redirected_from_monitoring(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        $response = $this->get(route('admin.monitoring'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_monitoring(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $admin->wallet()->create(['balance' => 0]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.monitoring'));
        $response->assertStatus(200);
        $response->assertSee('Server Monitoring');
    }

    public function test_admin_can_fetch_monitoring_data(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $admin->wallet()->create(['balance' => 0]);

        $this->actingAs($admin);

        $response = $this->getJson(route('admin.monitoring.data'));
        $response->assertStatus(200);
        
        $response->assertJsonStructure([
            'cpu',
            'memory' => [
                'total',
                'used',
                'percentage',
                'swap_total',
                'swap_used',
                'swap_percentage'
            ],
            'disk_space' => [
                'total',
                'used',
                'free',
                'percentage'
            ],
            'disk_io' => [
                'read_speed',
                'write_speed'
            ],
            'network' => [
                'incoming',
                'outgoing'
            ],
            'os'
        ]);
    }
}

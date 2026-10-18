<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::set('onesignal_app_id', 'test-app-id');
        AppSetting::set('onesignal_api_key', 'test-api-key');
    }

    public function test_guest_and_non_admin_cannot_access_push_notifications_page(): void
    {
        $response = $this->get(route('admin.notifications.index'));
        $response->assertRedirect(route('login'));

        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser);

        $response = $this->get(route('admin.notifications.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_push_notifications_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->get(route('admin.notifications.index'));
        $response->assertStatus(200);
        $response->assertSee('Send Push Notification');
    }

    public function test_admin_can_send_broadcast_push_notification(): void
    {
        Http::fake([
            'api.onesignal.com/*' => Http::response(['id' => 'notif-123456'], 200),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(3)->create(['is_admin' => false]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.notifications.send'), [
            'title'           => 'Special Promo',
            'message'         => 'Get 50% discount on data bundles today!',
            'target_audience' => 'all',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('push_notifications', [
            'title'           => 'Special Promo',
            'target_audience' => 'all',
            'status'          => 'sent',
        ]);
    }

    public function test_authenticated_user_can_update_device_push_token_via_api(): void
    {
        $user = User::factory()->create([
            'phone_verified_at' => now(),
            'transaction_pin'   => bcrypt('1234'),
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/user/device-token', [
            'fcm_device_token' => 'onesignal-player-id-998877',
            'device_type'      => 'android',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => true,
            'message' => 'Push notification device token registered successfully.',
            'data'    => [
                'device_token' => 'onesignal-player-id-998877',
                'device_type'  => 'android',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'id'               => $user->id,
            'fcm_device_token' => 'onesignal-player-id-998877',
            'device_type'      => 'android',
        ]);
    }

    public function test_admin_can_resend_push_notification(): void
    {
        Http::fake([
            'api.onesignal.com/*' => Http::response(['id' => 'notif-999'], 200),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $notification = PushNotification::create([
            'admin_id'        => $admin->id,
            'title'           => 'Weekly Update',
            'message'         => 'System maintenance tonight at 12 AM.',
            'target_audience' => 'all',
            'recipient_count' => 10,
            'status'          => 'sent',
        ]);

        $response = $this->post(route('admin.notifications.resend', $notification->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('push_notifications', [
            'title'   => 'Weekly Update (Resent)',
            'status'  => 'sent',
        ]);
    }

    public function test_onesignal_uses_key_scheme_for_v2_keys(): void
    {
        AppSetting::set('onesignal_app_id', '99999999-8888-7777-6666-555555555555');
        AppSetting::set('onesignal_api_key', 'os_v2_app_abcdef1234567890abcdef');

        Http::fake([
            'https://api.onesignal.com/notifications*' => Http::response(['id' => 'notif-v2-123'], 200),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->post(route('admin.notifications.send'), [
            'title'           => 'Modern V2 Test',
            'message'         => 'Testing modern OneSignal API key format',
            'target_audience' => 'all',
        ]);

        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Key os_v2_app_abcdef1234567890abcdef');
        });
    }

    public function test_onesignal_sanitizes_prefixed_or_whitespace_keys(): void
    {
        AppSetting::set('onesignal_app_id', '99999999-8888-7777-6666-555555555555');
        // Admin mistakenly pastes "Key os_v2_app_xyz" with spaces and quotes
        AppSetting::set('onesignal_api_key', '  "Key os_v2_app_cleaned_key"  ');

        Http::fake([
            'https://api.onesignal.com/notifications*' => Http::response(['id' => 'notif-clean-123'], 200),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->post(route('admin.notifications.send'), [
            'title'           => 'Sanitization Test',
            'message'         => 'Testing key sanitization',
            'target_audience' => 'all',
        ]);

        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Key os_v2_app_cleaned_key');
        });
    }

    public function test_onesignal_rejects_matching_app_id_as_api_key(): void
    {
        $appId = '11111111-2222-3333-4444-555555555555';
        AppSetting::set('onesignal_app_id', $appId);
        AppSetting::set('onesignal_api_key', $appId); // Wrongly set to same as App ID

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->post(route('admin.notifications.send'), [
            'title'           => 'App ID as Key Test',
            'message'         => 'Testing error handling',
            'target_audience' => 'all',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('OneSignal API Key matches your App ID', session('error'));
    }
}

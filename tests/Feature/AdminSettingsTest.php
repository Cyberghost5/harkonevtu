<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use App\Models\NetworkAirtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed settings that would normally be seeded by migrations
        AppSetting::set('service_data', '1');
        AppSetting::set('service_airtime', '1');
        AppSetting::set('airtime_off_percentage_mtn', '0');
        AppSetting::set('airtime_agent_off_percentage_mtn', '0');
        AppSetting::set('email_verification', '0'); // Disable to simplify test flow
        AppSetting::set('otp_verification', '0'); // Disable to simplify test flow

        // Create or update an active NetworkAirtime record for MTN
        NetworkAirtime::updateOrCreate(
            ['network_key' => 'mtn'],
            ['name' => 'MTN', 'enabled' => true, 'vtpass_id' => 'mtn']
        );
    }

    public function test_service_status_toggles_work(): void
    {
        // Create an Admin user
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        // Create a regular user
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        // Verify initially the service is enabled (returns successful or other error than disabled)
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['code' => '000', 'content' => ['transactions' => ['transactionId' => '123']]], 200),
        ]);

        $this->actingAs($user);
        $response = $this->postJson(route('services.airtime.purchase'), [
            'network' => 'mtn',
            'phone' => '08031234567',
            'amount' => 100,
            'transaction_pin' => '1234',
        ]);
        $response->assertStatus(200);

        // Now log in as admin and disable the airtime service
        $this->actingAs($admin);
        $response = $this->post(route('admin.settings.api.update'), [
            'service_airtime' => '0',
        ]);
        $response->assertSessionHas('success');

        // Verify it is disabled in DB
        $this->assertEquals('0', AppSetting::get('service_airtime'));

        // Now regular user tries to purchase and gets 503
        $this->actingAs($user);
        $response = $this->postJson(route('services.airtime.purchase'), [
            'network' => 'mtn',
            'phone' => '08031234567',
            'amount' => 100,
            'transaction_pin' => '1234',
        ]);
        $response->assertStatus(503);
        $response->assertJsonFragment([
            'message' => 'Airtime service is temporarily unavailable.'
        ]);
    }

    public function test_airtime_discount_percentage_works(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        // Login as admin and set MTN airtime discount to 5%
        $this->actingAs($admin);
        $response = $this->post(route('admin.settings.api.update'), [
            'airtime_off_percentage_mtn' => '5',
        ]);
        $response->assertSessionHas('success');

        $this->assertEquals('5', AppSetting::get('airtime_off_percentage_mtn'));

        // Now purchase as normal user.
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(['code' => '000', 'content' => ['transactions' => ['transactionId' => '123']]], 200),
        ]);

        $this->actingAs($user);
        $response = $this->postJson(route('services.airtime.purchase'), [
            'network' => 'mtn',
            'phone' => '08031234567',
            'amount' => 100,
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);

        // Wallet balance should be: 1000 - 95 = 905
        $this->assertEquals(905.00, (float) $user->wallet->fresh()->balance);
    }

    public function test_bulksms_otp_delivery_works(): void
    {
        // Set up BulkSMS credentials
        AppSetting::set('bulksms_api_key', 'test_api_key');
        AppSetting::set('bulksms_sender', 'TestSender');

        $user = User::factory()->create([
            'phone' => '08031234567',
            'phone_verified_at' => null,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://www.bulksmsnigeria.com/api/v2/sms' => \Illuminate\Support\Facades\Http::response(['status' => 'success'], 200),
        ]);

        $this->actingAs($user);
        
        // Request OTP
        $response = $this->post(route('verification.phone.send'));
        $response->assertStatus(302); // Redirects back

        // Assert BulkSMS HTTP request was sent with correct headers and payload
        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return $request->url() === 'https://www.bulksmsnigeria.com/api/v2/sms' &&
                $request->hasHeader('Authorization', 'Bearer test_api_key') &&
                $request['from'] === 'TestSender' &&
                $request['to'] === '2348031234567' &&
                str_contains($request['body'], 'verification code is:');
        });
    }

    public function test_onesignal_notification_is_triggered_on_wallet_transaction(): void
    {
        AppSetting::set('onesignal_app_id', 'test_app_id');
        AppSetting::set('onesignal_api_key', 'test_api_key');

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $wallet = $user->wallet()->create(['balance' => 1000]);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.onesignal.com/notifications*' => \Illuminate\Support\Facades\Http::response(['id' => 'notif_id'], 200),
        ]);

        // Trigger a wallet credit
        $wallet->credit(150, 'Test Credit', 'TX_123');

        // Verify OneSignal HTTP API was called
        \Illuminate\Support\Facades\Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'https://api.onesignal.com/notifications?c=push' &&
                $request->hasHeader('Authorization', 'Key test_api_key') &&
                $request['app_id'] === 'test_app_id' &&
                $request['headings']['en'] === 'Wallet Credited' &&
                str_contains($request['contents']['en'], 'Your wallet has been credited with ₦150.00') &&
                $request['include_aliases']['external_id'] === [(string) $user->id] &&
                $request['target_channel'] === 'push';
        });
    }

    public function test_onesignal_notification_is_triggered_on_successful_service_transaction(): void
    {
        AppSetting::set('onesignal_app_id', 'test_app_id');
        AppSetting::set('onesignal_api_key', 'test_api_key');

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->wallet()->create(['balance' => 1000]);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.onesignal.com/notifications*' => \Illuminate\Support\Facades\Http::response(['id' => 'notif_id'], 200),
        ]);

        // Create a successful ServiceTransaction
        \App\Models\ServiceTransaction::create([
            'user_id' => $user->id,
            'service_type' => 'airtime',
            'provider' => 'mtn',
            'recipient' => '08031234567',
            'amount' => 100,
            'status' => 'success',
            'reference' => 'TX_MTN_123',
        ]);

        // Verify OneSignal HTTP API was called
        \Illuminate\Support\Facades\Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'https://api.onesignal.com/notifications?c=push' &&
                $request->hasHeader('Authorization', 'Key test_api_key') &&
                $request['app_id'] === 'test_app_id' &&
                $request['headings']['en'] === 'Airtime Purchase Success' &&
                str_contains($request['contents']['en'], 'Your purchase of Airtime (₦100.00) for 08031234567 was successful.') &&
                $request['include_aliases']['external_id'] === [(string) $user->id] &&
                $request['target_channel'] === 'push';
        });
    }

    public function test_admin_dashboard_renders_successfully(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Welcome Admin');
    }

    public function test_user_dashboard_shows_admin_wallet_adjustments_in_notifications(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($admin);
        $response = $this->post(route('admin.users.adjust-wallet', $user), [
            'type' => 'credit',
            'amount' => 150,
            'description' => 'Test credit adjustment',
        ]);
        $response->assertSessionHas('success');

        $this->actingAs($user);
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Test credit adjustment');
        $response->assertSee('Wallet Credit');
    }

    public function test_admin_wallet_adjustment_triggers_push_notification(): void
    {
        AppSetting::set('onesignal_app_id', 'test_app_id');
        AppSetting::set('onesignal_api_key', 'test_api_key');

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.onesignal.com/notifications*' => \Illuminate\Support\Facades\Http::response(['id' => 'notif_id'], 200),
        ]);

        $this->actingAs($admin);
        $this->post(route('admin.users.adjust-wallet', $user), [
            'type' => 'credit',
            'amount' => 150,
            'description' => 'Test credit adjustment',
        ]);

        // Verify OneSignal HTTP API was called for Wallet Credited
        \Illuminate\Support\Facades\Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'https://api.onesignal.com/notifications?c=push' &&
                $request->hasHeader('Authorization', 'Key test_api_key') &&
                $request['app_id'] === 'test_app_id' &&
                $request['headings']['en'] === 'Wallet Credited' &&
                str_contains($request['contents']['en'], 'Your wallet has been credited with ₦150.00. New balance: ₦1,150.00.') &&
                $request['include_aliases']['external_id'] === [(string) $user->id] &&
                $request['target_channel'] === 'push';
        });
    }

    public function test_service_page_redirection_when_disabled(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
            'phone_verified_at' => now(),
            'transaction_pin' => bcrypt('1234'),
        ]);
        $user->wallet()->create(['balance' => 1000]);

        $this->actingAs($user);

        // Turn all services off
        AppSetting::set('service_airtime', '0');
        AppSetting::set('service_data', '0');
        AppSetting::set('service_electricity', '0');
        AppSetting::set('service_cable', '0');
        AppSetting::set('service_epins', '0');

        // Access index routes, expect redirect to dashboard with error
        $routes = [
            'services.airtime',
            'services.data',
            'services.electricity',
            'services.cable',
            'services.epins',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('error');
        }
    }
}




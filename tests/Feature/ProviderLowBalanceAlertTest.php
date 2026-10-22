<?php

namespace Tests\Feature;

use App\Mail\ProviderLowBalanceAlertMail;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ProviderAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProviderLowBalanceAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_detects_various_insufficient_funds_messages(): void
    {
        // 1. PayScribe real screenshot message
        $payscribeMsg = 'Insufficient Funds. You need a total of 50.00 ngn to process this transaction. Your available + facility balance is 0.00';
        $this->assertTrue(ProviderAlertService::isLowBalance($payscribeMsg));

        // 2. Generic and other provider messages
        $this->assertTrue(ProviderAlertService::isLowBalance('Insufficient balance'));
        $this->assertTrue(ProviderAlertService::isLowBalance('Insufficient fund'));
        $this->assertTrue(ProviderAlertService::isLowBalance('Wallet balance is less than required amount'));
        $this->assertTrue(ProviderAlertService::isLowBalance('Low wallet balance'));
        $this->assertTrue(ProviderAlertService::isLowBalance('Partner balance insufficient'));
        $this->assertTrue(ProviderAlertService::isLowBalance(['error' => 'Not enough funds to vend']));
        $this->assertTrue(ProviderAlertService::isLowBalance(['message' => ['description' => 'Below minimum balance']]));

        // 3. Regular non-balance errors
        $this->assertFalse(ProviderAlertService::isLowBalance('Invalid phone number'));
        $this->assertFalse(ProviderAlertService::isLowBalance('Network timeout occurred'));
        $this->assertFalse(ProviderAlertService::isLowBalance('Smartcard not found'));
        $this->assertFalse(ProviderAlertService::isLowBalance(''));
        $this->assertFalse(ProviderAlertService::isLowBalance(null));
    }

    public function test_process_failure_returns_friendly_message_on_low_balance(): void
    {
        $rawError = 'Insufficient Funds. You need a total of 50.00 ngn to process this transaction. Your available + facility balance is 0.00';

        $friendlyRefunded = ProviderAlertService::processFailure($rawError, [
            'service'   => 'Airtime (MTN)',
            'provider'  => 'payscribe',
            'amount'    => 50,
            'recipient' => '08012345678',
            'reference' => 'TEST_REF_1',
        ], true);

        $this->assertEquals(
            'This service is temporarily unavailable due to a provider gateway issue. Please try again shortly. Your wallet balance has been refunded.',
            $friendlyRefunded
        );

        $friendlyUncharged = ProviderAlertService::getUserFriendlyMessage(false);
        $this->assertEquals(
            'This service is temporarily unavailable due to a provider gateway issue. Please try again shortly. Your wallet has not been charged.',
            $friendlyUncharged
        );

        // Non low-balance error should remain as is
        $normalError = ProviderAlertService::processFailure('Phone number is blacklisted', [
            'service' => 'Airtime',
        ]);
        $this->assertEquals('Phone number is blacklisted', $normalError);
    }

    public function test_notify_low_balance_dispatches_email_and_push_notification(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.onesignal.com/*' => Http::response(['id' => 'test-notif-id', 'recipients' => 1], 200),
        ]);

        AppSetting::set('onesignal_app_id', 'test-app-id');
        AppSetting::set('onesignal_api_key', 'test-api-key');
        AppSetting::set('admin_email', 'siteadmin@example.com');

        $admin = User::factory()->create([
            'email'    => 'adminuser@example.com',
            'is_admin' => true,
        ]);

        $customer = User::factory()->create([
            'name'  => 'John Doe',
            'email' => 'customer@example.com',
            'phone' => '08011112222',
        ]);
        Wallet::create(['user_id' => $customer->id, 'balance' => 4500.00]);

        ProviderAlertService::notifyLowBalance([
            'service'   => 'Airtime (MTN)',
            'provider'  => 'payscribe',
            'amount'    => 500,
            'user'      => $customer,
            'recipient' => '08099998888',
            'reference' => 'AIR_TEST_123',
            'raw_error' => 'Insufficient Funds. Your available + facility balance is 0.00',
        ]);

        // Verify Mail sent to both admin_email and admin users
        Mail::assertSent(ProviderLowBalanceAlertMail::class, function ($mail) {
            $hasSiteAdmin = $mail->hasTo('siteadmin@example.com');
            $hasAdminUser = $mail->hasTo('adminuser@example.com');

            $data = $mail->alertData;
            $matchesService = ($data['service'] === 'Airtime (MTN)');
            $matchesAmount  = ($data['amount'] == 500);
            $matchesCust    = ($data['customer_name'] === 'John Doe');
            $matchesPhone   = ($data['customer_phone'] === '08011112222');
            $matchesTarget  = ($data['recipient'] === '08099998888');

            return ($hasSiteAdmin || $hasAdminUser) && $matchesService && $matchesAmount && $matchesCust && $matchesPhone && $matchesTarget;
        });

        // Verify OneSignal request was sent
        Http::assertSent(function ($request) use ($admin) {
            if (!str_contains($request->url(), 'api.onesignal.com')) {
                return false;
            }
            $payload = $request->data();
            $targetIds = $payload['include_aliases']['external_id'] ?? [];
            return in_array((string) $admin->id, $targetIds);
        });
    }

    public function test_notify_low_balance_is_throttled_to_prevent_flooding(): void
    {
        Mail::fake();
        AppSetting::set('admin_email', 'admin@example.com');

        $details = [
            'service'   => 'Data (MTN SME)',
            'provider'  => 'payscribe',
            'amount'    => 350,
            'recipient' => '08012345678',
            'reference' => 'DAT_1',
            'raw_error' => 'Insufficient Funds',
        ];

        // First call should trigger mail
        ProviderAlertService::notifyLowBalance($details);
        Mail::assertSent(ProviderLowBalanceAlertMail::class, 1);

        // Immediate second call should be suppressed by throttle
        ProviderAlertService::notifyLowBalance($details);
        Mail::assertSent(ProviderLowBalanceAlertMail::class, 1); // Still 1!
    }

    public function test_email_template_renders_successfully(): void
    {
        $rendered = view('emails.provider-low-balance-alert', [
            'alert' => [
                'service'                 => 'Airtime (MTN)',
                'provider'                => 'payscribe',
                'amount'                  => 500,
                'recipient'               => '08012345678',
                'reference'               => 'AIR_TEST_RENDER',
                'attempted_at'            => 'Oct 22, 2026 18:00',
                'customer_name'           => 'Jane Doe',
                'customer_email'          => 'jane@example.com',
                'customer_phone'          => '08088887777',
                'customer_id'             => '42',
                'customer_wallet_balance' => 12500.50,
                'raw_error'               => 'Insufficient Funds. Your available + facility balance is 0.00',
            ],
        ])->render();

        $this->assertStringContainsString('Provider Low Balance Alert', $rendered);
        $this->assertStringContainsString('Airtime (MTN)', $rendered);
        $this->assertStringContainsString('PAYSCRIBE', $rendered);
        $this->assertStringContainsString('500.00', $rendered);
        $this->assertStringContainsString('Jane Doe', $rendered);
        $this->assertStringContainsString('12,500.50', $rendered);
        $this->assertStringContainsString('08088887777', $rendered);
        $this->assertStringContainsString('Insufficient Funds', $rendered);
    }

    public function test_end_to_end_airtime_low_balance_transforms_error_and_alerts_admins(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.onesignal.com/*' => Http::response(['id' => 'push-id', 'recipients' => 1], 200),
            'https://api.payscribe.ng/api/v1/airtime' => Http::response([
                'status'  => false,
                'message' => 'Insufficient Funds. You need a total of 50.00 ngn to process this transaction. Your available + facility balance is 0.00',
            ], 400),
        ]);

        AppSetting::set('onesignal_app_id', 'test-app-id');
        AppSetting::set('onesignal_api_key', 'test-api-key');
        AppSetting::set('admin_email', 'siteadmin@example.com');
        AppSetting::set('airtime_api', 'payscribe');
        AppSetting::set('payscribe_secret_key', 'ps_secret_123');
        AppSetting::set('service_airtime', '1');

        $admin = User::factory()->create([
            'email'    => 'admin@platform.com',
            'is_admin' => true,
        ]);

        $customer = User::factory()->create([
            'name'            => 'Sarah Connor',
            'email'           => 'sarah@example.com',
            'phone'           => '08031234567',
            'transaction_pin' => bcrypt('1234'),
        ]);
        $wallet = Wallet::create(['user_id' => $customer->id, 'balance' => 5000.00]);

        \App\Models\NetworkAirtime::updateOrCreate(
            ['network_key' => 'mtn'],
            [
                'name'         => 'MTN',
                'vtpass_id'    => 'mtn',
                'payscribe_id' => 'mtn',
                'enabled'      => true,
            ]
        );

        $response = $this->actingAs($customer)->postJson(route('services.airtime.purchase'), [
            'network'         => 'mtn',
            'phone'           => '08031234567',
            'amount'          => 100,
            'transaction_pin' => '1234',
        ]);

        // 1. Response must return friendly message and refunded flag
        $response->assertStatus(422);
        $response->assertJson([
            'success'  => false,
            'refunded' => true,
            'message'  => 'This service is temporarily unavailable due to a provider gateway issue. Please try again shortly. Your wallet balance has been refunded.',
        ]);

        // 2. Customer balance must be intact (refunded)
        $this->assertEquals(5000.00, (float) $wallet->fresh()->balance);

        // 3. Admin Mail must be dispatched with full transaction and user details
        Mail::assertSent(ProviderLowBalanceAlertMail::class, function ($mail) {
            $data = $mail->alertData;
            return $data['customer_name'] === 'Sarah Connor'
                && $data['customer_phone'] === '08031234567'
                && $data['service'] === 'Airtime (MTN)'
                && $data['provider'] === 'payscribe'
                && str_contains($data['raw_error'], 'facility balance is 0.00');
        });

        // 4. OneSignal push must be dispatched to admin
        Http::assertSent(function ($request) use ($admin) {
            if (!str_contains($request->url(), 'api.onesignal.com')) {
                return false;
            }
            $payload = $request->data();
            $targetIds = $payload['include_aliases']['external_id'] ?? [];
            return in_array((string) $admin->id, $targetIds);
        });
    }

    public function test_api_v1_data_purchase_low_balance_transforms_error_and_alerts_admins(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.onesignal.com/*' => Http::response(['id' => 'push-id', 'recipients' => 1], 200),
            'https://api.payscribe.ng/api/v1/data/vend' => Http::response([
                'status'  => false,
                'message' => 'Insufficient Funds. You need a total of 300.00 ngn to process this transaction. Your available + facility balance is 0.00',
            ], 400),
        ]);

        AppSetting::set('onesignal_app_id', 'test-app-id');
        AppSetting::set('onesignal_api_key', 'test-api-key');
        AppSetting::set('admin_email', 'siteadmin@example.com');
        AppSetting::set('data_api_mtn', 'payscribe');
        AppSetting::set('payscribe_secret_key', 'ps_secret_123');
        AppSetting::set('service_data', '1');

        $admin = User::factory()->create([
            'email'    => 'admin@platform.com',
            'is_admin' => true,
        ]);

        $customer = User::factory()->create([
            'name'            => 'John Connor',
            'email'           => 'john@example.com',
            'phone'           => '08039876543',
            'transaction_pin' => bcrypt('1234'),
        ]);
        $wallet = Wallet::create(['user_id' => $customer->id, 'balance' => 5000.00]);

        \App\Models\NetworkAirtime::updateOrCreate(
            ['network_key' => 'mtn'],
            [
                'name'         => 'MTN',
                'vtpass_id'    => 'mtn',
                'payscribe_id' => 'mtn',
                'enabled'      => true,
            ]
        );

        $plan = \App\Models\DataPlan::create([
            'network_key'  => 'mtn',
            'data_type'    => 'sme',
            'plan_name'    => 'MTN SME 1GB',
            'validity'     => '30 Days',
            'amount'       => 300.00,
            'amount_agent' => 290.00,
            'payscribe_id' => 'PS_MTN_1GB',
            'enabled'      => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')->postJson('/api/v1/data/purchase', [
            'phone'           => '08039876543',
            'plan_id'         => $plan->id,
            'transaction_pin' => '1234',
        ]);

        // 1. Response must return friendly message and refunded flag
        $response->assertStatus(422);
        $response->assertJson([
            'status'   => false,
            'refunded' => true,
            'message'  => 'This service is temporarily unavailable due to a provider gateway issue. Please try again shortly. Your wallet balance has been refunded.',
        ]);

        // 2. Customer balance must be intact (refunded)
        $this->assertEquals(5000.00, (float) $wallet->fresh()->balance);

        // 3. Admin Mail must be dispatched
        Mail::assertSent(ProviderLowBalanceAlertMail::class, function ($mail) {
            $data = $mail->alertData;
            return $data['customer_name'] === 'John Connor'
                && $data['customer_phone'] === '08039876543'
                && $data['amount'] == 300.00
                && str_contains($data['service'], 'Data (MTN - MTN SME 1GB)');
        });
    }
}

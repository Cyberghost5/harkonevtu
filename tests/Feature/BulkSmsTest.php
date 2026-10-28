<?php

namespace Tests\Feature;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\BulkSmsCampaign;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\BulkSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BulkSmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
        AppSetting::set('service_bulksms', '1');
        AppSetting::set('bulksms_api_key', 'test_bulksms_token_123');
        AppSetting::set('bulksms_amount_per_unit', '4.00');
        AppSetting::set('bulksms_agent_rate', '3.50');
        AppSetting::set('bulksms_sender', 'QuickVTU');
    }

    private function createVerifiedUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
            'user_type'         => 'user',
        ], $attributes));
    }

    public function test_recipient_parsing_and_deduplication(): void
    {
        $raw = "08031234567, +2348031234567\n07099998888, invalid123, 2348031234567; 08123456789";
        $result = BulkSmsService::parseAndSanitizeRecipients($raw);

        // 08031234567 appears 3 times in various formats -> should be deduplicated to 1
        $this->assertEquals(3, $result['total_valid']);
        $this->assertContains('2348031234567', $result['valid']);
        $this->assertContains('2347099998888', $result['valid']);
        $this->assertContains('2348123456789', $result['valid']);
        $this->assertGreaterThanOrEqual(1, $result['duplicates_removed']);
        $this->assertContains('invalid123', $result['invalid']);
    }

    public function test_gsm_pages_calculation(): void
    {
        // 160 characters standard GSM ASCII -> 1 page
        $shortText = str_repeat('A', 160);
        $gsm1 = BulkSmsService::calculateGsmPages($shortText);
        $this->assertEquals(1, $gsm1['pages']);
        $this->assertFalse($gsm1['is_unicode']);
        $this->assertEquals(0, $gsm1['remaining_chars']);

        // 161 characters standard GSM ASCII -> 2 pages (153 chars/page)
        $multiText = str_repeat('A', 161);
        $gsm2 = BulkSmsService::calculateGsmPages($multiText);
        $this->assertEquals(2, $gsm2['pages']);
        $this->assertFalse($gsm2['is_unicode']);

        // Unicode (emoji) -> 70 chars max for 1 page
        $unicodeText = "Special Promo 🎉 " . str_repeat('B', 60);
        $gsm3 = BulkSmsService::calculateGsmPages($unicodeText);
        $this->assertTrue($gsm3['is_unicode']);
        $this->assertGreaterThanOrEqual(1, $gsm3['pages']);
    }

    public function test_web_bulksms_preview(): void
    {
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 500]);

        $response = $this->actingAs($user)->postJson(route('services.bulksms.preview'), [
            'message'    => 'Hello valued customers!',
            'recipients' => "08031234567, 08039998888\n08031234567",
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('total_valid', 2);
        $response->assertJsonPath('duplicates_removed', 1);
        $response->assertJsonPath('pages', 1);
        $this->assertEquals(4.0, $response->json('unit_rate'));
        $this->assertEquals(8.0, $response->json('total_cost'));
        $response->assertJsonPath('has_sufficient_balance', true);
    }

    public function test_web_bulksms_insufficient_balance(): void
    {
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 2.00]); // Needs ₦8.00

        $response = $this->actingAs($user)->postJson(route('services.bulksms.send'), [
            'sender_id'       => 'TestSender',
            'message'         => 'Hello customer!',
            'recipients'      => '08031234567, 08039998888',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Insufficient wallet balance', $response->json('message'));
    }

    public function test_web_bulksms_incorrect_pin(): void
    {
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 500]);

        $response = $this->actingAs($user)->postJson(route('services.bulksms.send'), [
            'sender_id'       => 'TestSender',
            'message'         => 'Hello customer!',
            'recipients'      => '08031234567',
            'transaction_pin' => '9999',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('pin_error', true);
    }

    public function test_web_bulksms_successful_dispatch(): void
    {
        $user = $this->createVerifiedUser();
        $wallet = $user->wallet()->create(['balance' => 1000]);

        Http::fake([
            'https://www.bulksmsnigeria.com/api/v2/sms' => Http::response([
                'status' => 'success',
                'data'   => [
                    'status'  => 'success',
                    'message' => 'Message Sent',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('services.bulksms.send'), [
            'sender_id'       => 'MyBrand',
            'message'         => 'Exclusive promo code: DISK50',
            'recipients'      => '08031234567, 08123456789',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Dispatched HTTP call to BulkSMS Nigeria with direct-corporate gateway
        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.bulksmsnigeria.com/api/v2/sms'
                && $request['from'] === 'MyBrand'
                && $request['gateway'] === 'direct-corporate'
                && str_contains($request['to'], '2348031234567')
                && str_contains($request['to'], '2348123456789')
                && $request['body'] === 'Exclusive promo code: DISK50';
        });

        // 2 recipients * 1 page * ₦4.00 = ₦8.00 debit
        $this->assertEquals(992.00, $wallet->fresh()->balance);

        // BulkSmsCampaign created in database
        $campaign = BulkSmsCampaign::where('user_id', $user->id)->first();
        $this->assertNotNull($campaign);
        $this->assertEquals('sent', $campaign->status);
        $this->assertEquals('direct-corporate', $campaign->gateway);
        $this->assertEquals(2, $campaign->recipient_count);
        $this->assertEquals(8.00, (float) $campaign->total_amount);
        $this->assertEquals('MyBrand', $campaign->sender_id);

        // ApiLog entry created
        $this->assertDatabaseHas('api_logs', [
            'user_id'  => $user->id,
            'provider' => 'bulksms_nigeria',
            'service'  => 'bulksms',
            'success'  => true,
        ]);
    }

    public function test_web_bulksms_provider_failure_and_automated_refund(): void
    {
        $user = $this->createVerifiedUser();
        $wallet = $user->wallet()->create(['balance' => 1000]);

        Http::fake([
            'https://www.bulksmsnigeria.com/api/v2/sms' => Http::response([
                'status'  => 'error',
                'message' => 'Insufficient provider balance.',
            ], 402),
        ]);

        $response = $this->actingAs($user)->postJson(route('services.bulksms.send'), [
            'sender_id'       => 'MyBrand',
            'message'         => 'Failure test SMS',
            'recipients'      => '08031234567',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        // Wallet was debited then refunded -> balance remains 1000
        $this->assertEquals(1000.00, $wallet->fresh()->balance);

        // BulkSmsCampaign created marked as failed
        $campaign = BulkSmsCampaign::where('user_id', $user->id)->first();
        $this->assertNotNull($campaign);
        $this->assertEquals('failed', $campaign->status);

        // Refund transaction logged
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type'    => 'credit',
            'status'  => 'success',
        ]);
    }

    public function test_bulksms_auto_retry_on_bsng_2013_promotional_rejection(): void
    {
        $user = $this->createVerifiedUser();
        $wallet = $user->wallet()->create(['balance' => 1000]);

        $callCount = 0;
        Http::fake([
            'https://www.bulksmsnigeria.com/api/v2/sms' => function ($request) use (&$callCount) {
                $callCount++;
                if ($callCount === 1) {
                    // First call: promotional route rejected by provider with BSNG-2013
                    return Http::response([
                        'status' => 'error',
                        'code'   => 'BSNG-2013',
                        'error'  => [
                            'message' => "This message can't be sent on the promotional route. OTPs, verification codes and other transactional messages must use the corporate/OTP route...",
                            'code'    => 'BSNG-2013',
                        ],
                        'message' => "This message can't be sent on the promotional route. OTPs, verification codes and other transactional messages must use the corporate/OTP route...",
                    ], 200);
                }

                // Second call: automatic retry via direct-corporate succeeds
                return Http::response([
                    'status' => 'success',
                    'data'   => ['status' => 'success', 'message' => 'Message Sent'],
                ], 200);
            },
        ]);

        $response = $this->actingAs($user)->postJson(route('services.bulksms.send'), [
            'sender_id'       => 'BankAlert',
            'gateway'         => 'direct-refund',
            'message'         => 'Your OTP code is 9871',
            'recipients'      => '08031234567',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertEquals(2, $callCount);

        // Campaign persisted with direct-corporate
        $campaign = BulkSmsCampaign::where('user_id', $user->id)->first();
        $this->assertNotNull($campaign);
        $this->assertEquals('sent', $campaign->status);
        $this->assertEquals('direct-corporate', $campaign->gateway);
    }

    public function test_api_v1_bulksms_pricing_and_agent_rate(): void
    {
        // Standard user rate
        $user = $this->createVerifiedUser(['user_type' => 'user']);
        $resp = $this->actingAs($user, 'sanctum')->getJson('/api/v1/bulksms/pricing');
        $resp->assertStatus(200);
        $this->assertEquals(4.0, $resp->json('data.unit_rate'));
        $this->assertEquals('direct-corporate', $resp->json('data.default_gateway'));
        $resp->assertJsonPath('data.user_tier', 'User');

        // Agent user rate
        $agent = $this->createVerifiedUser(['user_type' => 'agent']);
        $resp2 = $this->actingAs($agent, 'sanctum')->getJson('/api/v1/bulksms/pricing');
        $resp2->assertStatus(200);
        $this->assertEquals(3.5, $resp2->json('data.unit_rate'));
        $resp2->assertJsonPath('data.user_tier', 'Agent');
    }

    public function test_api_v1_bulksms_dispatch_and_history(): void
    {
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 500]);

        Http::fake([
            'https://www.bulksmsnigeria.com/api/v2/sms' => Http::response([
                'status' => 'success',
                'data'   => ['status' => 'success'],
            ], 200),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/bulksms/send', [
            'sender_id'       => 'MobileApp',
            'message'         => 'Order #901 confirmed.',
            'recipients'      => '08031234567',
            'transaction_pin' => '1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.sender_id', 'MobileApp');
        $response->assertJsonPath('data.gateway', 'direct-corporate');
        $response->assertJsonPath('data.recipient_count', 1);

        // Fetch history
        $historyResp = $this->actingAs($user, 'sanctum')->getJson('/api/v1/bulksms/history');
        $historyResp->assertStatus(200);
        $historyResp->assertJsonPath('status', true);
        $this->assertCount(1, $historyResp->json('data.campaigns'));
        $this->assertEquals('MobileApp', $historyResp->json('data.campaigns.0.sender_id'));
    }

    public function test_service_disabled_toggle(): void
    {
        AppSetting::set('service_bulksms', '0');

        $user = $this->createVerifiedUser();
        $response = $this->actingAs($user)->get(route('services.bulksms'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');

        $apiResp = $this->actingAs($user, 'sanctum')->getJson('/api/v1/bulksms/pricing');
        $apiResp->assertStatus(503);
    }
}

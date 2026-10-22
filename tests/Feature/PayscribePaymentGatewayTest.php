<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\PayscribePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayscribePaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::set('payscribe_public_key', 'ps_pub_test_12345');
        AppSetting::set('payscribe_secret_key', 'ps_sec_test_67890');
        AppSetting::set('payscribe_status', '1');
    }

    public function test_payscribe_service_detects_credentials_and_config(): void
    {
        $this->assertTrue(PayscribePaymentService::isConfigured());
        $this->assertTrue(PayscribePaymentService::isEnabled());
        $this->assertEquals('ps_sec_test_67890', PayscribePaymentService::getSecretKey());
        $this->assertEquals('ps_pub_test_12345', PayscribePaymentService::getPublicKey());
        $this->assertEquals('https://api.payscribe.ng/api/v1', PayscribePaymentService::getBaseUrl());
    }

    public function test_admin_can_select_payscribe_as_active_gateway(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->post(route('admin.settings.api-keys.update'), [
            'active_gateway'       => 'payscribe',
            'payscribe_public_key' => 'ps_pub_live_abc',
            'payscribe_secret_key' => 'ps_sec_live_xyz',
            'payscribe_status'     => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('payscribe', AppSetting::get('active_gateway'));
        $this->assertEquals('ps_pub_live_abc', AppSetting::get('payscribe_public_key'));
        $this->assertEquals('ps_sec_live_xyz', AppSetting::get('payscribe_secret_key'));
    }

    public function test_payscribe_service_creates_checkout_session(): void
    {
        Http::fake([
            '*api.payscribe.ng/api/v1/links*' => Http::response([
                'status'      => true,
                'description' => 'Create Payment Link — 200',
                'message'     => [
                    'details' => [
                        'id'       => '3f9a8c21-test-id',
                        'slug'     => 'chk_slug_9999',
                        'full_url' => 'https://links.payscribe.co/inv/chk_slug_9999',
                        'amount'   => 2500,
                        'currency' => 'NGN',
                        'status'   => 'active',
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $result = PayscribePaymentService::createCheckoutSession([
            'reference' => 'PSC_TEST_123',
            'amount'    => 2500,
            'name'      => 'Test User',
            'email'     => 'test@example.com',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('chk_slug_9999', $result['token']);
        $this->assertEquals('https://links.payscribe.co/pay/chk_slug_9999', $result['checkout_url']);
    }

    public function test_payscribe_service_verifies_transaction(): void
    {
        Http::fake([
            '*api.payscribe.ng/api/v1/requery*' => Http::response([
                'status'      => true,
                'description' => 'Transaction success',
                'message'     => [
                    'details' => [
                        'trans_id' => 'tx-uuid-123',
                        'ref'      => 'PSC_TEST_123',
                        'status'   => 'success',
                        'amount'   => 2500,
                        'currency' => 'NGN',
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $result = PayscribePaymentService::verifyTransaction('PSC_TEST_123');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['paid']);
        $this->assertEquals(2500, $result['amount']);
        $this->assertEquals('NGN', $result['currency']);
    }

    public function test_payscribe_webhook_signature_validation(): void
    {
        $payload = json_encode(['event' => 'payment_link.paid', 'ref' => 'PSC_TEST_123']);
        $secretKey = PayscribePaymentService::getSecretKey();

        // 1. Standard HMAC-SHA256 signature
        $validSha256 = hash_hmac('sha256', $payload, $secretKey);
        $requestSha256 = Request::create('/webhook/payscribe', 'POST', [], [], [], [
            'HTTP_X_PAYSCRIBE_SIGNATURE' => $validSha256,
            'CONTENT_TYPE'               => 'application/json',
        ], $payload);
        $this->assertTrue(PayscribePaymentService::validateWebhookSignature($requestSha256));

        // 2. Timestamped versioned signature (v1=...)
        $timestamp = (string) time();
        $eventId = 'evt_test_uuid_123';
        $v1Hash = hash_hmac('sha256', "{$timestamp}.{$eventId}.{$payload}", $secretKey);
        $requestV1 = Request::create('/webhook/payscribe', 'POST', [], [], [], [
            'HTTP_X_PAYSCRIBE_SIGNATURE' => 'v1=' . $v1Hash,
            'HTTP_X_PAYSCRIBE_TIMESTAMP' => $timestamp,
            'HTTP_X_PAYSCRIBE_EVENT_ID'  => $eventId,
            'CONTENT_TYPE'               => 'application/json',
        ], $payload);
        $this->assertTrue(PayscribePaymentService::validateWebhookSignature($requestV1));

        // 3. Invalid signature
        $invalidRequest = Request::create('/webhook/payscribe', 'POST', [], [], [], [
            'HTTP_X_PAYSCRIBE_SIGNATURE' => 'invalid_signature_hash',
            'CONTENT_TYPE'               => 'application/json',
        ], $payload);
        $this->assertFalse(PayscribePaymentService::validateWebhookSignature($invalidRequest));
    }

    public function test_payment_api_controller_initiates_payscribe_gateway(): void
    {
        AppSetting::set('active_gateway', 'payscribe');

        Http::fake([
            '*api.payscribe.ng/api/v1/links*' => Http::response([
                'status'      => true,
                'description' => 'Create Payment Link — 200',
                'message'     => [
                    'details' => [
                        'id'       => '3f9a8c21-api-id',
                        'slug'     => 'chk_token_api_123',
                        'full_url' => 'https://links.payscribe.co/inv/chk_token_api_123',
                        'amount'   => 1500,
                        'currency' => 'NGN',
                        'status'   => 'active',
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ])->postJson('/api/v1/payments/initialize', [
            'amount'  => 1500,
            'gateway' => 'payscribe',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data'   => [
                'gateway' => 'payscribe',
                'amount'  => 1500,
            ],
        ]);
    }

    private function createVerifiedUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'transaction_pin'   => bcrypt('1234'),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ], $attributes));
    }

    public function test_web_funding_initiate_generates_payscribe_checkout_session(): void
    {
        AppSetting::set('active_gateway', 'payscribe');
        $user = $this->createVerifiedUser();
        $this->actingAs($user);

        Http::fake([
            '*api.payscribe.ng/api/v1/links*' => Http::response([
                'status'      => true,
                'description' => 'Create Payment Link — 200',
                'message'     => [
                    'details' => [
                        'id'       => '3f9a8c21-web-id',
                        'slug'     => 'chk_web_token_777',
                        'full_url' => 'https://links.payscribe.co/inv/chk_web_token_777',
                        'amount'   => 1000,
                        'currency' => 'NGN',
                        'status'   => 'active',
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $response = $this->postJson('/wallet/fund/gateway/initiate', [
            'amount' => 1000,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'gateway'      => 'payscribe',
            'amount'       => 1000,
            'checkout_url' => 'https://links.payscribe.co/pay/chk_web_token_777',
            'token'        => 'chk_web_token_777',
        ]);

        $ref = $response->json('reference');
        $this->assertStringStartsWith('PSC', $ref);
        $this->assertNotNull(\Illuminate\Support\Facades\Cache::get('funding_intent_' . $ref));
    }

    public function test_web_funding_verify_payscribe_credits_wallet(): void
    {
        AppSetting::set('active_gateway', 'payscribe');
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 200]);
        $this->actingAs($user);

        $ref = 'PSC_TEST_VERIFY_999';
        \Illuminate\Support\Facades\Cache::put('funding_intent_' . $ref, [
            'user_id' => $user->id,
            'amount'  => 1000,
            'charge'  => 0,
            'total'   => 1000,
            'gateway' => 'payscribe',
        ], now()->addHour());

        Http::fake([
            '*api.payscribe.ng/api/v1/requery*' => Http::response([
                'status'      => true,
                'description' => 'Transaction success',
                'message'     => [
                    'details' => [
                        'trans_id' => 'tx-uuid-verify',
                        'ref'      => $ref,
                        'status'   => 'success',
                        'amount'   => 1000,
                        'currency' => 'NGN',
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $response = $this->postJson('/wallet/fund/gateway/verify/payscribe', [
            'reference' => $ref,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(1200, (float) $user->wallet()->value('balance'));
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id'   => $user->id,
            'reference' => $ref,
            'amount'    => 1000,
            'status'    => 'success',
        ]);
    }

    public function test_web_funding_verify_payscribe_rejects_underpaid_transaction(): void
    {
        AppSetting::set('active_gateway', 'payscribe');
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 200]);
        $this->actingAs($user);

        $ref = 'PSC_TEST_UNDERPAID_111';
        \Illuminate\Support\Facades\Cache::put('funding_intent_' . $ref, [
            'user_id' => $user->id,
            'amount'  => 5000,
            'charge'  => 0,
            'total'   => 5000,
            'gateway' => 'payscribe',
        ], now()->addHour());

        Http::fake([
            '*api.payscribe.ng/api/v1/requery*' => Http::response([
                'status'      => true,
                'description' => 'Transaction success',
                'message'     => [
                    'details' => [
                        'trans_id' => 'tx-uuid-underpaid',
                        'ref'      => $ref,
                        'status'   => 'success',
                        'amount'   => 1000, // Less than 5000
                        'currency' => 'NGN',
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $response = $this->postJson('/wallet/fund/gateway/verify/payscribe', [
            'reference' => $ref,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertEquals(200, (float) $user->wallet()->value('balance'));
    }

    public function test_web_funding_verify_payscribe_handles_redirect_callback(): void
    {
        AppSetting::set('active_gateway', 'payscribe');
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 0]);
        $this->actingAs($user);

        $ref = 'PSC_TEST_CALLBACK_555';
        \Illuminate\Support\Facades\Cache::put('funding_intent_' . $ref, [
            'user_id' => $user->id,
            'amount'  => 2000,
            'charge'  => 0,
            'total'   => 2000,
            'gateway' => 'payscribe',
        ], now()->addHour());

        Http::fake([
            '*api.payscribe.ng/api/v1/requery*' => Http::response([
                'status'      => true,
                'description' => 'Transaction success',
                'message'     => [
                    'details' => [
                        'trans_id' => 'tx-uuid-callback',
                        'ref'      => $ref,
                        'status'   => 'success',
                        'amount'   => 2000,
                        'currency' => 'NGN',
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $response = $this->get('/wallet/fund/gateway/verify/payscribe?reference=' . $ref);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');
        $this->assertEquals(2000, (float) $user->wallet()->value('balance'));
    }

    public function test_payscribe_webhook_rejects_invalid_signature(): void
    {
        $payload = json_encode([
            'event'   => 'payment_link.paid',
            'ref'     => 'PSC_TEST_FAIL_SIG',
            'amount'  => 1000,
        ]);

        $response = $this->call(
            'POST',
            '/webhook/payscribe',
            [],
            [],
            [],
            [
                'HTTP_X_PAYSCRIBE_SIGNATURE' => 'invalid_sha256_hash',
                'CONTENT_TYPE'               => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(401);
    }

    public function test_payscribe_webhook_credits_user_wallet_when_intent_exists(): void
    {
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 500]);

        $ref = 'PSC_WEBHOOK_INTENT_101';
        \Illuminate\Support\Facades\Cache::put('funding_intent_' . $ref, [
            'user_id' => $user->id,
            'amount'  => 2000,
            'charge'  => 0,
            'total'   => 2000,
            'gateway' => 'payscribe',
        ], now()->addHour());

        $payloadArray = [
            'event'          => 'payment_link.paid',
            'link_id'        => '3f9a8c21-test-id',
            'slug'           => 'chk_slug_101',
            'trans_id'       => 'tx-uuid-101',
            'ref'            => $ref,
            'amount'         => 2000,
            'currency'       => 'NGN',
            'payment_method' => 'bank_transfer',
            'customer'       => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'created_at'     => '2026-04-09T10:00:00Z',
        ];
        $payloadJson = json_encode($payloadArray);
        $signature = hash_hmac('sha256', $payloadJson, PayscribePaymentService::getSecretKey());

        $response = $this->call(
            'POST',
            '/webhook/payscribe',
            [],
            [],
            [],
            [
                'HTTP_X_PAYSCRIBE_SIGNATURE' => $signature,
                'CONTENT_TYPE'               => 'application/json',
            ],
            $payloadJson
        );

        $response->assertStatus(200);
        $this->assertEquals('ok', $response->getContent());

        $this->assertEquals(2500, (float) $user->wallet()->value('balance'));
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id'   => $user->id,
            'reference' => $ref,
            'amount'    => 2000,
            'status'    => 'success',
        ]);
    }

    public function test_payscribe_webhook_idempotency_prevents_duplicate_credits(): void
    {
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 500]);

        $ref = 'PSC_WEBHOOK_DUP_202';
        \Illuminate\Support\Facades\Cache::put('funding_intent_' . $ref, [
            'user_id' => $user->id,
            'amount'  => 1500,
            'charge'  => 0,
            'total'   => 1500,
            'gateway' => 'payscribe',
        ], now()->addHour());

        $payloadArray = [
            'event'          => 'payment_link.paid',
            'link_id'        => '3f9a8c21-test-id',
            'slug'           => 'chk_slug_202',
            'trans_id'       => 'tx-uuid-202',
            'ref'            => $ref,
            'amount'         => 1500,
            'currency'       => 'NGN',
            'payment_method' => 'bank_transfer',
            'customer'       => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ];
        $payloadJson = json_encode($payloadArray);
        $signature = hash_hmac('sha256', $payloadJson, PayscribePaymentService::getSecretKey());

        // Call 1
        $res1 = $this->call('POST', '/webhook/payscribe', [], [], [], [
            'HTTP_X_PAYSCRIBE_SIGNATURE' => $signature,
            'CONTENT_TYPE'               => 'application/json',
        ], $payloadJson);
        $res1->assertStatus(200);
        $this->assertEquals(2000, (float) $user->wallet()->value('balance'));

        // Call 2 (Duplicate)
        $res2 = $this->call('POST', '/webhook/payscribe', [], [], [], [
            'HTTP_X_PAYSCRIBE_SIGNATURE' => $signature,
            'CONTENT_TYPE'               => 'application/json',
        ], $payloadJson);
        $res2->assertStatus(200);
        // Balance remains 2000, not 3500
        $this->assertEquals(2000, (float) $user->wallet()->value('balance'));
    }

    public function test_payscribe_webhook_fallback_verification_when_intent_expired(): void
    {
        $user = $this->createVerifiedUser();
        $user->wallet()->create(['balance' => 100]);

        $ref = 'PSC_FALLBACK_303';
        // No cache intent

        Http::fake([
            '*api.payscribe.ng/api/v1/requery*' => Http::response([
                'status'      => true,
                'description' => 'Transaction success',
                'message'     => [
                    'details' => [
                        'trans_id' => 'tx-uuid-fallback-303',
                        'ref'      => $ref,
                        'status'   => 'success',
                        'amount'   => 3000,
                        'currency' => 'NGN',
                        'customer' => ['email' => $user->email],
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $payloadArray = [
            'event'   => 'payment_link.paid',
            'ref'     => $ref,
            'amount'  => 3000,
            'status'  => 'success',
        ];
        $payloadJson = json_encode($payloadArray);
        $signature = hash_hmac('sha256', $payloadJson, PayscribePaymentService::getSecretKey());

        $response = $this->call('POST', '/webhook/payscribe', [], [], [], [
            'HTTP_X_PAYSCRIBE_SIGNATURE' => $signature,
            'CONTENT_TYPE'               => 'application/json',
        ], $payloadJson);

        $response->assertStatus(200);
        $this->assertEquals(3100, (float) $user->wallet()->value('balance'));
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id'   => $user->id,
            'reference' => $ref,
            'amount'    => 3000,
            'status'    => 'success',
        ]);
    }

    public function test_web_funding_verify_payscribe_rejects_expired_or_invalid_reference(): void
    {
        $user = $this->createVerifiedUser();
        $this->actingAs($user);

        $response = $this->postJson('/wallet/fund/gateway/verify/payscribe', [
            'reference' => 'PSC_NON_EXISTENT_REF',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid or expired payment reference.',
        ]);
    }

    public function test_web_funding_gateway_view_renders_payscribe_when_active(): void
    {
        AppSetting::set('active_gateway', 'payscribe');
        $user = $this->createVerifiedUser();
        $this->actingAs($user);

        $response = $this->get(route('wallet.fund.gateway'));

        $response->assertStatus(200);
        $response->assertSee('PayScribe');
        $response->assertSee('Secured by');
    }
}

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

    public function test_payscribe_get_or_create_customer_creates_new_customer(): void
    {
        $user = $this->createVerifiedUser(['email' => 'dva_user@example.com', 'name' => 'John Doe']);

        Http::fake([
            '*api.payscribe.ng/api/v1/customers/create' => Http::response([
                'status'      => true,
                'description' => 'Customer created successfully.',
                'message'     => [
                    'details' => [
                        'customer_id' => 'cust_uuid_99999',
                        'first_name'  => 'John',
                        'last_name'   => 'Doe',
                        'email'       => 'dva_user@example.com',
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $customerId = PayscribePaymentService::getOrCreateCustomer($user);
        $this->assertEquals('cust_uuid_99999', $customerId);
    }

    public function test_payscribe_generate_virtual_accounts_returns_bank_accounts(): void
    {
        $user = $this->createVerifiedUser(['email' => 'dva_user2@example.com', 'name' => 'Alice Smith']);

        Http::fake([
            '*api.payscribe.ng/api/v1/customers/create' => Http::response([
                'status'      => true,
                'description' => 'Customer created successfully.',
                'message'     => [
                    'details' => [
                        'customer_id' => 'cust_uuid_alice',
                    ],
                ],
                'status_code' => 200,
            ], 200),
            '*api.payscribe.ng/api/v1/collections/virtual-accounts/create' => Http::response([
                'status'      => true,
                'description' => 'Virtual account created successfully.',
                'message'     => [
                    'details' => [
                        'customer' => [
                            'id'   => 'cust_uuid_alice',
                            'name' => 'Alice Smith',
                        ],
                        'account' => [
                            [
                                'id'             => 'acc_9psb_123',
                                'account_number' => '5031240100',
                                'account_name'   => 'PAYSCRIBE/ALICE SMITH',
                                'bank_name'      => '9PSB',
                                'bank_code'      => '120001',
                                'currency'       => 'NGN',
                                'account_type'   => 'static',
                            ],
                            [
                                'id'             => 'acc_palmpay_456',
                                'account_number' => '2710467183',
                                'account_name'   => 'PAYSCRIBE/ALICE SMITH',
                                'bank_name'      => 'palmpay',
                                'bank_code'      => '100033',
                                'currency'       => 'NGN',
                                'account_type'   => 'static',
                            ],
                        ],
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $result = PayscribePaymentService::generateVirtualAccounts($user, '22222222222');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['accounts']);
        $this->assertEquals('5031240100', $result['accounts'][0]['account_number']);
        $this->assertEquals('9PSB', $result['accounts'][0]['bank_name']);
        $this->assertEquals('2710467183', $result['accounts'][1]['account_number']);
    }

    public function test_virtual_account_model_provider_label(): void
    {
        $va = new \App\Models\VirtualAccount(['provider' => 'payscribe']);
        $this->assertEquals('PayScribe', $va->providerLabel());
    }

    public function test_web_funding_auto_generate_creates_payscribe_virtual_accounts(): void
    {
        // Disable other providers to isolate Payscribe
        AppSetting::set('paystack_status', '0');
        AppSetting::set('flutterwave_status', '0');
        AppSetting::set('monnify_status', '0');
        AppSetting::set('payscribe_status', '1');

        $user = $this->createVerifiedUser(['name' => 'Michael Scott', 'email' => 'michael@dunder.com']);
        $this->actingAs($user);

        Http::fake([
            '*api.payscribe.ng/api/v1/customers/create' => Http::response([
                'status'      => true,
                'description' => 'Customer created successfully.',
                'message'     => [
                    'details' => [
                        'customer_id' => 'cust_michael_uuid',
                    ],
                ],
                'status_code' => 200,
            ], 200),
            '*api.payscribe.ng/api/v1/collections/virtual-accounts/create' => Http::response([
                'status'      => true,
                'description' => 'Virtual account created successfully.',
                'message'     => [
                    'details' => [
                        'customer' => [
                            'id'   => 'cust_michael_uuid',
                            'name' => 'Michael Scott',
                        ],
                        'account' => [
                            [
                                'id'             => 'acc_9psb_michael',
                                'account_number' => '9988776655',
                                'account_name'   => 'PAYSCRIBE/MICHAEL SCOTT',
                                'bank_name'      => '9PSB',
                                'bank_code'      => '120001',
                                'currency'       => 'NGN',
                                'account_type'   => 'static',
                            ],
                        ],
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $response = $this->postJson(route('wallet.fund.auto.generate'), [
            'bvn' => '12345678901',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('virtual_accounts', [
            'user_id'        => $user->id,
            'provider'       => 'payscribe',
            'account_number' => '9988776655',
            'bank_name'      => '9PSB',
        ]);
    }

    public function test_api_dva_generate_creates_payscribe_virtual_accounts(): void
    {
        AppSetting::set('paystack_status', '0');
        AppSetting::set('flutterwave_status', '0');
        AppSetting::set('monnify_status', '0');
        AppSetting::set('payscribe_status', '1');

        $user = $this->createVerifiedUser(['name' => 'Jim Halpert', 'email' => 'jim@dunder.com']);
        $token = $user->createToken('test_dva')->plainTextToken;

        Http::fake([
            '*api.payscribe.ng/api/v1/customers/create' => Http::response([
                'status'      => true,
                'description' => 'Customer created successfully.',
                'message'     => [
                    'details' => [
                        'customer_id' => 'cust_jim_uuid',
                    ],
                ],
                'status_code' => 200,
            ], 200),
            '*api.payscribe.ng/api/v1/collections/virtual-accounts/create' => Http::response([
                'status'      => true,
                'description' => 'Virtual account created successfully.',
                'message'     => [
                    'details' => [
                        'customer' => [
                            'id'   => 'cust_jim_uuid',
                            'name' => 'Jim Halpert',
                        ],
                        'account' => [
                            [
                                'id'             => 'acc_palmpay_jim',
                                'account_number' => '8877665544',
                                'account_name'   => 'PAYSCRIBE/JIM HALPERT',
                                'bank_name'      => 'palmpay',
                                'bank_code'      => '100033',
                                'currency'       => 'NGN',
                                'account_type'   => 'static',
                            ],
                        ],
                    ],
                ],
                'status_code' => 200,
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ])->postJson('/api/v1/user/dva/generate', [
            'bvn' => '12345678901',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
        ]);

        $this->assertDatabaseHas('virtual_accounts', [
            'user_id'        => $user->id,
            'provider'       => 'payscribe',
            'account_number' => '8877665544',
            'bank_name'      => 'palmpay',
        ]);
    }

    public function test_web_funding_auto_generate_reuses_existing_payscribe_accounts(): void
    {
        AppSetting::set('paystack_status', '0');
        AppSetting::set('flutterwave_status', '0');
        AppSetting::set('monnify_status', '0');
        AppSetting::set('payscribe_status', '1');

        $user = $this->createVerifiedUser(['name' => 'Dwight Schrute', 'email' => 'dwight@dunder.com']);
        $this->actingAs($user);

        // Pre-create account in DB
        \App\Models\VirtualAccount::create([
            'user_id'        => $user->id,
            'provider'       => 'payscribe',
            'account_number' => '1122334455',
            'bank_name'      => '9PSB',
            'bank_code'      => '120001',
            'account_name'   => 'PAYSCRIBE/DWIGHT SCHRUTE',
        ]);

        Http::fake(); // No HTTP calls expected

        $response = $this->postJson(route('wallet.fund.auto.generate'), [
            'bvn' => '12345678901',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'account_number' => '1122334455',
            'provider'       => 'payscribe',
        ]);

        Http::assertNothingSent();
    }

    public function test_payscribe_webhook_credits_user_wallet_on_dva_deposit(): void
    {
        $user = $this->createVerifiedUser(['name' => 'Stanley Hudson', 'email' => 'stanley@dunder.com']);
        $user->wallet()->create(['balance' => 500]);

        $accountNumber = '5300286431';
        \App\Models\VirtualAccount::create([
            'user_id'        => $user->id,
            'provider'       => 'payscribe',
            'account_number' => $accountNumber,
            'bank_name'      => '9PSB',
            'bank_code'      => '120001',
            'account_name'   => 'PAYSCRIBE/STANLEY HUDSON',
        ]);

        $payloadArray = [
            'event'      => 'accounts.payments.status',
            'event_type' => 'accounts.payment.status',
            'trans_id'   => 'tx_psc_dva_555',
            'amount'     => 4500,
            'currency'   => 'NGN',
            'customer'   => [
                'id'     => 'cust_stanley_uuid',
                'name'   => 'PAYSCRIBE/STANLEY HUDSON',
                'number' => $accountNumber,
            ],
            'transaction' => [
                'session_id' => 'sess_nip_99881122',
                'amount'     => 4500,
                'narration'  => 'Transfer to Stanley',
            ],
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

        $this->assertEquals(5000, (float) $user->wallet()->value('balance'));
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id'   => $user->id,
            'reference' => 'tx_psc_dva_555',
            'amount'    => 4500,
            'status'    => 'success',
        ]);
    }

    public function test_payscribe_webhook_dva_idempotency_prevents_duplicate_credits(): void
    {
        $user = $this->createVerifiedUser(['name' => 'Kevin Malone', 'email' => 'kevin@dunder.com']);
        $user->wallet()->create(['balance' => 200]);

        $accountNumber = '5300998877';
        \App\Models\VirtualAccount::create([
            'user_id'        => $user->id,
            'provider'       => 'payscribe',
            'account_number' => $accountNumber,
            'bank_name'      => 'palmpay',
            'bank_code'      => '100033',
            'account_name'   => 'PAYSCRIBE/KEVIN MALONE',
        ]);

        $payloadArray = [
            'event'      => 'accounts.payments.status',
            'event_type' => 'accounts.payment.status',
            'trans_id'   => 'tx_psc_dva_kevin_777',
            'amount'     => 3000,
            'currency'   => 'NGN',
            'customer'   => [
                'number' => $accountNumber,
            ],
        ];

        $payloadJson = json_encode($payloadArray);
        $signature = hash_hmac('sha256', $payloadJson, PayscribePaymentService::getSecretKey());

        // First webhook call
        $res1 = $this->call(
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
        $res1->assertStatus(200);
        $this->assertEquals(3200, (float) $user->wallet()->value('balance'));

        // Duplicate call
        $res2 = $this->call(
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
        $res2->assertStatus(200);
        // Balance remains 3200, not 6200
        $this->assertEquals(3200, (float) $user->wallet()->value('balance'));
    }

    public function test_disabled_gateway_accounts_do_not_appear_in_web_fund_auto(): void
    {
        $user = $this->createVerifiedUser(['name' => 'Dwight Schrute', 'email' => 'dwight@dunder.com']);
        $this->actingAs($user);

        AppSetting::set('paystack_secret_key', 'pk_mock_test');
        AppSetting::set('paystack_status', '1');
        AppSetting::set('payscribe_status', '1');

        \App\Models\VirtualAccount::create([
            'user_id'        => $user->id,
            'provider'       => 'paystack',
            'account_number' => '1111222233',
            'bank_name'      => 'Wema Bank',
            'bank_code'      => 'wema-bank',
            'account_name'   => 'VTU/DWIGHT SCHRUTE',
        ]);

        \App\Models\VirtualAccount::create([
            'user_id'        => $user->id,
            'provider'       => 'payscribe',
            'account_number' => '4444555566',
            'bank_name'      => '9PSB',
            'bank_code'      => '120001',
            'account_name'   => 'PAYSCRIBE/DWIGHT SCHRUTE',
        ]);

        // When both are enabled, both appear
        $res = $this->get(route('wallet.fund.auto'));
        $res->assertStatus(200);
        $res->assertSee('1111222233');
        $res->assertSee('4444555566');

        // Disable PayScribe
        AppSetting::set('payscribe_status', '0');
        $res2 = $this->get(route('wallet.fund.auto'));
        $res2->assertStatus(200);
        $res2->assertSee('1111222233');
        $res2->assertDontSee('4444555566');

        // Disable all gateways
        AppSetting::set('paystack_status', '0');
        AppSetting::set('flutterwave_status', '0');
        AppSetting::set('monnify_status', '0');
        $res3 = $this->get(route('wallet.fund.auto'));
        $res3->assertStatus(200);
        $res3->assertDontSee('1111222233');
        $res3->assertDontSee('4444555566');
        $res3->assertSee('Automated Funding Temporarily Unavailable');

        // Re-enable PayScribe only
        AppSetting::set('payscribe_status', '1');
        $res4 = $this->get(route('wallet.fund.auto'));
        $res4->assertStatus(200);
        $res4->assertDontSee('1111222233');
        $res4->assertSee('4444555566');
    }

    public function test_how_it_works_section_disappears_when_virtual_accounts_exist(): void
    {
        $user = $this->createVerifiedUser(['name' => 'Michael Scott', 'email' => 'michael@dunder.com']);
        $this->actingAs($user);

        // Initially no virtual accounts -> "How it works" should be visible
        $resBefore = $this->get(route('wallet.fund.auto'));
        $resBefore->assertStatus(200);
        $resBefore->assertSee('How it works');

        // Create a virtual account
        \App\Models\VirtualAccount::create([
            'user_id'        => $user->id,
            'provider'       => 'payscribe',
            'bank_name'      => '9PSB',
            'bank_code'      => '120001',
            'account_number' => '5301657649',
            'account_name'   => 'Admin Dakeventures',
        ]);

        // Virtual account exists -> "How it works" should disappear
        $resAfter = $this->get(route('wallet.fund.auto'));
        $resAfter->assertStatus(200);
        $resAfter->assertDontSee('How it works');
        $resAfter->assertSee('Your Virtual Accounts');
        $resAfter->assertSee('5301657649');
    }

    public function test_disabled_gateway_accounts_do_not_appear_in_api_profile_and_dva_endpoints(): void
    {
        $user = $this->createVerifiedUser(['name' => 'Jim Halpert', 'email' => 'jim@dunder.com']);
        $token = $user->createToken('test-token')->plainTextToken;

        AppSetting::set('payscribe_status', '1');

        \App\Models\VirtualAccount::create([
            'user_id'        => $user->id,
            'provider'       => 'payscribe',
            'account_number' => '9988776655',
            'bank_name'      => 'PalmPay',
            'bank_code'      => '100033',
            'account_name'   => 'PAYSCRIBE/JIM HALPERT',
        ]);

        // Enabled: accounts returned in profile & dva endpoints
        $profRes = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/user/profile');
        $profRes->assertStatus(200);
        $this->assertCount(1, $profRes->json('data.virtual_accounts'));
        $this->assertEquals('9988776655', $profRes->json('data.virtual_accounts.0.account_number'));

        $dvaRes = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/payments/dva-accounts');
        $dvaRes->assertStatus(200);
        $this->assertEquals(1, $dvaRes->json('data.total_accounts'));
        $this->assertEquals('9988776655', $dvaRes->json('data.accounts.0.account_number'));

        // Disable PayScribe gateway
        AppSetting::set('payscribe_status', '0');

        $profRes2 = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/user/profile');
        $profRes2->assertStatus(200);
        $this->assertCount(0, $profRes2->json('data.virtual_accounts'));

        $dvaRes2 = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/payments/dva-accounts');
        $dvaRes2->assertStatus(200);
        $this->assertEquals(0, $dvaRes2->json('data.total_accounts'));
        $this->assertEmpty($dvaRes2->json('data.accounts'));
    }

    public function test_disabled_gateway_is_not_generated_when_status_is_disabled(): void
    {
        $user = $this->createVerifiedUser(['name' => 'Pam Beesly', 'email' => 'pam@dunder.com']);
        $token = $user->createToken('test-token')->plainTextToken;

        // PayScribe, Flutterwave, Monnify disabled, Paystack configured & enabled
        AppSetting::set('payscribe_status', '0');
        AppSetting::set('flutterwave_status', '0');
        AppSetting::set('monnify_status', '0');
        AppSetting::set('paystack_secret_key', 'sk_test_mock');
        AppSetting::set('paystack_status', '1');

        Http::fake([
            'https://api.paystack.co/customer' => Http::response([
                'status' => true,
                'data'   => ['customer_code' => 'CUST_pam_123'],
            ], 200),
            'https://api.paystack.co/dedicated_account' => Http::response([
                'status' => true,
                'data'   => [
                    'account_number' => '1234567890',
                    'account_name'   => 'VTU/PAM BEESLY',
                    'bank'           => ['name' => 'Wema Bank'],
                ],
            ], 200),
            // PayScribe should NOT be called at all
            'https://api.payscribe.ng/*' => Http::response([
                'status' => false,
                'message' => 'Should not be called',
            ], 500),
        ]);

        $res = $this->actingAs($user)->postJson(route('wallet.fund.auto.generate'), [
            'bvn' => '22222222222',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        // Only Paystack generated
        $this->assertDatabaseHas('virtual_accounts', [
            'user_id'  => $user->id,
            'provider' => 'paystack',
        ]);
        $this->assertDatabaseMissing('virtual_accounts', [
            'user_id'  => $user->id,
            'provider' => 'payscribe',
        ]);
    }

    public function test_all_gateways_disabled_rejects_virtual_account_generation(): void
    {
        $user = $this->createVerifiedUser(['name' => 'Stanley Hudson', 'email' => 'stanley@dunder.com']);
        $token = $user->createToken('test-token')->plainTextToken;

        AppSetting::set('payscribe_status', '0');
        AppSetting::set('paystack_status', '0');
        AppSetting::set('flutterwave_status', '0');
        AppSetting::set('monnify_status', '0');

        // Web generation
        $resWeb = $this->actingAs($user)->postJson(route('wallet.fund.auto.generate'), [
            'bvn' => '22222222222',
        ]);
        $resWeb->assertStatus(422);
        $resWeb->assertJsonPath('success', false);

        // API generation
        $resApi = $this->withHeader('Authorization', 'Bearer ' . $token)->postJson('/api/v1/user/dva/generate', [
            'bvn' => '22222222222',
        ]);
        $resApi->assertStatus(400);
        $resApi->assertJsonPath('status', false);
    }
}

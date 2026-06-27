<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppSetting;
use App\Models\VirtualAccount;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonnifyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('monnify_api_key', 'mock_monnify_api_key');
        AppSetting::set('monnify_secret_key', 'mock_monnify_secret_key');
        AppSetting::set('monnify_contract_no', 'mock_contract_code');
        AppSetting::set('monnify_mode', 'sandbox');
        
        AppSetting::set('email_verification', '0');
        AppSetting::set('otp_verification', '0');
    }

    public function test_generate_monnify_virtual_account_success(): void
    {
        $user = User::factory()->create([
            'transaction_pin' => bcrypt('1234'),
            'phone_verified_at' => now(),
        ]);
        $user->wallet()->create(['balance' => 0]);

        // Mock Monnify Login and DVA Reservation
        Http::fake([
            'https://sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => [
                    'accessToken' => 'mock_access_token',
                ]
            ], 200),
            'https://sandbox.monnify.com/api/v1/bank-transfer/reserved-accounts' => Http::response([
                'requestSuccessful' => true,
                'responseBody' => [
                    'contractCode' => 'mock_contract_code',
                    'accounts' => [
                        [
                            'bankName' => 'Wema Bank',
                            'bankCode' => '035',
                            'accountNumber' => '9876543210',
                            'accountName' => $user->name,
                        ]
                    ]
                ]
            ], 200),
        ]);

        $this->actingAs($user);
        $response = $this->postJson(route('wallet.fund.auto.generate'), [
            'bvn' => '12345678901'
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'provider' => 'monnify',
            'bank_name' => 'Wema Bank',
            'account_number' => '9876543210',
        ]);

        // Verify saved to database
        $this->assertDatabaseHas('virtual_accounts', [
            'user_id' => $user->id,
            'provider' => 'monnify',
            'bank_code' => '035',
            'account_number' => '9876543210',
        ]);
    }

    public function test_monnify_webhook_credits_user_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet()->create(['balance' => 500]);

        $va = VirtualAccount::create([
            'user_id' => $user->id,
            'provider' => 'monnify',
            'bank_name' => 'Wema Bank',
            'bank_code' => '035',
            'account_number' => '9876543210',
            'account_name' => $user->name,
        ]);

        $payload = [
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => [
                'transactionReference' => 'MON_TX_9999',
                'amountPaid' => 1200.00,
                'destinationAccountInformation' => [
                    'accountNumber' => '9876543210',
                ]
            ]
        ];

        $payloadStr = json_encode($payload);
        $signature = hash_hmac('sha512', $payloadStr, 'mock_monnify_secret_key');

        $response = $this->withHeaders([
            'monnify-signature' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson(route('webhook.monnify'), $payload);

        $response->assertStatus(200);

        // Verify balance updated
        $this->assertEquals(1700.00, $wallet->fresh()->balance);

        // Verify wallet transaction created
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'amount' => 1200.00,
            'type' => 'credit',
            'reference' => 'MON_TX_9999',
        ]);

        // Sending again should be ignored
        $responseDuplicate = $this->withHeaders([
            'monnify-signature' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson(route('webhook.monnify'), $payload);

        $responseDuplicate->assertStatus(200);
        $this->assertEquals(1700.00, $wallet->fresh()->balance); // unchanged
    }

    public function test_monnify_webhook_rejects_invalid_signature(): void
    {
        $payload = [
            'eventType' => 'SUCCESSFUL_TRANSACTION',
            'eventData' => [
                'transactionReference' => 'MON_TX_9999',
                'amountPaid' => 1200.00,
                'destinationAccountInformation' => [
                    'accountNumber' => '9876543210',
                ]
            ]
        ];

        $response = $this->withHeaders([
            'monnify-signature' => 'invalid_signature_hash',
            'Content-Type' => 'application/json',
        ])->postJson(route('webhook.monnify'), $payload);

        $response->assertStatus(401);
    }
}

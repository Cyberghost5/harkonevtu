<?php

namespace Tests\Feature;

use App\Models\ServiceTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VtpassWebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vtpass_webhook_updates_status_to_success_on_delivered(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet()->create(['balance' => 5000, 'total_spent' => 1000]);

        $walletTxn = WalletTransaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => 1000,
            'balance_before' => 6000,
            'balance_after' => 5000,
            'description' => 'MTN Data Purchase',
            'reference' => 'VTP_REQ_1001',
            'status' => 'success',
        ]);

        $serviceTxn = ServiceTransaction::create([
            'user_id' => $user->id,
            'wallet_transaction_id' => $walletTxn->id,
            'service_type' => 'data',
            'provider' => 'mtn',
            'recipient' => '08012345678',
            'amount' => 1000,
            'status' => 'pending',
            'reference' => 'VTP_REQ_1001',
            'api_reference' => 'VTP_REQ_1001',
        ]);

        $webhookPayload = [
            'code' => '000',
            'response_description' => 'TRANSACTION SUCCESSFUL',
            'requestId' => 'VTP_REQ_1001',
            'amount' => '1000.00',
            'content' => [
                'transactions' => [
                    'status' => 'delivered',
                    'product_name' => 'MTN Data',
                    'transactionId' => 'VTP_REQ_1001',
                    'requestId' => 'VTP_REQ_1001',
                    'amount' => 1000,
                    'phone' => '08012345678',
                ],
            ],
        ];

        $response = $this->postJson(route('webhook.vtpass'), $webhookPayload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('service_transactions', [
            'id' => $serviceTxn->id,
            'status' => 'success',
        ]);
    }

    public function test_vtpass_webhook_refunds_wallet_on_failed_transaction(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet()->create(['balance' => 4000, 'total_spent' => 1000]);

        $walletTxn = WalletTransaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => 1000,
            'balance_before' => 5000,
            'balance_after' => 4000,
            'description' => 'Glo Airtime Purchase',
            'reference' => 'VTP_REQ_1002',
            'status' => 'success',
        ]);

        $serviceTxn = ServiceTransaction::create([
            'user_id' => $user->id,
            'wallet_transaction_id' => $walletTxn->id,
            'service_type' => 'airtime',
            'provider' => 'glo',
            'recipient' => '08050000000',
            'amount' => 1000,
            'status' => 'pending',
            'reference' => 'VTP_REQ_1002',
            'api_reference' => 'VTP_REQ_1002',
        ]);

        $webhookPayload = [
            'code' => '016',
            'response_description' => 'TRANSACTION FAILED',
            'requestId' => 'VTP_REQ_1002',
            'content' => [
                'transactions' => [
                    'status' => 'failed',
                    'transactionId' => 'VTP_REQ_1002',
                    'requestId' => 'VTP_REQ_1002',
                ],
            ],
        ];

        $response = $this->postJson(route('webhook.vtpass'), $webhookPayload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_transactions', [
            'id' => $serviceTxn->id,
            'status' => 'refunded',
        ]);

        $this->assertEquals(5000, (float) $wallet->fresh()->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 1000,
            'reference' => 'REF_VTP_REQ_1002',
        ]);
    }
}

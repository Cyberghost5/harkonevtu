<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestWalletApiCommand extends Command
{
    protected $signature = 'test:api-wallet';
    protected $description = 'Test Milestone 5 Wallet & Payment Gateway REST API endpoints';

    public function handle()
    {
        $this->info("=================================================");
        $this->info(" TESTING MILESTONE 5 WALLET & PAYMENT GATEWAY APIs ");
        $this->info("=================================================");

        // Create a test user with funded wallet
        $testEmail = 'wallet_tester_' . time() . '@example.com';
        $user = User::create([
            'name'          => 'Wallet API Tester',
            'username'      => 'wallettester' . rand(100, 999),
            'email'         => $testEmail,
            'phone'         => '080' . rand(10000000, 99999999),
            'password'      => Hash::make('Password123'),
            'user_type'     => 'user',
            'is_admin'      => false,
            'is_active'     => true,
            'referral_code' => strtoupper(Str::random(8)),
        ]);

        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 15000.00, 'total_funded' => 20000.00, 'total_spent' => 5000.00]);
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Create sample transaction records
        $ref1 = 'PAY' . date('YmdHis') . 'TEST1';
        $ref2 = 'AIR' . date('YmdHis') . 'TEST2';

        WalletTransaction::create([
            'user_id'        => $user->id,
            'wallet_id'      => $wallet->id,
            'type'           => 'credit',
            'amount'         => 5000.00,
            'balance_before' => 10000.00,
            'balance_after'  => 15000.00,
            'description'    => 'Wallet Funding via Paystack',
            'reference'      => $ref1,
            'status'         => 'success',
            'metadata'       => ['service' => 'funding', 'source' => 'paystack'],
        ]);

        WalletTransaction::create([
            'user_id'        => $user->id,
            'wallet_id'      => $wallet->id,
            'type'           => 'debit',
            'amount'         => 1000.00,
            'balance_before' => 16000.00,
            'balance_after'  => 15000.00,
            'description'    => 'MTN Airtime - 08031234567',
            'reference'      => $ref2,
            'status'         => 'success',
            'metadata'       => ['service' => 'airtime', 'network' => 'mtn', 'phone' => '08031234567'],
        ]);

        $this->line("Created Test User ID: {$user->id} with Balance: ₦15,000.00 & 2 Sample Transactions.");

        // 1. Test GET /api/v1/wallet/balance
        $this->line("\n1. Testing GET /api/v1/wallet/balance...");
        $res = $this->callApi('GET', '/api/v1/wallet/balance', [], $token);
        $this->displayResult($res);

        // 2. Test GET /api/v1/wallet/transactions
        $this->line("\n2. Testing GET /api/v1/wallet/transactions...");
        $res = $this->callApi('GET', '/api/v1/wallet/transactions', [], $token);
        $this->displayResult($res);

        // 3. Test GET /api/v1/wallet/transactions/{reference}
        $this->line("\n3. Testing GET /api/v1/wallet/transactions/{$ref1}...");
        $res = $this->callApi('GET', "/api/v1/wallet/transactions/{$ref1}", [], $token);
        $this->displayResult($res);

        // 4. Test GET /api/v1/payments/dva-accounts
        $this->line("\n4. Testing GET /api/v1/payments/dva-accounts...");
        $res = $this->callApi('GET', '/api/v1/payments/dva-accounts', [], $token);
        $this->displayResult($res);

        // 5. Test POST /api/v1/payments/initialize (₦1,000 topup)
        $this->line("\n5. Testing POST /api/v1/payments/initialize (Amount: ₦1,000)...");
        $res = $this->callApi('POST', '/api/v1/payments/initialize', ['amount' => 1000], $token);
        $this->displayResult($res);

        // 6. Test POST /api/v1/payments/manual-request
        $this->line("\n6. Testing POST /api/v1/payments/manual-request...");
        $res = $this->callApi('POST', '/api/v1/payments/manual-request', [
            'bank_name'      => 'First Bank of Nigeria',
            'account_number' => '3012345678',
            'account_name'   => 'New Millennium Resources',
            'amount'         => 5000,
            'reference'      => 'DEPOSIT_REF_998877',
            'notes'          => 'Paid via mobile banking app',
        ], $token);
        $this->displayResult($res);

        // Cleanup test user & transactions
        WalletTransaction::where('user_id', $user->id)->delete();
        $user->tokens()->delete();
        $user->wallet()->delete();
        $user->delete();

        $this->info("\n=================================================");
        $this->info(" MILESTONE 5 API TEST SUITE COMPLETED SUCCESSFULLY ");
        $this->info("=================================================");
    }

    private function callApi(string $method, string $uri, array $data = [], string $token = '')
    {
        $request = Request::create($uri, $method, $data);
        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
            $request->headers->set('Accept', 'application/json');
        }

        if ($token && ($user = User::whereHas('tokens', fn($q) => $q->where('token', hash('sha256', explode('|', $token)[1] ?? '')))->first())) {
            auth()->setUser($user);
        }

        $response = app()->handle($request);
        return [
            'status' => $response->getStatusCode(),
            'body'   => json_decode($response->getContent(), true),
        ];
    }

    private function displayResult(array $res)
    {
        $code = $res['status'];
        $body = $res['body'];
        $statusBool = $body['status'] ?? 'N/A';
        $statusText = ($statusBool === true) ? 'TRUE (OK)' : (($statusBool === false) ? 'FALSE (ERR)' : $statusBool);

        if ($code >= 200 && $code < 300) {
            $this->info("  HTTP {$code} | API status: {$statusText} - " . ($body['message'] ?? ''));
        } else {
            $this->warn("  HTTP {$code} | API status: {$statusText} - " . ($body['message'] ?? ''));
        }

        $this->line("  Sample Response Payload: " . json_encode(array_slice((array)$body, 0, 4), JSON_PRETTY_PRINT));
    }
}

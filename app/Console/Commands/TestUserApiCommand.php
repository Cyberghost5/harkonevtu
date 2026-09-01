<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserApiCommand extends Command
{
    protected $signature = 'test:api-user';
    protected $description = 'Test Milestone 2 User Profile & Account Management API endpoints';

    public function handle()
    {
        $this->info("=================================================");
        $this->info(" TESTING MILESTONE 2 USER PROFILE & ACCOUNT APIs ");
        $this->info("=================================================");

        // Create a test user
        $testEmail = 'm2_user_' . time() . '@example.com';
        $user = User::create([
            'name'          => 'Milestone Two User',
            'username'      => 'm2user' . rand(100, 999),
            'email'         => $testEmail,
            'phone'         => '081' . rand(10000000, 99999999),
            'password'      => Hash::make('Password123'),
            'user_type'     => 'user',
            'is_admin'      => false,
            'is_active'     => true,
            'referral_code' => strtoupper(Str::random(8)),
        ]);
        Wallet::create(['user_id' => $user->id, 'balance' => 5000.00, 'total_funded' => 5000.00, 'total_spent' => 0.00]);

        $token = $user->createToken('mobile-app')->plainTextToken;
        $this->line("Created Test User ID: {$user->id} with Token.");

        // 1. Test GET /profile
        $this->line("\n1. Testing GET /api/v1/user/profile...");
        $res = $this->callApi('GET', '/api/v1/user/profile', [], $token);
        $this->displayResult($res);

        // 2. Test PUT /profile
        $this->line("\n2. Testing PUT /api/v1/user/profile...");
        $res = $this->callApi('PUT', '/api/v1/user/profile', [
            'name'  => 'Milestone Two Updated',
            'email' => $testEmail,
            'phone' => $user->phone,
        ], $token);
        $this->displayResult($res);

        // 3. Test PUT /pin (Set Transaction PIN)
        $this->line("\n3. Testing PUT /api/v1/user/pin (Set PIN)...");
        $res = $this->callApi('PUT', '/api/v1/user/pin', [
            'pin'              => '1234',
            'pin_confirmation' => '1234',
        ], $token);
        $this->displayResult($res);

        // 4. Test POST /pin/verify
        $this->line("\n4. Testing POST /api/v1/user/pin/verify...");
        $res = $this->callApi('POST', '/api/v1/user/pin/verify', [
            'pin' => '1234',
        ], $token);
        $this->displayResult($res);

        // 5. Test PUT /password
        $this->line("\n5. Testing PUT /api/v1/user/password...");
        $res = $this->callApi('PUT', '/api/v1/user/password', [
            'current_password'      => 'Password123',
            'password'              => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ], $token);
        $this->displayResult($res);

        // 6. Test PUT /bank
        $this->line("\n6. Testing PUT /api/v1/user/bank...");
        $res = $this->callApi('PUT', '/api/v1/user/bank', [
            'bank_name'           => 'Guaranty Trust Bank',
            'bank_account_number' => '0123456789',
            'bank_account_name'   => 'Milestone Two Updated',
        ], $token);
        $this->displayResult($res);

        // 7. Test POST /upgrade-agent
        $this->line("\n7. Testing POST /api/v1/user/upgrade-agent...");
        $res = $this->callApi('POST', '/api/v1/user/upgrade-agent', [], $token);
        $this->displayResult($res);

        $this->info("\n=================================================");
        $this->info(" ALL MILESTONE 2 TESTS COMPLETED OK              ");
        $this->info("=================================================");

        return 0;
    }

    private function callApi(string $method, string $uri, array $data = [], ?string $token = null): array
    {
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];
        if ($token) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $request = Request::create($uri, $method, [], [], [], $headers, json_encode($data));
        $response = app()->handle($request);

        return [
            'status_code' => $response->getStatusCode(),
            'body'        => json_decode($response->getContent(), true)
        ];
    }

    private function displayResult(array $res): void
    {
        $this->line("HTTP Status Code: " . $res['status_code']);
        $this->line("JSON Response: " . json_encode($res['body'], JSON_PRETTY_PRINT));
    }
}

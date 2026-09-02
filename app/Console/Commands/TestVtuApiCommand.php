<?php

namespace App\Console\Commands;

use App\Models\DataPlan;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestVtuApiCommand extends Command
{
    protected $signature = 'test:api-vtu';
    protected $description = 'Test Milestone 3 Airtime & Data Services REST API endpoints';

    public function handle()
    {
        $this->info("=================================================");
        $this->info(" TESTING MILESTONE 3 AIRTIME & DATA SERVICES APIs ");
        $this->info("=================================================");

        // Create a test user with funded wallet and transaction PIN "1234"
        $testEmail = 'vtu_tester_' . time() . '@example.com';
        $user = User::create([
            'name'          => 'VTU API Tester',
            'username'      => 'vtutester' . rand(100, 999),
            'email'         => $testEmail,
            'phone'         => '080' . rand(10000000, 99999999),
            'password'      => Hash::make('Password123'),
            'pin'           => Hash::make('1234'),
            'user_type'     => 'user',
            'is_admin'      => false,
            'is_active'     => true,
            'referral_code' => strtoupper(Str::random(8)),
        ]);

        Wallet::create(['user_id' => $user->id, 'balance' => 5000.00, 'total_funded' => 5000.00, 'total_spent' => 0.00]);
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->line("Created Test User ID: {$user->id} with Token & Balance: ₦5,000.00.");

        // 1. Test GET /api/v1/airtime/networks
        $this->line("\n1. Testing GET /api/v1/airtime/networks...");
        $res = $this->callApi('GET', '/api/v1/airtime/networks', [], $token);
        $this->displayResult($res);

        // 2. Test POST /api/v1/airtime/network-lookup
        $this->line("\n2. Testing POST /api/v1/airtime/network-lookup (08031234567)...");
        $res = $this->callApi('POST', '/api/v1/airtime/network-lookup', ['phone' => '08031234567'], $token);
        $this->displayResult($res);

        // 3. Test POST /api/v1/airtime/purchase (Invalid PIN)
        $this->line("\n3. Testing POST /api/v1/airtime/purchase (Invalid PIN 9999)...");
        $res = $this->callApi('POST', '/api/v1/airtime/purchase', [
            'network' => 'mtn',
            'phone'   => '08031234567',
            'amount'  => 100,
            'pin'     => '9999',
        ], $token);
        $this->displayResult($res);

        // 4. Test GET /api/v1/data/networks
        $this->line("\n4. Testing GET /api/v1/data/networks...");
        $res = $this->callApi('GET', '/api/v1/data/networks', [], $token);
        $this->displayResult($res);

        // 5. Test POST /api/v1/data/plans (MTN Data Plans)
        $this->line("\n5. Testing POST /api/v1/data/plans (Network: MTN)...");
        $res = $this->callApi('POST', '/api/v1/data/plans', ['network' => 'mtn'], $token);
        $this->displayResult($res);

        // 6. Test POST /api/v1/data/purchase (Invalid Plan ID)
        $this->line("\n6. Testing POST /api/v1/data/purchase (Non-existent Plan ID 99999)...");
        $res = $this->callApi('POST', '/api/v1/data/purchase', [
            'phone'   => '08031234567',
            'plan_id' => 99999,
            'pin'     => '1234',
        ], $token);
        $this->displayResult($res);

        // Cleanup test user
        $user->tokens()->delete();
        $user->wallet()->delete();
        $user->delete();

        $this->info("\n=================================================");
        $this->info(" MILESTONE 3 API TEST SUITE COMPLETED SUCCESSFULLY ");
        $this->info("=================================================");
    }

    private function callApi(string $method, string $uri, array $data = [], string $token = '')
    {
        $request = Request::create($uri, $method, $data);
        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
            $request->headers->set('Accept', 'application/json');
        }

        // Set authenticated user for Sanctum in request context
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

<?php

namespace App\Console\Commands;

use App\Models\CableProvider;
use App\Models\ElectricityDisco;
use App\Models\ExamPinType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestBillsApiCommand extends Command
{
    protected $signature = 'test:api-bills';
    protected $description = 'Test Milestone 4 Bills & Utilities REST API endpoints';

    public function handle()
    {
        $this->info("=================================================");
        $this->info(" TESTING MILESTONE 4 BILLS & UTILITIES APIs      ");
        $this->info("=================================================");

        // Create a test user with funded wallet and transaction PIN "1234"
        $testEmail = 'bills_tester_' . time() . '@example.com';
        $user = User::create([
            'name'          => 'Bills API Tester',
            'username'      => 'billstester' . rand(100, 999),
            'email'         => $testEmail,
            'phone'         => '080' . rand(10000000, 99999999),
            'password'      => Hash::make('Password123'),
            'pin'           => Hash::make('1234'),
            'user_type'     => 'user',
            'is_admin'      => false,
            'is_active'     => true,
            'referral_code' => strtoupper(Str::random(8)),
        ]);

        Wallet::create(['user_id' => $user->id, 'balance' => 10000.00, 'total_funded' => 10000.00, 'total_spent' => 0.00]);
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->line("Created Test User ID: {$user->id} with Token & Balance: ₦10,000.00.");

        $disco = ElectricityDisco::active()->first();
        $cableProvider = CableProvider::active()->first();
        $examType = ExamPinType::active()->first();

        // 1. Test GET /api/v1/bills/electricity/discos
        $this->line("\n1. Testing GET /api/v1/bills/electricity/discos...");
        $res = $this->callApi('GET', '/api/v1/bills/electricity/discos', [], $token);
        $this->displayResult($res);

        // 2. Test POST /api/v1/bills/electricity/validate-meter
        if ($disco) {
            $this->line("\n2. Testing POST /api/v1/bills/electricity/validate-meter (Disco: {$disco->name})...");
            $res = $this->callApi('POST', '/api/v1/bills/electricity/validate-meter', [
                'disco_id'     => $disco->id,
                'meter_type'   => 'prepaid',
                'meter_number' => '11111111111',
            ], $token);
            $this->displayResult($res);
        }

        // 3. Test GET /api/v1/bills/cable/providers
        $this->line("\n3. Testing GET /api/v1/bills/cable/providers...");
        $res = $this->callApi('GET', '/api/v1/bills/cable/providers', [], $token);
        $this->displayResult($res);

        // 4. Test POST /api/v1/bills/cable/plans
        if ($cableProvider) {
            $this->line("\n4. Testing POST /api/v1/bills/cable/plans (Provider: {$cableProvider->name})...");
            $res = $this->callApi('POST', '/api/v1/bills/cable/plans', [
                'provider_id' => $cableProvider->id,
            ], $token);
            $this->displayResult($res);
        }

        // 5. Test GET /api/v1/bills/exam-pins/types
        $this->line("\n5. Testing GET /api/v1/bills/exam-pins/types...");
        $res = $this->callApi('GET', '/api/v1/bills/exam-pins/types', [], $token);
        $this->displayResult($res);

        // 6. Test POST /api/v1/bills/exam-pins/purchase (Invalid PIN)
        if ($examType) {
            $this->line("\n6. Testing POST /api/v1/bills/exam-pins/purchase (Invalid PIN 9999)...");
            $res = $this->callApi('POST', '/api/v1/bills/exam-pins/purchase', [
                'exam_type_id' => $examType->id,
                'quantity'     => 1,
                'phone'        => '08031234567',
                'pin'          => '9999',
            ], $token);
            $this->displayResult($res);
        }

        // Cleanup test user
        $user->tokens()->delete();
        $user->wallet()->delete();
        $user->delete();

        $this->info("\n=================================================");
        $this->info(" MILESTONE 4 API TEST SUITE COMPLETED SUCCESSFULLY ");
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

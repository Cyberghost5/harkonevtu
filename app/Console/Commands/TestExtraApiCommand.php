<?php

namespace App\Console\Commands;

use App\Models\BettingPlatform;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestExtraApiCommand extends Command
{
    protected $signature = 'test:api-extra';
    protected $description = 'Test Milestone 6 Specialized Services REST API endpoints';

    public function handle()
    {
        $this->info("=================================================");
        $this->info(" TESTING MILESTONE 6 SPECIALIZED SERVICES APIs ");
        $this->info("=================================================");

        // Seed a sample betting platform if none exists
        if (BettingPlatform::count() === 0) {
            BettingPlatform::create(['name' => 'Bet9ja', 'slug' => 'bet9ja', 'code' => 'bet9ja', 'is_active' => true]);
        }

        // Create a test user with wallet
        $testEmail = 'extra_tester_' . time() . '@example.com';
        $user = User::create([
            'name'          => 'Extra API Tester',
            'username'      => 'extratester' . rand(100, 999),
            'email'         => $testEmail,
            'phone'         => '080' . rand(10000000, 99999999),
            'password'      => Hash::make('Password123'),
            'user_type'     => 'user',
            'is_admin'      => false,
            'is_active'     => true,
            'referral_code'   => strtoupper(Str::random(8)),
            'kyc_status'      => 'pending',
            'transaction_pin' => Hash::make('1234'),
        ]);

        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 20000.00, 'referral_balance' => 1500.00]);
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->line("Created Test User ID: {$user->id} with Balance: ₦20,000.00 & Referral Balance: ₦1,500.00.");

        // 0A. Test GET /api/v1/app-config
        $this->line("\n0A. Testing GET /api/v1/app-config (Mobile App Settings & Toggles)...");
        $res = $this->callApi('GET', '/api/v1/app-config');
        $this->displayResult($res);

        // 0B. Test GET /api/v1/onboarding
        $this->line("\n0B. Testing GET /api/v1/onboarding (Dynamic Mobile Onboarding Slides)...");
        $res = $this->callApi('GET', '/api/v1/onboarding');
        $this->displayResult($res);

        // 1. Test GET /api/v1/pricing
        $this->line("\n1. Testing GET /api/v1/pricing (Public Rate Table)...");
        $res = $this->callApi('GET', '/api/v1/pricing');
        $this->displayResult($res);

        // 2. Test GET /api/v1/betting/platforms
        $this->line("\n2. Testing GET /api/v1/betting/platforms...");
        $res = $this->callApi('GET', '/api/v1/betting/platforms', [], $token);
        $this->displayResult($res);

        // 3. Test POST /api/v1/betting/validate-account
        $this->line("\n3. Testing POST /api/v1/betting/validate-account...");
        $res = $this->callApi('POST', '/api/v1/betting/validate-account', ['platform' => 'bet9ja', 'customer_id' => '12345678'], $token);
        $this->displayResult($res);

        // 4. Test GET /api/v1/airtime-to-cash/settings
        $this->line("\n4. Testing GET /api/v1/airtime-to-cash/settings...");
        $res = $this->callApi('GET', '/api/v1/airtime-to-cash/settings', [], $token);
        $this->displayResult($res);

        // 5. Test POST /api/v1/vouchers/generate (2x ₦100 MTN Vouchers)
        $this->line("\n5. Testing POST /api/v1/vouchers/generate...");
        $res = $this->callApi('POST', '/api/v1/vouchers/generate', [
            'type'            => 'airtime',
            'network'         => 'mtn',
            'value'           => 100,
            'quantity'        => 2,
            'transaction_pin' => '1234',
        ], $token);
        $this->displayResult($res);

        // 6. Test GET /api/v1/referrals/summary
        $this->line("\n6. Testing GET /api/v1/referrals/summary...");
        $res = $this->callApi('GET', '/api/v1/referrals/summary', [], $token);
        $this->displayResult($res);

        // 7. Test POST /api/v1/referrals/withdraw
        $this->line("\n7. Testing POST /api/v1/referrals/withdraw...");
        $res = $this->callApi('POST', '/api/v1/referrals/withdraw', [], $token);
        $this->displayResult($res);

        // 8. Test GET /api/v1/support/contact
        $this->line("\n8. Testing GET /api/v1/support/contact...");
        $res = $this->callApi('GET', '/api/v1/support/contact', [], $token);
        $this->displayResult($res);

        // 9. Test GET /api/v1/kyc/status
        $this->line("\n9. Testing GET /api/v1/kyc/status...");
        $res = $this->callApi('GET', '/api/v1/kyc/status', [], $token);
        $this->displayResult($res);

        // Cleanup test user
        $user->tokens()->delete();
        $user->wallet()->delete();
        $user->delete();

        $this->info("\n=================================================");
        $this->info(" MILESTONE 6 API TEST SUITE COMPLETED SUCCESSFULLY ");
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

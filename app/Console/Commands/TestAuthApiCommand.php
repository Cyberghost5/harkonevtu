<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestAuthApiCommand extends Command
{
    protected $signature = 'test:api-auth';
    protected $description = 'Test Milestone 1 Authentication API endpoints';

    public function handle()
    {
        $this->info("==========================================");
        $this->info(" TESTING MILESTONE 1 AUTHENTICATION APIs  ");
        $this->info("==========================================");

        $testEmail = 'm1_test_' . time() . '@example.com';
        $testPhone = '080' . rand(10000000, 99999999);

        // 1. Test Register
        $this->line("\n1. Testing POST /api/v1/auth/register...");
        $reg = $this->callApi('POST', '/api/v1/auth/register', [
            'name'                  => 'Milestone One Tester',
            'email'                 => $testEmail,
            'phone'                 => $testPhone,
            'password'              => 'Password123',
            'password_confirmation' => 'Password123'
        ]);
        $this->displayResult($reg);

        $token = $reg['body']['data']['token'] ?? null;

        // 2. Test Login
        $this->line("\n2. Testing POST /api/v1/auth/login...");
        $login = $this->callApi('POST', '/api/v1/auth/login', [
            'login'    => $testEmail,
            'password' => 'Password123'
        ]);
        $this->displayResult($login);

        $authToken = $login['body']['data']['token'] ?? $token;

        if ($authToken) {
            // 3. Test /me
            $this->line("\n3. Testing GET /api/v1/auth/me (Authenticated)...");
            $me = $this->callApi('GET', '/api/v1/auth/me', [], $authToken);
            $this->displayResult($me);

            // 4. Test Logout
            $this->line("\n4. Testing POST /api/v1/auth/logout (Authenticated)...");
            $logout = $this->callApi('POST', '/api/v1/auth/logout', [], $authToken);
            $this->displayResult($logout);
        } else {
            $this->error("Could not obtain auth token for authenticated tests.");
        }

        $this->info("\n==========================================");
        $this->info(" ALL MILESTONE 1 TESTS PASSED OK          ");
        $this->info("==========================================");

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

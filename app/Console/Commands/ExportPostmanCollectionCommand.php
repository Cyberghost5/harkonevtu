<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use Illuminate\Console\Command;

class ExportPostmanCollectionCommand extends Command
{
    protected $signature = 'export:postman';
    protected $description = 'Generate and export Postman / Hoppscotch Collection v2.1.0 JSON file';

    public function handle()
    {
        $siteName = AppSetting::get('site_name', config('app.name', 'Harkone VTU'));
        $baseUrl = url('/api/v1');

        $collection = [
            'info' => [
                'name'        => "{$siteName} REST API Collection",
                'description' => "Official Postman & Hoppscotch REST API Collection for {$siteName} (v1). Includes all 6 Milestones, Auth, VTU Services, Bills, Payments, Betting, Vouchers, Referrals, Support, KYC, and App Config.",
                'schema'      => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                [
                    'key'   => 'base_url',
                    'value' => $baseUrl,
                    'type'  => 'string',
                ],
                [
                    'key'   => 'auth_token',
                    'value' => 'YOUR_SANCTUM_BEARER_TOKEN_HERE',
                    'type'  => 'string',
                ],
            ],
            'item' => [
                // 1. Authentication
                [
                    'name' => '1. Authentication',
                    'item' => [
                        $this->createRequest('Register New User', 'POST', '{{base_url}}/auth/register', [
                            'name'     => 'John Doe',
                            'username' => 'johndoe',
                            'email'    => 'johndoe@example.com',
                            'phone'    => '08012345678',
                            'password' => 'Password123',
                        ], false),
                        $this->createRequest('Login User', 'POST', '{{base_url}}/auth/login', [
                            'login'    => 'johndoe@example.com',
                            'password' => 'Password123',
                        ], false),
                        $this->createRequest('Verify Email / Phone OTP', 'POST', '{{base_url}}/auth/verify-otp', [
                            'email' => 'johndoe@example.com',
                            'otp'   => '123456',
                        ], false),
                        $this->createRequest('Resend Verification OTP', 'POST', '{{base_url}}/auth/resend-otp', [
                            'email' => 'johndoe@example.com',
                        ], false),
                        $this->createRequest('Forgot Password', 'POST', '{{base_url}}/auth/forgot-password', [
                            'email' => 'johndoe@example.com',
                        ], false),
                        $this->createRequest('Reset Password', 'POST', '{{base_url}}/auth/reset-password', [
                            'email'                 => 'johndoe@example.com',
                            'otp'                   => '123456',
                            'password'              => 'NewPassword123',
                            'password_confirmation' => 'NewPassword123',
                        ], false),
                        $this->createRequest('Get Current User (Me)', 'GET', '{{base_url}}/auth/me', null, true),
                        $this->createRequest('Logout User', 'POST', '{{base_url}}/auth/logout', null, true),
                    ],
                ],

                // 2. User Profile & Account Management
                [
                    'name' => '2. User Account',
                    'item' => [
                        $this->createRequest('Get User Profile', 'GET', '{{base_url}}/user/profile', null, true),
                        $this->createRequest('Update Profile Info', 'PUT', '{{base_url}}/user/profile', [
                            'name'  => 'John Smith',
                            'phone' => '08099887766',
                        ], true),
                        $this->createRequest('Change Password', 'PUT', '{{base_url}}/user/password', [
                            'current_password'      => 'Password123',
                            'password'              => 'NewPassword123',
                            'password_confirmation' => 'NewPassword123',
                        ], true),
                        $this->createRequest('Set / Update Transaction PIN', 'PUT', '{{base_url}}/user/pin', [
                            'current_pin'      => '1234',
                            'pin'              => '5678',
                            'pin_confirmation' => '5678',
                        ], true),
                        $this->createRequest('Verify Transaction PIN', 'POST', '{{base_url}}/user/pin/verify', [
                            'pin' => '1234',
                        ], true),
                        $this->createRequest('Update Settlement Bank Details', 'PUT', '{{base_url}}/user/bank', [
                            'bank_name'           => 'GTBank',
                            'bank_account_number' => '0123456789',
                            'bank_account_name'   => 'John Doe',
                        ], true),
                        $this->createRequest('Upgrade Account to Agent Tier', 'POST', '{{base_url}}/user/upgrade-agent', [
                            'transaction_pin' => '1234',
                        ], true),
                        $this->createRequest('Generate Virtual Bank Account (DVA)', 'POST', '{{base_url}}/user/dva/generate', [
                            'bvn' => '22113344556',
                        ], true),
                        $this->createRequest('Delete User Account (POST)', 'POST', '{{base_url}}/user/account/delete', [
                            'password' => 'Password123',
                        ], true),
                        $this->createRequest('Delete User Account (DELETE)', 'DELETE', '{{base_url}}/user/account', [
                            'password' => 'Password123',
                        ], true),
                    ],
                ],

                // 3. Airtime & Data Services
                [
                    'name' => '3. Airtime & Data',
                    'item' => [
                        $this->createRequest('Get Airtime Networks', 'GET', '{{base_url}}/airtime/networks', null, true),
                        $this->createRequest('Lookup Network Phone Number', 'POST', '{{base_url}}/airtime/network-lookup', [
                            'phone' => '08031234567',
                        ], true),
                        $this->createRequest('Purchase Airtime Topup', 'POST', '{{base_url}}/airtime/purchase', [
                            'network'         => 'mtn',
                            'phone'           => '08031234567',
                            'amount'          => 500,
                            'airtime_type'    => 'VTU',
                            'transaction_pin' => '1234',
                        ], true),
                        $this->createRequest('Get Airtime Transactions', 'GET', '{{base_url}}/airtime/history', null, true),
                        $this->createRequest('Get Data Plans', 'GET', '{{base_url}}/data/plans?network=mtn', null, true),
                        $this->createRequest('Purchase Data Bundle', 'POST', '{{base_url}}/data/purchase', [
                            'plan_id'         => 1,
                            'phone'           => '08031234567',
                            'transaction_pin' => '1234',
                        ], true),
                        $this->createRequest('Get Data Transactions', 'GET', '{{base_url}}/data/history', null, true),
                    ],
                ],

                // 4. Bills & Utilities
                [
                    'name' => '4. Bills & Utilities',
                    'item' => [
                        $this->createRequest('Get Electricity Discos', 'GET', '{{base_url}}/bills/electricity/discos', null, true),
                        $this->createRequest('Validate Electricity Meter Number', 'POST', '{{base_url}}/bills/electricity/validate-meter', [
                            'disco'      => 'ikeja-electric',
                            'meter_no'   => '11223344556',
                            'meter_type' => 'prepaid',
                        ], true),
                        $this->createRequest('Purchase Electricity Bill Token', 'POST', '{{base_url}}/bills/electricity/purchase', [
                            'disco'           => 'ikeja-electric',
                            'meter_no'        => '11223344556',
                            'meter_type'      => 'prepaid',
                            'amount'          => 2000,
                            'phone'           => '08031234567',
                            'transaction_pin' => '1234',
                        ], true),
                        $this->createRequest('Get Cable TV Providers', 'GET', '{{base_url}}/bills/cable/providers', null, true),
                        $this->createRequest('Get Cable TV Packages / Plans', 'GET', '{{base_url}}/bills/cable/plans?provider=dstv', null, true),
                        $this->createRequest('Validate Cable Smartcard Number', 'POST', '{{base_url}}/bills/cable/validate-smartcard', [
                            'provider'      => 'dstv',
                            'smartcard_no'  => '1234567890',
                        ], true),
                        $this->createRequest('Purchase Cable TV Subscription', 'POST', '{{base_url}}/bills/cable/purchase', [
                            'provider'        => 'dstv',
                            'plan_id'         => 1,
                            'smartcard_no'    => '1234567890',
                            'phone'           => '08031234567',
                            'transaction_pin' => '1234',
                        ], true),
                        $this->createRequest('Get Exam Pin Types', 'GET', '{{base_url}}/bills/exam-pins/types', null, true),
                        $this->createRequest('Purchase Exam Pin Token', 'POST', '{{base_url}}/bills/exam-pins/purchase', [
                            'type'            => 'waec',
                            'quantity'        => 1,
                            'transaction_pin' => '1234',
                        ], true),
                    ],
                ],

                // 5. Wallet & Payment Gateways
                [
                    'name' => '5. Wallet & Payments',
                    'item' => [
                        $this->createRequest('Get Wallet Balance', 'GET', '{{base_url}}/wallet/balance', null, true),
                        $this->createRequest('Get Wallet Transactions (Paginated)', 'GET', '{{base_url}}/wallet/transactions', null, true),
                        $this->createRequest('Get Transaction Receipt Details', 'GET', '{{base_url}}/wallet/transactions/TX-REF-12345', null, true),
                        $this->createRequest('Initialize Online Payment (Paystack/Monnify)', 'POST', '{{base_url}}/payments/initialize', [
                            'amount'   => 5000,
                            'gateway'  => 'paystack',
                            'callback' => 'https://yourdomain.com/payment/callback',
                        ], true),
                        $this->createRequest('Verify Payment Reference', 'POST', '{{base_url}}/payments/verify', [
                            'reference' => 'PAY-REF-12345',
                            'gateway'   => 'paystack',
                        ], true),
                        $this->createRequest('Get User Virtual Account Numbers (DVA)', 'GET', '{{base_url}}/payments/dva-accounts', null, true),
                        $this->createRequest('Submit Manual Bank Transfer Request', 'POST', '{{base_url}}/payments/manual-request', [
                            'amount'         => 10000,
                            'bank_name'      => 'Zenith Bank',
                            'sender_name'    => 'John Doe',
                            'account_number' => '1234567890',
                        ], true),
                        $this->createRequest('Redeem Coupon Code', 'POST', '{{base_url}}/payments/redeem-coupon', [
                            'code' => 'BONUS500',
                        ], true),
                    ],
                ],

                // 6. Specialized Services, Support & App Config
                [
                    'name' => '6. Extra Services & App Config',
                    'item' => [
                        $this->createRequest('Get Mobile App Config & Settings', 'GET', '{{base_url}}/app-config', null, false),
                        $this->createRequest('Get Mobile Onboarding Slides', 'GET', '{{base_url}}/onboarding', null, false),
                        $this->createRequest('Get Public Rates & Pricing Table', 'GET', '{{base_url}}/pricing', null, false),
                        $this->createRequest('Get Betting Platforms', 'GET', '{{base_url}}/betting/platforms', null, true),
                        $this->createRequest('Validate Betting Customer ID', 'POST', '{{base_url}}/betting/validate-account', [
                            'platform'    => 'bet9ja',
                            'customer_id' => '12345678',
                        ], true),
                        $this->createRequest('Fund Betting Wallet Account', 'POST', '{{base_url}}/betting/fund', [
                            'platform'        => 'bet9ja',
                            'customer_id'     => '12345678',
                            'amount'          => 1000,
                            'customer_name'   => 'John Doe',
                            'transaction_pin' => '1234',
                        ], true),
                        $this->createRequest('Get Airtime to Cash Settings', 'GET', '{{base_url}}/airtime-to-cash/settings', null, true),
                        $this->createRequest('Submit Airtime to Cash Request', 'POST', '{{base_url}}/airtime-to-cash/submit', [
                            'network' => 'mtn',
                            'phone'   => '08031234567',
                            'amount'  => 2000,
                        ], true),
                        $this->createRequest('Get Airtime to Cash History', 'GET', '{{base_url}}/airtime-to-cash/history', null, true),
                        $this->createRequest('Generate / Print Voucher Pins', 'POST', '{{base_url}}/vouchers/generate', [
                            'type'            => 'airtime',
                            'network'         => 'mtn',
                            'value'           => 100,
                            'quantity'        => 2,
                            'transaction_pin' => '1234',
                        ], true),
                        $this->createRequest('Get Voucher Pins History', 'GET', '{{base_url}}/vouchers/history', null, true),
                        $this->createRequest('Get Referral Summary & Link', 'GET', '{{base_url}}/referrals/summary', null, true),
                        $this->createRequest('Get Referral List', 'GET', '{{base_url}}/referrals/history', null, true),
                        $this->createRequest('Withdraw Referral Earnings to Wallet', 'POST', '{{base_url}}/referrals/withdraw', null, true),
                        $this->createRequest('Get Support Contact Details', 'GET', '{{base_url}}/support/contact', null, true),
                        $this->createRequest('Submit Support Inquiry', 'POST', '{{base_url}}/support/inquiry', [
                            'subject' => 'Issue with transaction',
                            'message' => 'Hello, my data purchase was debited but pending.',
                        ], true),
                        $this->createRequest('Get Account KYC Status', 'GET', '{{base_url}}/kyc/status', null, true),
                        $this->createRequest('Submit KYC Identity Documents', 'POST', '{{base_url}}/kyc/submit', [
                            'id_type'   => 'nin',
                            'id_number' => '12345678901',
                            'bvn'       => '22113344556',
                        ], true),
                        $this->createRequest('Register Push Notification Device Token', 'POST', '{{base_url}}/user/device-token', [
                            'device_token' => 'fcm_sample_device_token_xyz123',
                            'device_type'  => 'android',
                        ], true),
                        $this->createRequest('Remove Push Notification Device Token', 'POST', '{{base_url}}/user/device-token/remove', null, true),
                        $this->createRequest('Get User Notifications List', 'GET', '{{base_url}}/notifications', null, true),
                    ],
                ],
            ],
        ];

        $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(public_path('postman_collection.json'), $json);

        $this->info("Successfully exported Postman / Hoppscotch Collection JSON file to: " . public_path('postman_collection.json'));
    }

    private function createRequest(string $name, string $method, string $url, ?array $body = null, bool $requiresAuth = true): array
    {
        $headers = [
            [
                'key'   => 'Accept',
                'value' => 'application/json',
                'type'  => 'text',
            ],
            [
                'key'   => 'Content-Type',
                'value' => 'application/json',
                'type'  => 'text',
            ],
        ];

        if ($requiresAuth) {
            $headers[] = [
                'key'   => 'Authorization',
                'value' => 'Bearer {{auth_token}}',
                'type'  => 'text',
            ];
        }

        $urlParsed = parse_url(str_replace('{{base_url}}', 'http://localhost/api/v1', $url));
        $pathSegments = array_values(array_filter(explode('/', $urlParsed['path'] ?? '')));

        // Reconstruct host/path array for Postman v2.1.0 schema
        $postmanUrl = [
            'raw'  => $url,
            'host' => ['{{base_url}}'],
            'path' => array_slice($pathSegments, 2), // trim api/v1
        ];

        $req = [
            'name'    => $name,
            'request' => [
                'method' => $method,
                'header' => $headers,
                'url'    => $postmanUrl,
            ],
        ];

        if ($body !== null) {
            $req['request']['body'] = [
                'mode' => 'raw',
                'raw'  => json_encode($body, JSON_PRETTY_PRINT),
                'options' => [
                    'raw' => [
                        'language' => 'json',
                    ],
                ],
            ];
        }

        return $req;
    }
}

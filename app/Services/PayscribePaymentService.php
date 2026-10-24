<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayscribePaymentService
{
    public static ?string $lastError = null;

    /**
     * Get the configured Payscribe Secret Key.
     */
    public static function getSecretKey(): ?string
    {
        $key = AppSetting::get('payscribe_secret_key') ?: config('services.payscribe.secret_key');
        return $key ? trim((string) $key) : null;
    }

    /**
     * Get the configured Payscribe Public Key.
     */
    public static function getPublicKey(): ?string
    {
        $key = AppSetting::get('payscribe_public_key') ?: config('services.payscribe.public_key');
        return $key ? trim((string) $key) : null;
    }

    /**
     * Get the PayScribe API Token used for Bearer authentication in API requests.
     * PayScribe API requires the Secret Key (ps_live_... / ps_sk_...) for API transactions (Airtime, Data, Bills, Betting, KYC).
     * Automatically resolves the ps_ key whether saved in secret_key or public_key.
     */
    public static function getApiKey(): ?string
    {
        $sec = self::getSecretKey();
        $pub = self::getPublicKey();

        // 1. If secret key is provided and looks like a PayScribe secret key (ps_live, ps_sk, ps_, sk_, or >= 40 chars)
        if ($sec && (
            str_starts_with($sec, 'ps_') ||
            str_starts_with($sec, 'sk_') ||
            strlen($sec) >= 40
        )) {
            return $sec;
        }

        // 2. If the user accidentally placed the secret key (ps_...) in the public key field
        if ($pub && (
            str_starts_with($pub, 'ps_') ||
            str_starts_with($pub, 'sk_') ||
            strlen($pub) >= 40
        )) {
            return $pub;
        }

        // 3. Fallback to secret key if present, otherwise public key
        return $sec ?: $pub;
    }

    /**
     * Get the PayScribe Webhook Secret Key used for HMAC signature verification.
     * PayScribe signs webhooks using the ps_... key (Secret Key, 64-hex chars).
     * Automatically resolves the ps_ key whether saved in secret_key or public_key.
     */
    public static function getWebhookSecret(): ?string
    {
        $sec = self::getSecretKey();
        $pub = self::getPublicKey();

        // 1. If secret key starts with ps_live, ps_test, ps_sk, sk_, contains sec, or is >= 40 chars
        if ($sec && (
            str_starts_with($sec, 'ps_') ||
            str_starts_with($sec, 'sk_') ||
            strlen($sec) >= 40
        )) {
            return $sec;
        }

        // 2. If public key field was populated with the secret key
        if ($pub && (
            str_starts_with($pub, 'ps_') ||
            str_starts_with($pub, 'sk_') ||
            strlen($pub) >= 40
        )) {
            return $pub;
        }

        return $sec ?: $pub;
    }

    /**
     * Get the configured Payscribe Base URL.
     */
    public static function getBaseUrl(): string
    {
        return rtrim((string) config('services.payscribe.base_url', 'https://api.payscribe.ng/api/v1'), '/');
    }

    /**
     * Check if Payscribe payment credentials are fully configured.
     */
    public static function isConfigured(): bool
    {
        return !empty(self::getApiKey()) || (!empty(self::getSecretKey()) && !empty(self::getPublicKey()));
    }

    /**
     * Check if Payscribe gateway is enabled by admin.
     */
    public static function isEnabled(): bool
    {
        return AppSetting::get('payscribe_status', '1') === '1';
    }

    /**
     * Initialize a checkout collection session with Payscribe via Payment Links.
     *
     * @param array $payload Must include: amount, reference, email, name
     * @return array ['success' => bool, 'reference' => string, 'token' => ?string, 'checkout_url' => ?string, 'message' => ?string, 'data' => ?array]
     */
    public static function createCheckoutSession(array $payload): array
    {
        self::$lastError = null;
        $secretKey = self::getApiKey() ?: self::getSecretKey();

        if (!$secretKey) {
            self::$lastError = 'Payscribe API credentials are not configured.';
            return ['success' => false, 'message' => self::$lastError];
        }

        $endpoint = self::getBaseUrl() . '/links';
        $body = [
            // 'title'           => $payload['title'] ?? ($payload['reference'] ?? ''),
            'title'           => $payload['reference'],
            'description'     => $payload['description'] ?? ('Payment for wallet funding - ' . ($payload['name'] ?? $payload['email'] ?? '')),
            'currency'        => $payload['currency'] ?? 'NGN',
            'amount'          => (float) $payload['amount'],
            'success_message' => $payload['success_message'] ?? 'Thank you! Payment received.',
            'redirect'        => $payload['redirect_url'] ?? $payload['redirect'] ?? url('/wallet/fund/gateway/verify/payscribe'),
            'custom_link'     => $payload['custom_link'] ?? '',
        ];

        $startTime = hrtime(true);
        try {
            $response = Http::withToken($secretKey)
                ->acceptJson()
                ->timeout(20)
                ->post($endpoint, $body);

            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            $resData = $response->json() ?? [];
            $status = $resData['status'] ?? false;
            $success = $response->successful() && ($status === true || $status === 'success' || ($resData['status_code'] ?? $resData['code'] ?? null) == 200);

            ApiLog::record([
                'user_id'         => auth()->id(),
                'service'         => 'funding',
                'provider'        => 'payscribe',
                'reference'       => $payload['reference'],
                'endpoint'        => $endpoint,
                'method'          => 'POST',
                'payload'         => $body,
                'response'        => $resData ?: ['raw' => $response->body()],
                'http_status'     => $response->status(),
                'duration_ms'     => $durationMs,
                'success'         => $success ? 1 : 0,
            ]);

            if (!$success) {
                $errorMsg = $resData['description'] ?? $resData['message'] ?? $resData['error'] ?? 'Could not initialize Payscribe checkout link.';
                self::$lastError = is_array($errorMsg) ? implode(', ', $errorMsg) : (string) $errorMsg;
                return [
                    'success' => false,
                    'message' => self::$lastError,
                    'data'    => $resData,
                ];
            }

            $details = $resData['message']['details'] ?? $resData['data']['details'] ?? $resData['data'] ?? $resData['details'] ?? $resData;
            $slug = $details['slug'] ?? null;
            $rawUrl = $details['full_url'] ?? $details['url'] ?? $details['link'] ?? $details['checkout_url'] ?? null;

            // Direct customer to the payment checkout page (/pay/) instead of invoice view (/inv/)
            if (!empty($slug)) {
                $checkoutUrl = 'https://links.payscribe.co/pay/' . $slug;
            } elseif (!empty($rawUrl)) {
                $checkoutUrl = str_replace('/inv/', '/pay/', $rawUrl);
            } else {
                $checkoutUrl = null;
            }

            $token = $slug ?? $details['id'] ?? $details['token'] ?? null;

            return [
                'success'      => true,
                'reference'    => $payload['reference'],
                'token'        => $token,
                'checkout_url' => $checkoutUrl,
                'message'      => $resData['description'] ?? $resData['message'] ?? 'Checkout link created successfully.',
                'data'         => $details,
            ];
        } catch (\Exception $e) {
            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            self::$lastError = 'Payscribe connection error: ' . $e->getMessage();
            Log::error('Payscribe checkout session creation exception', ['error' => $e->getMessage()]);

            ApiLog::record([
                'user_id'         => auth()->id(),
                'service'         => 'funding',
                'provider'        => 'payscribe',
                'reference'       => $payload['reference'],
                'endpoint'        => $endpoint,
                'method'          => 'POST',
                'payload'         => $body,
                'response'        => ['error' => $e->getMessage()],
                'http_status'     => 500,
                'duration_ms'     => $durationMs,
                'success'         => 0,
            ]);

            return ['success' => false, 'message' => self::$lastError];
        }
    }

    /**
     * Verify a transaction status from Payscribe API via Requery endpoint.
     *
     * @param string $reference
     * @return array ['success' => bool, 'paid' => bool, 'amount' => float, 'currency' => string, 'reference' => string, 'message' => string, 'data' => array]
     */
    public static function verifyTransaction(string $reference): array
    {
        self::$lastError = null;
        $secretKey = self::getApiKey() ?: self::getSecretKey();

        if (!$secretKey) {
            self::$lastError = 'Payscribe API credentials are not configured.';
            return ['success' => false, 'paid' => false, 'message' => self::$lastError, 'amount' => 0.0, 'currency' => 'NGN', 'reference' => $reference, 'data' => []];
        }

        // Official Payscribe transaction requery endpoint
        $endpoint = self::getBaseUrl() . '/requery/?trans_id=' . urlencode($reference);
        $startTime = hrtime(true);

        try {
            $response = Http::withToken($secretKey)
                ->acceptJson()
                ->timeout(20)
                ->get($endpoint);

            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            $resData = $response->json() ?? [];
            $details = $resData['message']['details'] ?? $resData['data']['details'] ?? $resData['data'] ?? $resData['details'] ?? $resData;

            $statusStr = strtolower((string) ($details['status'] ?? $resData['status'] ?? ''));
            $isPaid = in_array($statusStr, ['success', 'successful', 'paid', 'completed'], true);
            $success = $response->successful() && $isPaid;

            $amount = (float) ($details['amount'] ?? $details['paid_amount'] ?? $details['unit'] ?? 0);
            $currency = strtoupper((string) ($details['currency'] ?? 'NGN'));

            ApiLog::record([
                'user_id'         => auth()->id(),
                'service'         => 'funding',
                'provider'        => 'payscribe',
                'reference'       => $reference,
                'endpoint'        => $endpoint,
                'method'          => 'GET',
                'payload'         => ['trans_id' => $reference],
                'response'        => $resData ?: ['raw' => $response->body()],
                'http_status'     => $response->status(),
                'duration_ms'     => $durationMs,
                'success'         => $success ? 1 : 0,
            ]);

            if (!$response->successful()) {
                $errorMsg = $resData['description'] ?? $resData['message'] ?? 'Could not verify transaction with Payscribe.';
                self::$lastError = is_array($errorMsg) ? implode(', ', $errorMsg) : (string) $errorMsg;
                return [
                    'success'   => false,
                    'paid'      => false,
                    'amount'    => $amount,
                    'currency'  => $currency,
                    'reference' => $reference,
                    'message'   => self::$lastError,
                    'data'      => $resData,
                ];
            }

            return [
                'success'   => $success,
                'paid'      => $isPaid,
                'amount'    => $amount,
                'currency'  => $currency,
                'reference' => $reference,
                'message'   => $resData['description'] ?? $resData['message'] ?? ($isPaid ? 'Payment verified successfully.' : 'Payment is still pending.'),
                'data'      => $details,
            ];
        } catch (\Exception $e) {
            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            self::$lastError = 'Payscribe verification exception: ' . $e->getMessage();
            Log::error('Payscribe verification exception', ['reference' => $reference, 'error' => $e->getMessage()]);

            ApiLog::record([
                'user_id'         => auth()->id(),
                'service'         => 'funding',
                'provider'        => 'payscribe',
                'reference'       => $reference,
                'endpoint'        => $endpoint,
                'method'          => 'GET',
                'payload'         => ['trans_id' => $reference],
                'response'        => ['error' => $e->getMessage()],
                'http_status'     => 500,
                'duration_ms'     => $durationMs,
                'success'         => 0,
            ]);

            return [
                'success'   => false,
                'paid'      => false,
                'amount'    => 0.0,
                'currency'  => 'NGN',
                'reference' => $reference,
                'message'   => self::$lastError,
                'data'      => [],
            ];
        }
    }

    /**
     * Validate an incoming webhook from Payscribe.
     */
    public static function validateWebhookSignature(Request $request): bool
    {
        $rawBody = $request->getContent();

        // 1. Check signature header (e.g. x-payscribe-signature, x-signature)
        $signature = $request->header('x-payscribe-signature') 
            ?? $request->header('x-signature') 
            ?? $request->header('payscribe-signature');

        // Secret key candidates: try Webhook Secret first (ps_...), then all configured keys
        $candidates = array_filter(array_unique([
            self::getWebhookSecret(),
            self::getSecretKey(),
            self::getPublicKey(),
            config('services.payscribe.webhook_secret'),
        ]));

        if (empty($candidates)) {
            return false;
        }

        if ($signature) {
            foreach ($candidates as $secretKey) {
                // Support timestamped versioned signature: v1=<hash>
                if (str_starts_with($signature, 'v1=')) {
                    $hash = substr($signature, 3);
                    $timestamp = $request->header('x-payscribe-timestamp');
                    $eventId = $request->header('x-payscribe-event-id');

                    if ($timestamp && $eventId) {
                        // Prevent replay attacks (5 minutes tolerance)
                        if (abs(time() - (int) $timestamp) <= 300) {
                            $expectedHash = hash_hmac('sha256', "{$timestamp}.{$eventId}.{$rawBody}", $secretKey);
                            if (hash_equals($expectedHash, $hash)) {
                                return true;
                            }
                        }
                    }
                }

                // Standard HMAC-SHA256 (Payscribe official documentation)
                $computedSha256 = hash_hmac('sha256', $rawBody, $secretKey);
                if (hash_equals($computedSha256, $signature)) {
                    return true;
                }

                // Fallback HMAC-SHA512
                $computedSha512 = hash_hmac('sha512', $rawBody, $secretKey);
                if (hash_equals($computedSha512, $signature)) {
                    return true;
                }
            }
        }

        // 2. Check secret token authentication header (e.g. x-payscribe-token, authorization bearer)
        $tokenHeader = $request->header('x-payscribe-token') ?? $request->bearerToken();
        if ($tokenHeader) {
            foreach ($candidates as $secretKey) {
                if (hash_equals($secretKey, $tokenHeader)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get or create a PayScribe customer profile for Dedicated Virtual Accounts.
     */
    public static function getOrCreateCustomer(User $user): string
    {
        $cacheKey = 'payscribe_customer_id_' . $user->id;
        $cachedId = Cache::get($cacheKey);
        if ($cachedId) {
            return (string) $cachedId;
        }

        // Check if an existing VirtualAccount has the customer_id stored
        $existingVa = VirtualAccount::where('user_id', $user->id)
            ->where('provider', 'payscribe')
            ->first();
        if ($existingVa && !empty($existingVa->metadata['customer']['id'])) {
            $cId = (string) $existingVa->metadata['customer']['id'];
            Cache::forever($cacheKey, $cId);
            return $cId;
        }

        $secretKey = self::getApiKey() ?: self::getSecretKey();
        if (!$secretKey) {
            throw new \Exception('PayScribe API credentials are not configured.');
        }

        $nameParts = preg_split('/\s+/', trim($user->name ?: 'Customer User'), 2);
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName  = $nameParts[1] ?? 'User';

        $phone = preg_replace('/[^0-9]/', '', (string) ($user->phone ?? ''));
        if (str_starts_with($phone, '0') && strlen($phone) === 11) {
            $phone = '234' . substr($phone, 1);
        } elseif (strlen($phone) === 10) {
            $phone = '234' . $phone;
        }
        if (empty($phone)) {
            $phone = '2348000000000';
        }

        $endpoint = self::getBaseUrl() . '/customers/create';
        $body = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $phone,
            'email'      => $user->email,
            'country'    => 'NG',
        ];

        $startTime = hrtime(true);
        try {
            $response = Http::withToken($secretKey)
                ->acceptJson()
                ->timeout(20)
                ->post($endpoint, $body);

            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            $resData = $response->json() ?? [];
            $success = $response->successful() && ($resData['status'] ?? false);

            ApiLog::record([
                'user_id'     => $user->id,
                'service'     => 'customer_create',
                'provider'    => 'payscribe',
                'reference'   => 'PSC_CUST_' . $user->id,
                'endpoint'    => $endpoint,
                'method'      => 'POST',
                'payload'     => $body,
                'response'    => $resData ?: ['raw' => $response->body()],
                'http_status' => $response->status(),
                'duration_ms' => $durationMs,
                'success'     => $success ? 1 : 0,
            ]);

            $details = $resData['message']['details'] ?? $resData['data']['details'] ?? $resData['data'] ?? [];
            $customerId = $details['customer_id'] ?? $details['id'] ?? null;

            if ($customerId) {
                Cache::forever($cacheKey, (string) $customerId);
                return (string) $customerId;
            }

            // If customer creation failed because customer already exists, lookup customer via GET /customers/?search={email}
            $searchEndpoint = self::getBaseUrl() . '/customers/?search=' . urlencode($user->email);
            $searchResp = Http::withToken($secretKey)->acceptJson()->timeout(15)->get($searchEndpoint);
            $searchData = $searchResp->json() ?? [];
            $customers = $searchData['message']['details']['customers'] ?? $searchData['data']['customers'] ?? [];

            foreach ($customers as $c) {
                if (strtolower($c['email'] ?? '') === strtolower($user->email)) {
                    $foundId = (string) ($c['customer_id'] ?? $c['id'] ?? '');
                    if ($foundId) {
                        Cache::forever($cacheKey, $foundId);
                        return $foundId;
                    }
                }
            }

            $errMsg = $resData['description'] ?? $resData['message'] ?? 'Could not create PayScribe customer profile.';
            throw new \Exception('PayScribe: ' . (is_array($errMsg) ? implode(', ', $errMsg) : (string) $errMsg));
        } catch (\Exception $e) {
            Log::error('PayScribe customer creation exception', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate Permanent / Dedicated Virtual Accounts (DVA) for a user with PayScribe.
     *
     * @param User $user
     * @param string $bvn
     * @param array $banks e.g. ['9psb', 'palmpay']
     * @return array Array of generated account details
     */
    public static function generateVirtualAccounts(User $user, string $bvn, array $banks = ['9psb', 'palmpay']): array
    {
        $secretKey = self::getApiKey() ?: self::getSecretKey();
        if (!$secretKey) {
            throw new \Exception('PayScribe API credentials are not configured.');
        }

        $customerId = self::getOrCreateCustomer($user);

        $endpoint = self::getBaseUrl() . '/collections/virtual-accounts/create';
        $body = [
            'account_type'    => 'static',
            'currency'        => 'NGN',
            'customer_id'     => $customerId,
            'bank'            => $banks,
            'identity_type'   => 'bvn',
            'identity_number' => $bvn,
        ];

        $ref = 'DVA_PSC_' . $user->id . '_' . time();
        $startTime = hrtime(true);

        try {
            $response = Http::withToken($secretKey)
                ->acceptJson()
                ->timeout(25)
                ->post($endpoint, $body);

            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            $resData = $response->json() ?? [];
            $status = $resData['status'] ?? false;
            $success = $response->successful() && ($status === true || ($resData['status_code'] ?? null) == 200);

            ApiLog::record([
                'user_id'     => $user->id,
                'service'     => 'dva_generate',
                'provider'    => 'payscribe',
                'reference'   => $ref,
                'endpoint'    => $endpoint,
                'method'      => 'POST',
                'payload'     => array_merge($body, ['identity_number' => '***' . substr($bvn, -4)]),
                'response'    => $resData ?: ['raw' => $response->body()],
                'http_status' => $response->status(),
                'duration_ms' => $durationMs,
                'success'     => $success ? 1 : 0,
            ]);

            if (!$success) {
                $errorMsg = $resData['description'] ?? $resData['message'] ?? 'Failed to create PayScribe virtual account.';
                throw new \Exception('PayScribe: ' . (is_array($errorMsg) ? implode(', ', $errorMsg) : (string) $errorMsg));
            }

            $details = $resData['message']['details'] ?? $resData['data']['details'] ?? $resData['data'] ?? [];
            $accounts = $details['account'] ?? [];

            // If a single account object was returned instead of array of accounts
            if (empty($accounts) && !empty($details['account_number'])) {
                $accounts = [$details];
            }

            return [
                'success'  => true,
                'customer' => $details['customer'] ?? ['id' => $customerId],
                'accounts' => $accounts,
                'data'     => $details,
            ];
        } catch (\Exception $e) {
            Log::error('PayScribe generateVirtualAccounts exception', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}

<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\AppSetting;
use Illuminate\Http\Request;
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
        return !empty(self::getSecretKey()) && !empty(self::getPublicKey());
    }

    /**
     * Check if Payscribe gateway is enabled by admin.
     */
    public static function isEnabled(): bool
    {
        return AppSetting::get('payscribe_status', '1') === '1';
    }

    /**
     * Initialize a checkout collection session with Payscribe.
     *
     * @param array $payload Must include: amount, reference, email, name
     * @return array ['success' => bool, 'reference' => string, 'token' => ?string, 'checkout_url' => ?string, 'message' => ?string, 'data' => ?array]
     */
    public static function createCheckoutSession(array $payload): array
    {
        self::$lastError = null;
        $secretKey = self::getSecretKey();

        if (!$secretKey) {
            self::$lastError = 'Payscribe secret key is not configured.';
            return ['success' => false, 'message' => self::$lastError];
        }

        $endpoint = self::getBaseUrl() . '/collections/initialize';
        $body = [
            'reference'    => $payload['reference'],
            'amount'       => (float) $payload['amount'],
            'currency'     => $payload['currency'] ?? 'NGN',
            'name'         => $payload['name'] ?? '',
            'email'        => $payload['email'] ?? '',
            'phone'        => $payload['phone'] ?? '',
            'redirect_url' => $payload['redirect_url'] ?? url('/wallet/fund/gateway/verify/payscribe'),
            'metadata'     => $payload['metadata'] ?? [],
        ];

        $startTime = hrtime(true);
        try {
            $response = Http::withToken($secretKey)
                ->timeout(20)
                ->post($endpoint, $body);

            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            $resData = $response->json() ?? [];
            $status = $resData['status'] ?? false;
            $success = $response->successful() && ($status === true || $status === 'success' || ($resData['code'] ?? null) == 200);

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
                $errorMsg = $resData['message'] ?? $resData['error'] ?? 'Could not initialize Payscribe checkout.';
                self::$lastError = is_array($errorMsg) ? implode(', ', $errorMsg) : (string) $errorMsg;
                return [
                    'success' => false,
                    'message' => self::$lastError,
                    'data'    => $resData,
                ];
            }

            $data = $resData['data'] ?? $resData;
            return [
                'success'      => true,
                'reference'    => $payload['reference'],
                'token'        => $data['token'] ?? $data['checkout_token'] ?? $data['access_code'] ?? null,
                'checkout_url' => $data['checkout_url'] ?? $data['payment_url'] ?? $data['link'] ?? null,
                'message'      => $resData['message'] ?? 'Checkout session created successfully.',
                'data'         => $data,
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
     * Verify a transaction status from Payscribe API.
     *
     * @param string $reference
     * @return array ['success' => bool, 'paid' => bool, 'amount' => float, 'currency' => string, 'reference' => string, 'message' => string, 'data' => array]
     */
    public static function verifyTransaction(string $reference): array
    {
        self::$lastError = null;
        $secretKey = self::getSecretKey();

        if (!$secretKey) {
            self::$lastError = 'Payscribe secret key is not configured.';
            return ['success' => false, 'paid' => false, 'message' => self::$lastError, 'amount' => 0.0, 'currency' => 'NGN', 'reference' => $reference, 'data' => []];
        }

        $endpoint = self::getBaseUrl() . '/collections/transactions/' . urlencode($reference);
        $startTime = hrtime(true);

        try {
            $response = Http::withToken($secretKey)
                ->timeout(20)
                ->get($endpoint);

            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            $resData = $response->json() ?? [];
            $data = $resData['data'] ?? $resData;

            $statusStr = strtolower((string) ($data['status'] ?? $resData['status'] ?? ''));
            $isPaid = in_array($statusStr, ['success', 'successful', 'paid', 'completed'], true);
            $success = $response->successful() && $isPaid;

            $amount = (float) ($data['amount'] ?? $data['paid_amount'] ?? 0);
            $currency = strtoupper((string) ($data['currency'] ?? 'NGN'));

            ApiLog::record([
                'user_id'         => auth()->id(),
                'service'         => 'funding',
                'provider'        => 'payscribe',
                'reference'       => $reference,
                'endpoint'        => $endpoint,
                'method'          => 'GET',
                'payload'         => ['reference' => $reference],
                'response'        => $resData ?: ['raw' => $response->body()],
                'http_status'     => $response->status(),
                'duration_ms'     => $durationMs,
                'success'         => $success ? 1 : 0,
            ]);

            if (!$response->successful()) {
                $errorMsg = $resData['message'] ?? 'Could not verify transaction with Payscribe.';
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
                'message'   => $resData['message'] ?? ($isPaid ? 'Payment verified successfully.' : 'Payment is still pending.'),
                'data'      => $data,
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
                'payload'         => ['reference' => $reference],
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
        $secretKey = self::getSecretKey();
        if (!$secretKey) {
            return false;
        }

        // 1. Check signature header (e.g. x-payscribe-signature, x-signature)
        $signature = $request->header('x-payscribe-signature') 
            ?? $request->header('x-signature') 
            ?? $request->header('payscribe-signature');

        if ($signature) {
            $computedSha512 = hash_hmac('sha512', $request->getContent(), $secretKey);
            $computedSha256 = hash_hmac('sha256', $request->getContent(), $secretKey);
            if (hash_equals($computedSha512, $signature) || hash_equals($computedSha256, $signature)) {
                return true;
            }
        }

        // 2. Check secret token authentication header (e.g. x-payscribe-token, authorization bearer)
        $tokenHeader = $request->header('x-payscribe-token') ?? $request->bearerToken();
        if ($tokenHeader && hash_equals($secretKey, $tokenHeader)) {
            return true;
        }

        return false;
    }
}

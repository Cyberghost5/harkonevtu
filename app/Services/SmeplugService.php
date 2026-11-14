<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\AppSetting;
use App\Models\ServiceTransaction;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmeplugService
{
    /**
     * Get the configured SME Plug Private Key (API Bearer token).
     */
    public static function getPrivateKey(): ?string
    {
        $key = AppSetting::get('smeplug_private_key') ?: config('services.smeplug.private_key');
        return $key ? trim((string) $key) : null;
    }

    /**
     * Alias for getPrivateKey.
     */
    public static function getApiKey(): ?string
    {
        return self::getPrivateKey();
    }

    /**
     * Get the configured base URL for SME Plug API.
     */
    public static function getBaseUrl(): string
    {
        $url = AppSetting::get('smeplug_base_url') ?: config('services.smeplug.base_url', 'https://smeplug.ng/api/v1');
        return rtrim((string) $url, '/');
    }

    /**
     * Map network key or name to SME Plug network ID.
     * 1 = MTN, 2 = Airtel, 3 = 9mobile, 4 = Glo
     */
    public static function networkIdFromKey(string $key): ?int
    {
        return match (strtolower(trim($key))) {
            'mtn'                 => 1,
            'airtel'              => 2,
            'etisalat', '9mobile' => 3,
            'glo', 'globacom'     => 4,
            default               => null,
        };
    }

    /**
     * Format a Nigerian phone number to the standard 11-digit local format (e.g. 08012345678).
     */
    public static function formatPhone(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone);
        if (str_starts_with($clean, '+234')) {
            $clean = '0' . substr($clean, 4);
        } elseif (str_starts_with($clean, '234') && strlen($clean) === 13) {
            $clean = '0' . substr($clean, 3);
        }
        return $clean;
    }

    /**
     * Purchase airtime via SME Plug API.
     *
     * @param string $networkKey
     * @param float|int $amount
     * @param string $phone
     * @param string $reference
     * @return array ['success' => bool, 'reference' => string, 'response' => array]
     */
    public function purchaseAirtime(string $networkKey, float|int $amount, string $phone, string $reference): array
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            return [
                'success'   => false,
                'reference' => $reference,
                'response'  => ['message' => 'SME Plug API private key is not configured.'],
            ];
        }

        $networkId = self::networkIdFromKey($networkKey);
        if (!$networkId) {
            return [
                'success'   => false,
                'reference' => $reference,
                'response'  => ['message' => "Unsupported network [{$networkKey}] for SME Plug."],
            ];
        }

        $formattedPhone = self::formatPhone($phone);
        $endpoint       = self::getBaseUrl() . '/airtime/purchase';
        $payload        = [
            'network_id'         => $networkId,
            'phone'              => $formattedPhone,
            'amount'             => (int) round($amount),
            'customer_reference' => $reference,
        ];

        $requestHeaders = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];

        $data            = [];
        $httpStatus      = null;
        $responseHeaders = null;
        $success         = false;
        $apiRef          = $reference;
        $start           = hrtime(true);

        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(35)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];

            $rawStatus = $data['status'] ?? null;
            $success   = $response->successful() && (
                $rawStatus === true ||
                $rawStatus === 'true' ||
                $rawStatus === 'success' ||
                (($data['data']['status'] ?? null) === 'success')
            );

            $apiRef = $data['data']['reference']
                ?? $data['reference']
                ?? $data['data']['id']
                ?? $data['id']
                ?? $reference;

            if (!$success) {
                $msg = $data['msg']
                    ?? $data['message']
                    ?? $data['data']['msg']
                    ?? $data['data']['message']
                    ?? 'SME Plug airtime delivery failed.';
                $data['message'] = is_string($msg) ? $msg : json_encode($msg);
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => 'Unable to connect to SME Plug service provider.'];
            Log::error('SME Plug airtime request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            $loggedHeaders = $requestHeaders;
            $loggedHeaders['Authorization'] = 'Bearer ***';
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'airtime',
                'provider'         => 'smeplug',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $loggedHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    /**
     * Purchase data bundle via SME Plug API.
     *
     * @param string $networkKey
     * @param int|string $planId
     * @param string $phone
     * @param string $reference
     * @return array ['success' => bool, 'reference' => string, 'response' => array]
     */
    public function purchaseData(string $networkKey, int|string $planId, string $phone, string $reference): array
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            return [
                'success'   => false,
                'reference' => $reference,
                'response'  => ['message' => 'SME Plug API private key is not configured.'],
            ];
        }

        $networkId = self::networkIdFromKey($networkKey);
        if (!$networkId) {
            return [
                'success'   => false,
                'reference' => $reference,
                'response'  => ['message' => "Unsupported network [{$networkKey}] for SME Plug."],
            ];
        }

        $formattedPhone = self::formatPhone($phone);
        $endpoint       = self::getBaseUrl() . '/data/purchase';
        $payload        = [
            'network_id'         => $networkId,
            'plan_id'            => is_numeric($planId) ? (int) $planId : $planId,
            'phone'              => $formattedPhone,
            'customer_reference' => $reference,
        ];

        $requestHeaders = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];

        $data            = [];
        $httpStatus      = null;
        $responseHeaders = null;
        $success         = false;
        $apiRef          = $reference;
        $start           = hrtime(true);

        try {
            $response        = Http::withHeaders($requestHeaders)->timeout(35)->post($endpoint, $payload);
            $httpStatus      = $response->status();
            $responseHeaders = $response->headers();
            $data            = $response->json() ?? [];

            $rawStatus = $data['status'] ?? null;
            $success   = $response->successful() && (
                $rawStatus === true ||
                $rawStatus === 'true' ||
                $rawStatus === 'success' ||
                (($data['data']['status'] ?? null) === 'success')
            );

            $apiRef = $data['data']['reference']
                ?? $data['reference']
                ?? $data['data']['id']
                ?? $data['id']
                ?? $reference;

            if (!$success) {
                $msg = $data['msg']
                    ?? $data['message']
                    ?? $data['data']['msg']
                    ?? $data['data']['message']
                    ?? 'SME Plug data delivery failed.';
                $data['message'] = is_string($msg) ? $msg : json_encode($msg);
            }
        } catch (\Exception $e) {
            $data = ['error' => $e->getMessage(), 'message' => 'Unable to connect to SME Plug service provider.'];
            Log::error('SME Plug data request failed', ['reference' => $reference, 'error' => $e->getMessage()]);
        } finally {
            $duration = (int) ((hrtime(true) - $start) / 1e6);
            $loggedHeaders = $requestHeaders;
            $loggedHeaders['Authorization'] = 'Bearer ***';
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'data',
                'provider'         => 'smeplug',
                'reference'        => $reference,
                'endpoint'         => $endpoint,
                'method'           => 'POST',
                'payload'          => $payload,
                'request_headers'  => $loggedHeaders,
                'response'         => $data,
                'http_status'      => $httpStatus,
                'response_headers' => $responseHeaders,
                'duration_ms'      => $duration,
                'success'          => $success,
            ]);
        }

        return ['success' => $success, 'reference' => $apiRef, 'response' => $data];
    }

    /**
     * Query wallet balance from SME Plug API.
     */
    public function getBalance(): array
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'SME Plug private key is not configured.'];
        }

        $endpoint = self::getBaseUrl() . '/account/balance';
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept'        => 'application/json',
            ])->timeout(20)->get($endpoint);

            $data = $response->json() ?? [];
            if ($response->successful()) {
                $balance = $data['balance'] ?? $data['data']['balance'] ?? 0;
                return [
                    'success'  => true,
                    'balance'  => (float) $balance,
                    'response' => $data,
                ];
            }

            return [
                'success'  => false,
                'message'  => $data['msg'] ?? $data['message'] ?? 'Failed to retrieve balance from SME Plug.',
                'response' => $data,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch list of available data plans from SME Plug.
     */
    public function getDataPlans(): array
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'SME Plug private key is not configured.'];
        }

        $endpoint = self::getBaseUrl() . '/data/plans';
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept'        => 'application/json',
            ])->timeout(20)->get($endpoint);

            $data = $response->json() ?? [];
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $data['data'] ?? $data,
                ];
            }

            return [
                'success' => false,
                'message' => $data['msg'] ?? $data['message'] ?? 'Failed to fetch data plans from SME Plug.',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Query transaction status from SME Plug API.
     */
    public function queryTransaction(string $reference): array
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'SME Plug private key is not configured.'];
        }

        $endpoint = self::getBaseUrl() . '/transactions/' . urlencode($reference);
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept'        => 'application/json',
            ])->timeout(20)->get($endpoint);

            $data = $response->json() ?? [];
            return [
                'success'  => $response->successful(),
                'data'     => $data['data'] ?? $data,
                'response' => $data,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle incoming webhook notification from SME Plug.
     * Payload typically:
     * {
     *   "transaction": {
     *     "status": "success" | "failed",
     *     "reference": "SP123456789",
     *     "customer_reference": "AIR...",
     *     "type": "airtime" | "data",
     *     "beneficiary": "09061668519",
     *     "price": 100
     *   }
     * }
     */
    public function handleWebhook(array $payload): array
    {
        $txn = $payload['transaction'] ?? $payload['data'] ?? $payload;
        $status = strtolower((string) ($txn['status'] ?? ''));
        $smeRef = $txn['reference'] ?? null;
        $custRef = $txn['customer_reference'] ?? null;

        $targetRef = $custRef ?: $smeRef;

        // Find corresponding ServiceTransaction
        $serviceTxn = null;
        if ($custRef) {
            $serviceTxn = ServiceTransaction::where('reference', $custRef)->first();
        }
        if (!$serviceTxn && $smeRef) {
            $serviceTxn = ServiceTransaction::where('api_reference', $smeRef)
                ->orWhere('reference', $smeRef)
                ->first();
        }

        // Record API Log for audit
        ApiLog::record([
            'user_id'          => $serviceTxn?->user_id,
            'service'          => 'webhook',
            'provider'         => 'smeplug',
            'reference'        => (string) ($targetRef ?? 'UNKNOWN'),
            'endpoint'         => url('/webhook/smeplug'),
            'method'           => 'POST',
            'payload'          => $payload,
            'response'         => ['status' => $targetRef ? 'Webhook processed' : 'Ignored: No reference found in payload'],
            'http_status'      => $targetRef ? 200 : 400,
            'duration_ms'      => 0,
            'success'          => (bool) $targetRef,
        ]);

        if (!$targetRef) {
            return ['status' => 'ignored', 'message' => 'No reference found in payload'];
        }

        if (!$serviceTxn) {
            return ['status' => 'ok', 'message' => 'Transaction not found or non-service event logged.'];
        }

        $isSuccessful = in_array($status, ['success', 'successful', 'delivered'], true);
        $isFailed     = in_array($status, ['failed', 'cancelled', 'reversed', 'error'], true);

        if ($isSuccessful) {
            if ($serviceTxn->status !== 'success') {
                $serviceTxn->update([
                    'status'       => 'success',
                    'api_response' => array_merge($serviceTxn->api_response ?? [], ['webhook' => $payload]),
                ]);

                // Send push notification if configured
                try {
                    $formattedService = ucfirst($serviceTxn->service_type);
                    $title = $formattedService . ' Purchase Successful';
                    $message = "Your purchase of {$formattedService} (₦" . number_format((float) $serviceTxn->amount, 2) . ") for {$serviceTxn->recipient} was completed successfully.";
                    OneSignalService::sendNotificationToUser((string) $serviceTxn->user_id, $title, $message);
                } catch (\Throwable $e) {
                    Log::warning('SME Plug webhook push notification failed: ' . $e->getMessage());
                }
            }
            return ['status' => 'success', 'message' => 'Transaction marked successful.'];
        }

        if ($isFailed) {
            if ($serviceTxn->status !== 'failed' && $serviceTxn->status !== 'refunded') {
                DB::transaction(function () use ($serviceTxn, $payload) {
                    $serviceTxn->update([
                        'status'       => 'refunded',
                        'api_response' => array_merge($serviceTxn->api_response ?? [], ['webhook' => $payload]),
                    ]);

                    $user = $serviceTxn->user;
                    if ($user && $user->wallet) {
                        $refundRef = 'REF_' . $serviceTxn->reference;
                        if (!WalletTransaction::where('reference', $refundRef)->exists()) {
                            $user->wallet->credit(
                                (float) $serviceTxn->amount,
                                'Refund: ' . ucfirst($serviceTxn->service_type) . ' delivery failed (SME Plug webhook)',
                                $refundRef,
                                ['source' => 'webhook', 'provider' => 'smeplug', 'original_reference' => $serviceTxn->reference]
                            );
                        }
                    }
                });
            }
            return ['status' => 'refunded', 'message' => 'Transaction marked refunded and credited to wallet.'];
        }

        return ['status' => 'ok', 'message' => 'Unhandled status: ' . $status];
    }
}

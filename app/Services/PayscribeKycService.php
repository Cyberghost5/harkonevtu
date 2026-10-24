<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayscribeKycService
{
    protected string $baseUrl;
    protected ?string $secretKey;

    public function __construct()
    {
        $mode = AppSetting::get('payscribe_mode', 'production');
        $configuredBaseUrl = config('services.payscribe.base_url');

        if ($configuredBaseUrl) {
            $this->baseUrl = rtrim($configuredBaseUrl, '/');
        } else {
            $this->baseUrl = ($mode === 'sandbox')
                ? 'https://sandbox.payscribe.ng/api/v1'
                : 'https://api.payscribe.ng/api/v1';
        }

        $this->secretKey = \App\Services\PayscribePaymentService::getApiKey();
    }

    /**
     * Checks if PayScribe credentials are configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Perform KYC identity lookup (BVN/NIN) with PayScribe and cross-match names.
     */
    public function lookupIdentity(string $type, string $idNumber, string $firstName, string $lastName): array
    {
        if (!$this->isConfigured()) {
            return [
                'status'  => false,
                'message' => 'PayScribe KYC service is not configured with an API Secret Key.',
            ];
        }

        $cleanType   = strtolower(trim($type));
        $cleanNumber = trim($idNumber);

        $endpoint = "{$this->baseUrl}/kyc/lookup";
        $queryParams = [
            'type'  => $cleanType,
            'value' => $cleanNumber,
        ];

        $requestHeaders = [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Accept'        => 'application/json',
        ];

        $start = hrtime(true);

        try {
            $response = Http::withHeaders($requestHeaders)
                ->timeout(25)
                ->get($endpoint, $queryParams);

            $duration = (int) ((hrtime(true) - $start) / 1e6);
            $resBody = $response->json() ?? [];
            $isSuccess = $response->successful() && !empty($resBody['status']) && $resBody['status'] === true;

            // Log API attempt
            ApiLog::record([
                'user_id'          => auth()->id(),
                'service'          => 'kyc_verification',
                'provider'         => 'payscribe',
                'reference'        => 'KYC-PSC-' . $cleanNumber,
                'endpoint'         => $endpoint . '?' . http_build_query($queryParams),
                'method'           => 'GET',
                'payload'          => array_merge($queryParams, ['value' => '***' . substr($cleanNumber, -4)]),
                'request_headers'  => $requestHeaders,
                'response'         => $resBody ?: ['raw' => $response->body()],
                'http_status'      => $response->status(),
                'response_headers' => $response->headers(),
                'duration_ms'      => $duration,
                'success'          => $isSuccess ? 1 : 0,
            ]);

            // Check if failure is due to provider low balance / insufficient float
            if (!$isSuccess && ProviderAlertService::isLowBalance($resBody)) {
                $friendlyMsg = ProviderAlertService::processFailure($resBody, [
                    'service'   => 'KYC Lookup (' . strtoupper($cleanType) . ')',
                    'provider'  => 'payscribe',
                    'amount'    => 0,
                    'user'      => auth()->user(),
                    'recipient' => $cleanNumber,
                    'reference' => 'KYC-PSC-' . $cleanNumber,
                ], false);

                return [
                    'status'  => false,
                    'message' => $friendlyMsg,
                ];
            }

            if (!$isSuccess) {
                $errorMsg = $resBody['description'] 
                    ?? ($resBody['message'] ?? 'Identity lookup failed. Please verify the ID number.');
                
                if (is_array($errorMsg)) {
                    $errorMsg = $errorMsg['description'] ?? ($errorMsg['message'] ?? 'Identity lookup failed.');
                }

                return [
                    'status'  => false,
                    'message' => $errorMsg,
                ];
            }

            $details = $resBody['message']['details'] ?? ($resBody['details'] ?? []);

            // 1. Blacklist check
            if (!empty($details['blacklist']) && $details['blacklist'] === true) {
                return [
                    'status'  => false,
                    'message' => 'Identity verification rejected. This record is flagged as blacklisted in the verification registry.',
                ];
            }

            // 2. Name Matching Verification
            $retrievedFirst  = strtolower(trim($details['first_name'] ?? ''));
            $retrievedLast   = strtolower(trim($details['last_name'] ?? ''));
            $retrievedMiddle = strtolower(trim($details['middle_name'] ?? ''));

            $match = $this->verifyNameMatch($firstName, $lastName, $retrievedFirst, $retrievedLast, $retrievedMiddle);

            if (!$match) {
                return [
                    'status'  => false,
                    'message' => 'Identity verification mismatch. The name registered to this ID does not match your profile name.',
                    'data'    => $details,
                ];
            }

            return [
                'status'  => true,
                'message' => 'Identity verified successfully via PayScribe.',
                'data'    => $details,
            ];
        } catch (\Throwable $e) {
            Log::error('PayScribe KYC Lookup Exception: ' . $e->getMessage(), [
                'type' => $cleanType,
            ]);

            return [
                'status'  => false,
                'message' => 'Unable to connect to PayScribe verification provider. Please try again shortly.',
            ];
        }
    }

    /**
     * Cross-match user input names against the returned PayScribe BVN/NIN record.
     */
    public function verifyNameMatch(
        string $inputFirst,
        string $inputLast,
        string $retrievedFirst,
        string $retrievedLast,
        string $retrievedMiddle = ''
    ): bool {
        $inputTokens = array_filter(array_map('strtolower', preg_split('/\s+/', "{$inputFirst} {$inputLast}")));
        $retrievedTokens = array_filter(array_map('strtolower', array_filter([$retrievedFirst, $retrievedLast, $retrievedMiddle])));

        if (empty($inputTokens) || empty($retrievedTokens)) {
            return false;
        }

        // Count how many input tokens match the retrieved tokens
        $matchedCount = 0;
        foreach ($inputTokens as $token) {
            foreach ($retrievedTokens as $retToken) {
                // Exact match or substring match for hyphenated / compound names
                if ($token === $retToken || str_contains($token, $retToken) || str_contains($retToken, $token)) {
                    $matchedCount++;
                    break;
                }
            }
        }

        // At least 2 name tokens must match (e.g. First and Last Name)
        return $matchedCount >= min(2, count($inputTokens));
    }
}

<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QoreIDService
{
    protected string $baseUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;

    public function __construct()
    {
        $mode = AppSetting::get('qoreid_mode', 'sandbox');
        $this->baseUrl = $mode === 'production' 
            ? 'https://api.qoreid.com' 
            : 'https://api.qoreid.com';

        $this->clientId = AppSetting::get('qoreid_client_key');
        $this->clientSecret = AppSetting::get('qoreid_secret_key');
    }

    /**
     * Checks if the QoreID credentials have been saved.
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Obtains the authorization access token.
     */
    public function getAccessToken(): ?string
    {
        try {
            $response = Http::post("{$this->baseUrl}/token", [
                'clientId' => $this->clientId,
                'secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                return $response->json('accessToken');
            }

            Log::error('QoreID Token request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('QoreID Token Exception', ['message' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Verifies user identity details using NIN or BVN.
     */
    public function verifyIdentity(string $type, string $idNumber, string $firstName, string $lastName): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'status' => false,
                'message' => 'Unable to authenticate with verification provider.'
            ];
        }

        $endpoint = $type === 'bvn' ? '/v1/ng/identities/bvn' : '/v1/ng/identities/nin';
        
        try {
            Log::info('QoreID Verification Request', [
                'type' => $type,
                'idNumber' => $idNumber,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'endpoint' => "{$this->baseUrl}{$endpoint}/{$idNumber}",
            ]);
            
            $response = Http::withToken($token)
                ->post("{$this->baseUrl}{$endpoint}/{$idNumber}", [
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // QoreID match verification payload response check
                $statusState = $data['status']['state'] ?? null;
                if ($statusState === 'VERIFIED' || $statusState === 'MATCH') {
                    return ['status' => true, 'data' => $data];
                }
                
                return [
                    'status' => false,
                    'message' => 'Identity details mismatch. Please check name spellings and ID number.',
                    'data' => $data
                ];
            }

            $errorBody = $response->json();
            $message = $errorBody['summary'] ?? $errorBody['message'] ?? 'Identity lookup failed.';

            return ['status' => false, 'message' => $message];

        } catch (\Exception $e) {
            Log::error('QoreID Verification Exception', ['message' => $e->getMessage()]);
            return ['status' => false, 'message' => 'An error occurred during verification.'];
        }
    }
}

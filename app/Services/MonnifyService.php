<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonnifyService
{
    private static function getBaseUrl(): string
    {
        $mode = AppSetting::get('monnify_mode', 'sandbox');
        return $mode === 'production' 
            ? 'https://api.monnify.com' 
            : 'https://sandbox.monnify.com';
    }

    /**
     * Get bearer authentication token from Monnify.
     */
    public static function getAccessToken(): ?string
    {
        $apiKey = AppSetting::get('monnify_api_key');
        $secretKey = AppSetting::get('monnify_secret_key');

        if (!$apiKey || !$secretKey) {
            Log::warning('Monnify API Key or Secret Key not configured.');
            return null;
        }

        $url = self::getBaseUrl() . '/api/v1/auth/login';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $secretKey),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->timeout(15)->post($url);

            if ($response->successful() && $response->json('requestSuccessful') === true) {
                return $response->json('responseBody.accessToken');
            }

            Log::error('Monnify authentication failed', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Monnify authentication exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Reserve accounts (DVA) for a user.
     */
    public static function generateReservedAccounts(\App\Models\User $user): array
    {
        $accessToken = self::getAccessToken();
        if (!$accessToken) {
            throw new \Exception('Could not authenticate with Monnify.');
        }

        $contractCode = AppSetting::get('monnify_contract_no');
        if (!$contractCode) {
            throw new \Exception('Monnify Contract Code/Number is not configured.');
        }

        $url = self::getBaseUrl() . '/api/v1/bank-transfer/reserved-accounts';

        $payload = [
            'accountReference'     => 'DVA_MONNIFY_' . $user->id . '_' . time(),
            'accountName'          => $user->name,
            'currencyCode'         => 'NGN',
            'contractCode'         => $contractCode,
            'customerEmail'        => $user->email,
            'customerName'         => $user->name,
            'getAllAvailableBanks' => true
        ];

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->timeout(20)
                ->post($url, $payload);

            if ($response->successful() && $response->json('requestSuccessful') === true) {
                return $response->json('responseBody');
            }

            $message = $response->json('responseMessage') ?? 'Request failed';
            throw new \Exception('Monnify: ' . $message);
        } catch (\Exception $e) {
            Log::error('Monnify Reserved Accounts generation exception', [
                'user_id' => $user->id,
                'error'   => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

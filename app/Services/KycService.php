<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;

class KycService
{
    protected QoreIDService $qoreIDService;
    protected PayscribeKycService $payscribeKycService;

    public function __construct(
        QoreIDService $qoreIDService,
        PayscribeKycService $payscribeKycService
    ) {
        $this->qoreIDService = $qoreIDService;
        $this->payscribeKycService = $payscribeKycService;
    }

    /**
     * Get the currently active KYC verification provider.
     */
    public function getActiveProvider(): string
    {
        return AppSetting::get('kyc_provider', 'qoreid');
    }

    /**
     * Verify user identity (BVN / NIN) using the active provider.
     */
    public function verifyIdentity(
        string $type,
        string $idNumber,
        string $firstName,
        string $lastName,
        ?User $user = null
    ): array {
        $provider = $this->getActiveProvider();
        $userObj = $user ?? auth()->user();

        // ── 1. PAYSCRIBE PROVIDER ─────────────────────────────────────────────
        if ($provider === 'payscribe') {
            if (!$this->payscribeKycService->isConfigured()) {
                return $this->simulateFallbackVerification($firstName, $lastName, $userObj, 'PayScribe (Simulation)');
            }

            $result = $this->payscribeKycService->lookupIdentity($type, $idNumber, $firstName, $lastName);
            $result['provider'] = 'payscribe';
            return $result;
        }

        // ── 2. QOREID PROVIDER (DEFAULT) ──────────────────────────────────────
        if (!$this->qoreIDService->isConfigured()) {
            return $this->simulateFallbackVerification($firstName, $lastName, $userObj, 'QoreID (Simulation)');
        }

        $result = $this->qoreIDService->verifyIdentity($type, $idNumber, $firstName, $lastName);
        $result['provider'] = 'qoreid';
        if ($result['status'] && empty($result['message'])) {
            $result['message'] = 'Identity verified successfully via QoreID.';
        }
        return $result;
    }

    /**
     * Local/sandbox simulated fallback if API keys are not yet configured.
     */
    protected function simulateFallbackVerification(
        string $firstName,
        string $lastName,
        ?User $user,
        string $providerLabel
    ): array {
        $userName = $user ? $user->name : "{$firstName} {$lastName}";
        $firstNameInput = strtolower(trim($firstName));
        $lastNameInput = strtolower(trim($lastName));
        $userNames = array_map('strtolower', explode(' ', trim($userName)));

        $nameMatches = in_array($firstNameInput, $userNames) || in_array($lastNameInput, $userNames);

        if ($nameMatches) {
            return [
                'status'   => true,
                'message'  => "Identity verified successfully ({$providerLabel}).",
                'provider' => 'simulated',
                'data'     => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                ],
            ];
        }

        return [
            'status'   => false,
            'message'  => 'Verification failed. The names retrieved from your profile do not match your account identity.',
            'provider' => 'simulated',
        ];
    }
}

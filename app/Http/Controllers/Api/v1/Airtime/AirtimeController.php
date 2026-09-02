<?php

namespace App\Http\Controllers\Api\v1\Airtime;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\NetworkAirtime;
use App\Models\ServiceTransaction;
use App\Models\Wallet;
use App\Services\GloErsService;
use App\Services\MtnErsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AirtimeController extends Controller
{
    /**
     * List all active airtime networks with tier discount rates.
     */
    public function networks(Request $request): JsonResponse
    {
        if (AppSetting::get('service_airtime', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Airtime service is temporarily unavailable.',
            ], 503);
        }

        $user    = auth()->user();
        $isAgent = $user ? $user->isAgent() : false;
        $prefix  = $isAgent ? 'airtime_agent_off_percentage_' : 'airtime_off_percentage_';

        $networks = NetworkAirtime::active()->get()->map(function (NetworkAirtime $net) use ($prefix) {
            $discount = (float) AppSetting::get($prefix . $net->network_key, 0);
            return [
                'id'                  => $net->id,
                'name'                => $net->name,
                'network_key'         => $net->network_key,
                'discount_percentage' => $discount,
                'enabled'             => (bool) $net->enabled,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Airtime networks retrieved successfully.',
            'data'    => [
                'user_tier' => $isAgent ? 'Agent' : 'User',
                'networks'  => $networks,
            ],
        ]);
    }

    /**
     * Auto-detect network key from recipient phone number prefix.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:14'],
        ]);

        $phone = preg_replace('/^\+234/', '0', preg_replace('/[^0-9]/', '', $request->phone));
        if (strlen($phone) === 10 && !str_starts_with($phone, '0')) {
            $phone = '0' . $phone;
        }

        $prefix = substr($phone, 0, 4);
        $networkKey = match (true) {
            in_array($prefix, ['0803', '0806', '0810', '0813', '0814', '0816', '0703', '0704', '0706', '0903', '0906', '0913', '0916']) => 'mtn',
            in_array($prefix, ['0805', '0807', '0811', '0815', '0705', '0905', '0915']) => 'glo',
            in_array($prefix, ['0802', '0808', '0812', '0701', '0708', '0901', '0902', '0904', '0907', '0912']) => 'airtel',
            in_array($prefix, ['0809', '0817', '0818', '0908', '0909']) => 'etisalat',
            default => null,
        };

        if (!$networkKey) {
            return response()->json([
                'status'  => false,
                'message' => 'Could not auto-detect network for this phone number. Please select network manually.',
            ], 422);
        }

        $network = NetworkAirtime::where('network_key', $networkKey)->first();

        return response()->json([
            'status'  => true,
            'message' => 'Network detected successfully.',
            'data'    => [
                'phone'       => $phone,
                'prefix'      => $prefix,
                'network_key' => $networkKey,
                'name'        => $network ? $network->name : strtoupper($networkKey),
            ],
        ]);
    }

    /**
     * Execute airtime disbursement transaction.
     */
    public function purchase(Request $request): JsonResponse
    {
        if (AppSetting::get('service_airtime', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'message' => 'Airtime service is temporarily unavailable.',
            ], 503);
        }

        $validNetworkKeys = NetworkAirtime::where('enabled', true)->pluck('network_key')->implode(',');

        $request->validate([
            'network' => ['required', 'string', 'in:' . $validNetworkKeys],
            'phone'   => ['required', 'string', 'regex:/^(0|\+234)[789][01]\d{8}$/'],
            'amount'  => ['required', 'numeric', 'min:1', 'max:50000'],
            'pin'     => ['nullable', 'digits:4'],
            'transaction_pin' => ['nullable', 'digits:4'],
        ]);

        $user = auth()->user();
        $providedPin = $request->pin ?? $request->transaction_pin;

        if (!$providedPin) {
            return response()->json([
                'status'  => false,
                'message' => 'Transaction PIN is required.',
                'errors'  => ['pin' => ['Transaction PIN is required.']],
            ], 422);
        }

        // 1. Verify transaction PIN
        if (!$user->verifyPin($providedPin)) {
            return response()->json([
                'status'  => false,
                'message' => 'Incorrect transaction PIN. Please try again.',
                'errors'  => ['pin' => ['Incorrect transaction PIN.']],
            ], 422);
        }

        $networkKey = $request->network;
        $network    = NetworkAirtime::where('network_key', $networkKey)->where('enabled', true)->firstOrFail();
        $phone      = preg_replace('/^\+234/', '0', $request->phone);
        $faceAmount = (float) $request->amount;
        $chargedAmount = $this->calculateChargedAmount($faceAmount, $networkKey, $user);

        // 2. Daily spending limit check
        $dailyLimit = (float) AppSetting::get('airtime_daily_limit', 100000);
        $todaySpent = ServiceTransaction::where('user_id', $user->id)
            ->where('service_type', 'airtime')
            ->whereIn('status', ['success'])
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount');

        if (($todaySpent + $chargedAmount) > $dailyLimit) {
            return response()->json([
                'status'  => false,
                'message' => 'Daily airtime spending limit of ₦' . number_format($dailyLimit, 0) . ' reached. Try again tomorrow.',
            ], 422);
        }

        // 3. Wallet balance check
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->hasSufficientBalance($chargedAmount)) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient wallet balance. You require ₦' . number_format($chargedAmount, 2) . ' to complete this transaction.',
                'errors'  => ['balance' => ['Insufficient wallet balance.']],
            ], 422);
        }

        // 4. Debit wallet in DB transaction
        $reference = 'AIR' . date('YmdHis') . Str::upper(Str::random(8));
        $walletTx  = null;

        try {
            $walletTx = DB::transaction(function () use ($user, $chargedAmount, $networkKey, $phone, $reference, $network) {
                $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                if (!$w->hasSufficientBalance($chargedAmount)) {
                    throw new \Exception('Insufficient wallet balance. Please fund your wallet.');
                }
                return $w->debit(
                    $chargedAmount,
                    $network->name . ' Airtime - ' . $phone,
                    $reference,
                    ['service' => 'airtime', 'network' => $networkKey, 'phone' => $phone]
                );
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        // 5. Dispatch API call to configured VTU gateway
        ['success' => $apiSuccess, 'reference' => $apiRef, 'response' => $apiResponse]
            = $this->callAirtimeGateway($network, $faceAmount, $phone, $reference);

        // 6. Handle refund on VTU failure
        if (!$apiSuccess) {
            try {
                DB::transaction(function () use ($user, $chargedAmount, $reference, $network, $phone) {
                    $w = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                    $w->credit(
                        $chargedAmount,
                        'Refund: ' . $network->name . ' Airtime failed - ' . $phone,
                        'REFUND_' . $reference,
                        ['type' => 'refund', 'original_reference' => $reference]
                    );
                });
            } catch (\Exception $e) {
                Log::critical('Airtime API refund failed', [
                    'user_id'   => $user->id,
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }

            return response()->json([
                'status'   => false,
                'refunded' => true,
                'message'  => $apiResponse['message'] ?? 'Airtime delivery failed. Your wallet balance has been refunded.',
            ], 422);
        }

        // 7. Record transaction log on clean success
        $api = AppSetting::get('airtime_api', 'vtpass');

        ServiceTransaction::create([
            'user_id'               => $user->id,
            'wallet_transaction_id' => $walletTx->id,
            'service_type'          => 'airtime',
            'provider'              => $networkKey,
            'recipient'             => $phone,
            'amount'                => $chargedAmount,
            'status'                => 'success',
            'reference'             => $reference,
            'api_reference'         => $apiRef,
            'api_response'          => array_merge(is_array($apiResponse) ? $apiResponse : ['raw' => $apiResponse], ['api_provider' => $api]),
        ]);

        $freshWallet = $user->fresh()->wallet;

        return response()->json([
            'status'  => true,
            'message' => '₦' . number_format($faceAmount, 0) . ' ' . $network->name . ' airtime sent to ' . $phone . ' successfully.',
            'data'    => [
                'reference'        => $reference,
                'api_reference'    => $apiRef,
                'network'          => $network->name,
                'network_key'      => $networkKey,
                'recipient'        => $phone,
                'face_amount'      => $faceAmount,
                'charged_amount'   => $chargedAmount,
                'discount_applied' => round($faceAmount - $chargedAmount, 2),
                'balance_after'    => (float) ($freshWallet ? $freshWallet->balance : 0),
                'date'             => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Calculate wallet debit amount after tier discount.
     */
    private function calculateChargedAmount(float $faceAmount, string $networkKey, $user): float
    {
        $isAgent = $user->isAgent();
        $prefix  = $isAgent ? 'airtime_agent_off_percentage_' : 'airtime_off_percentage_';
        $percent = (float) AppSetting::get($prefix . $networkKey, 0);

        if ($percent <= 0) {
            return $faceAmount;
        }

        $discount = ($faceAmount * $percent) / 100;
        return max(0, $faceAmount - $discount);
    }

    /**
     * Dispatch VTU airtime call to active gateway provider.
     */
    private function callAirtimeGateway(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        $networkKey = $network->network_key;
        $api = AppSetting::get('airtime_net_' . $networkKey);
        if (!$api) {
            $api = AppSetting::get('airtime_api', 'vtpass');
        }

        return match ($api) {
            'glo_ers'     => $this->callGloErs($network, $amount, $phone, $reference),
            'mtn_ers'     => $this->callMtnErs($network, $amount, $phone, $reference),
            'vtpass'      => $this->callVtpass($network, $amount, $phone, $reference),
            'clubkonnect' => $this->callClubkonnect($network, $amount, $phone, $reference),
            'autopilot'   => $this->callAutopilot($network, $amount, $phone, $reference),
            'easyaccess'  => $this->callEasyaccess($network, $amount, $phone, $reference),
            default       => $this->callVtpass($network, $amount, $phone, $reference),
        };
    }

    private function callGloErs(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        try {
            $service = app(GloErsService::class);
            $res = $service->vendAirtime($phone, $amount, $reference);
            return [
                'success'   => $res['success'] ?? false,
                'reference' => $res['tx_id'] ?? $reference,
                'response'  => $res,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }

    private function callMtnErs(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        try {
            $service = app(MtnErsService::class);
            $res = $service->vendAirtime($phone, $amount, $reference);
            return [
                'success'   => $res['success'] ?? false,
                'reference' => $res['transaction_id'] ?? $reference,
                'response'  => $res,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }

    private function callVtpass(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        try {
            $serviceIds = ['mtn' => 'mtn', 'glo' => 'glo', 'airtel' => 'airtel', 'etisalat' => 'etisalat'];
            $res = Http::withHeaders([
                'api-key' => AppSetting::get('vtpass_api_key'),
                'secret-key' => AppSetting::get('vtpass_secret_key'),
            ])->post('https://vtpass.com/api/pay', [
                'request_id' => $reference,
                'serviceID'  => $serviceIds[$network->network_key] ?? $network->network_key,
                'amount'     => $amount,
                'phone'      => $phone,
            ]);

            $body = $res->json() ?? [];
            $code = $body['code'] ?? null;
            $success = ($code === '000');

            return [
                'success'   => $success,
                'reference' => $body['requestId'] ?? $reference,
                'response'  => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }

    private function callClubkonnect(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        try {
            $netCodes = ['mtn' => '01', 'glo' => '02', 'airtel' => '03', 'etisalat' => '04'];
            $res = Http::get('https://www.nellobytesystems.com/APIAirtimeV1.asp', [
                'UserID'    => AppSetting::get('clubkonnect_user_id'),
                'APIKey'    => AppSetting::get('clubkonnect_api_key'),
                'MobileNetwork' => $netCodes[$network->network_key] ?? '01',
                'Amount'    => $amount,
                'MobileNo'  => $phone,
                'RequestID' => $reference,
            ]);

            $body = $res->json() ?? [];
            $status = $body['status'] ?? '';
            $success = str_contains(strtolower($status), 'order_received') || str_contains(strtolower($status), 'success');

            return [
                'success'   => $success,
                'reference' => $body['orderid'] ?? $reference,
                'response'  => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }

    private function callAutopilot(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        try {
            $netIds = ['mtn' => 1, 'glo' => 3, 'airtel' => 2, 'etisalat' => 4];
            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . AppSetting::get('autopilot_api_key'),
            ])->post(config('services.autopilot.base_url', 'https://autopilot.com.ng/api/v1') . '/airtime', [
                'network'     => $netIds[$network->network_key] ?? 1,
                'amount'      => $amount,
                'phone'       => $phone,
                'airtimeType' => 'VTU',
                'reference'   => $reference,
            ]);

            $body = $res->json() ?? [];
            $success = ($body['status'] ?? false) === true || ($body['code'] ?? '') === '00';

            return [
                'success'   => $success,
                'reference' => $body['reference'] ?? $reference,
                'response'  => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }

    private function callEasyaccess(NetworkAirtime $network, float $amount, string $phone, string $reference): array
    {
        try {
            $netIds = ['mtn' => 01, 'glo' => 02, 'airtel' => 03, 'etisalat' => 04];
            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . AppSetting::get('easyaccess_api_key'),
            ])->post('https://easyaccess.com.ng/api/airtime.php', [
                'network'   => $netIds[$network->network_key] ?? 01,
                'amount'    => $amount,
                'mobileno'  => $phone,
                'airtimetype' => 'VTU',
                'client_reference' => $reference,
            ]);

            $body = $res->json() ?? [];
            $success = str_contains(strtolower($body['message'] ?? ''), 'successful') || ($body['success'] ?? false) === true;

            return [
                'success'   => $success,
                'reference' => $body['reference'] ?? $reference,
                'response'  => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'reference' => null, 'response' => ['message' => $e->getMessage()]];
        }
    }
}

<?php

namespace App\Http\Controllers\Api\v1\BulkSms;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\BulkSmsCampaign;
use App\Services\BulkSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BulkSmsApiController extends Controller
{
    /**
     * Get active Bulk SMS pricing rates, sender guidelines, and service status.
     * Accessible to both authenticated users and public apps.
     */
    public function pricing(Request $request): JsonResponse
    {
        $enabled = AppSetting::get('service_bulksms', '1') === '1';
        $user    = auth('sanctum')->user();
        $rate    = BulkSmsService::getUnitRate($user);

        return response()->json([
            'status'  => true,
            'success' => true,
            'message' => 'Bulk SMS pricing and configuration retrieved successfully.',
            'data'    => [
                'enabled'            => $enabled,
                'user_tier'          => $user ? ($user->isAgent() ? 'Agent' : 'User') : 'Guest',
                'unit_rate'          => $rate,
                'formatted_rate'     => '₦' . number_format($rate, 2),
                'default_sender_id'  => BulkSmsService::sanitizeSenderId(null),
                'max_sender_length'  => 11,
                'rules'              => [
                    'gsm_7bit' => [
                        'single_page_limit' => 160,
                        'multi_page_limit'  => 153,
                    ],
                    'unicode' => [
                        'single_page_limit' => 70,
                        'multi_page_limit'  => 67,
                    ],
                    'allowed_sender_pattern' => 'Alphanumeric only, no spaces or special symbols, max 11 characters',
                ],
            ],
        ], $enabled ? 200 : 503);
    }

    /**
     * Real-time calculation preview for recipients, GSM pages, and total wallet debit.
     */
    public function preview(Request $request): JsonResponse
    {
        if (AppSetting::get('service_bulksms', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'success' => false,
                'message' => 'Bulk SMS service is currently disabled.',
            ], 503);
        }

        $request->validate([
            'message'    => ['required', 'string', 'min:1', 'max:1000'],
            'recipients' => ['required'],
        ]);

        $user       = auth()->user();
        $gsmInfo    = BulkSmsService::calculateGsmPages((string) $request->input('message'));
        $parsed     = BulkSmsService::parseAndSanitizeRecipients($request->input('recipients'));
        $unitRate   = BulkSmsService::getUnitRate($user);
        $totalCost  = (float) ($parsed['total_valid'] * $gsmInfo['pages'] * $unitRate);
        $walletBal  = (float) ($user->wallet?->balance ?? 0);

        return response()->json([
            'status'  => true,
            'success' => true,
            'message' => 'Campaign preview generated successfully.',
            'data'    => [
                'characters'            => $gsmInfo['characters'],
                'pages'                 => $gsmInfo['pages'],
                'is_unicode'            => $gsmInfo['is_unicode'],
                'remaining_chars'       => $gsmInfo['remaining_chars'],
                'chars_per_page'        => $gsmInfo['chars_per_page'],
                'total_valid'           => $parsed['total_valid'],
                'total_invalid'         => $parsed['total_invalid'],
                'duplicates_removed'    => $parsed['duplicates_removed'],
                'unit_rate'             => $unitRate,
                'total_cost'            => $totalCost,
                'formatted_cost'        => '₦' . number_format($totalCost, 2),
                'wallet_balance'        => $walletBal,
                'formatted_balance'     => '₦' . number_format($walletBal, 2),
                'has_sufficient_balance'=> ($walletBal >= $totalCost),
            ],
        ]);
    }

    /**
     * Dispatch Bulk SMS campaign with Transaction PIN verification.
     */
    public function send(Request $request): JsonResponse
    {
        if (AppSetting::get('service_bulksms', '1') !== '1') {
            return response()->json([
                'status'  => false,
                'success' => false,
                'message' => 'Bulk SMS service is currently disabled.',
            ], 503);
        }

        $request->validate([
            'sender_id'       => ['required', 'string', 'max:11'],
            'message'         => ['required', 'string', 'min:1', 'max:1000'],
            'recipients'      => ['required'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user = auth()->user();

        // 1. PIN verification
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json([
                'status'    => false,
                'success'   => false,
                'pin_error' => true,
                'message'   => 'Incorrect transaction PIN. Please try again.',
            ], 422);
        }

        // 2. Parse & sanitize recipient numbers
        $parsed = BulkSmsService::parseAndSanitizeRecipients($request->recipients);
        if ($parsed['total_valid'] === 0) {
            return response()->json([
                'status'  => false,
                'success' => false,
                'message' => 'No valid phone numbers found. Please provide valid Nigerian mobile numbers.',
            ], 422);
        }

        // 3. Process campaign (Atomic debit, provider call, logging, refund on failure)
        $result = BulkSmsService::processCampaign(
            $user,
            $request->sender_id,
            $parsed['valid'],
            $request->message
        );

        if (!$result['success']) {
            return response()->json([
                'status'  => false,
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        /** @var BulkSmsCampaign $campaign */
        $campaign = $result['campaign'];

        return response()->json([
            'status'  => true,
            'success' => true,
            'message' => $result['message'],
            'data'    => [
                'campaign_id'     => $campaign->id,
                'reference'       => $campaign->reference,
                'sender_id'       => $campaign->sender_id,
                'recipient_count' => $campaign->recipient_count,
                'pages'           => $campaign->pages,
                'unit_rate'       => (float) $campaign->unit_rate,
                'total_amount'    => (float) $campaign->total_amount,
                'status'          => $campaign->status,
                'balance_after'   => $result['balance_after'],
                'formatted_balance'=> '₦' . number_format($result['balance_after'], 2),
                'created_at'      => $campaign->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get paginated campaign history for authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $perPage = min((int) $request->input('per_page', 15), 50);

        $campaigns = BulkSmsCampaign::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);

        $transformed = $campaigns->getCollection()->map(function (BulkSmsCampaign $c) {
            $recips = (array) $c->recipients;
            return [
                'id'               => $c->id,
                'reference'        => $c->reference,
                'sender_id'        => $c->sender_id,
                'message_preview'  => mb_substr($c->message, 0, 80) . (mb_strlen($c->message) > 80 ? '...' : ''),
                'recipient_count'  => $c->recipient_count,
                'pages'            => $c->pages,
                'unit_rate'        => (float) $c->unit_rate,
                'total_amount'     => (float) $c->total_amount,
                'status'           => $c->status,
                'sample_recipients'=> array_slice($recips, 0, 3),
                'created_at'       => $c->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'status'  => true,
            'success' => true,
            'message' => 'Bulk SMS campaigns retrieved successfully.',
            'data'    => [
                'campaigns'    => $transformed,
                'pagination'   => [
                    'current_page' => $campaigns->currentPage(),
                    'last_page'    => $campaigns->lastPage(),
                    'per_page'     => $campaigns->perPage(),
                    'total'        => $campaigns->total(),
                    'has_more'     => $campaigns->hasMorePages(),
                ],
            ],
        ]);
    }

    /**
     * Get full details of a specific campaign by reference or ID.
     */
    public function campaignDetails(Request $request, string $reference): JsonResponse
    {
        $user = auth()->user();

        $campaign = BulkSmsCampaign::where('user_id', $user->id)
            ->where(function ($q) use ($reference) {
                $q->where('reference', $reference)
                  ->orWhere('id', $reference);
            })
            ->first();

        if (!$campaign) {
            return response()->json([
                'status'  => false,
                'success' => false,
                'message' => 'Bulk SMS campaign not found.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'success' => true,
            'message' => 'Campaign details retrieved successfully.',
            'data'    => [
                'id'              => $campaign->id,
                'reference'       => $campaign->reference,
                'sender_id'       => $campaign->sender_id,
                'message'         => $campaign->message,
                'recipient_count' => $campaign->recipient_count,
                'recipients'      => $campaign->recipients,
                'pages'           => $campaign->pages,
                'unit_rate'       => (float) $campaign->unit_rate,
                'total_amount'    => (float) $campaign->total_amount,
                'status'          => $campaign->status,
                'created_at'      => $campaign->created_at?->toIso8601String(),
            ],
        ]);
    }
}

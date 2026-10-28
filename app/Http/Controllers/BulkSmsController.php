<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\BulkSmsCampaign;
use App\Services\BulkSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;

class BulkSmsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                if (AppSetting::get('service_bulksms', '1') !== '1') {
                    if ($request->expectsJson()) {
                        return response()->json(['success' => false, 'message' => 'Bulk SMS service is currently disabled.'], 503);
                    }
                    return redirect()->route('dashboard')->with('error', 'Bulk SMS service is currently disabled.');
                }
                return $next($request);
            }
        ];
    }

    /**
     * Show the Bulk SMS dispatch portal & campaign history.
     */
    public function index(): View|RedirectResponse
    {
        $user          = auth()->user();
        $rate          = BulkSmsService::getUnitRate($user);
        $defaultSender = BulkSmsService::sanitizeSenderId(null);
        $campaigns     = BulkSmsCampaign::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('services.bulksms', compact('user', 'rate', 'defaultSender', 'campaigns'));
    }

    /**
     * Real-time calculation preview for recipients, GSM pages, and total wallet cost.
     */
    public function preview(Request $request): JsonResponse
    {
        $user       = auth()->user();
        $rawMessage = (string) $request->input('message', '');
        $rawRecips  = (string) $request->input('recipients', '');

        $gsmInfo   = BulkSmsService::calculateGsmPages($rawMessage);
        $parsed    = BulkSmsService::parseAndSanitizeRecipients($rawRecips);
        $unitRate  = BulkSmsService::getUnitRate($user);
        $totalCost = (float) ($parsed['total_valid'] * $gsmInfo['pages'] * $unitRate);

        $hasSufficientBalance = $user->wallet && ($user->wallet->balance >= $totalCost);

        return response()->json([
            'success'               => true,
            'characters'            => $gsmInfo['characters'],
            'pages'                 => $gsmInfo['pages'],
            'is_unicode'            => $gsmInfo['is_unicode'],
            'remaining_chars'       => $gsmInfo['remaining_chars'],
            'total_valid'           => $parsed['total_valid'],
            'total_invalid'         => $parsed['total_invalid'],
            'duplicates_removed'    => $parsed['duplicates_removed'],
            'unit_rate'             => $unitRate,
            'total_cost'            => $totalCost,
            'formatted_cost'        => '₦' . number_format($totalCost, 2),
            'has_sufficient_balance'=> $hasSufficientBalance,
            'wallet_balance'        => (float) ($user->wallet?->balance ?? 0),
        ]);
    }

    /**
     * Dispatch Bulk SMS campaign.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'sender_id'       => ['required', 'string', 'max:11'],
            'message'         => ['required', 'string', 'min:1', 'max:1000'],
            'recipients'      => ['required', 'string'],
            'transaction_pin' => ['required', 'digits:4'],
        ]);

        $user = auth()->user();
        if (!$user->verifyPin($request->transaction_pin)) {
            return response()->json([
                'success'   => false,
                'message'   => 'Incorrect transaction PIN. Please try again.',
                'pin_error' => true,
            ], 422);
        }

        $parsed = BulkSmsService::parseAndSanitizeRecipients($request->recipients);
        if ($parsed['total_valid'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No valid phone numbers found. Please provide valid Nigerian mobile numbers.',
            ], 422);
        }

        $result = BulkSmsService::processCampaign(
            $user,
            $request->sender_id,
            $parsed['valid'],
            $request->message
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success'       => true,
            'message'       => $result['message'],
            'balance'       => '₦' . number_format($result['balance_after'], 2),
            'campaign_id'   => $result['campaign']->id,
            'reference'     => $result['campaign']->reference,
        ]);
    }
}

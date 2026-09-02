<?php

namespace App\Http\Controllers\Api\v1\Wallet;

use App\Http\Controllers\Controller;
use App\Models\ServiceTransaction;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletApiController extends Controller
{
    /**
     * Retrieve current authenticated user's wallet balance and summary.
     */
    public function balance(Request $request): JsonResponse
    {
        $user   = auth()->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'status'  => false,
                'message' => 'Wallet record not found.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Wallet balance retrieved successfully.',
            'data'    => [
                'user_id'      => $user->id,
                'username'     => $user->username,
                'balance'      => (float) $wallet->balance,
                'total_funded' => (float) $wallet->total_funded,
                'total_spent'  => (float) $wallet->total_spent,
                'currency'     => 'NGN',
                'formatted'    => '₦' . number_format((float) $wallet->balance, 2),
            ],
        ]);
    }

    /**
     * Fetch paginated history of wallet transactions.
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = auth()->user();

        $type        = $request->query('type');        // 'credit' or 'debit'
        $serviceType = $request->query('service_type'); // 'airtime', 'data', 'electricity', 'cable', 'epin', 'funding', 'refund'
        $status      = $request->query('status');       // 'success', 'failed', 'pending'
        $search      = $request->query('search');
        $perPage     = min(100, max(10, (int) $request->query('per_page', 15)));

        $query = WalletTransaction::where('user_id', $user->id);

        if (!empty($type) && in_array($type, ['credit', 'debit'])) {
            $query->where('type', $type);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($serviceType)) {
            $query->where(function ($q) use ($serviceType) {
                $q->whereJsonContains('metadata->service', $serviceType)
                  ->orWhereJsonContains('metadata->service_type', $serviceType)
                  ->orWhereJsonContains('metadata->source', $serviceType);
            });
        }

        $paginator = $query->latest()->paginate($perPage);

        $items = collect($paginator->items())->map(function (WalletTransaction $tx) {
            return [
                'id'             => $tx->id,
                'reference'      => $tx->reference,
                'type'           => $tx->type,
                'amount'         => (float) $tx->amount,
                'balance_before' => (float) $tx->balance_before,
                'balance_after'  => (float) $tx->balance_after,
                'description'    => $tx->description,
                'status'         => $tx->status ?? 'success',
                'service_type'   => $tx->metadata['service'] ?? $tx->metadata['service_type'] ?? $tx->metadata['source'] ?? ($tx->isCredit() ? 'funding' : 'debit'),
                'metadata'       => $tx->metadata,
                'date'           => $tx->created_at ? $tx->created_at->toDateTimeString() : null,
                'human_date'     => $tx->created_at ? $tx->created_at->diffForHumans() : null,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Wallet transactions retrieved successfully.',
            'data'    => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'transactions' => $items,
            ],
        ]);
    }

    /**
     * Get detailed receipt payload for a single transaction.
     */
    public function transactionDetails(Request $request, string $reference): JsonResponse
    {
        $user = auth()->user();

        $tx = WalletTransaction::where('user_id', $user->id)
            ->where('reference', $reference)
            ->first();

        if (!$tx) {
            return response()->json([
                'status'  => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        // Fetch corresponding ServiceTransaction if applicable
        $serviceTx = ServiceTransaction::where('user_id', $user->id)
            ->where('reference', $reference)
            ->first();

        return response()->json([
            'status'  => true,
            'message' => 'Transaction details retrieved successfully.',
            'data'    => [
                'reference'      => $tx->reference,
                'type'           => $tx->type,
                'amount'         => (float) $tx->amount,
                'balance_before' => (float) $tx->balance_before,
                'balance_after'  => (float) $tx->balance_after,
                'description'    => $tx->description,
                'status'         => $tx->status ?? 'success',
                'service_type'   => $tx->metadata['service'] ?? $tx->metadata['service_type'] ?? ($tx->isCredit() ? 'funding' : 'service'),
                'recipient'      => $serviceTx ? $serviceTx->recipient : ($tx->metadata['phone'] ?? $tx->metadata['meter'] ?? $tx->metadata['smartcard'] ?? null),
                'provider'       => $serviceTx ? strtoupper($serviceTx->provider) : null,
                'api_reference'  => $serviceTx ? $serviceTx->api_reference : null,
                'metadata'       => $tx->metadata,
                'created_at'     => $tx->created_at ? $tx->created_at->toDateTimeString() : null,
            ],
        ]);
    }
}

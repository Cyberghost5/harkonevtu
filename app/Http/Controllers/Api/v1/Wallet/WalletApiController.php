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

        // Exclude refund entries unless explicitly requested
        if ($serviceType !== 'refund') {
            $refundedOriginalRefs = WalletTransaction::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('reference', 'like', 'REFUND_%')
                      ->orWhere('description', 'like', 'Refund%')
                      ->orWhereJsonContains('metadata->type', 'refund');
                })
                ->pluck('reference')
                ->map(fn ($r) => str_replace('REFUND_', '', $r))
                ->filter()
                ->values()
                ->all();

            $query->where('reference', 'not like', 'REFUND_%')
                  ->where('description', 'not like', 'Refund%')
                  ->where(function ($q) {
                      $q->whereNull('metadata->type')
                        ->orWhere('metadata->type', '!=', 'refund');
                  });

            if (!empty($refundedOriginalRefs)) {
                $query->whereNotIn('reference', $refundedOriginalRefs);
            }
        }

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

        // Fetch corresponding ServiceTransactions for these wallet transactions to extract tokens/units/recipients
        $references = collect($paginator->items())->pluck('reference')->filter()->all();
        $txIds      = collect($paginator->items())->pluck('id')->filter()->all();

        $serviceTxs = ServiceTransaction::where('user_id', $user->id)
            ->where(function ($q) use ($references, $txIds) {
                if (!empty($references)) {
                    $q->whereIn('reference', $references);
                }
                if (!empty($txIds)) {
                    $q->orWhereIn('wallet_transaction_id', $txIds);
                }
            })
            ->get();

        $serviceTxsByRef = $serviceTxs->keyBy('reference');
        $serviceTxsById  = $serviceTxs->filter(fn($st) => !empty($st->wallet_transaction_id))->keyBy('wallet_transaction_id');

        $items = collect($paginator->items())->map(function (WalletTransaction $tx) use ($serviceTxsByRef, $serviceTxsById) {
            $meta = is_array($tx->metadata) ? $tx->metadata : [];
            $stx  = $serviceTxsByRef->get($tx->reference) ?? $serviceTxsById->get($tx->id);

            $token = null;
            if ($stx && $stx->token && strtoupper(trim($stx->token)) !== 'N/A') {
                $token = $stx->token;
            } elseif (!empty($meta['token']) && strtoupper(trim((string)$meta['token'])) !== 'N/A') {
                $token = trim((string)$meta['token']);
            } elseif (!empty($meta['purchased_code']) && strtoupper(trim((string)$meta['purchased_code'])) !== 'N/A') {
                $token = preg_replace('/^Token\s*[:\-]\s*/i', '', trim((string)$meta['purchased_code']));
            }
            if ($token) {
                $meta['token'] = $token;
            }

            $units = null;
            if ($stx && $stx->units && strtoupper(trim($stx->units)) !== 'N/A') {
                $units = $stx->units;
            } elseif (!empty($meta['units']) && strtoupper(trim((string)$meta['units'])) !== 'N/A') {
                $units = trim((string)$meta['units']);
            }
            if ($units) {
                $meta['units'] = $units;
            }

            if (!isset($meta['recipient']) && $stx && $stx->recipient) {
                $meta['recipient'] = $stx->recipient;
            }

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
                'token'          => $token,
                'units'          => $units,
                'metadata'       => $meta,
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
            ->where(function ($q) use ($tx) {
                $q->where('reference', $tx->reference)
                  ->orWhere('wallet_transaction_id', $tx->id);
            })
            ->first();

        $meta  = is_array($tx->metadata) ? $tx->metadata : [];
        $token = null;
        if ($serviceTx && $serviceTx->token && strtoupper(trim($serviceTx->token)) !== 'N/A') {
            $token = $serviceTx->token;
        } elseif (!empty($meta['token']) && strtoupper(trim((string)$meta['token'])) !== 'N/A') {
            $token = trim((string)$meta['token']);
        } elseif (!empty($meta['purchased_code']) && strtoupper(trim((string)$meta['purchased_code'])) !== 'N/A') {
            $token = preg_replace('/^Token\s*[:\-]\s*/i', '', trim((string)$meta['purchased_code']));
        }
        if ($token) {
            $meta['token'] = $token;
        }

        $units = null;
        if ($serviceTx && $serviceTx->units && strtoupper(trim($serviceTx->units)) !== 'N/A') {
            $units = $serviceTx->units;
        } elseif (!empty($meta['units']) && strtoupper(trim((string)$meta['units'])) !== 'N/A') {
            $units = trim((string)$meta['units']);
        }
        if ($units) {
            $meta['units'] = $units;
        }

        if (!isset($meta['recipient']) && $serviceTx && $serviceTx->recipient) {
            $meta['recipient'] = $serviceTx->recipient;
        }

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
                'token'          => $token,
                'units'          => $units,
                'metadata'       => $meta,
                'created_at'     => $tx->created_at ? $tx->created_at->toDateTimeString() : null,
            ],
        ]);
    }
}

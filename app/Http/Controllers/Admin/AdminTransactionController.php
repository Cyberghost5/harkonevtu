<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceTransaction;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'services'); // 'services' | 'wallet'

        if ($tab === 'wallet') {
            $query = WalletTransaction::with('user')->latest();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('reference', 'like', "%$s%")
                      ->orWhere('description', 'like', "%$s%")
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$s%")
                          ->orWhere('email', 'like', "%$s%")
                          ->orWhere('phone', 'like', "%$s%"));
                });
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transactions = $query->paginate(25)->withQueryString();
            $serviceTypes = collect([]);
        } else {
            $query = ServiceTransaction::with('user')->latest();

            if ($request->filled('service')) {
                $query->where('service_type', $request->service);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('reference', 'like', "%$s%")
                      ->orWhere('recipient', 'like', "%$s%")
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$s%")
                          ->orWhere('email', 'like', "%$s%")
                          ->orWhere('phone', 'like', "%$s%"));
                });
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transactions = $query->paginate(25)->withQueryString();

            $serviceTypes = ServiceTransaction::select('service_type')
                ->distinct()->pluck('service_type');
        }

        return view('admin.transactions.index', compact('transactions', 'serviceTypes', 'tab'));
    }

    public function show(Request $request, string $type, string $id)
    {
        $isWallet = in_array(strtolower($type), ['wallet', 'wallet_transaction', 'wallet-transaction']);

        if ($isWallet) {
            $transaction = WalletTransaction::with(['user.wallet', 'user.virtualAccounts'])
                ->where('id', $id)
                ->orWhere('reference', $id)
                ->firstOrFail();
            $transactionType = 'wallet';
        } else {
            $transaction = ServiceTransaction::with(['user.wallet', 'user.virtualAccounts', 'walletTransaction'])
                ->where('id', $id)
                ->orWhere('reference', $id)
                ->firstOrFail();
            $transactionType = 'service';
        }

        $user = $transaction->user;

        // Search ApiLogs matching the transaction
        $ref    = $transaction->reference;
        $apiRef = $transaction->api_reference ?? null;

        $apiLogs = \App\Models\ApiLog::where(function ($q) use ($ref, $apiRef, $transaction) {
            $q->where('reference', $ref);
            if ($apiRef) {
                $q->orWhere('reference', $apiRef);
            }
            $q->orWhere('payload', 'like', '%' . $ref . '%')
              ->orWhere('response', 'like', '%' . $ref . '%');

            if (is_array($transaction->metadata ?? null)) {
                if (!empty($transaction->metadata['trans_id'])) {
                    $q->orWhere('reference', $transaction->metadata['trans_id']);
                }
                if (!empty($transaction->metadata['session_id'])) {
                    $q->orWhere('reference', $transaction->metadata['session_id']);
                }
            }
        })
        ->orderByDesc('id')
        ->get();

        // Proximity fallback if no logs matched directly by reference
        if ($apiLogs->isEmpty() && $user && $transaction->created_at) {
            $apiLogs = \App\Models\ApiLog::where('user_id', $user->id)
                ->whereBetween('created_at', [
                    $transaction->created_at->copy()->subMinutes(10),
                    $transaction->created_at->copy()->addMinutes(10),
                ])
                ->orderByDesc('id')
                ->get();
        }

        // Linked transaction (ServiceTransaction <-> WalletTransaction)
        $linkedTransaction = null;
        if ($transactionType === 'wallet') {
            $linkedTransaction = ServiceTransaction::where('wallet_transaction_id', $transaction->id)
                ->orWhere('reference', $transaction->reference)
                ->first();
        } else {
            $linkedTransaction = $transaction->walletTransaction;
        }

        return view('admin.transactions.show', compact('transaction', 'transactionType', 'user', 'apiLogs', 'linkedTransaction'));
    }

    public function viewByReference(string $reference)
    {
        $ref = trim($reference);

        // 1. Direct match on WalletTransaction reference
        $walletTx = WalletTransaction::where('reference', $ref)->first();
        if ($walletTx) {
            return redirect()->route('admin.transactions.show', ['type' => 'wallet', 'id' => $walletTx->id]);
        }

        // 2. Direct match on ServiceTransaction reference or api_reference
        $serviceTx = ServiceTransaction::where('reference', $ref)
            ->orWhere('api_reference', $ref)
            ->first();
        if ($serviceTx) {
            return redirect()->route('admin.transactions.show', ['type' => 'service', 'id' => $serviceTx->id]);
        }

        // 3. Fallback: Search in WalletTransaction metadata
        $walletMetaTx = WalletTransaction::where('metadata', 'like', '%' . $ref . '%')->first();
        if ($walletMetaTx) {
            return redirect()->route('admin.transactions.show', ['type' => 'wallet', 'id' => $walletMetaTx->id]);
        }

        // 4. Fallback: Check if this reference exists in ApiLog and has an associated transaction
        $apiLog = \App\Models\ApiLog::where('reference', $ref)->latest('id')->first();
        if ($apiLog) {
            $associated = $apiLog->resolveTransaction();
            if ($associated) {
                return redirect()->route('admin.transactions.show', ['type' => $associated['type'], 'id' => $associated['id']]);
            }
        }

        return redirect()->route('admin.transactions.index')
            ->with('error', 'No transaction found matching reference: ' . $reference);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ServiceTransaction;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $tab  = $request->input('tab', 'services'); // 'services' | 'wallet'

        // ── Service Transactions ──────────────────────────────────────────────
        $serviceQuery = ServiceTransaction::where('user_id', $user->id)->latest();

        if ($request->filled('service')) {
            $serviceQuery->where('service_type', $request->service);
        }
        if ($request->filled('date_from')) {
            $serviceQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $serviceQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $serviceQuery->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                  ->orWhere('recipient', 'like', "%$s%")
                  ->orWhere('provider', 'like', "%$s%");
            });
        }

        // ── Wallet Transactions ───────────────────────────────────────────────
        // Collect refund references so we can exclude both the refund credit
        // and its paired orphaned debit from the wallet history.
        $refundedOriginalRefs = WalletTransaction::where('user_id', $user->id)
            ->where('reference', 'like', 'REFUND_%')
            ->pluck('reference')
            ->map(fn ($r) => substr($r, 7))
            ->all();

        $walletQuery = WalletTransaction::where('user_id', $user->id)
            ->where('reference', 'not like', 'REFUND_%')
            ->when($refundedOriginalRefs, fn ($q) =>
                $q->whereNotIn('reference', $refundedOriginalRefs)
            )
            ->latest();

        if ($request->filled('date_from')) {
            $walletQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $walletQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $walletQuery->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%");
            });
        }

        $transactions  = ($tab === 'wallet' ? $walletQuery : $serviceQuery)
                            ->paginate(20)
                            ->withQueryString();

        $serviceTxCount = ServiceTransaction::where('user_id', $user->id)->count();
        $walletTxCount  = WalletTransaction::where('user_id', $user->id)
                            ->where('reference', 'not like', 'REFUND_%')
                            ->when($refundedOriginalRefs, fn ($q) =>
                                $q->whereNotIn('reference', $refundedOriginalRefs)
                            )->count();

        $serviceTypes = ServiceTransaction::where('user_id', $user->id)
            ->distinct()->pluck('service_type');

        return view('transactions.index', compact(
            'transactions',
            'serviceTypes',
            'tab',
            'serviceTxCount',
            'walletTxCount',
        ));
    }
}

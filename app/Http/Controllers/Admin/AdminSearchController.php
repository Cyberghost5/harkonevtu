<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSearchController extends Controller
{
    /**
     * Dedicated full search results page.
     * GET /admin/search?q=...
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $tab = $request->input('tab', 'all'); // 'all', 'users', 'transactions'

        $users = collect([]);
        $serviceTransactions = collect([]);
        $walletTransactions = collect([]);

        if ($q !== '') {
            // Search Users
            $users = User::with('wallet')
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                          ->orWhere('username', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%")
                          ->orWhere('phone', 'like', "%{$q}%");
                })
                ->latest()
                ->take(30)
                ->get();

            // Search Service Transactions
            $serviceTransactions = ServiceTransaction::with('user')
                ->where(function ($query) use ($q) {
                    $query->where('reference', 'like', "%{$q}%")
                          ->orWhere('recipient', 'like', "%{$q}%")
                          ->orWhere('service_type', 'like', "%{$q}%")
                          ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%")
                              ->orWhere('email', 'like', "%{$q}%")
                              ->orWhere('phone', 'like', "%{$q}%"));
                })
                ->latest()
                ->take(30)
                ->get();

            // Search Wallet Transactions (e.g. DVA funding, manual transfer, admin debit/credit)
            $walletTransactions = WalletTransaction::with('user')
                ->where(function ($query) use ($q) {
                    $query->where('reference', 'like', "%{$q}%")
                          ->orWhere('description', 'like', "%{$q}%")
                          ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%")
                              ->orWhere('email', 'like', "%{$q}%")
                              ->orWhere('phone', 'like', "%{$q}%"));
                })
                ->latest()
                ->take(30)
                ->get();
        }

        return view('admin.search.index', compact(
            'q',
            'tab',
            'users',
            'serviceTransactions',
            'walletTransactions'
        ));
    }

    /**
     * Fast AJAX endpoint for live search dropdown in the admin header.
     * GET /admin/search/live?q=...
     */
    public function live(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'status'       => true,
                'users'        => [],
                'transactions' => [],
            ]);
        }

        // Matching Users
        $matchedUsers = User::with('wallet')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('username', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
            })
            ->latest()
            ->take(5)
            ->get()
            ->map(function (User $u) {
                return [
                    'id'           => $u->id,
                    'name'         => $u->name,
                    'username'     => $u->username,
                    'email'        => $u->email,
                    'phone'        => $u->phone,
                    'balance'      => number_format((float) ($u->wallet?->balance ?? 0), 2),
                    'is_admin'     => (bool) $u->is_admin,
                    'is_active'    => (bool) $u->is_active,
                    'avatar'       => $u->avatar,
                    'initials'     => $u->initials(),
                    'url'          => route('admin.users.show', $u->id),
                ];
            });

        // Matching Service Transactions
        $serviceTxs = ServiceTransaction::with('user')
            ->where(function ($query) use ($q) {
                $query->where('reference', 'like', "%{$q}%")
                      ->orWhere('recipient', 'like', "%{$q}%")
                      ->orWhere('service_type', 'like', "%{$q}%")
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%")
                          ->orWhere('phone', 'like', "%{$q}%"));
            })
            ->latest()
            ->take(4)
            ->get()
            ->map(function (ServiceTransaction $tx) {
                return [
                    'id'               => $tx->id,
                    'reference'        => $tx->reference,
                    'category'         => 'service',
                    'service_type'     => ucfirst(str_replace('_', ' ', $tx->service_type)),
                    'recipient'        => $tx->recipient,
                    'amount'           => number_format((float) $tx->amount, 2),
                    'status'           => $tx->status,
                    'date'             => $tx->created_at->format('d M Y, H:i'),
                    'user_name'        => optional($tx->user)->name ?? 'User #' . $tx->user_id,
                    'url'              => route('admin.transactions.index', ['search' => $tx->reference]),
                ];
            });

        // Matching Wallet Transactions (if we have room)
        $walletTxs = WalletTransaction::with('user')
            ->where(function ($query) use ($q) {
                $query->where('reference', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%")
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%")
                          ->orWhere('phone', 'like', "%{$q}%"));
            })
            ->latest()
            ->take(3)
            ->get()
            ->map(function (WalletTransaction $wtx) {
                return [
                    'id'               => $wtx->id,
                    'reference'        => $wtx->reference,
                    'category'         => 'wallet',
                    'service_type'     => ucfirst($wtx->type) . ' Wallet',
                    'recipient'        => $wtx->description,
                    'amount'           => number_format((float) $wtx->amount, 2),
                    'status'           => $wtx->status,
                    'date'             => $wtx->created_at->format('d M Y, H:i'),
                    'user_name'        => optional($wtx->user)->name ?? 'User #' . $wtx->user_id,
                    'url'              => route('admin.transactions.index', ['tab' => 'wallet', 'search' => $wtx->reference]),
                ];
            });

        $combinedTxs = $serviceTxs->merge($walletTxs)->take(5);

        return response()->json([
            'status'       => true,
            'query'        => $q,
            'users'        => $matchedUsers,
            'transactions' => $combinedTxs,
        ]);
    }
}

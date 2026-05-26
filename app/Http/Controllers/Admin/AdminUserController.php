<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('wallet')
            ->where('is_admin', false)
            ->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('username', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%");
            });
        }

        if ($request->filled('type')) {
            $query->where('user_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load('wallet');
        $transactions = $user->wallet?->transactions()->latest()->paginate(15);
        $serviceTransactions = $user->serviceTransactions()->latest()->take(10)->get();

        return view('admin.users.show', compact('user', 'transactions', 'serviceTransactions'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'user_type' => ['sometimes', Rule::in(['user', 'agent'])],
            'is_admin'  => ['sometimes', 'boolean'],
        ]);

        $user->update($validated);

        return back()->with('success', 'User updated successfully.');
    }

    public function adjustWallet(Request $request, User $user)
    {
        $validated = $request->validate([
            'type'        => ['required', Rule::in(['credit', 'debit'])],
            'amount'      => ['required', 'numeric', 'min:1', 'max:1000000'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $wallet = $user->wallet;
        if (!$wallet) {
            return back()->with('error', 'User has no wallet.');
        }

        $amount = (float) $validated['amount'];

        if ($validated['type'] === 'debit' && $wallet->balance < $amount) {
            return back()->with('error', 'Insufficient wallet balance for debit.');
        }

        DB::transaction(function () use ($wallet, $validated, $amount, $user) {
            $before = $wallet->balance;

            if ($validated['type'] === 'credit') {
                $wallet->increment('balance', $amount);
                $wallet->increment('total_funded', $amount);
            } else {
                $wallet->decrement('balance', $amount);
                $wallet->increment('total_spent', $amount);
            }

            $wallet->refresh();

            WalletTransaction::create([
                'user_id'        => $user->id,
                'wallet_id'      => $wallet->id,
                'type'           => $validated['type'],
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $wallet->balance,
                'description'    => '[Admin] ' . $validated['description'],
                'reference'      => 'ADM-' . strtoupper(uniqid()),
                'status'         => 'success',
            ]);
        });

        return back()->with('success', 'Wallet adjusted successfully.');
    }
}

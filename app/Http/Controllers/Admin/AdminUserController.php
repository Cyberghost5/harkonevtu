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

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'username'        => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'           => ['required', 'string', 'max:255', 'unique:users,phone'],
            'password'        => ['required', 'string', 'min:6'],
            'transaction_pin' => ['required', 'string', 'digits:4'],
            'user_type'       => ['required', Rule::in(['user', 'agent'])],
            'is_admin'        => ['required', 'boolean'],
            'is_active'       => ['required', 'boolean'],
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['transaction_pin'] = bcrypt($validated['transaction_pin']);
        $validated['email_verified_at'] = now();
        $validated['phone_verified_at'] = now();

        DB::transaction(function () use ($validated) {
            $user = User::create($validated);
            $user->wallet()->create([
                'balance' => 0.00,
                'total_funded' => 0.00,
                'total_spent' => 0.00,
            ]);
        });

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name'            => ['sometimes', 'required', 'string', 'max:255'],
            'username'        => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email'           => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'           => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'        => ['nullable', 'string', 'min:6'],
            'transaction_pin' => ['nullable', 'string', 'digits:4'],
            'user_type'       => ['sometimes', 'required', Rule::in(['user', 'agent'])],
            'is_active'       => ['sometimes', 'required', 'boolean'],
            'is_admin'        => ['sometimes', 'required', 'boolean'],
        ];

        $validated = $request->validate($rules);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (!empty($validated['transaction_pin'])) {
            $validated['transaction_pin'] = bcrypt($validated['transaction_pin']);
        } else {
            unset($validated['transaction_pin']);
        }

        $user->update($validated);

        if ($request->has('_redirect_to_index')) {
            return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
        }

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
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

    /**
     * Impersonate a user.
     */
    public function impersonate(User $user)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        // Store current admin's ID in the session
        session(['impersonator_id' => auth()->id()]);

        // Login as target user
        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'You are now impersonating ' . $user->name);
    }

    /**
     * Stop impersonating and return to admin.
     */
    public function stopImpersonate()
    {
        $impersonatorId = session('impersonator_id');
        if (!$impersonatorId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($impersonatorId);
        if (!$admin || !$admin->isAdmin()) {
            session()->forget('impersonator_id');
            return redirect()->route('dashboard');
        }

        // Log back in as the admin
        auth()->login($admin);
        session()->forget('impersonator_id');

        return redirect()->route('admin.users.index')->with('success', 'Returned to Admin Panel.');
    }
}

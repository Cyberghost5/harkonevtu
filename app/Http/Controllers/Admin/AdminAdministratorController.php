<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminAdministratorController extends Controller
{
    /**
     * List all staff administrators (Super Admin is excluded from this list).
     */
    public function index(Request $request)
    {
        $query = User::with(['wallet', 'assignedRole'])
            ->where('is_admin', true)
            ->where('is_super_admin', false);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('username', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $admins = $query->latest()->paginate(20)->withQueryString();
        $roles = Role::where('slug', '!=', 'super-admin')->orderBy('name')->get();

        return view('admin.admins.index', compact('admins', 'roles'));
    }

    /**
     * Show form to create or promote an admin.
     */
    public function create()
    {
        $roles = Role::where('slug', '!=', 'super-admin')->orderBy('name')->get();

        return view('admin.admins.create', compact('roles'));
    }

    /**
     * Store a new administrator account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'username'        => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'           => ['required', 'string', 'max:255', 'unique:users,phone'],
            'password'        => ['required', 'string', 'min:6'],
            'transaction_pin' => ['required', 'string', 'digits:4'],
            'role_id'         => ['nullable', 'exists:roles,id'],
            'is_active'       => ['required', 'boolean'],
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['transaction_pin'] = bcrypt($validated['transaction_pin']);
        $validated['email_verified_at'] = now();
        $validated['phone_verified_at'] = now();
        $validated['is_admin'] = true;
        $validated['is_super_admin'] = false;
        $validated['role'] = 'admin';
        $validated['user_type'] = 'normal';

        DB::transaction(function () use ($validated) {
            $admin = User::create($validated);
            $admin->wallet()->create([
                'balance'      => 0.00,
                'total_funded' => 0.00,
                'total_spent'  => 0.00,
            ]);
        });

        return redirect()->route('admin.admins.index')->with('success', 'Staff administrator created successfully.');
    }

    /**
     * Show form to edit an administrator.
     */
    public function edit(User $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.admins.index')->with('error', 'Super Administrator accounts must be edited from the profile or database.');
        }

        $roles = Role::where('slug', '!=', 'super-admin')->orderBy('name')->get();

        return view('admin.admins.edit', compact('user', 'roles'));
    }

    /**
     * Update an administrator account.
     */
    public function update(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Administrator accounts cannot be modified here.');
        }

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'username'        => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email'           => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'           => ['required', 'string', 'max:255', Rule::unique('users', 'phone')->ignore($user->id)],
            'password'        => ['nullable', 'string', 'min:6'],
            'transaction_pin' => ['nullable', 'string', 'digits:4'],
            'role_id'         => ['nullable', 'exists:roles,id'],
            'is_active'       => ['required', 'boolean'],
        ]);

        $updateData = [
            'name'      => $validated['name'],
            'username'  => $validated['username'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'],
            'role_id'   => $validated['role_id'] ?? null,
            'is_active' => $validated['is_active'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = bcrypt($validated['password']);
        }

        if (!empty($validated['transaction_pin'])) {
            $updateData['transaction_pin'] = bcrypt($validated['transaction_pin']);
        }

        $user->update($updateData);

        return redirect()->route('admin.admins.index')->with('success', "Administrator '{$user->name}' updated successfully.");
    }

    /**
     * Demote an administrator back to regular user.
     */
    public function demote(User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Admin accounts cannot be demoted.');
        }

        $user->update([
            'is_admin' => false,
            'role'     => 'user',
            'role_id'  => null,
        ]);

        return redirect()->route('admin.admins.index')->with('success', "{$user->name} has been demoted to a regular user.");
    }
}

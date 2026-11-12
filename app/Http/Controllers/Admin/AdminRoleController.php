<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminRoleController extends Controller
{
    /**
     * Display a listing of all roles.
     */
    public function index()
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->orderBy('is_system', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::all()->groupBy('module');

        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description'   => ['nullable', 'string', 'max:500'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $slug = Str::slug($validated['name']);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $role = Role::create([
            'name'        => $validated['name'],
            'slug'        => $slug,
            'description' => $validated['description'] ?? null,
            'is_system'   => false,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' created successfully with " . count($validated['permissions'] ?? []) . " permissions.");
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy('module');
        $rolePermissionIds = $role->permissions()->pluck('permissions.id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissionIds'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'description'   => ['nullable', 'string', 'max:500'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        // If it's a system role like super-admin, protect its slug
        $roleData = [
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ];

        if (!$role->is_system) {
            $roleData['slug'] = Str::slug($validated['name']);
        }

        $role->update($roleData);

        // Sync permissions
        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' updated successfully.");
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $assignedAdminsCount = $role->users()->count();
        if ($assignedAdminsCount > 0) {
            return back()->with('error', "Cannot delete role '{$role->name}' because it is currently assigned to {$assignedAdminsCount} administrator(s). Please reassign them before deleting.");
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' was deleted successfully.");
    }
}

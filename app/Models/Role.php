<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Role $role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // ─── Permission Helpers ───────────────────────────────────────────────────

    public function hasPermission(string $permissionSlug): bool
    {
        // Wildcard check
        if ($this->permissions->contains('slug', '*')) {
            return true;
        }

        // Direct match
        if ($this->permissions->contains('slug', $permissionSlug)) {
            return true;
        }

        // Module-level wildcard match (e.g. users.* matches users.read)
        if (str_contains($permissionSlug, '.')) {
            [$module] = explode('.', $permissionSlug, 2);
            if ($this->permissions->contains('slug', $module . '.*')) {
                return true;
            }
        }

        return false;
    }

    public function givePermission(Permission|string $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::firstOrCreate([
                'slug' => $permission,
            ], [
                'name'   => ucwords(str_replace(['.', '_'], ' ', $permission)),
                'module' => explode('.', $permission)[0] ?? 'general',
                'action' => explode('.', $permission)[1] ?? 'read',
            ]);
        }

        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function revokePermission(Permission|string $permission): void
    {
        $id = $permission instanceof Permission ? $permission->id : Permission::where('slug', $permission)->value('id');
        if ($id) {
            $this->permissions()->detach($id);
        }
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }
}

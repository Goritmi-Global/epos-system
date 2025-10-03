<?php

namespace App\Models;
use App\Models\User;
use App\Models\Permission;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name'];

    // Permissions assigned to this role
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            config('permission.table_names.role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    // Users that have this role
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
           User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            'role_id',
            'model_id'
        );
    }
}

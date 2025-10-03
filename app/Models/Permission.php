<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = ['name', 'guard_name', 'description'];

    // Users that have this permission (morphed)
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            \App\Models\User::class,
            'model',
            config('permission.table_names.model_has_permissions'),
            'permission_id',
            'model_id'
        );
    }

    // Roles that have this permission
    // public function roles(): BelongsToMany
    // {
    //     return $this->belongsToMany(
    //         \App\Models\Role::class,
    //         config('permission.table_names.role_has_permissions'),
    //         'permission_id',
    //         'role_id'
    //     );
    // }
}

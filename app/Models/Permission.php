<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;
use App\Models\Role;
use App\Models\User;
class Permission extends SpatiePermission
{
    protected $fillable = ['name', 'guard_name', 'description'];
 
    // Roles that have this permission
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            config('permission.table_names.role_has_permissions'),
            'permission_id',
            'role_id'
        );
    }
    // Users that have this permission
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_permissions'),
            'permission_id',
            'model_id'
        );
    }

}

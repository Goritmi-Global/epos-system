<?php
 
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    // GET /user-roles/users
    public function users()
    {
        $users = User::query()
            ->select('id','name','email')
            ->with(['roles:id,name']) // eager load roles
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'email' => $u->email,
                'roles' => $u->roles->pluck('name')->values(),
            ]);

        return response()->json($users);
    }

    // GET /user-roles/roles
    public function roles()
    {
        $roles = Role::query()
            ->select('id','name')
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    // POST /user-roles/assign/{user}
    public function assign(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_ids' => ['array'],
            'role_ids.*' => ['integer','exists:roles,id'],
        ]);

        $roleIds = $validated['role_ids'] ?? [];

        // Convert role IDs -> names (Spatie syncRoles works with names)
        $roleNames = Role::whereIn('id', $roleIds)->pluck('name')->all();

        // Sync (replace) the user's roles with provided roles
        $user->syncRoles($roleNames);

        // Return fresh user w/ roles
        $user->load('roles:id,name');

        return response()->json([
            'message' => 'Roles updated successfully.',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values(),
            ],
        ]);
    }
}

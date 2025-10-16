<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    // GET /roles  -> roles with permissions (light payload)
    public function index()
    {
        $roles = Role::query()
            ->select('id', 'name')
            ->with(['permissions:id,name,description'])
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    // GET /roles/{role} -> a single role with its permission ids (for edit form)
    public function show(Role $role)
    {
        $role->load(['permissions:id']);
        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'permission_ids' => $role->permissions->pluck('id'),
        ]);
    }

    // POST /roles
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*.id' => ['exists:permissions,id'], // ✅ expect objects
            'guard_name'  => ['nullable', 'string', 'max:255'],
        ]);

        $data['guard_name'] = $data['guard_name'] ?? config('auth.defaults.guard', 'web');

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'],
        ]);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        // clear spatie cache
        app('cache')->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        return response()->json($role->only(['id', 'name']), 201);
    }

    // PUT /roles/{role}
    public function update(Request $request, Role $role)
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255', Rule::unique('roles','name')->ignore($role->id)],
        'permissions' => ['nullable', 'array'],
        'permissions.*.id' => ['exists:permissions,id'], 
    ]);

    // Normalize permissions to integer IDs
    $data['permissions'] = array_map(function($p) {
        if (is_array($p) && isset($p['id'])) return $p['id'];
        if (is_object($p) && isset($p->id)) return $p->id;
        return $p; 
    }, $request->permissions ?? []);

    $role->update(['name' => $data['name']]);
    $role->syncPermissions($data['permissions'] ?? []);

    // Clear cache
    app('cache')->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
        ->forget(config('permission.cache.key'));

    return response()->json($role->only(['id','name']));
}


    // Optional helper: list all permissions (for modal)
    public function allPermissions()
    {
        return response()->json(
            Permission::select('id', 'name', 'description')->orderBy('name')->get()
        );
    }
}

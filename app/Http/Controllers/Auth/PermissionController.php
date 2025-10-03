<?php 
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller; 
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{ 
    public function index()
    {
        return response()->json(
            Permission::select('id','name','description')->orderBy('name')->get()
        );
    }
 
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:255','unique:permissions,name'],
            'description' => ['nullable','string','max:255'],
            'guard_name'  => ['nullable','string','max:255'], // default 'web'
        ]);

        $data['guard_name'] = $data['guard_name'] ?? config('auth.defaults.guard', 'web');

        $permission = Permission::create($data);

        // Clear Spatie cache to reflect changes immediately
        app('cache')->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        return response()->json($permission->only(['id','name','description']), 201);
    }

    // Update
    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:255', Rule::unique('permissions','name')->ignore($permission->id)],
            'description' => ['nullable','string','max:255'],
        ]);

        $permission->update($data);

        app('cache')->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        return response()->json($permission->only(['id','name','description']));
    }
}

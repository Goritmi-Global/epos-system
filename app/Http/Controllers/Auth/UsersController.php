<?php
 
namespace App\Http\Controllers\Auth; 
use App\Http\Controllers\Controller;
  
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Mail\VerifyAccountMail;
use Illuminate\Support\Facades\Mail;

class UsersController extends Controller
{
    private array $protectedRoles = ['Super Admin', 'Admin'];

    public function index(): JsonResponse
    {
        $users = User::select('id','name','email','status')
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'status' => $u->status ?? 'Active',
                'role' => $u->roles->pluck('name')->first(), // single main role for table
                'role_id' => optional(Role::where('name',$u->roles->pluck('name')->first())->first())->id
            ]);

        $roles = Role::select('id','name')
            // ->whereIn('name',['Super Admin','Admin','Manager','Cashier','Waiter'])
            ->orderByRaw("FIELD(name, 'Super Admin','Admin','Manager','Cashier','Waiter')")
            ->get();
   

        return response()->json(['users'=>$users,'roles'=>$roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['required','confirmed','min:8'],
            'pin'      => ['required','digits:4','unique:users,pin'],
            'status'   => ['required', Rule::in(['Active','Inactive'])],
            'role_id'  => ['required','integer','exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'pin'               => Hash::make($validated['pin']),
            'status'            => $validated['status'],
            // 'email_verified_at' => now(),  
        ]);

        // send email with credentials
        Mail::to($user->email)->send(new VerifyAccountMail($user, $validated['password'], $validated['pin'],''));
        $user->syncRoles([$role->name]);

        return response()->json([
            'message' => 'User created',
            'user' => [
                'id' => $user->id,
                'name'=> $user->name,
                'email'=>$user->email,
                'status'=> $user->status,
                'role'=> $role->name,
                'role_id'=> $role->id,
            ]
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'status'   => ['required', Rule::in(['Active','Inactive'])],
            'role_id'  => ['required','integer','exists:roles,id'],
            'password' => ['nullable','confirmed','min:8'],
            'pin'      => ['nullable','digits:4', Rule::unique('users','pin')->ignore($user->id)],
        ]);

        $user->name   = $validated['name'];
        $user->email  = $validated['email'];
        $user->status = $validated['status'];
        if (!empty($validated['password'])) $user->password = Hash::make($validated['password']);
        if (!empty($validated['pin']))      $user->pin      = Hash::make($validated['pin']);
        $user->save();

        $role = Role::findOrFail($validated['role_id']);
        $user->syncRoles([$role->name]);

        return response()->json([
            'message' => 'User updated',
            'user' => [
                'id'=>$user->id,'name'=>$user->name,'email'=>$user->email,
                'status'=>$user->status,'role'=>$role->name,'role_id'=>$role->id,
            ]
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $roleNames = $user->getRoleNames()->toArray();

        // block if user is Super Admin/Admin
        if (array_intersect($this->protectedRoles, $roleNames)) {
            return response()->json(['message'=>'Cannot delete a protected role user.'], 403);
        }

        // also guard: do not delete last Super Admin in system
        $superAdmins = User::role('Super Admin')->count();
        if (in_array('Super Admin', $roleNames) && $superAdmins <= 1) {
            return response()->json(['message'=>'Cannot delete the last Super Admin.'], 403);
        }

        $user->delete();
        return response()->json(['message'=>'Deleted']);
    }
}

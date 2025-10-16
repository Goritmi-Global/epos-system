<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyAccountMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)               // Minimum 8 characters
                    ->letters()               // Must contain letters
                    ->mixedCase()
                    ->numbers()               // Must contain at least one number
            ],
            'pin' => 'required|digits:4|unique:users,pin',
        ]);

        $rawPassword = $request->password;
        $rawPin = $request->pin;
        $otp = rand(100000, 999999);

        // Check if there is already a first super admin
        $hasFirstSuperAdmin = User::where('is_first_super_admin', true)->exists();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($rawPassword),
            'pin' => Hash::make($rawPin),
            'verifying_otp' => $otp,
            'is_first_super_admin' => $hasFirstSuperAdmin ? false : true,
        ]);

        // ✅ Ensure Super Admin role exists before assigning
        $role = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );

        // ✅ Assign role to new user
        $user->assignRole($role);

        // ✅ (Optional) give all permissions to Super Admin automatically
        $role->syncPermissions(\Spatie\Permission\Models\Permission::all());

        // Send verification email
        Mail::to($user->email)->send(new VerifyAccountMail($user, $rawPassword, $rawPin, $otp));

        throw ValidationException::withMessages([
            'unverified' => 'Account not verified. A new OTP has been sent to your email.',
            'email_address' => $user->email,
        ]);
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || $user->verifying_otp !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        $user->email_verified_at = now();
        $user->verifying_otp = null;
        $user->save();
        //   Automatically log the user in
        Auth::login($user);

        return response()->json(['message' => 'Verified successfully']);
    }
}

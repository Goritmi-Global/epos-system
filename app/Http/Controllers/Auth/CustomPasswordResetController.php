<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomPasswordResetController extends Controller
{
    public function requestReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate a token
        $token = Str::random(64);

        // Store it in password_resets table
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Build the reset URL
        $url = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));

        // Send the email
        Mail::to($user->email)->send(new ResetPasswordMail($user, $url));

        return back()->with('status', 'Password reset link sent!');
    }
}

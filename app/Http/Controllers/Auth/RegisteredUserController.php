<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Mail\VerifyAccountMail;
use Illuminate\Support\Facades\Mail;
 
 
 
    


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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'pin' => 'required|digits:4|unique:users,pin',
        ]);

        $rawPassword = $request->password;
        $rawPin = $request->pin;
        $otp = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($rawPassword),
            'pin' => Hash::make($rawPin),
            'verifying_otp' => $otp,
        ]);

        // Send a custom verification email (Laravel Recommended via Mailable)
        Mail::to($user->email)->send(new VerifyAccountMail($user, $rawPassword, $rawPin,$otp));
        // Mail::to($user->email)->send(new OtpVerificationMail($user, $otp));

        return Inertia::render('Auth/Register', [
            'showOtpModal' => true,
            'email' => $user->email,

        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->verifying_otp !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        $user->email_verified_at = now();
        $user->verifying_otp = null;
        $user->save();

        return response()->json(['message' => 'Verified successfully']);
    }


}

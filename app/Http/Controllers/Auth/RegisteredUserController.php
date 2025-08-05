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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($rawPassword),
            'pin' => Hash::make($rawPin),
        ]);

        // Send a custom verification email (Laravel Recommended via Mailable)
        Mail::to($user->email)->send(new VerifyAccountMail($user, $rawPassword, $rawPin));

        return redirect()->route('login')->with('message', 'Account created! Please check your email to verify your account.');
    }

}

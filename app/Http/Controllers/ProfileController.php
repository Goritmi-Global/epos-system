<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user(); // or find by ID if you're passing ID in request

        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'pin' => 'required|string|max:10',
            'role' => 'required|string',
        ]);

        $user->name = $validated['username'];
        if (! empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->pin = $validated['pin'] ?? $user->pin;
        $user->role = $validated['role'];
        $user->save();

        return response()->json(['success' => true, 'message' => 'Profile updated successfully']);
    }
}

<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 

class VerifyAccountController extends Controller
{
    public function verify($id)
    {
        $user = User::findOrFail($id);

        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        return redirect()->route('login')->with('message', '✅ Your account has been verified. You can now log in.');
    }
}

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

        // 🚫 If already verified, abort with 404
        if ($user->email_verified_at) {
            abort(404); // or: return abort(404, 'Already verified');
        }
 
        $user->email_verified_at = now();
        $user->save();

        return redirect()->route('login')->with('message', 'Your account has been verified. You can now log in.');
    }

}

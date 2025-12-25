<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerViewUrl;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerViewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        // Get the Super Admin user
        $superAdmin = User::where('role', 'super_admin')
            ->orWhere('is_first_super_admin', 1)
            ->first();
        if (!$superAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin not found',
            ], 404);
        }

        $record = CustomerViewUrl::updateOrCreate(
            ['id' => 1],
            [
                'url' => $request->url,
                'user_id' => $superAdmin->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer view URL saved',
            'data' => $record,
        ], 201);
    }
}
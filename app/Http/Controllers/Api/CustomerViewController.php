<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerViewUrl;
use Illuminate\Http\Request;

class CustomerViewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $record = CustomerViewUrl::updateOrCreate(
            ['id' => 1],
            ['url' => $request->url]
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer view URL saved',
            'data' => $record,
        ], 201);
    }
}

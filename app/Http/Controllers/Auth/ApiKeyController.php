<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Helpers\UploadHelper;
use App\Models\ProfileStep2;
use App\Models\ProfileStep6;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiKeyController extends Controller
{
    /**
     * Verify super admin credentials, store API key, and return business info
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeApiKey(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'api_key' => 'required|string|max:500',
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if user is Super Admin
        if (!$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can access this endpoint.'
            ], 403);
        }

        // Check if email is verified
        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email not verified'
            ], 403);
        }

        // Store the API key
        $user->printer_api_key = $request->api_key;
        $user->save();

        // === FETCH BUSINESS INFO ===
        $superAdmin = User::where('is_first_super_admin', true)->first();
        $onboardingUserId = $superAdmin ? $superAdmin->id : $user->id;

        $business = ProfileStep2::where('user_id', $onboardingUserId)
            ->select('business_name', 'phone', 'address', 'upload_id')
            ->first();

        $footer = ProfileStep6::where('user_id', $onboardingUserId)
            ->select('receipt_footer')
            ->first();

        // Prepare business data
        $businessData = [
            'business_name' => $business->business_name ?? 'Business Name',
            'phone' => $business->phone ?? '+44 0000 000000',
            'address' => $business->address ?? 'Unknown Address',
            'receipt_footer' => $footer->receipt_footer ?? 'Thank You',
            'logo_url' => null,
        ];

        // Get logo URL if available
        if ($business && $business->upload_id) {
            $businessData['logo_url'] = UploadHelper::url($business->upload_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verified and API key stored successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'business' => $businessData
        ], 200);
    }

    /**
     * Verify super admin credentials only (without storing API key)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyCredentials(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can access.'
            ], 403);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email not verified'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Credentials verified successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 200);
    }

    /**
     * Get business info for authenticated super admin
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBusinessInfo(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Super Admin can access.'
            ], 403);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email not verified'
            ], 403);
        }

        // === FETCH BUSINESS INFO ===
        $superAdmin = User::where('is_first_super_admin', true)->first();
        $onboardingUserId = $superAdmin ? $superAdmin->id : $user->id;

        $business = ProfileStep2::where('user_id', $onboardingUserId)
            ->select('business_name', 'phone', 'address', 'upload_id')
            ->first();

        $footer = ProfileStep6::where('user_id', $onboardingUserId)
            ->select('receipt_footer')
            ->first();

        $businessData = [
            'business_name' => $business->business_name ?? 'Business Name',
            'phone' => $business->phone ?? '+44 0000 000000',
            'address' => $business->address ?? 'Unknown Address',
            'receipt_footer' => $footer->receipt_footer ?? 'Thank You',
            'logo_url' => null,
        ];

        if ($business && $business->upload_id) {
            $businessData['logo_url'] = UploadHelper::url($business->upload_id);
        }

        return response()->json([
            'success' => true,
            'business' => $businessData
        ], 200);
    }

    /**
     * Get stored API key for authenticated super admin
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApiKey(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->hasRole('Super Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        if (!$user->printer_api_key) {
            return response()->json([
                'success' => false,
                'message' => 'No API key found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'api_key' => $user->printer_api_key
        ], 200);
    }
}
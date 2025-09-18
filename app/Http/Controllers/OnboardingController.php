<?php

namespace App\Http\Controllers;

use App\Models\OnboardingProgress;
use App\Models\RestaurantProfile;

use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $profile = RestaurantProfile::where('user_id', $user->id)->first();
        if ($profile && $profile->status === 'complete') {
            return redirect()->route('dashboard');
        }
        if (!$request->user()) {
            return redirect()->route('login');
        }
        return Inertia::render('Onboarding/Index');
    }

    public function show(Request $request)
    {
        $user = $request->user();

        $profile = RestaurantProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'draft']
        );

        $progress = OnboardingProgress::firstOrCreate(
            ['user_id' => $user->id],
            ['current_step' => 1]
        );

        return response()->json([
            'profile'  => $profile,
            'progress' => $progress,
        ]);
    }

    public function saveStep(Request $request, int $step)
    {
        $user = $request->user();
        $profile = RestaurantProfile::firstOrCreate(['user_id' => $user->id]);

        $data = match ($step) {
            1 => $request->validate([
                'timezone'            => 'string|max:100',
                'language'            => 'string|max:10',
                'languages_supported' => 'nullable|array',
                'languages_supported.*' => 'string|max:10',
            ]),
            2 => $request->validate([
                'business_name' => 'required|string|max:190',
                'legal_name'    => 'required|string|max:190',
                'phone'         => 'required|string|max:60',
                'email'         => 'required|email|max:190',
                'address'       => 'required|string|max:500',
                'website'       => 'required|max:190',
                'logo_path'     => 'nullable|string|max:255',
            ]),
            3 => $request->validate([
                'currency'                => 'required|string|max:8',
                'currency_symbol_position' => 'required|in:before,after',
                'number_format'           => 'required|string|max:20',
                'date_format'             => 'required|string|max:20',
                'time_format'             => 'required|in:12-hour,24-hour',
            ]),
            4 => $request->validate([
                'tax_registered' => 'required|boolean',
                'tax_type'          => 'required|string|max:50',
                'tax_rate'          => 'required|numeric|min:0|max:100',
                'extra_tax_rates'   => 'required|array',
                'price_includes_tax' => 'required|boolean',
            ]),
            5 => $request->validate([
                'order_types'               => 'required|array|min:1',
                'table_management_enabled'  => 'required|boolean',
                'online_ordering'   => 'required|boolean',
                'tables'          => 'required|integer|min:0',
                'table_details' => 'required|nullable|array',
                

            ]),
            6 => $request->validate([
                'receipt_header'           => 'required|string|max:2000',
                'receipt_footer'           => 'required|string|max:2000',
                'receipt_logo' => 'required|string|max:2000',
                'show_qr'       => 'required|boolean',
                'tax_breakdown' => 'required|boolean',
            
                'kitchen_printer'          => 'required|boolean',
            ]),
            7 => $request->validate([
                'cash_enabled'            => 'required|boolean',
                'card_enabled'            => 'required|boolean',
                // 'integrated_terminal'     => 'required|nullable|string|max:50',
                // 'custom_payment_options'  => 'required|nullable|array',
                // 'default_payment_method'  => 'required|nullable|string|max:50',
            ]),
            // 8 => $request->validate([
            //     'attendance_policy' => 'required|nullable|array', // {clock_enabled, require_shift, track_location, allow_late_clockin, require_reason}
            // ]),
            9 => $request->validate([
                'business_hours'           => 'required|array',
                'auto_disable_after_hours' => 'required|boolean',
            ]),
            10 => $request->validate([
                'loyalty_enabled'            => 'required|boolean',
                'cloud_backup_enabled'       => 'required|boolean',
                'theme'                      => 'required|nullable|string|max:30',
                'inventory_tracking_enabled' => 'required|boolean',
                'multi_location_enabled'     => 'required|boolean',
            ]),
            default => []
        };

        $profile->fill($data)->save();

        // update progress
        $progress = OnboardingProgress::firstOrCreate(['user_id' => $user->id]);
        $completed = collect($progress->completed_steps ?? []);
        $completed = $completed->merge([$step])->unique()->values();
        $progress->completed_steps = $completed->all();
        $progress->current_step = max($progress->current_step, min($step + 1, 10));
        $progress->save();

        return response()->json(['ok' => true, 'profile' => $profile, 'progress' => $progress]);
    }

    public function complete(Request $request)
    { 
        $user = $request->user();

        // Grab all profile data from request
        $profileData = $request->input('profile', []);
    
        $completed = $request->input('completed_steps', []);

        // Comprehensive validation - match what your frontend actually sends
        $validated = validator($profileData, [
            // Step 1 - Welcome & Language Selection
           'timezone'            => 'string|max:100',
                'language'            => 'string|max:10',
                'languages_supported' => 'nullable|array',
                'languages_supported.*' => 'string|max:10',

            // Step 2 - Business Information
              'business_name' => 'required|string|max:190',
                'legal_name'    => 'required|string|max:190',
                'phone'         => 'required|string|max:60',
                'email'         => 'required|email|max:190',
                'address'       => 'required|string|max:500',
                'website'       => 'required|max:190',
                'logo_path'     => 'nullable|string|max:255',
            // Step 5 - Order Types (your frontend sends this)
           'order_types'               => 'required|array|min:1',
                'table_management_enabled'  => 'required|boolean',
                'online_ordering'   => 'required|boolean',
                'tables'          => 'required|integer|min:0',
                'table_details' => 'required|nullable|array',
            // Step 6 - Receipt settings
           'receipt_header'           => 'required|string|max:2000',
                'receipt_footer'           => 'required|string|max:2000',
                'receipt_logo' => 'required|string|max:2000',
                'show_qr'       => 'required|boolean',
                'tax_breakdown' => 'required|boolean',
            
                'kitchen_printer'          => 'required|boolean',
            // Tax fields
            'tax_registered' => 'required|boolean',
                'tax_type'          => 'required|string|max:50',
                'tax_rate'          => 'required|numeric|min:0|max:100',
                'extra_tax_rates'   => 'required|array',
                'price_includes_tax' => 'required|boolean',
            // // Business Hours - this is the key field causing your error
            // 'hours'              => 'required|array|size:7',
            // 'business_hours.*.name'       => 'required|string',
            // 'business_hours.*.open'       => 'required|boolean',
            // 'business_hours.*.start'      => 'required|nullable|string',
            // 'business_hours.*.end'        => 'required|nullable|string',
            // 'business_hours.*.breaks'     => 'required|nullable|array',
            // 'auto_disable_after_hours'    => 'required|boolean',

            // Other fields from your data dump
            'country_code'                => 'required|nullable|string|max:10',
            'country_name'                => 'required|nullable|string|max:100',
            'phone_country'               => 'required|nullable|string|max:10',
            'phone_code'                  => 'required|nullable|string|max:10',
            'phone_local'                 => 'required|nullable|string|max:60',
            'store_phone'                 => 'required|nullable|string|max:60',
            'store_name'                  => 'required|nullable|string|max:255',

            // Add other fields as needed based on your data structure
        ])->validate();

        // Find or create RestaurantProfile for the user
        $profile = RestaurantProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'draft']
        );

        // Transform some string fields to boolean for database storage if needed
        $transformedData = $validated;

        // Convert "yes"/"no" strings to booleans
        $booleanFields = ['table_mgmt', 'online_ordering', 'show_qr', 'tax_breakdown', 'kitchen_printer', 'tax_registered', 'price_includes_tax'];
        foreach ($booleanFields as $field) {
            if (isset($transformedData[$field])) {
                $transformedData[$field] = $transformedData[$field] === 'yes';
            }
        }

        // Fill and save all data
        $profile->fill($transformedData);
        $profile->status = 'complete';
        $profile->save();
 
        // Update onboarding progress
        $progress = OnboardingProgress::firstOrCreate(['user_id' => $user->id]);
        $progress->completed_steps = collect($progress->completed_steps ?? [])
            ->merge(range(1, 10))
            ->unique()
            ->values();
        $progress->current_step = 10;
        $progress->is_completed = true;
        $progress->completed_at = now();
        $progress->save();
        return redirect('/');

// return redirect()->route('dashboard');


        // return response()->json([
        //     'ok'      => true,
        //     'profile' => $profile,
        //     'progress' => $progress,
        // ]);
    }
}

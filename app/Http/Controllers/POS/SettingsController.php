<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\RestaurantProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Show settings page with all profile data
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get the complete profile
        $profile = RestaurantProfile::where('user_id', $user->id)->first();
        
        if (!$profile) {
            return redirect()->route('onboarding.index')
                ->with('message', 'Please complete onboarding first');
        }

        // Load all step data
        $profileData = $this->loadAllStepData($user->id);

        return Inertia::render('Backend/Settings/Index', [
            'profile' => $profile,
            'profileData' => $profileData,
        ]);
    }

    /**
     * Update a specific step/section
     */
    public function updateStep(Request $request, int $step)
    {
        $user = $request->user();
        
        // Check if profile exists
        $profile = RestaurantProfile::where('user_id', $user->id)->first();
        if (!$profile) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        // Apply the same alias mapping as onboarding
        $payload = $request->all();
        $aliasMap = [
            'tax_registered' => 'is_tax_registered',
            'table_mgmt' => 'table_management_enabled',
            'tables' => 'number_of_tables',
            'online_ordering' => 'online_ordering_enabled',
            'show_qr' => 'show_qr_on_receipt',
            'tax_breakdown' => 'tax_breakdown_on_receipt',
            'kitchen_printer' => 'kitchen_printer_enabled',
            'receipt_logo_file' => 'receipt_logo_path',
            'order_types' => 'order_types',
        ];

        foreach ($aliasMap as $alias => $canonical) {
            if (array_key_exists($alias, $payload) && !array_key_exists($canonical, $payload)) {
                $payload[$canonical] = $payload[$alias];
            }
        }

        // Normalize booleans
        $boolKeys = [
            'is_tax_registered', 'table_management_enabled', 'online_ordering_enabled',
            'show_qr_on_receipt', 'tax_breakdown_on_receipt', 'kitchen_printer_enabled',
            'price_includes_tax',
        ];
        foreach ($boolKeys as $k) {
            if (isset($payload[$k]) && is_string($payload[$k])) {
                $lower = strtolower($payload[$k]);
                $payload[$k] = in_array($lower, ['1', 'true', 'yes', 'on']) ? true : 
                               (in_array($lower, ['0', 'false', 'no', 'off']) ? false : $payload[$k]);
            }
        }

        $request->replace($payload);

        // Use the same validation rules from OnboardingController
        $data = $this->validateStep($request, $step);

        // Update the appropriate step model
        $this->updateStepModel($user->id, $step, $data);

        // Update main restaurant profile if needed
        $this->updateMainProfile($user->id, $data);

        return response()->json([
            'ok' => true,
            'message' => 'Settings updated successfully',
            'data' => $data,
        ]);
    }

    /**
     * Validate step data (reuse validation from onboarding)
     */
    private function validateStep(Request $request, int $step): array
    {
        return match ($step) {
            1 => $request->validate([
                'country_name' => 'required',
                'timezone' => 'required|string|max:100',
                'language' => 'required|string|max:10',
                'languages_supported' => 'nullable|array',
                'languages_supported.*' => 'string|max:10',
            ]),
            2 => $request->validate([
                'business_name' => 'required|string|max:190',
                'legal_name' => 'required|string|max:190',
                'business_type' => 'required',
                'phone' => 'required|string|max:60',
                'phone_local' => 'required|string|max:60',
                'email' => 'required|email|max:190',
                'address' => 'required|string|max:500',
                'website' => 'required|max:190',
                'logo' => 'nullable',
            ]),
            3 => $request->validate([
                'currency' => 'required|string|max:8',
                'currency_symbol_position' => 'required|in:before,after',
                'number_format' => 'required|string|max:20',
                'date_format' => 'required|string|max:20',
                'time_format' => 'required|in:12-hour,24-hour',
            ]),
            4 => $request->validate([
                'tax_registered' => 'required|boolean',
                'tax_type' => 'required_if:tax_registered,1|string|max:50',
                'tax_rate' => 'required_if:tax_registered,1|numeric|min:0|max:100',
                'tax_id' => 'required_if:tax_registered,1',
                'extra_tax_rates' => 'required_if:tax_registered,1',
                'price_includes_tax' => 'required|boolean',
            ]),
            5 => $request->validate([
                'order_types' => 'required|array|min:1',
                'table_management_enabled' => 'required|boolean',
                'online_ordering' => 'required|boolean',
                'tables' => 'required_if:table_management_enabled,1|integer|min:1',
                'table_details' => 'required_if:table_management_enabled,1|array|min:1',
                'table_details.*.name' => 'required_if:table_management_enabled,1|string|max:255',
                'table_details.*.chairs' => 'required_if:table_management_enabled,1|integer|min:1',
            ]),
            6 => $request->validate([
                'receipt_header' => 'required|string|max:2000',
                'receipt_footer' => 'required|string|max:2000',
                'receipt_logo' => 'required|string|max:255',
                'show_qr_on_receipt' => 'required|boolean',
                'tax_breakdown_on_receipt' => 'required|boolean',
                'kitchen_printer_enabled' => 'required|boolean',
                'printers' => 'nullable|array',
            ]),
            7 => $request->validate([
                'cash_enabled' => 'required|boolean',
                'card_enabled' => 'required|boolean',
            ]),
            default => []
        };
    }

    /**
     * Update individual step model
     */
    private function updateStepModel(int $userId, int $step, array $data): void
    {
        $modelClass = "App\\Models\\ProfileStep{$step}";
        
        if (class_exists($modelClass)) {
            $modelClass::updateOrCreate(
                ['user_id' => $userId],
                $data
            );
        }
    }

    /**
     * Update main restaurant profile
     */
    private function updateMainProfile(int $userId, array $data): void
    {
        $profile = RestaurantProfile::where('user_id', $userId)->first();
        
        if (!$profile) return;

        // Map fields that should be updated in main profile
        $mainProfileFields = [
            'is_tax_registered', 'order_types', 'table_management_enabled',
            'online_ordering_enabled', 'number_of_tables', 'table_details',
            'receipt_logo_path', 'show_qr_on_receipt', 'tax_breakdown_on_receipt',
            'kitchen_printer_enabled', 'business_hours', 'auto_disable_after_hours',
            'business_name', 'legal_name', 'phone', 'email', 'address', 'website',
            'currency', 'timezone', 'language',
        ];

        $updateData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $mainProfileFields)) {
                $updateData[$key] = $value;
            }
        }

        if (!empty($updateData)) {
            $profile->update($updateData);
        }
    }

    /**
     * Load all step data for display
     */
    private function loadAllStepData(int $userId): array
    {
        $data = [];
        
        for ($i = 1; $i <= 9; $i++) {
            $modelClass = "App\\Models\\ProfileStep{$i}";
            if (class_exists($modelClass)) {
                $stepData = $modelClass::where('user_id', $userId)->first();
                $data["step{$i}"] = $stepData ? $stepData->toArray() : [];
            }
        }

        return $data;
    }
}

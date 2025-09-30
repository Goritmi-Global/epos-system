<?php

namespace App\Http\Controllers;

use App\Helpers\UploadHelper;
use App\Models\BusinessHour;
use App\Models\Country;
use App\Models\DisableOrderAfterHour;
use App\Models\OnboardingProgress;
use App\Models\ProfileStep1;
use App\Models\ProfileStep2;
use App\Models\ProfileStep3;
use App\Models\ProfileStep4;
use App\Models\ProfileStep5;
use App\Models\ProfileStep6;
use App\Models\ProfileStep7;
use App\Models\ProfileStep8;
use App\Models\ProfileStep9;
use App\Models\RestaurantProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        // If a final profile already exists and complete -> go to dashboard
        $profile = RestaurantProfile::where('user_id', $user->id)->first();
        if ($profile && $profile->status === 'complete') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Index');
    }

    /**
     * Return merged step data and progress for the frontend
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Get progress and temporary data stored in session/cache
        $progress = OnboardingProgress::firstOrCreate(['user_id' => $user->id], ['current_step' => 1]);

        // Get temporary onboarding data from session
        $tempData = session()->get('onboarding_data', []);

        return response()->json([
            'profile' => $tempData,
            'progress' => $progress,
        ]);
    }

    /**
     * Save step data temporarily (in session) and update progress
     */
    public function saveStep(Request $request, int $step)
    {
        $user = $request->user();

        // Accept aliases from frontend
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
            'is_tax_registered',
            'table_management_enabled',
            'online_ordering_enabled',
            'show_qr_on_receipt',
            'tax_breakdown_on_receipt',
            'kitchen_printer_enabled',
            'price_includes_tax',
        ];

        foreach ($boolKeys as $k) {
            if (isset($payload[$k]) && is_string($payload[$k])) {
                $lower = strtolower($payload[$k]);
                $payload[$k] = in_array($lower, ['1', 'true', 'yes', 'on']) ? true : (in_array($lower, ['0', 'false', 'no', 'off']) ? false : $payload[$k]);
            }
        }

        $request->replace($payload);

        // Validate per step
        $data = match ($step) {
            1 => $request->validate([
                'country_code' => 'required|string|exists:countries,iso2',
                'timezone' => 'required|string|max:100',
                'language' => 'required|string|max:10',
            ]),
            2 => $request->validate(
                [
                    'business_name' => 'required|string|max:190',
                    'legal_name'    => 'required|string|max:190',
                    'business_type' => 'required',
                    'phone'         => 'required|string|max:60',
                    'phone_local'   => 'required|string|max:60',
                    'email'         => 'required|email|max:190',
                    'address'       => 'required|string|max:500',
                    'website'       => 'required|max:190',
                    'logo'          => 'nullable',
                    'logo_file'     => 'required',
                ],
                [
                    'phone_local.required' => 'The phone field is required.',
                ]
            ),

            3 => $request->validate([
                'currency' => 'required|string|max:8',
                'currency_symbol_position' => 'required|in:before,after',
                'number_format' => 'required|string|max:20',
                'date_format' => 'required|string|max:20',
                'time_format' => 'required|in:12-hour,24-hour',
            ]),
            4 => $request->validate([
                'tax_registered' => 'required|boolean',
                'tax_type' => 'required|string|max:50',
                'tax_rate' => 'required|numeric|min:0|max:100',
                'tax_id' => 'required|string',
                'extra_tax_rates' => 'required|string',
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
                'receipt_logo' => 'nullable',
                'receipt_logo_file' => 'required',
                'show_qr_on_receipt' => 'required|boolean',
                'tax_breakdown_on_receipt' => 'required|boolean',
                'kitchen_printer_enabled' => 'required|boolean',
                'printers' => 'nullable|array',
            ]),
            7 => $request->validate([
                'cash_enabled' => 'required|boolean',
                'card_enabled' => 'required|boolean',
            ]),
            8 => $request->validate([
                'auto_disable' => 'required|in:yes,no',
                'hours' => 'required|array|size:7',
                'hours.*.name' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'hours.*.open' => 'required|boolean',
                'hours.*.start' => 'required_if:hours.*.open,1|date_format:H:i',
                'hours.*.end' => 'required_if:hours.*.open,1|date_format:H:i|after:hours.*.start',
                'hours.*.breaks' => 'nullable|array',
                'hours.*.breaks.*.start' => 'required_with:hours.*.breaks|date_format:H:i',
                'hours.*.breaks.*.end' => 'required_with:hours.*.breaks|date_format:H:i|after:hours.*.breaks.*.start',
            ]),
            9 => $request->validate([
                'feat_loyalty' => 'required|in:yes,no',
                'feat_inventory' => 'required|in:yes,no',
                'feat_backup' => 'required|in:yes,no',
                'feat_multilocation' => 'required|in:yes,no',
                'feat_theme' => 'required|in:yes,no',
            ]),
            default => []
        };

        // Handle Step 1: Country lookup
        if ($step === 1 && !empty($data['country_code'])) {
            $country = Country::where('iso2', $data['country_code'])->first();
            $data['country_id'] = $country->id ?? null;
            $data['country_code'] = $country->iso2 ?? null;
        }

        // ⚠️ CRITICAL: Handle Step 2 file upload IMMEDIATELY
        if ($step === 2 && $request->hasFile('logo_file')) {
            try {
                // Upload the file using your helper
                $upload = UploadHelper::store(
                    $request->file('logo_file'),
                    'logos',
                    'public'
                );

                // Store only the upload ID and path (not the file object)
                $data['upload_id'] = $upload->id;
                $data['logo_path'] = $upload->path;
                $data['logo_url'] = $upload->url; // if your helper provides this

                // Remove the file object from data
                unset($data['logo_file']);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to upload logo. Please try again.',
                ], 500);
            }
        }

        // In saveStep() method, add after Step 2 file handling:

        // Handle Step 6 receipt logo upload
        if ($step === 6 && $request->hasFile('receipt_logo_file')) {
            try {
                $upload = UploadHelper::store(
                    $request->file('receipt_logo_file'),
                    'receipt_logos',
                    'public'
                );

                $data['upload_id'] = $upload->id;
                $data['receipt_logo_path'] = $upload->path;
                $data['receipt_logo_url'] = $upload->url;

                unset($data['receipt_logo_file']);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to upload receipt logo. Please try again.',
                ], 500);
            }
        }

        // Get existing session data
        $tempData = session()->get('onboarding_data', []);

        // Store step data in nested format
        $tempData[$step] = $data;

        // Also flatten for backward compatibility
        foreach ($data as $key => $value) {
            $tempData[$key] = $value;
        }

        // Save to session (now without file objects)
        session()->put('onboarding_data', $tempData);

        // Update progress
        $progress = OnboardingProgress::firstOrCreate(['user_id' => $user->id]);
        $completed = collect($progress->completed_steps ?? []);
        $completed = $completed->merge([$step])->unique()->values();
        $progress->completed_steps = $completed->all();
        $progress->current_step = max($progress->current_step, min($step + 1, 10));
        $progress->save();

        return response()->json([
            'ok' => true,
            'profile' => $tempData,
            'progress' => $progress,
            'message' => 'Step ' . $step . ' data saved successfully',
        ]);
    }

    /**
     * Save all step data to database tables and finalize
     */
    public function complete(Request $request)
    {
        $user = $request->user();
        $tempData = session()->get('onboarding_data', []);

        if (empty($tempData)) {
            return response()->json([
                'error' => 'No onboarding data found. Please complete all steps first.',
            ], 400);
        }

        // Separate data by steps
        $stepData = $this->separateDataBySteps($tempData);
        // dd($stepData);
        // Save Step 1
        if (!empty($stepData[1])) {
            ProfileStep1::updateOrCreate(['user_id' => $user->id], $stepData[1]);
        }

        // Save Step 2 (logo already uploaded in saveStep)
        if (!empty($stepData[2])) {
            // ✅ No need to upload again - already done in saveStep()
            // Just save the upload_id that was stored
            ProfileStep2::updateOrCreate(['user_id' => $user->id], $stepData[2]);
        }

        // Save Step 3
        if (!empty($stepData[3])) {
            ProfileStep3::updateOrCreate(['user_id' => $user->id], $stepData[3]);
        }

        // Save Step 4
        if (!empty($stepData[4])) {
            ProfileStep4::updateOrCreate(['user_id' => $user->id], $stepData[4]);
        }

        // Save Step 5
        if (!empty($stepData[5])) {
            $step5 = $stepData[5];

            // First, save profile_tables entry
            $profileTable = null;
            if (!empty($step5['table_details'])) {
                $profileTable = \App\Models\ProfileTable::updateOrCreate(
                    ['id' => $step5['profile_table_id'] ?? null], // reuse if exists
                    [
                        'number_of_tables' => $step5['number_of_tables'] ?? count($step5['table_details']),
                        'table_details' => $step5['table_details'],
                    ]
                );
            }

            // Then save step 5 with reference to profile_table_id
            ProfileStep5::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'order_types' => $step5['order_types'] ?? [],
                    'table_management_enabled' => $step5['table_management_enabled'] ?? false,
                    'online_ordering_enabled' => $step5['online_ordering_enabled'] ?? false,
                    'profile_table_id' => $profileTable?->id,
                ]
            );
        }


        // Save Step 6
        if (!empty($stepData[6])) {
            ProfileStep6::updateOrCreate(['user_id' => $user->id], $stepData[6]);
        }

        // Save Step 7
        if (!empty($stepData[7])) {
            ProfileStep7::updateOrCreate(['user_id' => $user->id], $stepData[7]);
        }

        // Save Step 8 (Business Hours)
        if (!empty($stepData[8])) {
            $step8 = $stepData[8];

            // Save disable_order_after_hours
            $disable = DisableOrderAfterHour::updateOrCreate(
                ['user_id' => $user->id],
                ['status' => $step8['auto_disable'] === 'yes']
            );

            // Save business_hours (all 7 days)
            $businessHourIds = [];
            foreach ($step8['hours'] as $day) {
                $bh = BusinessHour::updateOrCreate(
                    ['user_id' => $user->id, 'day' => $day['name']],
                    [
                        'from' => $day['start'] ?? null,
                        'to' => $day['end'] ?? null,
                        'is_open' => $day['open'] ?? false,
                    ]
                );

                // Save breaks if any
                if (!empty($day['breaks'])) {
                    foreach ($day['breaks'] as $break) {
                        $bh->break_from = $break['start'];
                        $bh->break_to = $break['end'];
                        $bh->save();
                    }
                }

                $businessHourIds[] = $bh->id;
            }

            // Save profile_step_8 linking table
            ProfileStep8::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'disable_order_after_hours_id' => $disable->id,
                    'business_hours_id' => $businessHourIds[0] ?? null,
                ]
            );
        }

        // Save Step 9
        if (!empty($stepData[9])) {
            $step9 = $stepData[9];

            ProfileStep9::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'enable_loyalty_system' => $step9['feat_loyalty'] === 'yes',
                    'enable_inventory_tracking' => $step9['feat_inventory'] === 'yes',
                    'enable_cloud_backup' => $step9['feat_backup'] === 'yes',
                    'enable_multi_location' => $step9['feat_multilocation'] === 'yes',
                    'theme_preference' => $step9['feat_theme'] === 'yes' ? 'default_theme' : null,
                ]
            );
        }

        // Map fields to restaurant_profiles
        $map = [
            'is_tax_registered' => 'is_tax_registered',
            'order_types' => 'order_types',
            'table_management_enabled' => 'table_management_enabled',
            'online_ordering_enabled' => 'online_ordering_enabled',
            'number_of_tables' => 'number_of_tables',
            'table_details' => 'table_details',
            'receipt_logo_path' => 'receipt_logo_path',
            'show_qr_on_receipt' => 'show_qr_on_receipt',
            'tax_breakdown_on_receipt' => 'tax_breakdown_on_receipt',
            'kitchen_printer_enabled' => 'kitchen_printer_enabled',
            'business_hours' => 'business_hours',
            'auto_disable_after_hours' => 'auto_disable_after_hours',
        ];

        $toSave = [];
        foreach ($tempData as $k => $v) {
            // Skip nested step arrays
            if (is_numeric($k)) continue;

            $targetKey = $map[$k] ?? $k;
            $toSave[$targetKey] = $v;
        }

        $toSave['status'] = 'complete';
        $toSave['user_id'] = $user->id;

        // Create final restaurant profile
        RestaurantProfile::updateOrCreate(
            ['user_id' => $user->id],
            $toSave
        );

        // Mark progress as completed
        $progress = OnboardingProgress::firstOrCreate(['user_id' => $user->id]);
        $progress->completed_steps = range(1, 9);
        $progress->current_step = 10;
        $progress->is_completed = true;
        $progress->completed_at = now();
        $progress->save();

        // Clear session
        session()->forget('onboarding_data');

        return redirect('/dashboard')->with('success', 'Onboarding completed successfully!');
    }

    /**
     * Helper method to separate merged data back into step-specific arrays
     */
    private function separateDataBySteps(array $tempData): array
    {
        $stepFields = [
            1 => ['country_id', 'timezone', 'language', 'languages_supported', 'country_code'],
            2 => ['business_name', 'business_type', 'legal_name', 'phone', 'phone_local', 'email', 'address', 'website', 'upload_id', 'logo_path', 'logo_url'],
            3 => ['currency', 'currency_symbol_position', 'number_format', 'date_format', 'time_format'],
            4 => ['is_tax_registered', 'tax_type', 'tax_id', 'tax_rate', 'extra_tax_rates', 'price_includes_tax'],
            5 => ['order_types', 'table_management_enabled', 'online_ordering_enabled', 'number_of_tables', 'table_details', 'profile_table_id'],
            6 => ['receipt_header', 'receipt_footer', 'receipt_logo_path', 'upload_id', 'receipt_logo_url', 'show_qr_on_receipt', 'tax_breakdown_on_receipt', 'kitchen_printer_enabled', 'printers'],
            7 => ['cash_enabled', 'card_enabled', 'integrated_terminal', 'custom_payment_options', 'default_payment_method'],
            8 => ['auto_disable', 'hours'],
            9 => ['feat_loyalty', 'feat_inventory', 'feat_backup', 'feat_multilocation', 'feat_theme'],
        ];

        $stepData = [];

        foreach ($stepFields as $stepNumber => $fields) {
            $stepData[$stepNumber] = [];

            // Check nested format first
            if (isset($tempData[$stepNumber]) && is_array($tempData[$stepNumber])) {
                foreach ($fields as $field) {
                    if (array_key_exists($field, $tempData[$stepNumber])) {
                        $stepData[$stepNumber][$field] = $tempData[$stepNumber][$field];
                    }
                }
            } else {
                // Fallback to flat structure
                foreach ($fields as $field) {
                    if (array_key_exists($field, $tempData)) {
                        $stepData[$stepNumber][$field] = $tempData[$field];
                    }
                }
            }
        }

        return $stepData;
    }



    /**
     * Optional: Clear temporary data if user wants to restart
     */
    public function clearTemporaryData(Request $request)
    {
        session()->forget('onboarding_data');

        $user = $request->user();
        $progress = OnboardingProgress::where('user_id', $user->id)->first();
        if ($progress) {
            $progress->update([
                'current_step' => 1,
                'completed_steps' => [],
                'is_completed' => false,
                'completed_at' => null,
            ]);
        }

        return response()->json(['ok' => true, 'message' => 'Temporary data cleared']);
    }
}

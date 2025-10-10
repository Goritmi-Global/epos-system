<?php

namespace App\Http\Controllers\POS;

use App\Helpers\UploadHelper;
use App\Http\Controllers\Controller;
use App\Models\BusinessHour;
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
use App\Models\Upload;
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

        if (! $user) {
            return redirect()->route('login');
        }

        $role = $user->getRoleNames()->first();

        // Only enforce onboarding for Super Admin
        if ($role === 'Super Admin') {
            $stepsCompleted = ProfileStep1::where('user_id', $user->id)->exists()
                && ProfileStep2::where('user_id', $user->id)->exists()
                && ProfileStep3::where('user_id', $user->id)->exists()
                && ProfileStep4::where('user_id', $user->id)->exists()
                && ProfileStep5::where('user_id', $user->id)->exists()
                && ProfileStep6::where('user_id', $user->id)->exists()
                && ProfileStep7::where('user_id', $user->id)->exists()
                && ProfileStep8::where('user_id', $user->id)->exists()
                && ProfileStep9::where('user_id', $user->id)->exists();

            if (! $stepsCompleted) {
                return redirect()->route('onboarding.index')
                    ->with('message', 'Please complete onboarding first');
            }
        }

        // Load all step data if needed (optional)
        $profileData = $this->loadAllStepData($user->id);

        return Inertia::render('Backend/Settings/Index', [
            'profileData' => $profileData,
            'role' => $role, // optional: pass role to frontend
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
            'price_includes_tax',
        ];
        foreach ($boolKeys as $k) {
            if (isset($payload[$k]) && is_string($payload[$k])) {
                $lower = strtolower($payload[$k]);
                $payload[$k] = in_array($lower, ['1', 'true', 'yes', 'on']) ? true : (in_array($lower, ['0', 'false', 'no', 'off']) ? false : $payload[$k]);
            }
        }


        $request->replace($payload);
        $existingStepData = $this->getExistingStepData($user->id, $step);

        $data = $this->validateStep($request, $step, $existingStepData);
        // dd($data);

        if ($step === 1) {
            // Find the country by its code
            $country = \App\Models\Country::where('iso2', $data['country_code'] ?? null)->first();

            // Update or create ProfileStep1 with country_id
            $profileStep1 = \App\Models\ProfileStep1::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'country_id' => $country->id ?? null,
                    'timezone_id' => $data['timezone_id'] ?? null,
                    'language' => $data['language'] ?? 'en',
                ]
            );
        }


        //  Step 2
        // Step 2: only save upload_id in ProfileStep2, not RestaurantProfile
        if ($step === 2 && $request->hasFile('logo_file')) {
            try {
                $existingStep = \App\Models\ProfileStep2::where('user_id', $user->id)->first();
                $oldUploadId = $existingStep->upload_id ?? null;

                $upload = UploadHelper::store(
                    $request->file('logo_file'),
                    'logos',
                    'public'
                );

                $data['upload_id'] = $upload->id;

                if ($oldUploadId) {
                    UploadHelper::delete($oldUploadId);
                }

                unset($data['logo_file']);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to upload logo'], 500);
            }
        }

        // Step 5
        if ($step === 5) {
            // Get existing ProfileStep5 to find profile_table_id
            $existingStep5 = \App\Models\ProfileStep5::where('user_id', $user->id)->first();
            $profileTableId = $existingStep5->profile_table_id ?? null;

            // Update or create ProfileTable if table details exist
            if (!empty($data['table_details'])) {
                $tableCount = $data['tables'] ?? count($data['table_details']);

                if ($profileTableId) {
                    // Update existing ProfileTable
                    $profileTable = \App\Models\ProfileTable::find($profileTableId);
                    if ($profileTable) {
                        $profileTable->update([
                            'number_of_tables' => $tableCount,
                            'table_details' => $data['table_details'],
                        ]);
                    }
                } else {
                    // Create new ProfileTable
                    $profileTable = \App\Models\ProfileTable::create([
                        'number_of_tables' => $tableCount,
                        'table_details' => $data['table_details'],
                    ]);
                    $profileTableId = $profileTable->id;
                }
            }

            // Update data to save to ProfileStep5
            $data = [
                'order_types' => $data['order_types'] ?? [],
                'table_management_enabled' => $data['table_management_enabled'] ?? false,
                'online_ordering_enabled' => $data['online_ordering'] ?? false,
                'profile_table_id' => $profileTableId,
            ];
        }


        //  Step 6
        if ($step === 6 && $request->hasFile('receipt_logo_file')) {
            try {
                $existingStep = \App\Models\ProfileStep6::where('user_id', $user->id)->first();
                $oldUploadId = $existingStep->upload_id ?? null;

                $upload = UploadHelper::store(
                    $request->file('receipt_logo_file'),
                    'receipt_logos',
                    'public'
                );

                $data['upload_id'] = $upload->id;

                if ($oldUploadId) {
                    UploadHelper::delete($oldUploadId); //  FIXED
                }

                unset($data['receipt_logo_file']);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to upload receipt logo'], 500);
            }
        }

        if ($step === 8 && isset($data['hours'])) {
            // Update disable order after hours
            $disableStatus = isset($data['auto_disable']) && $data['auto_disable'] === 'yes';
            $disable = \App\Models\DisableOrderAfterHour::updateOrCreate(
                ['user_id' => $user->id],
                ['status' => $disableStatus]
            );

            $businessHourIds = [];
            foreach ($data['hours'] as $day) {
                $bh = \App\Models\BusinessHour::updateOrCreate(
                    ['user_id' => $user->id, 'day' => $day['name']],
                    [
                        'from' => $day['start'] ?? null,
                        'to' => $day['end'] ?? null,
                        'is_open' => $day['open'] ?? false,
                    ]
                );

                if (!empty($day['breaks'])) {
                    $break = $day['breaks'][0]; // only first break
                    $bh->break_from = $break['start'];
                    $bh->break_to = $break['end'];
                    $bh->save();
                }

                $businessHourIds[] = $bh->id;
            }

            \App\Models\ProfileStep8::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'disable_order_after_hours_id' => $disable->id,
                    'business_hours_id' => $businessHourIds[0] ?? null,
                ]
            );
        }

        if ($step === 9) {
            // Map form fields to database columns
            $data = [
                'enable_loyalty_system' => $data['feat_loyalty'] === 'yes',
                'enable_inventory_tracking' => $data['feat_inventory'] === 'yes',
                'enable_cloud_backup' => $data['feat_backup'] === 'yes',
                'enable_multi_location' => $data['feat_multilocation'] === 'yes',
                'theme_preference' => $data['feat_theme'] === 'yes' ? 'default_theme' : null,
            ];
        }


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
     * Get existing step data for validation context
     */
    private function getExistingStepData(int $userId, int $step): array
    {
        $modelClass = "App\\Models\\ProfileStep{$step}";

        if (class_exists($modelClass)) {
            $stepData = $modelClass::where('user_id', $userId)->first();
            return $stepData ? $stepData->toArray() : [];
        }

        return [];
    }

    /**
     * Validate step data (reuse validation from onboarding)
     */
    private function validateStep(Request $request, int $step, array $existingData = []): array
    {
        return match ($step) {
            1 => $request->validate([
                'country_code' => 'required|string|exists:countries,iso2',
                'timezone_id'  => 'required|integer|exists:timezones,id', // FIXED: Changed from string to integer with exists check
                'language'     => 'required|string|max:10',
            ], [
                'country_code.required' => 'Country field is required',
                'country_code.exists'   => 'Selected country is invalid',
                'timezone_id.required'  => 'Timezone field is required',
                'timezone_id.exists'    => 'Selected timezone is invalid', // FIXED: Added error message
                'language.required'     => 'Language field is required',
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
                    'website'       => 'nullable|max:190',
                    'logo'          => 'nullable',
                    // FIXED: Check existing data properly
                    'logo_file'     => (!empty($existingData['upload_id']) || !empty($existingData['logo_url']))
                        ? 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048'
                        : 'required|file|mimes:jpeg,jpg,png,webp|max:2048',
                ],
                [
                    'phone_local.required' => 'The phone field is required.',
                    'logo_file.required' => 'Please upload a business logo.',
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
                'tax_registered'    => 'required|boolean',
                'tax_type'          => 'required_if:tax_registered,1|max:50',
                'tax_rate'          => 'required_if:tax_registered,1|numeric|min:0|max:100',
                'tax_id'            => 'required_if:tax_registered,1',
                'extra_tax_rates'   => 'required_if:tax_registered,1|numeric|min:0|max:100',
                'price_includes_tax' => 'required|boolean',
            ], [
                'tax_type.required_if'        => 'The Tax Type field is required when Tax Registered is checked Yes.',
                'tax_rate.required_if'        => 'The Tax Rate field is required when Tax Registered is checked Yes.',
                'tax_rate.min'                => 'The Tax Rate cannot be less than 0%.',
                'tax_rate.max'                => 'The Tax Rate cannot exceed 100%.',
                'extra_tax_rates.min'         => 'The Extra Tax Rate cannot be less than 0%.',
                'extra_tax_rates.max'         => 'The Extra Tax Rate cannot exceed 100%.',
                'tax_id.required_if'          => 'The Tax ID field is required when Tax Registered is checked Yes.',
                'extra_tax_rates.required_if' => 'The Extra Tax Rates field is required when Tax Registered is checked Yes.',
            ]),

            5 => $request->validate(
                [
                    'order_types' => 'required|array|min:1',
                    'table_management_enabled' => 'required|boolean',
                    'online_ordering' => 'required|boolean',

                    'tables' => 'exclude_unless:table_management_enabled,1|integer|min:1',
                    'table_details' => 'exclude_unless:table_management_enabled,1|array|min:1',
                    'table_details.*.name' => 'exclude_unless:table_management_enabled,1|string|max:255',
                    'table_details.*.chairs' => 'exclude_unless:table_management_enabled,1|integer|min:1',
                ],
                [
                    'table_details.*.name.*' => 'Please Enter Tables Details. Click on Enter Names.',
                    'table_details.*.chairs.*' => 'Please Enter Tables Details. Click on Enter Names.',
                    'table_details.*.required' => 'Please Enter Tables Details. Click on Enter Names.',
                ]
            ),

            6 => $request->validate([
                'receipt_header' => 'required|string|max:2000',
                'receipt_footer' => 'required|string|max:2000',
                'receipt_logo' => 'nullable',
                // FIXED: Check existing data properly
                'receipt_logo_file' => (!empty($existingData['upload_id']) || !empty($existingData['receipt_logo_url']))
                    ? 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048'
                    : 'required|file|mimes:jpeg,jpg,png,webp|max:2048',
                'show_qr_on_receipt' => 'required|boolean',
                'customer_printer' => 'nullable|string|max:255',
                'kot_printer' => 'nullable|string|max:255',
                'tax_breakdown_on_receipt' => 'required|boolean',
                'printers' => 'nullable|array',
            ], [
                'receipt_logo_file.required' => 'Please upload a receipt logo.',
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
            'is_tax_registered',
            'order_types',
            'table_management_enabled',
            'online_ordering_enabled',
            'number_of_tables',
            'table_details',
            'receipt_logo_path',
            'show_qr_on_receipt',
            'tax_breakdown_on_receipt',
            'kitchen_printer_enabled',
            'business_hours',
            'auto_disable_after_hours',
            'business_name',
            'legal_name',
            'phone',
            'email',
            'address',
            'website',
            'currency',
            'timezone_id',
            'language',
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

                //  Step 1: map country info
                if ($i === 1 && !empty($data['step1']['country_id'])) {
                    $country = \App\Models\Country::where('id', $data['step1']['country_id'])->first();
                    if ($country) {
                        $data['step1']['country_code'] = $country->iso2;
                        $data['step1']['country_name'] = $country->name;
                    }
                }

                if ($i === 2 && $stepData) {
                    $data['step2'] = $stepData->toArray();

                    // Extract phone breakdown from full phone number
                    if (!empty($data['step2']['phone'])) {
                        $fullPhone = $data['step2']['phone'];

                        // Get all countries and sort by phone_code length (longest first)
                        $countries = \App\Models\Country::whereNotNull('phone_code')
                            ->where('phone_code', '!=', '')
                            ->get()
                            ->sortByDesc(function ($c) {
                                return strlen($c->phone_code);
                            });

                        // Find matching country
                        foreach ($countries as $country) {
                            if (str_starts_with($fullPhone, $country->phone_code)) {
                                $data['step2']['phone_country'] = $country->iso2;
                                $data['step2']['phone_code'] = $country->phone_code;
                                $data['step2']['phone_local'] = substr($fullPhone, strlen($country->phone_code));
                                break;
                            }
                        }
                    }

                    // Load logo
                    $uploadId = $stepData->upload_id ?? null;
                    $data['step2']['logo_url'] = UploadHelper::url($uploadId) ?? asset('assets/img/default.png');
                }


                if ($i === 5 && $stepData) {
                    $data['step5'] = $stepData->toArray();

                    // Fetch profile_table linked to this step
                    if (!empty($stepData->profile_table_id)) {
                        $profileTable = \App\Models\ProfileTable::find($stepData->profile_table_id);
                        if ($profileTable) {
                            $data['step5']['tables'] = $profileTable->number_of_tables;
                            $data['step5']['table_details'] = $profileTable->table_details ?? [];
                        }
                    }
                }


                //  Special handling for step 8 (business hours)
                if ($i === 8 && $stepData) {
                    $businessHours = BusinessHour::where('user_id', $userId)->get();

                    $data['step8']['hours'] = $businessHours->map(function ($h) {
                        return [
                            'id' => $h->id,
                            'day' => $h->day,
                            'is_open' => (bool)$h->is_open,
                            'from' => $h->from ?? '09:00',
                            'to' => $h->to ?? '17:00',
                            'breaks' => $h->break_from && $h->break_to ? [
                                ['start' => $h->break_from, 'end' => $h->break_to]
                            ] : [],
                        ];
                    })->toArray();
                }

                if ($i === 9 && !empty($data['step9'])) {
                    // Map database columns back to form fields
                    $data['step9']['feat_loyalty'] = ($data['step9']['enable_loyalty_system'] ?? false) ? 'yes' : 'no';
                    $data['step9']['feat_inventory'] = ($data['step9']['enable_inventory_tracking'] ?? false) ? 'yes' : 'no';
                    $data['step9']['feat_backup'] = ($data['step9']['enable_cloud_backup'] ?? false) ? 'yes' : 'no';
                    $data['step9']['feat_multilocation'] = ($data['step9']['enable_multi_location'] ?? false) ? 'yes' : 'no';
                    $data['step9']['feat_theme'] = !empty($data['step9']['theme_preference']) ? 'yes' : 'no';
                }

                //  Step 2: Logo using UploadHelper
                if ($i === 2 && $stepData) {
                    $uploadId = $stepData->upload_id ?? null;
                    $data['step2']['logo_url'] = UploadHelper::url($uploadId) ?? asset('assets/img/default.png');
                }
                if ($i === 6 && $stepData) {
                    $uploadId = $stepData->upload_id ?? null;
                    $data['step6']['receipt_logo'] = UploadHelper::url($uploadId) ?? asset('assets/img/default.png');
                }
            }
        }

        return $data;
    }
}

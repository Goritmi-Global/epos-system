<?php

namespace App\Http\Controllers;

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

        // Accept aliases from frontend (common)
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
            if (array_key_exists($alias, $payload) && ! array_key_exists($canonical, $payload)) {
                $payload[$canonical] = $payload[$alias];
            }
        }

        // normalize yes/no strings to booleans for likely fields
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
        // dd($request->all());
        // merge normalized payload into request so we can reuse $request->validate(...)
        $request->replace($payload);
        // Validate per step
        $data = match ($step) {
            1 => $request->validate([
                'country_code' => 'required|string|exists:countries,iso2',
                'timezone' => 'required|string|max:100',
                'language' => 'required|string|max:10',
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
            8 => $request->validate([
                'auto_disable' => 'required|in:yes,no',
                'hours' => 'required|array|size:7', // 7 days a week
                'hours.*.name' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'hours.*.open' => 'required|boolean',
                'hours.*.start' => 'required_if:hours.*.open,1|date_format:H:i',
                'hours.*.end' => 'required_if:hours.*.open,1|date_format:H:i|after:hours.*.start',
                'hours.*.breaks' => 'nullable|array',
                'hours.*.breaks.*.start' => 'required_with:hours.*.breaks|date_format:H:i',
                'hours.*.breaks.*.end' => 'required_with:hours.*.breaks|date_format:H:i|after:hours.*.breaks.*.start',
            ]),
            9 => $request->validate([
                'feat_loyalty'      => 'required|in:yes,no',
                'feat_inventory'    => 'required|in:yes,no',
                'feat_backup'       => 'required|in:yes,no',
                'feat_multilocation' => 'required|in:yes,no',
                'feat_theme'     => 'required|in:yes,no',
            ]),
            default => []
        };


        if ($step === 1 && !empty($data['country_code'])) {
            $country = Country::where('iso2', $data['country_code'])->first();
            $data['country_id'] = $country->id ?? null;
        }

        // Store data temporarily in session
        // $tempData = session()->get('onboarding_data', []);
        // $tempData = array_merge($tempData, $data);
        // session()->put('onboarding_data', $tempData);
        $tempData = session()->get('onboarding_data', []);
        $tempData[$step] = $data; // store per step
        session()->put('onboarding_data', $tempData);


        // Update progress tracking
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
            'message' => 'Step ' . $step . ' data saved temporarily',
        ]);
    }

    /**
     * Save all step data to database tables and finalize
     */
    public function complete(Request $request)
    {
        $user = $request->user();
        // Get all temporary data from session
        $tempData = session()->get('onboarding_data', []);

        if (empty($tempData)) {
            return response()->json([
                'error' => 'No onboarding data found. Please complete all steps first.',
            ], 400);
        }

        // Separate data by steps for saving to respective tables
        $stepData = $this->separateDataBySteps($tempData);
        // Save to individual step tables
        if (! empty($stepData[1])) {
            ProfileStep1::updateOrCreate(['user_id' => $user->id], $stepData[1]);
        }
        if (! empty($stepData[2])) {
            ProfileStep2::updateOrCreate(['user_id' => $user->id], $stepData[2]);
        }
        if (! empty($stepData[3])) {
            ProfileStep3::updateOrCreate(['user_id' => $user->id], $stepData[3]);
        }
        if (! empty($stepData[4])) {
            ProfileStep4::updateOrCreate(['user_id' => $user->id], $stepData[4]);
        }
        if (! empty($stepData[5])) {
            ProfileStep5::updateOrCreate(['user_id' => $user->id], $stepData[5]);
        }
        if (! empty($stepData[6])) {
            ProfileStep6::updateOrCreate(['user_id' => $user->id], $stepData[6]);
        }
        if (! empty($stepData[7])) {
            ProfileStep7::updateOrCreate(['user_id' => $user->id], $stepData[7]);
        }
        if (!empty($stepData[8])) {
            $step8 = $stepData[8];

            // 1️⃣ Save disable_order_after_hours
            $disable = DisableOrderAfterHour::updateOrCreate(
                ['user_id' => $user->id],
                ['status' => $step8['auto_disable'] === 'yes']
            );

            // 2️⃣ Save business_hours (all 7 days)
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

                // Save breaks if any (optional: you might want a separate table if multiple breaks per day)
                if (!empty($day['breaks'])) {
                    foreach ($day['breaks'] as $break) {
                        $bh->break_from = $break['start'];
                        $bh->break_to = $break['end'];
                        $bh->save();
                    }
                }

                $businessHourIds[] = $bh->id;
            }

            // 3️⃣ Save profile_step_8 linking table
            ProfileStep8::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'disable_order_after_hours_id' => $disable->id,
                    'business_hours_id' => $businessHourIds[0] ?? null, // or handle multiple IDs differently
                ]
            );
        }

        if (! empty($stepData[9])) {
            $step9 = $stepData[9];

            ProfileStep9::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'enable_loyalty_system'     => $step9['feat_loyalty'] === 'yes',
                    'enable_inventory_tracking' => $step9['feat_inventory'] === 'yes',
                    'enable_cloud_backup'       => $step9['feat_backup'] === 'yes',
                    'enable_multi_location'     => $step9['feat_multilocation'] === 'yes',
                    'theme_preference'          => $step9['feat_theme'] === 'yes' ? 'default_theme' : null,
                ]
            );
        }



        // Map step fields -> restaurant_profiles columns
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
            $targetKey = $map[$k] ?? $k;
            $toSave[$targetKey] = $v;
        }

        $toSave['status'] = 'complete';

        // Create final restaurant profile
        $profile = RestaurantProfile::updateOrCreate(
            ['user_id' => $user->id],
            $toSave
        );

        // Mark progress as completed
        $progress = OnboardingProgress::firstOrCreate(['user_id' => $user->id]);
        $progress->completed_steps = collect($progress->completed_steps ?? [])->merge(range(1, 9))->unique()->values();
        $progress->current_step = 10;
        $progress->is_completed = true;
        $progress->completed_at = now();
        $progress->save();

        // Clear temporary session data
        session()->forget('onboarding_data');

        return redirect('/dashboard');
    }

    /**
     * Helper method to separate merged data back into step-specific arrays
     */
    private function separateDataBySteps(array $tempData): array
    {
        $stepFields = [
            1 => ['country_id', 'timezone', 'language', 'languages_supported'],
            2 => ['business_name', 'business_type', 'legal_name', 'phone', 'email', 'address', 'website', 'logo_path'],
            3 => ['currency', 'currency_symbol_position', 'number_format', 'date_format', 'time_format'],
            4 => ['is_tax_registered', 'tax_type', 'tax_id', 'tax_rate', 'extra_tax_rates', 'price_includes_tax'],
            5 => ['order_types', 'table_management_enabled', 'online_ordering_enabled', 'number_of_tables', 'table_details'],
            6 => ['receipt_header', 'receipt_footer', 'receipt_logo_path', 'show_qr_on_receipt', 'tax_breakdown_on_receipt', 'kitchen_printer_enabled', 'printers'],
            7 => ['cash_enabled', 'card_enabled', 'integrated_terminal', 'custom_payment_options', 'default_payment_method'],
            8 => ['auto_disable', 'hours'],
            9 => ['feat_loyalty', 'feat_inventory', 'feat_backup', 'feat_multilocation', 'feat_theme'],
        ];

        $stepData = [];

        foreach ($stepFields as $stepNumber => $fields) {
            $stepData[$stepNumber] = [];

            // 🔑 Only check inside this step’s data
            if (!isset($tempData[$stepNumber])) {
                continue;
            }

            foreach ($fields as $field) {
                if (array_key_exists($field, $tempData[$stepNumber])) {
                    $stepData[$stepNumber][$field] = $tempData[$stepNumber][$field];
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

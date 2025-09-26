<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingRequest;
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
use App\Services\POS\SettingsService;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $service) {}

    public function index()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Fetch all step data
        $profile = RestaurantProfile::where('user_id', $user->id)->first();
        $step1 = ProfileStep1::where('user_id', $user->id)->first();
        $step2 = ProfileStep2::where('user_id', $user->id)->first();
        $step3 = ProfileStep3::where('user_id', $user->id)->first();
        $step4 = ProfileStep4::where('user_id', $user->id)->first();
        $step5 = ProfileStep5::where('user_id', $user->id)->first();
        $step6 = ProfileStep6::where('user_id', $user->id)->first();
        $step7 = ProfileStep7::where('user_id', $user->id)->first();
        $step8 = ProfileStep8::where('user_id', $user->id)->first();
        $step9 = ProfileStep9::where('user_id', $user->id)->first();

        // Merge into single object
        $mergedProfile = array_merge(
            $step1?->toArray() ?? [],
            $step2?->toArray() ?? [],
            $step3?->toArray() ?? [],
            $step4?->toArray() ?? [],
            $step5?->toArray() ?? [],
            $step6?->toArray() ?? [],
            $step7?->toArray() ?? [],
            $step8?->toArray() ?? [],
            $step9?->toArray() ?? [],
            $profile?->toArray() ?? []
        );

        return Inertia::render('Backend/Settings/Index', [
            'profile' => $mergedProfile,
            'completed_steps' => range(1, 9), // all steps completed
        ]);
    }

    public function saveStep(Request $request, int $step)
    {
        $user = $request->user();
        $payload = $request->all();

        // Validate like onboarding
        $data = match ($step) {
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

        // Save per step table
        $stepModel = match ($step) {
            1 => ProfileStep1::class,
            2 => ProfileStep2::class,
            3 => ProfileStep3::class,
            4 => ProfileStep4::class,
            5 => ProfileStep5::class,
            6 => ProfileStep6::class,
            7 => ProfileStep7::class,
            8 => ProfileStep8::class,
            9 => ProfileStep9::class,
        };

        $stepModel::updateOrCreate(['user_id' => $user->id], $data);

        // Merge into RestaurantProfile if needed
        RestaurantProfile::updateOrCreate(['user_id' => $user->id], $data);

        return response()->json(['ok' => true, 'profile' => $data]);
    }
}

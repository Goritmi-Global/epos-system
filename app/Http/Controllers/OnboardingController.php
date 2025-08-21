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
        dd($request);
        $user = $request->user();
        $profile = RestaurantProfile::firstOrCreate(['user_id' => $user->id]);

        $data = match ($step) {
            1 => $request->validate([
                'timezone'            => 'nullable|string|max:100',
                'language'            => 'nullable|string|max:10',
                'languages_supported' => 'nullable|array',
                'languages_supported.*' => 'string|max:10',
            ]),
            2 => $request->validate([
                'business_name' => 'required|string|max:190',
                'legal_name'    => 'nullable|string|max:190',
                'phone'         => 'nullable|string|max:60',
                'email'         => 'nullable|email|max:190',
                'address'       => 'nullable|string|max:500',
                'website'       => 'nullable|url|max:190',
                'logo_path'     => 'nullable|string|max:255',
            ]),
            3 => $request->validate([
                'currency'                => 'required|string|max:8',
                'currency_symbol_position'=> 'required|in:before,after',
                'number_format'           => 'nullable|string|max:20',
                'date_format'             => 'required|string|max:20',
                'time_format'             => 'required|in:12-hour,24-hour',
            ]),
            4 => $request->validate([
                'is_tax_registered' => 'required|boolean',
                'tax_type'          => 'nullable|string|max:50',
                'tax_rate'          => 'nullable|numeric|min:0|max:100',
                'extra_tax_rates'   => 'nullable|array',
                'price_includes_tax'=> 'required|boolean',
            ]),
            5 => $request->validate([
                'order_types'               => 'array|min:1',
                'table_management_enabled'  => 'required|boolean',
                'online_ordering_enabled'   => 'required|boolean',
                'number_of_tables'          => 'nullable|integer|min:0',
            ]),
            6 => $request->validate([
                'receipt_header'           => 'nullable|string|max:2000',
                'receipt_footer'           => 'nullable|string|max:2000',
                'receipt_logo_path'        => 'nullable|string|max:255',
                'show_qr_on_receipt'       => 'required|boolean',
                'tax_breakdown_on_receipt' => 'required|boolean',
                'kitchen_printer_enabled'  => 'required|boolean',
                'printers'                 => 'nullable|array',
            ]),
            7 => $request->validate([
                'cash_enabled'            => 'required|boolean',
                'card_enabled'            => 'required|boolean',
                'integrated_terminal'     => 'nullable|string|max:50',
                'custom_payment_options'  => 'nullable|array',
                'default_payment_method'  => 'nullable|string|max:50',
            ]),
            8 => $request->validate([
                'attendance_policy' => 'nullable|array', // {clock_enabled, require_shift, track_location, allow_late_clockin, require_reason}
            ]),
            9 => $request->validate([
                'business_hours'           => 'required|array',
                'auto_disable_after_hours' => 'required|boolean',
            ]),
            10 => $request->validate([
                'loyalty_enabled'            => 'required|boolean',
                'cloud_backup_enabled'       => 'required|boolean',
                'theme'                      => 'nullable|string|max:30',
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
    // 1. Grab everything
    $profile = $request->input('profile', []);
    $completed = $request->input('completed_steps', []);

    // 2. Debug / inspect
    dd($profile, $completed);

    // 3. Example: validate & save
    $validated = validator($profile, [
        'business_name' => 'required|string|max:255',
        'email'         => 'nullable|email',
        'order_types'   => 'required|array|min:1',
        'hours'         => 'required|array|size:7',
        // … add rules for other fields
    ])->validate();

    $user = $request->user();

    // store JSON on user (or in its own table)
    $user->onboarding_profile      = $validated;
    $user->onboarding_completed_at = now();
    $user->save();

    return response()->json([
        'ok'      => true,
        'profile' => $user->onboarding_profile,
    ]);
}

}

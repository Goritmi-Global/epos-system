<?php

namespace App\Http\Middleware;

use App\Models\ProfileStep1;
use App\Models\ProfileStep2;
use App\Models\ProfileStep3;
use App\Models\ProfileStep4;
use App\Models\ProfileStep5;
use App\Models\ProfileStep6;
use App\Models\ProfileStep7;
use App\Models\ProfileStep8;
use App\Models\ProfileStep9;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Helpers\UploadHelper;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $stripe_public_key = config('app.stripe_public_key');

        // Select only required columns per step
        $fields = [
            'language_and_location' => ['country_id', 'timezone_id', 'language'],
            'business_information'  => ['business_name', 'business_type', 'legal_name', 'phone', 'email', 'address', 'website', 'upload_id'],
            'currency_and_locale'   => ['currency', 'currency_symbol_position', 'number_format', 'date_format', 'time_format'],
            'tax_and_vat'           => ['is_tax_registered', 'tax_type', 'tax_rate', 'tax_id', 'extra_tax_rates', 'price_includes_tax'],
            'service_options'       => ['order_types', 'table_management_enabled', 'online_ordering_enabled', 'profile_table_id'],
            'receipt_and_printers'  => ['receipt_header', 'receipt_footer', 'upload_id', 'show_qr_on_receipt', 'tax_breakdown_on_receipt', 'customer_printer', 'kot_printer', 'printers'],
            'payment_methods'       => ['cash_enabled', 'card_enabled'],
            'business_hours'        => ['disable_order_after_hours_id', 'business_hours_id'],
            'optional_features'     => ['enable_loyalty_system', 'enable_inventory_tracking', 'enable_cloud_backup', 'enable_multi_location', 'theme_preference'],
        ];

        $models = [
            'language_and_location' => ProfileStep1::class,
            'business_information'  => ProfileStep2::class,
            'currency_and_locale'   => ProfileStep3::class,
            'tax_and_vat'           => ProfileStep4::class,
            'service_options'       => ProfileStep5::class,
            'receipt_and_printers'  => ProfileStep6::class,
            'payment_methods'       => ProfileStep7::class,
            'business_hours'        => ProfileStep8::class,
            'optional_features'     => ProfileStep9::class,
        ];

        // Initialize
        $onboarding   = [];
        $businessInfo = null;

        if ($user) {
            // 🔥 GET FIRST SUPER ADMIN'S USER ID (instead of current user)
            $superAdmin = User::where('is_first_super_admin', true)->first();
            $onboardingUserId = $superAdmin ? $superAdmin->id : $user->id; // Fallback to current user if no super admin found

            // Fetch onboarding data using super admin's ID
            foreach ($models as $key => $modelClass) {
                $cols = $fields[$key] ?? ['id'];
                $row  = $modelClass::where('user_id', $onboardingUserId)->select($cols)->first();
                $onboarding[$key] = $row ? $row->toArray() : null;
            }

            // Safely build business info with image_url
            $info     = $onboarding['business_information'] ?? null;
            $uploadId = data_get($info, 'upload_id');
            $imageUrl = $uploadId ? UploadHelper::url((int)$uploadId) : null;

            $businessInfo = $info ? array_merge($info, ['image_url' => $imageUrl]) : null;
        }

        // Normalized formatting block for the SPA
        $fmt = [
            'locale'           => data_get($onboarding, 'language_and_location.language', 'en-US'),
            'dateFormat'       => data_get($onboarding, 'currency_and_locale.date_format', 'yyyy-MM-dd'),
            'timeFormat'       => data_get($onboarding, 'currency_and_locale.time_format', 'HH:mm'),
            'currency'         => strtoupper(data_get($onboarding, 'currency_and_locale.currency', 'PKR')),
            'currencyPosition' => data_get($onboarding, 'currency_and_locale.currency_symbol_position', 'before'),
            'timezone'         => data_get($onboarding, 'language_and_location.timezone', 'UTC'),
            'numberPattern'    => data_get($onboarding, 'currency_and_locale.number_format', '1,234.56'),
        ];

        return [
            ...parent::share($request),

            'current_user' => $user ? [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'pin' => $user->pin,
                'roles'       => $this->getUserRoles(),
                'permissions' => $this->getUserPermissions(),
            ] : null,

            'stripe_public_key' => $stripe_public_key,
            'onboarding' => $onboarding,
            'business_info' => $businessInfo,
            'formatting' => $fmt,

            'flash' => [
                'success'       => fn() => $request->session()->get('success'),
                'error'         => fn() => $request->session()->get('error'),
                'print_payload' => fn() => $request->session()->get('print_payload'),
            ],
        ];
    }

    protected function getUserPermissions()
    {
        if (auth()->check()) {
            return auth()->user()->getAllPermissions()->pluck('name')->toArray();
        }
        return [];
    }

    protected function getUserRoles()
    {
        if (auth()->check()) {
            return auth()->user()->getRoleNames()->toArray();
        }
        return [];
    }
}
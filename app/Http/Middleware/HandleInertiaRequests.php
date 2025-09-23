<?php

namespace App\Http\Middleware;

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
use Inertia\Middleware;

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
        $stripe_public_key = config('app.stripe_public_key');
        $user = $request->user();

        $onboarding = [];
        if ($user) {
            $onboarding = [
                'progress'  => OnboardingProgress::where('user_id', $user->id)->first(),
                'step1'     => ProfileStep1::where('user_id', $user->id)->first(),
                'step2'     => ProfileStep2::where('user_id', $user->id)->first(),
                'step3'     => ProfileStep3::where('user_id', $user->id)->first(),
                'step4'     => ProfileStep4::where('user_id', $user->id)->first(),
                'step5'     => ProfileStep5::where('user_id', $user->id)->first(),
                'step6'     => ProfileStep6::where('user_id', $user->id)->first(),
                'step7'     => ProfileStep7::where('user_id', $user->id)->first(),
                'step8'     => ProfileStep8::where('user_id', $user->id)->first(),
                'step9'     => ProfileStep9::where('user_id', $user->id)->first(),
                'profile'   => RestaurantProfile::where('user_id', $user->id)->first(),
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'stripe_public_key' => $stripe_public_key,
            'onboarding' => $onboarding, 
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
            ],
        ];
    }
}

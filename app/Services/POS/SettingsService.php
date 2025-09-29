<?php

namespace App\Services\POS;

use App\Models\OnboardingProgress;
use App\Models\RestaurantProfile;
use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingsService
{
    public function fetchProfileAndProgress($user)
    {
        // Fetch restaurant profile
        $profile = RestaurantProfile::where('user_id', $user->id)->first();

        // Fetch onboarding progress
        $progress = OnboardingProgress::firstOrCreate(
            ['user_id' => $user->id],
            ['current_step' => 1]
        );

        return [
            'profile' => $profile,
            'progress' => $progress,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public function saveMany(array $data): void
    {
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

<?php

namespace App\Services\POS;

use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingsService
{
    public function all(): Collection
    {
        return Setting::all()->pluck('value', 'key');
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

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Timezone;

class TimezonesTableSeeder extends Seeder
{
    public function run(): void
    {
        $timezones = [
            // UK
            ['name' => 'Europe/London', 'gmt_offset' => '+01:00', 'is_default' => true],

            // Pakistan
            ['name' => 'Asia/Karachi', 'gmt_offset' => '+05:00', 'is_default' => true],

            // India
            ['name' => 'Asia/Kolkata', 'gmt_offset' => '+05:30', 'is_default' => true],

            // UAE
            ['name' => 'Asia/Dubai', 'gmt_offset' => '+04:00', 'is_default' => true],

            // USA
            ['name' => 'America/New_York', 'gmt_offset' => '-04:00', 'is_default' => true],
            ['name' => 'America/Los_Angeles', 'gmt_offset' => '-07:00', 'is_default' => false],
        ];

        foreach ($timezones as $tz) {
            Timezone::create($tz);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Timezone;
use Illuminate\Support\Facades\DB;

class TimezonesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Disable FK checks for truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Timezone::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $timezones = [
            ['name' => 'Europe/London', 'gmt_offset' => '+01:00', 'is_default' => true, 'country_id' => 'GB'],
            ['name' => 'Asia/Karachi', 'gmt_offset' => '+05:00', 'is_default' => true, 'country_id' => 'PK'],
            ['name' => 'Asia/Kolkata', 'gmt_offset' => '+05:30', 'is_default' => true, 'country_id' => 'IN'],
            ['name' => 'Asia/Dubai', 'gmt_offset' => '+04:00', 'is_default' => true, 'country_id' => 'AE'], // Corrected to ISO2
            ['name' => 'America/New_York', 'gmt_offset' => '-04:00', 'is_default' => true, 'country_id' => 'US'],
            ['name' => 'America/Los_Angeles', 'gmt_offset' => '-07:00', 'is_default' => false, 'country_id' => 'US'],
        ];

        foreach ($timezones as $tz) {
            Timezone::create($tz);
        }
    }
}
`
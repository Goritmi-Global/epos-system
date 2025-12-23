<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WalkInCounter;

class WalkInCounterSeeder extends Seeder
{
    public function run(): void
    {
        WalkInCounter::firstOrCreate(
            ['id' => 1],
            ['current_number' => 1]
        );
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            "Vegan",
            "Vegetarian",
            "Halal",
            "Kosher",
            "Organic",
            "Locally Sourced",
            "Fairtrade",
            "Spicy",
            "Free-From Nuts",
            "Contains Soy",
            "Free-From Egg",
            "Sugar-Free",
            "Ethically Sourced",
            "Red Tractor Certified",
            "Scottish Produce",
            "Welsh Lamb",
        ];

        foreach ($tags as $tagName) {
            Tag::create([
                'name' => $tagName,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Allergy;

class AllergySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allergies = [
            "Crustaceans",
            "Eggs",
            "Fish",
            "Lupin",
            "Milk",
            "Molluscs",
            "Mustard",
            "Peanuts",
            "Sesame seeds",
            "Soybeans",
            "Sulphur dioxide / sulphites",
            "Tree nuts",
        ];

        foreach ($allergies as $allergyName) {
            Allergy::create([
                'name' => $allergyName,
                'description' => null, // optional, can add details later
            ]);
        }
    }
}

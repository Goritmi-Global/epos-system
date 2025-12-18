<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Allergy;

class AllergySeeder extends Seeder
{
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
            Allergy::updateOrCreate(
                ['name' => $allergyName], // search condition
                ['description' => null]   // values to update
            );
        }
    }
}

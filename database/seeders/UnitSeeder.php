<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Base Units
        $baseUnits = [
            'Kilogram (kg)',
            'Gram (g)',
            'Milligram (mg)',
            'Pound (lb)',
            'Ounce (oz)',
            'Litre (L)',
            'Millilitre (ml)',
            'Pint (pt)',
            'Gallon (gal)',
            'Piece (pc)',
            'Dozen (doz)',
            'Crate',
            'Box',
            'Bottle',
            'Pack',
            'Carton',
            'Bag',
            'Bundle',
            'Bunch',
            'Serving',
        ];

        $baseUnitModels = [];

        // Create all base units
        foreach ($baseUnits as $unitName) {
            $unit = Unit::create([
                'name' => $unitName,
                'base_unit_id' => null,
                'conversion_factor' => 1,
            ]);
            $baseUnitModels[$unitName] = $unit;
        }

        // Add derived units (5 examples)
        $derivedUnits = [
            ['name' => 'Gram (g)', 'base' => 'Kilogram (kg)', 'factor' => 0.001],
            ['name' => 'Milligram (mg)', 'base' => 'Kilogram (kg)', 'factor' => 0.000001],
            ['name' => 'Millilitre (ml)', 'base' => 'Litre (L)', 'factor' => 0.001],
            ['name' => 'Dozen (doz)', 'base' => 'Piece (pc)', 'factor' => 12],
            ['name' => 'Bundle', 'base' => 'Piece (pc)', 'factor' => 5],
        ];

        foreach ($derivedUnits as $d) {
            Unit::create([
                'name' => $d['name'],
                'base_unit_id' => $baseUnitModels[$d['base']]->id,
                'conversion_factor' => $d['factor'],
            ]);
        }
    }
}

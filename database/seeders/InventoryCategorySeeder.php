<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryCategory;

class InventoryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Fruits', 'icon' => 'Apple', 'sub' => 'Citrus'],
            ['name' => 'Vegetables', 'icon' => 'Zap', 'sub' => 'Leafy Greens'],
            ['name' => 'Beverages', 'icon' => 'Coffee', 'sub' => 'Hot Drinks'],
            ['name' => 'Snacks', 'icon' => 'Layers', 'sub' => 'Chips'],
            ['name' => 'Bakery', 'icon' => 'Bread', 'sub' => 'Bread Loaves'],
        ];

        foreach ($categories as $cat) {
            // Create parent category
            $parent = InventoryCategory::create([
                'name' => $cat['name'],
                'icon' => $cat['icon'],
                'active' => true,
                'total_value' => 0,
                'total_items' => 0,
                'out_of_stock' => 0,
                'low_stock' => 0,
                'in_stock' => 0,
            ]);

            // Create one subcategory
            InventoryCategory::create([
                'name' => $cat['sub'],
                'icon' => $cat['icon'], // same icon as parent or choose different if needed
                'active' => true,
                'parent_id' => $parent->id,
                'total_value' => 0,
                'total_items' => 0,
                'out_of_stock' => 0,
                'low_stock' => 0,
                'in_stock' => 0,
            ]);
        }
    }
}

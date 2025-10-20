<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuCategory;
use App\Models\Upload;
use Illuminate\Support\Facades\Storage;

class MenuCategorySeeder extends Seeder
{
    public function run()
    {
        // Parent category → Image mapping
        $categories = [
            'Hot Drinks' => 'coffee.png',
            'Cold Drinks' => 'soda.png',
            'Breakfast' => 'english-breakfast.png',
            'Street Snacks' => 'food.png',
            'Wala Wraps' => 'burrito.png',
            'Bombay Toasties' => 'food (1).png',
            'Salads' => 'salad.png',
            'Bombay Bowls' => 'porridge.png',
            'Bakery' => 'cake.png',
            'Soup' => 'bowl.png',
            'Desserts' => 'cupcake.png',
            'Ice Cream' => 'ice-cream.png',
        ];

        foreach ($categories as $name => $imageFile) {
            $upload = $this->getOrCreateUpload($imageFile);

            // Create or update parent category
            $parent = MenuCategory::updateOrCreate(
                ['name' => $name, 'parent_id' => null],
                [
                    'upload_id' => $upload?->id,
                    'active' => true,
                    'total_value' => 0,
                    'total_items' => 0,
                    'out_of_stock' => 0,
                    'low_stock' => 0,
                    'in_stock' => 0,
                ]
            );

            // ✅ Only one subcategory for demonstration (e.g. Hot Drinks → Tea)
            if ($name === 'Hot Drinks') {
                MenuCategory::updateOrCreate(
                    ['name' => 'Tea', 'parent_id' => $parent->id],
                    [
                        'active' => true,
                        'total_value' => 0,
                        'total_items' => 0,
                        'out_of_stock' => 0,
                        'low_stock' => 0,
                        'in_stock' => 0,
                        'upload_id' => null, // ❌ No image for subcategory
                    ]
                );
            }
        }

        // $this->command->info('✅ Menu categories (with one subcategory) seeded successfully!');
    }

    /**
     * Helper to copy image and create Upload record if not exists.
     */
    private function getOrCreateUpload(string $imageFile): ?Upload
    {
        $imagePath = public_path('assets/img/' . $imageFile);

        if (!file_exists($imagePath)) {
            $this->command->warn("⚠️ Image not found: $imagePath");
            return null;
        }

        $storedPath = 'uploads/' . $imageFile;

        // Copy image if missing
        if (!Storage::disk('public')->exists($storedPath)) {
            Storage::disk('public')->put(
                $storedPath,
                file_get_contents($imagePath)
            );
        }

        return Upload::firstOrCreate(
            ['file_name' => $storedPath],
            [
                'file_original_name' => $imageFile,
                'file_size' => filesize($imagePath),
                'extension' => pathinfo($imageFile, PATHINFO_EXTENSION),
                'type' => mime_content_type($imagePath),
            ]
        );
    }
}

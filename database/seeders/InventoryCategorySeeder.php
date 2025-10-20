<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use App\Helpers\UploadHelper;
use App\Models\Upload;
use App\Models\InventoryCategory;

class InventoryCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Produce (Veg/Fruit)' => 'vegetable.png',
            'Meat' => 'meat.png',
            'Poultry' => 'poultry.png',
            'Dairy' => 'dairy.png',
            'Grains & Rice' => 'rice.png',
            'Spices & Herbs' => 'spice.png',
            'Oils & Fats' => 'olive-oil.png',
            'Sauces & Condiments' => 'sauces.png',
            'Nuts & Seeds' => 'nuts.png',
            'Other' => 'other.png',
        ];


        foreach ($categories as $name => $fileName) {
            $path = public_path('assets/img/' . $fileName);

            if (!file_exists($path)) {
                $this->command->warn("⚠️ Missing file: $fileName");
                continue;
            }

            // Create Upload entry (if not already exists)
            $upload = Upload::where('file_original_name', $fileName)->first();

            if (!$upload) {
                $file = new UploadedFile(
                    $path,
                    $fileName,
                    mime_content_type($path),
                    null,
                    true
                );

                $upload = UploadHelper::store($file);
                // $this->command->info("✅ Uploaded $fileName (ID: {$upload->id})");
            } else {
                // $this->command->info("ℹ️ Already uploaded: $fileName (ID: {$upload->id})");
            }

            // Create category (skip if already exists)
            InventoryCategory::firstOrCreate(
                ['name' => $name],
                [
                    'upload_id' => $upload->id,
                    'active' => true,
                    'total_value' => 0,
                    'total_items' => 0,
                    'out_of_stock' => 0,
                    'low_stock' => 0,
                    'in_stock' => 0,
                ]
            );
        }
    }
}

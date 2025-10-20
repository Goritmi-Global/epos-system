<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use App\Helpers\UploadHelper;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\Tag;
use App\Models\Allergy;
use App\Models\InventoryItemNutrition;
use Carbon\Carbon;

class InventoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Cappuccino',
                'description' => 'Hot coffee with steamed milk foam',
                'minAlert' => 5,
                'unit_name' => 'Millilitre (ml)',
                'category_name' => 'Other',
                'image' => 'coffee.png',
                'nutrition' => ['calories' => 150, 'protein' => 6, 'fat' => 4, 'carbs' => 18],
                'tags' => ['Organic', 'Fairtrade'],
                'allergies' => [],
            ],
            [
                'name' => 'Cheese Sandwich',
                'description' => 'Toasted sandwich with cheddar cheese',
                'minAlert' => 10,
                'unit_name' => 'Piece (pc)',
                'category_name' => 'Dairy',
                'image' => 'food.png',
                'nutrition' => ['calories' => 300, 'protein' => 12, 'fat' => 10, 'carbs' => 40],
                'tags' => ['Vegetarian'],
                'allergies' => ['Milk', 'Eggs'],
            ],
            [
                'name' => 'Chocolate Muffin',
                'description' => 'Rich chocolate muffin',
                'minAlert' => 8,
                'unit_name' => 'Piece (pc)',
                'category_name' => 'Other',
                'image' => 'cake.png',
                'nutrition' => ['calories' => 350, 'protein' => 5, 'fat' => 15, 'carbs' => 50],
                'tags' => ['Vegetarian'],
                'allergies' => ['Eggs', 'Milk', 'Tree nuts'],
            ],
            [
                'name' => 'Green Salad',
                'description' => 'Fresh green salad with dressing',
                'minAlert' => 5,
                'unit_name' => 'Gram (g)',
                'category_name' => 'Produce (Veg/Fruit)',
                'image' => 'salad.png',
                'nutrition' => ['calories' => 80, 'protein' => 2, 'fat' => 4, 'carbs' => 10],
                'tags' => ['Vegan', 'Organic'],
                'allergies' => [],
            ],
            [
                'name' => 'Vanilla Ice Cream',
                'description' => 'Creamy vanilla ice cream scoop',
                'minAlert' => 10,
                'unit_name' => 'Millilitre (ml)',
                'category_name' => 'Dairy',
                'image' => 'ice-cream.png',
                'nutrition' => ['calories' => 200, 'protein' => 4, 'fat' => 8, 'carbs' => 28],
                'tags' => ['Vegetarian', 'Organic'],
                'allergies' => ['Milk'],
            ],
        ];


        $categories = InventoryCategory::all()->keyBy('name');
        $units = Unit::all()->keyBy('name');
        $suppliers = Supplier::all();
        $allergies = Allergy::all()->keyBy('name');
        $tags = Tag::all()->keyBy('name');

        if ($suppliers->isEmpty()) {
            throw new \Exception("No suppliers found! Please seed the suppliers table first.");
        }

        $now = Carbon::now();

        foreach ($items as $data) {
            // Category
            if (!isset($categories[$data['category_name']])) {
                $this->command->warn("Category not found: {$data['category_name']}, skipping item {$data['name']}.");
                continue;
            }
            $data['category_id'] = $categories[$data['category_name']]->id;

            // Unit
            if (!isset($units[$data['unit_name']])) {
                $this->command->warn("Unit not found: {$data['unit_name']}, skipping item {$data['name']}.");
                continue;
            }
            $data['unit_id'] = $units[$data['unit_name']]->id;

            // Suppliers: pick all suppliers for this category if multiple exist
            $categorySuppliers = $suppliers->filter(
                fn($s) =>
                stripos($s->preferred_items, $data['category_name']) !== false
            );
            if ($categorySuppliers->isEmpty()) {
                // fallback: random supplier
                $supplier = $suppliers->random();
                $data['supplier_id'] = $supplier->id;
            } else {
                // pick one randomly from matching suppliers
                $supplier = $categorySuppliers->random();
                $data['supplier_id'] = $supplier->id;
            }

            // Upload image
            $path = public_path('assets/img/' . $data['image']);
            if (!file_exists($path)) {
                $this->command->warn("Image not found: {$data['image']}, skipping item {$data['name']}.");
                continue;
            }
            $file = new UploadedFile($path, $data['image'], mime_content_type($path), null, true);
            $upload = UploadHelper::store($file);
            $data['upload_id'] = $upload->id;
            unset($data['image']);

            // Determine suitable tags (match by keyword in item name)
            $itemTags = [];
            foreach ($tags as $tag) {
                if (stripos($data['name'], $tag->name) !== false) {
                    $itemTags[$tag->id] = ['created_at' => $now, 'updated_at' => $now];
                }
            }

            // Determine suitable allergies (based on keywords in name/description)
            $itemAllergies = [];
            foreach ($allergies as $allergy) {
                if (stripos($data['name'], $allergy->name) !== false || stripos($data['description'], $allergy->name) !== false) {
                    $itemAllergies[$allergy->id] = ['created_at' => $now, 'updated_at' => $now];
                }
            }

            // Create item
            $item = InventoryItem::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'minAlert' => $data['minAlert'],
                'unit_id' => $data['unit_id'],
                'category_id' => $data['category_id'],
                'supplier_id' => $data['supplier_id'],
                'upload_id' => $data['upload_id'],
                'user_id' => 1,
            ]);

            // Attach tags and allergies with timestamps
            if (!empty($itemTags)) $item->tags()->sync($itemTags);
            if (!empty($itemAllergies)) $item->allergies()->sync($itemAllergies);

            // Add nutrition
            if (!empty($data['nutrition'])) {
                InventoryItemNutrition::updateOrCreate(
                    ['inventory_item_id' => $item->id],
                    $data['nutrition']
                );
            }

            $this->command->info("✅ Created Food Item: {$item->name} (ID: {$item->id})");
        }
    }
}

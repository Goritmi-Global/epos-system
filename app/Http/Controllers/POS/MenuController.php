<?php

namespace App\Http\Controllers\POS;

use App\Helpers\UploadHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Models\AddonGroup;
use App\Models\Allergy;
use App\Models\Meal;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tag;
use App\Models\VariantGroup;
use App\Services\POS\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MenuController extends Controller
{
    public function __construct(private MenuService $service) {}

    public function index(Request $request)
    {
        $categories = MenuCategory::active()
            ->whereNull('parent_id')
            ->with('children')
            ->get(['id', 'name', 'parent_id']);
        $allergies = Allergy::all(['id', 'name']);
        $tags = Tag::all(['id', 'name']);
        $meals = Meal::all(['id', 'name', 'start_time', 'end_time']);
        $variantGroups = VariantGroup::with('variants:id,name,variant_group_id')->get(['id', 'name']);
        $addonGroups = AddonGroup::select('id', 'name', 'min_select', 'max_select', 'description', 'status')
            ->where('status', 'active')
            ->get();

        return Inertia::render('Backend/Menu/Index', [
            'categories' => $categories,
            'allergies' => $allergies,
            'tags' => $tags,
            'meals' => $meals,
            'variantGroups' => $variantGroups,
            'addonGroups' => $addonGroups,
        ]);
    }

    public function create()
    {
        return Inertia::render('Menu/Form', ['mode' => 'create']);
    }

    public function store(StoreMenuRequest $request)
    {
        try {
            $menu = $this->service->create($request->validated(), $request);

            return response()->json([
                'message' => 'Menu created successfully',
                'data' => $menu,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create menu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateMenuRequest $request, MenuItem $menu)
    {
        try {
            $updatedMenu = $this->service->update($menu, $request->validated(), $request);

            return response()->json([
                'message' => 'Menu updated successfully',
                'data' => $updatedMenu,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update menu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function apiIndex()
    {
        $menus = MenuItem::with([
            'category',
            'ingredients',
            'variants.ingredients', // ✅ Include variant ingredients
            'allergies',
            'tags',
            'nutrition',
            'addonGroupRelations.addonGroup',
        ])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'description' => $item->description,
                    'is_taxable' => $item->is_taxable,
                    'label_color' => $item->label_color,
                    'status' => $item->status,
                    'category' => $item->category,
                    'meals' => $item->meals,
                    'ingredients' => $item->ingredients,
                    'nutrition' => $item->nutrition,
                    'allergies' => $item->allergies,
                    'tags' => $item->tags,
                    'image_url' => UploadHelper::url($item->upload_id),
                    'addon_group_id' => $item->addonGroupRelations->first()?->addon_group_id,

                    'is_saleable' => $item->is_saleable,
                    'resale_type' => $item->resale_type,
                    'resale_value' => $item->resale_value,
                    'resale_price' => $item->resale_price,

                    // ✅ Include variant info with ingredients and prices
                    'variants' => $item->variants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'price' => $variant->price,
                            'ingredients' => $variant->ingredients->map(function ($ing) {
                                return [
                                    'inventory_item_id' => $ing->inventory_item_id,
                                    'product_name' => $ing->product_name,
                                    'quantity' => $ing->quantity,
                                    'cost' => $ing->cost,
                                ];
                            }),
                        ];
                    }),
                ];
            });

        return response()->json([
            'message' => 'Menu items fetched successfully',
            'data' => $menus,
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $menu = MenuItem::findOrFail($id);
        $menu->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status updated successfully',
            'status' => $menu->status,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $items = $request->input('items', []);

        foreach ($items as $row) {
            // ✅ 1. Handle Category properly using MenuCategory model
            $category = null;
            if (! empty($row['category'])) {
                $category = MenuCategory::firstOrCreate(
                    ['name' => $row['category']],
                    ['icon' => '📦', 'active' => 1]
                );
            }

            // ✅ 2. Create Menu Item with price
            $item = MenuItem::create([
                'name' => $row['name'] ?? '',
                'price' => $row['price'] ?? 0,
                'description' => $row['description'] ?? '',
                'category_id' => $category?->id,
                'active' => $row['active'] ?? 1,
            ]);

            // ✅ 3. Nutrition (optional)
            if (! empty($row['calories']) || ! empty($row['fat']) || ! empty($row['protein']) || ! empty($row['carbs'])) {
                DB::table('menu_nutrition')->insert([
                    'menu_item_id' => $item->id,
                    'calories' => $row['calories'] ?? 0,
                    'fat' => $row['fat'] ?? 0,
                    'protein' => $row['protein'] ?? 0,
                    'carbs' => $row['carbs'] ?? 0,
                ]);
            }

            // ✅ 4. Handle Allergies (comma-separated string or array)
            if (! empty($row['allergies'])) {
                $allergies = is_array($row['allergies'])
                    ? $row['allergies']
                    : array_map('trim', explode(',', $row['allergies']));

                foreach ($allergies as $allergyName) {
                    if (! empty($allergyName)) {
                        // Find or create the allergy
                        $allergy = Allergy::firstOrCreate(
                            ['name' => $allergyName],
                            ['description' => '', 'active' => 1]
                        );

                        // Attach to menu item via pivot table
                        DB::table('menu_allergies')->insertOrIgnore([
                            'menu_item_id' => $item->id,
                            'allergy_id' => $allergy->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // ✅ 5. Handle Tags (comma-separated string, array, or IDs)
            if (! empty($row['tags'])) {
                $tags = is_array($row['tags'])
                    ? $row['tags']
                    : array_map('trim', explode(',', $row['tags']));

                foreach ($tags as $tagValue) {
                    if (! empty($tagValue)) {
                        // If it's numeric, treat as tag ID; otherwise find/create by name
                        if (is_numeric($tagValue)) {
                            $tagId = (int) $tagValue;
                        } else {
                            $tag = Tag::firstOrCreate(
                                ['name' => $tagValue],
                                ['description' => '', 'active' => 1]
                            );
                            $tagId = $tag->id;
                        }

                        // Attach to menu item via pivot table
                        DB::table('menu_tags')->insertOrIgnore([
                            'menu_item_id' => $item->id,
                            'tag_id' => $tagId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        return response()->json(['message' => 'Items imported successfully']);
    }
}

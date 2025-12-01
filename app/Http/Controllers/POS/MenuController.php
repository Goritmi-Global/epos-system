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
use App\Models\MenuVariant;
use App\Models\MenuVariantIngredient;
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

    public function apiIndex(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default 10 items per page

        $menus = MenuItem::with([
            'category',
            'ingredients',
            'variants.ingredients',
            'allergies',
            'tags',
            'nutrition',
            'addonGroupRelations.addonGroup',
            'meals',
        ])
            ->paginate($perPage)
            ->through(function ($item) {
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
                    'created_at' => $item->created_at,
                    'variants' => $item->variants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'price' => $variant->price,
                            'is_saleable' => $variant->is_saleable,
                            'resale_type' => $variant->resale_type,
                            'resale_value' => $variant->resale_value,
                            'resale_price' => $variant->resale_price,
                            'display_name' => $variant->display_name,
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

        $totalCount = MenuItem::count();
        $activeCount = MenuItem::where('status', 1)->count();
        $inactiveCount = MenuItem::where('status', 0)->count();

        return response()->json([
            'message' => 'Menu items fetched successfully',
            'data' => $menus->items(),
            'pagination' => [
                'current_page' => $menus->currentPage(),
                'last_page' => $menus->lastPage(),
                'per_page' => $menus->perPage(),
                'total' => $menus->total(),
                'from' => $menus->firstItem(),
                'to' => $menus->lastItem(),
                'links' => $menus->linkCollection()->toArray(),
            ],
            'counts' => [
            'total' => $totalCount,
            'active' => $activeCount,
            'inactive' => $inactiveCount,
        ],
        ]);
    }

    private function calculateResalePrice($item)
    {
        if (! $item->is_saleable || ! $item->resale_type || ! $item->resale_value) {
            return 0;
        }

        if ($item->resale_type === 'flat') {
            return (float) $item->resale_value;
        }

        if ($item->resale_type === 'percentage') {
            return (float) ($item->price * ($item->resale_value / 100));
        }

        return 0;
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

        DB::beginTransaction();

        try {
            foreach ($items as $row) {
                // Determine if this is a variant menu
                $isVariantMenu = ! empty($row['variant_data']) && is_array($row['variant_data']);

                // Handle Category
                $category = null;
                if (! empty($row['category'])) {
                    $category = MenuCategory::firstOrCreate(
                        ['name' => $row['category']],
                        ['icon' => '📦', 'active' => 1]
                    );
                }

                // Create Menu Item
                $item = MenuItem::create([
                    'name' => $row['name'] ?? '',
                    'price' => $isVariantMenu ? 0 : ($row['price'] ?? 0),
                    'description' => $row['description'] ?? '',
                    'category_id' => $category?->id,
                    'status' => $row['status'] ?? 1,
                    'label_color' => $row['label_color'] ?? null,
                    'is_taxable' => $row['is_taxable'] ?? 0,
                    'is_saleable' => $isVariantMenu ? 0 : ($row['is_saleable'] ?? 0),
                    'resale_type' => $isVariantMenu ? null : ($row['resale_type'] ?? null),
                    'resale_value' => $isVariantMenu ? null : ($row['resale_value'] ?? null),
                ]);

                // Handle Nutrition
                if (! empty($row['calories']) || ! empty($row['fat']) || ! empty($row['protein']) || ! empty($row['carbs'])) {
                    DB::table('menu_nutrition')->insert([
                        'menu_item_id' => $item->id,
                        'calories' => $row['calories'] ?? 0,
                        'fat' => $row['fat'] ?? 0,
                        'protein' => $row['protein'] ?? 0,
                        'carbs' => $row['carbs'] ?? 0,
                    ]);
                }

                // Handle Allergies
                if (! empty($row['allergies'])) {
                    $allergies = is_array($row['allergies'])
                        ? $row['allergies']
                        : array_map('trim', explode(',', $row['allergies']));

                    foreach ($allergies as $allergyName) {
                        if (! empty($allergyName)) {
                            $allergy = Allergy::firstOrCreate(
                                ['name' => $allergyName],
                                ['description' => '', 'active' => 1]
                            );

                            DB::table('menu_allergies')->insertOrIgnore([
                                'menu_item_id' => $item->id,
                                'allergy_id' => $allergy->id,
                                'type' => 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // Handle Tags
                if (! empty($row['tags'])) {
                    $tags = is_array($row['tags'])
                        ? $row['tags']
                        : array_map('trim', explode(',', $row['tags']));

                    foreach ($tags as $tagValue) {
                        if (! empty($tagValue)) {
                            if (is_numeric($tagValue)) {
                                $tagId = (int) $tagValue;
                            } else {
                                $tag = Tag::firstOrCreate(
                                    ['name' => $tagValue],
                                    ['description' => '', 'active' => 1]
                                );
                                $tagId = $tag->id;
                            }

                            DB::table('menu_tags')->insertOrIgnore([
                                'menu_item_id' => $item->id,
                                'tag_id' => $tagId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // ✅ HANDLE VARIANT MENU
                if ($isVariantMenu) {
                    foreach ($row['variant_data'] as $variantData) {
                        // Create Variant
                        $variant = MenuVariant::create([
                            'menu_item_id' => $item->id,
                            'name' => $variantData['name'] ?? '',
                            'price' => $variantData['price'] ?? 0,
                            'is_saleable' => $variantData['is_saleable'] ?? 0,
                            'resale_type' => $variantData['resale_type'] ?? null,
                            'resale_value' => $variantData['resale_value'] ?? null,
                        ]);

                        // Add Variant Ingredients
                        if (! empty($variantData['ingredients']) && is_array($variantData['ingredients'])) {
                            foreach ($variantData['ingredients'] as $ingredient) {
                                MenuVariantIngredient::create([
                                    'variant_id' => $variant->id,
                                    'inventory_item_id' => $ingredient['inventory_item_id'] ?? null,
                                    'product_name' => $ingredient['product_name'] ?? '',
                                    'quantity' => $ingredient['quantity'] ?? 0,
                                    'cost' => $ingredient['cost'] ?? 0,
                                ]);
                            }
                        }
                    }
                } else {
                    // ✅ HANDLE SIMPLE MENU INGREDIENTS
                    if (! empty($row['ingredients']) && is_array($row['ingredients'])) {
                        foreach ($row['ingredients'] as $ingredient) {
                            DB::table('menu_ingredients')->insert([
                                'menu_item_id' => $item->id,
                                'inventory_item_id' => $ingredient['inventory_item_id'] ?? null,
                                'product_name' => $ingredient['product_name'] ?? '',
                                'quantity' => $ingredient['quantity'] ?? 0,
                                'cost' => $ingredient['cost'] ?? 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Items imported successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}

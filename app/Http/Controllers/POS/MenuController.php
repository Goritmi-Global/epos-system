<?php

namespace App\Http\Controllers\POS;

use App\Helpers\UploadHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Models\AddonGroup;
use App\Models\Allergy;
use App\Models\Deal;
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

        $menuItems = MenuItem::select('id', 'name', 'price')
            ->where('status', 1)
            ->whereDoesntHave('variantPrices')
            ->whereDoesntHave('variantIngredients')
            ->orderBy('name')
            ->get();

        return Inertia::render('Backend/Menu/Index', [
            'categories' => $categories,
            'allergies' => $allergies,
            'tags' => $tags,
            'meals' => $meals,
            'variantGroups' => $variantGroups,
            'addonGroups' => $addonGroups,
            'menuItems' => $menuItems,
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
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        // Build query for Menu Items
        $menuQuery = MenuItem::query()
            ->select('id', 'name', 'price', 'description', 'status', 'category_id', 'upload_id', 'is_taxable', 'label_color', 'is_saleable', 'resale_type', 'resale_value', 'created_at');

        // Build query for Deals
        $dealQuery = Deal::query()
            ->select('id', 'name', 'price', 'description', 'status', 'category_id', 'upload_id', 'is_taxable', 'label_color', 'created_at');

        // Apply search filter to both
        if ($request->filled('search')) {
            $search = $request->search;

            $menuQuery->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });

            $dealQuery->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter to both
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $menuQuery->where('status', $request->status);
            $dealQuery->where('status', $request->status);
        }

        // Apply price range filters to both
        if ($request->filled('price_min')) {
            $menuQuery->where('price', '>=', $request->price_min);
            $dealQuery->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $menuQuery->where('price', '<=', $request->price_max);
            $dealQuery->where('price', '<=', $request->price_max);
        }

        // Apply date range filters to both
        if ($request->filled('date_from')) {
            $menuQuery->whereDate('created_at', '>=', $request->date_from);
            $dealQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $menuQuery->whereDate('created_at', '<=', $request->date_to);
            $dealQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // Get filtered items from both tables
        $menus = $menuQuery->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => 'menu',
                'name' => $item->name,
                'price' => $item->price,
                'description' => $item->description,
                'status' => $item->status,
                'category_id' => $item->category_id,
                'upload_id' => $item->upload_id,
                'is_taxable' => $item->is_taxable,
                'label_color' => $item->label_color,
                'is_saleable' => $item->is_saleable,
                'resale_type' => $item->resale_type,
                'resale_value' => $item->resale_value,
                'created_at' => $item->created_at,
            ];
        });

        $deals = $dealQuery->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => 'deal',
                'name' => $item->name,
                'price' => $item->price,
                'description' => $item->description,
                'status' => $item->status,
                'category_id' => $item->category_id,
                'upload_id' => $item->upload_id,
                'is_taxable' => $item->is_taxable,
                'label_color' => $item->label_color,
                'is_saleable' => null,
                'resale_type' => null,
                'resale_value' => null,
                'created_at' => $item->created_at,
            ];
        });

        // Merge collections
        $allItems = $menus->merge($deals);

        // Apply sorting
        if ($request->filled('sort_by')) {
            switch ($request->sort_by) {
                case 'price_desc':
                    $allItems = $allItems->sortByDesc('price');
                    break;
                case 'price_asc':
                    $allItems = $allItems->sortBy('price');
                    break;
                case 'name_asc':
                    $allItems = $allItems->sortBy('name');
                    break;
                case 'name_desc':
                    $allItems = $allItems->sortByDesc('name');
                    break;
                default:
                    $allItems = $allItems->sortByDesc('created_at');
            }
        } else {
            $allItems = $allItems->sortByDesc('created_at');
        }

        // Manual pagination
        $total = $allItems->count();
        $items = $allItems->forPage($page, $perPage)->values();

        // Load full relationships and format
        $formattedItems = $items->map(function ($item) {
            if ($item['type'] === 'menu') {
                $menuItem = MenuItem::with([
                    'category',
                    'ingredients',
                    'variants.ingredients',
                    'allergies',
                    'tags',
                    'nutrition',
                    'addonGroupRelations.addonGroup',
                    'meals',
                ])->find($item['id']);

                return [
                    'id' => $menuItem->id,
                    'name' => $menuItem->name,
                    'type' => 'menu',
                    'display_name' => $menuItem->name,
                    'price' => $menuItem->price,
                    'description' => $menuItem->description,
                    'is_taxable' => $menuItem->is_taxable,
                    'label_color' => $menuItem->label_color,
                    'status' => $menuItem->status == '1' ? 1 : 0,
                    'category' => $menuItem->category,
                    'meals' => $menuItem->meals,
                    'ingredients' => $menuItem->ingredients,
                    'nutrition' => $menuItem->nutrition,
                    'allergies' => $menuItem->allergies,
                    'tags' => $menuItem->tags,
                    'image_url' => UploadHelper::url($menuItem->upload_id),
                    'addon_group_ids' => $menuItem->addonGroupRelations->pluck('addon_group_id')->toArray(),
                    'addon_group_relations' => $menuItem->addonGroupRelations,
                    'is_saleable' => $menuItem->is_saleable,
                    'resale_type' => $menuItem->resale_type,
                    'resale_value' => $menuItem->resale_value,
                    'resale_price' => $menuItem->resale_price,
                    'created_at' => $menuItem->created_at,
                    'variants' => $menuItem->variants->map(function ($variant) {
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
            } else {
                $deal = Deal::with([
                    'category',
                    'menuItems',
                    'allergies',
                    'tags',
                    'meals',
                    'addonGroups.addons',
                ])->find($item['id']);

                return [
                    'id' => $deal->id,
                    'name' => $deal->name,
                    'type' => 'deal',
                    'display_name' => $deal->name,
                    'price' => $deal->price,
                    'description' => $deal->description,
                    'status' => (int) $deal->status,
                    'image_url' => UploadHelper::url($deal->upload_id),
                    'is_taxable' => $deal->is_taxable,
                    'label_color' => $deal->label_color,
                    'category_id' => $deal->category_id,
                    'category' => $deal->category,
                    'menu_items' => $deal->menuItems->map(fn ($mi) => [
                        'id' => $mi->id,
                        'name' => $mi->name,
                        'qty' => $mi->pivot->quantity ?? 1,
                    ]),
                    'deal_addon_groups' => $deal->addonGroups->map(fn ($group) => [
                        'group_id' => $group->id,
                        'addons' => $group->addons->map(fn ($addon) => [
                            'id' => $addon->id,
                            'name' => $addon->name,
                            'price' => $addon->price,
                        ]),
                    ]),
                    'allergies' => $deal->allergies,
                    'tags' => $deal->tags,
                    'meals' => $deal->meals,
                    'created_at' => $deal->created_at,
                ];
            }
        });

        // Calculate counts
        $totalMenuCount = MenuItem::count();
        $totalDealCount = Deal::count();
        $activeMenuCount = MenuItem::where('status', 1)->count();
        $activeDealCount = Deal::where('status', 1)->count();
        $inactiveMenuCount = MenuItem::where('status', 0)->count();
        $inactiveDealCount = Deal::where('status', 0)->count();

        // Build pagination links
        $lastPage = (int) ceil($total / $perPage);
        $links = $this->buildPaginationLinks($page, $lastPage, $request);

        return response()->json([
            'message' => 'Items fetched successfully',
            'data' => $formattedItems,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
                'links' => $links,
            ],
            'counts' => [
                'total' => $totalMenuCount + $totalDealCount,
                'active' => $activeMenuCount + $activeDealCount,
                'inactive' => $inactiveMenuCount + $inactiveDealCount,
            ],
        ]);
    }

    private function buildPaginationLinks($currentPage, $lastPage, $request)
    {
        $links = [];
        $baseUrl = $request->url();
        $params = $request->except('page');

        // Previous link
        $links[] = [
            'url' => $currentPage > 1 ? $baseUrl.'?'.http_build_query(array_merge($params, ['page' => $currentPage - 1])) : null,
            'label' => '&laquo; Previous',
            'active' => false,
        ];

        // Page links
        for ($i = 1; $i <= $lastPage; $i++) {
            $links[] = [
                'url' => $baseUrl.'?'.http_build_query(array_merge($params, ['page' => $i])),
                'label' => (string) $i,
                'active' => $i === $currentPage,
            ];
        }

        // Next link
        $links[] = [
            'url' => $currentPage < $lastPage ? $baseUrl.'?'.http_build_query(array_merge($params, ['page' => $currentPage + 1])) : null,
            'label' => 'Next &raquo;',
            'label' => 'Next &raquo;',
            'active' => false,
        ];

        return $links;
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

            return response()->json(['message' => 'Import failed: '.$e->getMessage()], 500);
        }
    }
}

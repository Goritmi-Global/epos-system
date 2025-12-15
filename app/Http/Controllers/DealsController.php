<?php

namespace App\Http\Controllers;

use App\Helpers\UploadHelper;
use App\Models\Deal;
use App\Models\MenuItem;
use App\Http\Requests\Deals\StoreDealRequest;
use App\Http\Requests\Deals\UpdateDealRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DealsController extends Controller
{
    public function index()
    {
        $menuItems = MenuItem::select('id', 'name', 'price')
            ->where('status', 1)
            ->whereDoesntHave('variantPrices')
            ->whereDoesntHave('variantIngredients')
            ->orderBy('name')
            ->get();

        return Inertia::render('Backend/Menu/Deals', [
            'menuItems' => $menuItems,
        ]);
    }


    public function apiIndex(Request $request)
    {
        $query = Deal::with([
            'category',
            'menuItems.category',
            'menuItems.ingredients.inventoryItem',
            'menuItems.variantIngredients.inventoryItem',
            'menuItems.variants.ingredients.inventoryItem',
            'menuItems.addonGroups.addons',
            'allergies',
            'tags',
            'meals',
            'addonGroups.addons', // deal-level addons
        ]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Price range filter
        if ($request->filled('price_min')) $query->where('price', '>=', $request->price_min);
        if ($request->filled('price_max')) $query->where('price', '<=', $request->price_max);

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $query->orderBy(match ($sortBy) {
            'price_asc' => 'price',
            'price_desc' => 'price',
            'name_asc' => 'name',
            'name_desc' => 'name',
            default => 'created_at'
        }, match ($sortBy) {
            'price_desc', 'name_desc' => 'desc',
            default => 'asc'
        });

        $perPage = $request->get('per_page', 10);

        $deals = $query->paginate($perPage)
            ->through(function ($deal) {
                return [
                    'id' => $deal->id,
                    'name' => $deal->name,
                    'price' => $deal->price,
                    'description' => $deal->description,
                    'status' => (int) $deal->status,
                    'image_url' => UploadHelper::url($deal->upload_id),
                    'is_taxable' => $deal->is_taxable,
                    'label_color' => $deal->label_color,
                    'category_id' => $deal->category_id,
                    'category' => $deal->category ? [
                        'id' => $deal->category->id,
                        'name' => $deal->category->name,
                    ] : null,

                    'menu_items' => $deal->menuItems->map(fn($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'qty' => $item->pivot->quantity,
                        'category' => $item->category,
                        'ingredients' => $item->ingredients->map(fn($ing) => [
                            'id' => $ing->inventory_item_id,
                            'product_name' => $ing->inventoryItem->name ?? 'Unknown',
                            'quantity' => $ing->quantity,
                            'stock' => $ing->inventoryItem->stock ?? 0,
                        ]),
                        'variant_ingredients_grouped' => $item->getVariantIngredientsGroupedAttribute(),
                        'is_variant_menu' => $item->isVariantMenu(),
                        'addon_groups' => $item->addonGroups->map(fn($group) => [
                            'group_id' => $group->id,
                            'group_name' => $group->name,
                            'min_select' => $group->pivot->min_select ?? 0,
                            'max_select' => $group->pivot->max_select ?? 0,
                            'addons' => $group->addons->map(fn($addon) => [
                                'id' => $addon->id,
                                'name' => $addon->name,
                                'price' => $addon->price,
                            ]),
                        ]),
                    ]),
                    'deal_addon_groups' => $deal->addonGroups->map(fn($group) => [
                        'group_id' => $group->id,
                        'group_name' => $group->name,
                        'min_select' => $group->pivot->min_select ?? 0,
                        'max_select' => $group->pivot->max_select ?? 0,
                        'addons' => $group->addons->map(fn($addon) => [
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
            });

        return response()->json([
            'data' => $deals->items(),
            'pagination' => [
                'current_page' => $deals->currentPage(),
                'total' => $deals->total(),
                'per_page' => $deals->perPage(),
                'last_page' => $deals->lastPage(),
                'links' => $this->getPaginationLinks($deals),
            ],
            'counts' => [
                'total' => Deal::count(),
                'active' => Deal::where('status', 1)->count(),
                'inactive' => Deal::where('status', 0)->count(),
            ],
        ]);
    }


    private function getPaginationLinks($paginator)
    {
        $links = [];

        // Previous link
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false,
        ];

        // Page links
        for ($i = 1; $i <= $paginator->lastPage(); $i++) {
            $links[] = [
                'url' => $paginator->url($i),
                'label' => (string) $i,
                'active' => $i === $paginator->currentPage(),
            ];
        }

        // Next link
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false,
        ];

        return $links;
    }

    public function store(StoreDealRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            /* =========================
                IMAGE UPLOAD
            ========================== */
            if (!empty($data['image'])) {
                $upload = UploadHelper::store($data['image'], 'deals', 'public');
                $data['upload_id'] = $upload->id;
            }

            unset($data['image']);

            /* =========================
                CREATE DEAL
            ========================== */
            $deal = Deal::create([
                'name'         => $data['name'],
                'price'        => $data['price'],
                'description'  => $data['description'] ?? null,
                'upload_id'    => $data['upload_id'] ?? null,
                'status'       => $data['status'] ?? 1,
                'is_taxable'   => $data['is_taxable'] ?? 0,
                'label_color'  => $data['label_color'] ?? null,
                'category_id'  => $data['category_id'] ?? null,
            ]);

            /* =========================
                ATTACH MENUS WITH QTY
            ========================== */
            if (!empty($data['menu_item_ids'])) {
                $menuItems = [];

                foreach ($data['menu_item_ids'] as $item) {
                    if (is_array($item) && isset($item['id'])) {
                        $menuItems[$item['id']] = [
                            'quantity' => $item['qty'] ?? 1
                        ];
                    } else {
                        $menuItems[$item] = ['quantity' => 1];
                    }
                }

                $deal->menuItems()->sync($menuItems);
            }

            /* =========================
                ALLERGIES (WITH TYPE)
            ========================== */
            if (!empty($data['allergies'])) {
                $syncData = [];

                foreach ($data['allergies'] as $i => $allergyId) {
                    $syncData[$allergyId] = [
                        'type' => $data['allergy_types'][$i] ?? 1
                    ];
                }

                $deal->allergies()->sync($syncData);
            }

            /* =========================
                TAGS
            ========================== */
            if (!empty($data['tags'])) {
                $deal->tags()->sync($data['tags']);
            }

            /* =========================
                MEALS
            ========================== */
            if (!empty($data['meals'])) {
                $deal->meals()->sync($data['meals']);
            }

            /* =========================
                Addon Group
            ========================== */
            if (!empty($data['addon_group_id'])) {
                $deal->addonGroups()->sync((array) $data['addon_group_id']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Deal created successfully',
                'deal' => $deal->load([
                    'menuItems',
                    'allergies',
                    'tags',
                    'meals',
                ]),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create deal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function update(UpdateDealRequest $request, Deal $deal)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            /* =========================
                IMAGE UPLOAD
            ========================== */
            if (!empty($data['image'])) {
                // Delete old image if exists
                if ($deal->upload_id) {
                    UploadHelper::delete($deal->upload_id);
                }

                $upload = UploadHelper::store($data['image'], 'deals', 'public');
                $data['upload_id'] = $upload->id;
            }

            unset($data['image']);

            /* =========================
                UPDATE DEAL
            ========================== */
            $deal->update([
                'name'         => $data['name'] ?? $deal->name,
                'price'        => $data['price'] ?? $deal->price,
                'description'  => $data['description'] ?? $deal->description,
                'upload_id'    => $data['upload_id'] ?? $deal->upload_id,
                'status'       => $data['status'] ?? $deal->status,
                'is_taxable'   => $data['is_taxable'] ?? $deal->is_taxable,
                'label_color'  => $data['label_color'] ?? $deal->label_color,
                'category_id'  => $data['category_id'] ?? $deal->category_id,
            ]);

            /* =========================
                MENU ITEMS WITH QUANTITIES
            ========================== */
            if (!empty($data['menu_item_ids'])) {
                $menuItems = [];
                foreach ($data['menu_item_ids'] as $item) {
                    if (is_array($item) && isset($item['id'])) {
                        $menuItems[$item['id']] = ['quantity' => $item['qty'] ?? 1];
                    } else {
                        $menuItems[$item] = ['quantity' => 1];
                    }
                }
                $deal->menuItems()->sync($menuItems);
            } else {
                $deal->menuItems()->sync([]);
            }

            /* =========================
                ALLERGIES WITH TYPE
            ========================== */
            if (!empty($data['allergies'])) {
                $syncData = [];
                foreach ($data['allergies'] as $i => $allergyId) {
                    $syncData[$allergyId] = [
                        'type' => $data['allergy_types'][$i] ?? 1
                    ];
                }
                $deal->allergies()->sync($syncData);
            } else {
                $deal->allergies()->sync([]);
            }

            /* =========================
                TAGS
            ========================== */
            if (!empty($data['tags'])) {
                $deal->tags()->sync($data['tags']);
            } else {
                $deal->tags()->sync([]);
            }

            /* =========================
                MEALS
            ========================== */
            if (!empty($data['meals'])) {
                $deal->meals()->sync($data['meals']);
            } else {
                $deal->meals()->sync([]);
            }

            /* =======================================================================
                ADDON GROUP (No direct addons sync needed)
                The addon_ids are just for UI display - they belong to the group
            ========================================================================== */
            if (!empty($data['addon_group_id'])) {
                $deal->addonGroups()->sync([(int) $data['addon_group_id']]);
            } else {
                $deal->addonGroups()->sync([]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Deal updated successfully',
                'deal' => $deal->load(['menuItems', 'allergies', 'tags', 'meals', 'addonGroups.addons']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update deal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateStatus(Request $request, Deal $deal)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $newStatus = (int) $request->status;
        $deal->update(['status' => $newStatus]);

        return response()->json([
            'message' => 'Status updated successfully',
            'deal' => [
                'id' => $deal->id,
                'status' => $newStatus,
            ],
        ]);
    }

    public function destroy(Deal $deal)
    {
        DB::beginTransaction();

        try {
            // Delete image using UploadHelper
            if ($deal->upload_id) {
                UploadHelper::delete($deal->upload_id);
            }

            $deal->delete();

            DB::commit();

            return response()->json([
                'message' => 'Deal deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete deal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

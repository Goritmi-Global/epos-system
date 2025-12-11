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
            'menuItems:id,name,price',
            'menuItems.ingredients.inventoryItem:id,name',
            'menuItems.variantIngredients.inventoryItem:id,name',
            'menuItems.variants',
            'menuItems.addonGroups.addons' // Add this line
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
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

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
                    'menu_items' => $deal->menuItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'price' => $item->price,
                            'ingredients' => $item->ingredients->map(function ($ingredient) {
                                return [
                                    'id' => $ingredient->inventory_item_id,
                                    'product_name' => $ingredient->inventoryItem->name ?? 'Unknown',
                                    'quantity' => $ingredient->quantity,
                                    'stock' => $ingredient->inventoryItem->stock ?? 0,
                                ];
                            }),
                            'variant_ingredients_grouped' => $item->getVariantIngredientsGroupedAttribute(),
                            'is_variant_menu' => $item->isVariantMenu(),
                            'addon_groups' => $item->addonGroups->map(function ($group) {
                                return [
                                    'group_id' => $group->id,
                                    'group_name' => $group->name,
                                    'min_select' => $group->pivot->min_select ?? 0,
                                    'max_select' => $group->pivot->max_select ?? 0,
                                    'addons' => $group->addons->map(function ($addon) {
                                        return [
                                            'id' => $addon->id,
                                            'name' => $addon->name,
                                            'price' => $addon->price,
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    }),
                    'created_at' => $deal->created_at,
                ];
            });

        // Get counts
        $counts = [
            'total' => Deal::count(),
            'active' => Deal::where('status', 1)->count(),
            'inactive' => Deal::where('status', 0)->count(),
        ];

        return response()->json([
            'data' => $deals->items(),
            'pagination' => [
                'current_page' => $deals->currentPage(),
                'total' => $deals->total(),
                'per_page' => $deals->perPage(),
                'last_page' => $deals->lastPage(),
                'links' => $this->getPaginationLinks($deals),
            ],
            'counts' => $counts,
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

            // Handle image upload using UploadHelper
            if (isset($data['image']) && $data['image']) {
                $upload = UploadHelper::store($data['image'], 'deals', 'public');
                $data['upload_id'] = $upload->id;
            }

            unset($data['image']);

            $deal = Deal::create([
                'name' => $data['name'],
                'price' => $data['price'],
                'description' => $data['description'] ?? null,
                'upload_id' => $data['upload_id'] ?? null,
                'status' => $data['status'] ?? 1,
            ]);

            // Attach menu items
            if (!empty($data['menu_item_ids'])) {
                $deal->menuItems()->sync($data['menu_item_ids']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Deal created successfully',
                'deal' => $deal->load('menuItems'),
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

            // Handle image upload using UploadHelper
            if (isset($data['image']) && $data['image']) {
                // Delete old image if exists
                if ($deal->upload_id) {
                    UploadHelper::delete($deal->upload_id); // ✅ Pass ID, not model
                }

                $upload = UploadHelper::store($data['image'], 'deals', 'public');
                $data['upload_id'] = $upload->id;
            }

            unset($data['image']);

            $deal->update([
                'name' => $data['name'],
                'price' => $data['price'],
                'description' => $data['description'] ?? null,
                'upload_id' => $data['upload_id'] ?? $deal->upload_id,
                'status' => $data['status'] ?? $deal->status,
            ]);

            // Sync menu items
            if (isset($data['menu_item_ids'])) {
                $deal->menuItems()->sync($data['menu_item_ids']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Deal updated successfully',
                'deal' => $deal->load('menuItems'),
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

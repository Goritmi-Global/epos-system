<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryRequest;
use App\Http\Requests\Inventory\UpdateInventoryRequest;
use App\Models\Allergy;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Tag;
use App\Models\Unit;
use App\Services\POS\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $service) {}

    public function index(Request $request)
    {

        $inventories = $this->service->list($request->only('q'));

        return Inertia::render('Backend/Inventory/Index', [
            'inventories' => $inventories,

            // all as arrays of {id, name}
            'allergies' => Allergy::select('id', 'name')->orderBy('name')->get(),
            'tags' => Tag::select('id', 'name')->orderBy('name')->get(),
            'units' => Unit::select('id', 'name')
                ->whereNull('base_unit_id')
                ->orderBy('name')
                ->get(),
            'suppliers' => Supplier::select('id', 'name')->orderBy('name')->get(),

            // if you have parent/child categories, keep parent_id; otherwise just id/name
            'categories' => InventoryCategory::select('id', 'name', 'parent_id')->where('active', 1)->orderBy('name')->get(),
        ]);
    }

    public function apiList(Request $request)
{
    // Check if we need all items (for dropdowns/modals)
    if ($request->boolean('all')) {
        $inventories = $this->service->listAll($request->only([
            'q',
            'category',
            'supplier',
        ]));
        
        return response()->json([
            'data' => $inventories
        ]);
    }
    
    // Regular paginated response
    $inventories = $this->service->list($request->only([
        'q',
        'per_page',
        'category',
        'supplier',
        'stockStatus',
        'priceMin',
        'priceMax',
        'sortBy',
    ]));

    return response()->json($inventories);
}

    public function kpiStats()
    {
        $stats = $this->service->getKpiStats();

        return response()->json($stats);
    }

    public function create()
    {
        return Inertia::render('Inventory/Form', ['mode' => 'create']);
    }

    public function store(StoreInventoryRequest $request)
    {
        $inventory = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Inventory created successfully',
            'data' => $inventory,
        ], 201);
    }

    public function show(InventoryItem $inventory, InventoryService $service)
    {
        return response()->json($service->show($inventory));
    }

    public function edit(InventoryItem $inventory, InventoryService $service)
    {
        return Inertia::render('Inventory/Form', [
            'mode' => 'edit',
            'item' => $service->editPayload($inventory),
        ]);
    }

    public function update(UpdateInventoryRequest $request, InventoryItem $inventory)
    {
        $this->service->update($inventory, $request->validated());

        return redirect()->route('inventory.index')->with('success', 'Item updated');
    }

    public function destroy(InventoryItem $inventory)
    {
        $this->service->delete($inventory);

        return back()->with('success', 'Item deleted');
    }

    public function import(Request $request): JsonResponse
    {
        $items = $request->input('items', []);

        foreach ($items as $row) {

            // 1. Handle category
            $category = null;
            if (! empty($row['category'])) {
                $category = InventoryCategory::firstOrCreate(
                    ['name' => $row['category'], 'parent_id' => null],
                    ['icon' => '📦', 'active' => 1]
                );
            }

            // 2. Handle supplier
            $supplier = null;
            if (! empty($row['preferred_supplier'])) {
                $supplier = Supplier::where('name', $row['preferred_supplier'])->first();
            }

            // 3. Handle unit
            $unit = null;
            if (! empty($row['unit'])) {
                $unit = Unit::firstOrCreate(
                    ['name' => $row['unit']],
                    ['active' => 1]
                );
            }

            // 4. Skip if SKU already exists
            $existing = InventoryItem::where('sku', $row['sku'])->first();
            if ($existing) {
                continue;
            }

            // 5. Create the inventory item
            $item = InventoryItem::create([
                'name' => $row['name'],
                'sku' => $row['sku'],
                'description' => $row['description'],
                'category_id' => $category?->id,
                'minAlert' => $row['min_alert'],
                'unit_id' => $unit?->id,
                'supplier_id' => $supplier?->id,
                'purchase_price' => $row['purchase_price'] ?? null,
                'sale_price' => $row['sale_price'] ?? null,
                'stock' => $row['available_stock'] ?? 0,
                'active' => $row['active'] ?? 1,
                'user_id' => auth()->id(),

            ]);

            // 6. Insert nutrition info
            if (! empty($row['calories']) || ! empty($row['fat']) || ! empty($row['protein']) || ! empty($row['carbs'])) {
                DB::table('inventory_item_nutrition')->insert([
                    'inventory_item_id' => $item->id,
                    'calories' => $row['calories'] ?? 0,
                    'fat' => $row['fat'] ?? 0,
                    'protein' => $row['protein'] ?? 0,
                    'carbs' => $row['carbs'] ?? 0,
                ]);
            }

            // 7. Handle allergies
            if (! empty($row['allergies'])) {
                $allergies = explode(',', $row['allergies']);
                foreach ($allergies as $a) {
                    $a = trim($a);
                    if ($a === '') {
                        continue;
                    }

                    $allergy = Allergy::firstOrCreate(['name' => $a]);
                    DB::table('inventory_item_allergies')->insert([
                        'inventory_item_id' => $item->id,
                        'allergy_id' => $allergy->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 8. Handle tags
            if (! empty($row['tags'])) {
                $tags = explode(',', $row['tags']);
                foreach ($tags as $t) {
                    $t = trim($t);
                    if ($t === '') {
                        continue;
                    }

                    $tag = Tag::firstOrCreate(['name' => $t]);
                    DB::table('inventory_item_tags')->insert([
                        'inventory_item_id' => $item->id,
                        'tag_id' => $tag->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Items imported successfully']);
    }
}

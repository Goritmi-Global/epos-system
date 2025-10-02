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
            'units' => Unit::select('id', 'name')->orderBy('name')->get(),
            'suppliers' => Supplier::select('id', 'name')->orderBy('name')->get(),

            // if you have parent/child categories, keep parent_id; otherwise just id/name
            'categories' => InventoryCategory::select('id', 'name', 'parent_id')->orderBy('name')->get(),
        ]);
    }

    public function apiList(Request $request)
    {

        $inventories = $this->service->list($request->only('q'));

        // dd($inventories);
        return response()->json($inventories);
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

            // 2. Handle supplier (if you have a suppliers table)
            $supplier = null;
            if (! empty($row['preferred_supplier'])) {
                $supplier = Supplier::where('name', $row['preferred_supplier'])->first();
            }

            $unit = null;
            if (! empty($row['unit'])) {
                $unit = Unit::firstOrCreate(
                    ['name' => $row['unit']],
                    ['active' => 1]
                );
            }

            // 3. Skip if SKU already exists
            $existing = InventoryItem::where('sku', $row['sku'])->first();
            if ($existing) {
                continue;
            }

            // 4. Create item
            $item = InventoryItem::create([
                'name' => $row['name'],
                'sku' => $row['sku'],
                'category_id' => $category?->id,
                'minAlert' => $row['min_alert'],
                'unit_id' => $unit?->id, 
                'supplier_id' => $supplier?->id,
                'purchase_price' => $row['purchase_price'],
                'sale_price' => $row['sale_price'],
                'stock' => $row['stock'],
                'active' => $row['active'] ?? 1,
            ]);

            // 5. Insert nutrition if available
            if ($row['calories'] || $row['fat'] || $row['protein'] || $row['carbs']) {
                DB::table('inventory_item_nutrition')->insert([
                    'inventory_item_id' => $item->id,
                    'calories' => $row['calories'] ?? 0,
                    'fat' => $row['fat'] ?? 0,
                    'protein' => $row['protein'] ?? 0,
                    'carbs' => $row['carbs'] ?? 0,
                ]);
            }
        }

        return response()->json(['message' => 'Items imported successfully']);
    }
}

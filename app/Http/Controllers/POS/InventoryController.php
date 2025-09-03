<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryRequest;
use App\Http\Requests\Inventory\UpdateInventoryRequest;
use App\Models\Allergy;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Supplier;
use App\Models\Tag;
use App\Models\Unit;
use App\Services\POS\InventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $service) {}

    public function index(Request $request)
    {
        $inventories = $this->service->list($request->only('q'));
        $allergies = Allergy::get();
        $categories = Category::get();
        $units = Unit::get();
        $suppliers = Supplier::get();
        $tags = Tag::get();
        return Inertia::render('Backend/Inventory/Index', [
            'inventories' => $inventories,
            'allergies' => $allergies,
            'units' => $units,
            'suppliers' => $suppliers,
            'tags' => $tags,
            'categories' => $categories
        ]);
    }
    public function apiList(Request $request)
    {
        $inventories = $this->service->list($request->only('q'));
        // Return only JSON data, no Inertia
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
            'data' => $inventory
        ], 201);
    }

    public function show(Inventory $inventory)
    {
        return response()->json($inventory->load(['user'])); // add relations like 'user' if needed
    }

    public function edit(Inventory $inventory)
    {
        return Inertia::render('Inventory/Form', ['mode' => 'edit', 'item' => $inventory]);
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory)
    {
        $this->service->update($inventory, $request->validated());
        return redirect()->route('inventory.index')->with('success', 'Item updated');
    }

    public function destroy(Inventory $inventory)
    {
        $this->service->delete($inventory);
        return back()->with('success', 'Item deleted');
    }
}

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
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Helpers\UploadHelper;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $service) {}

    public function index(Request $request)
    {
        $inventories = $this->service->list($request->only('q'));

        return Inertia::render('Backend/Inventory/Index', [
            'inventories' => $inventories,

            // all as arrays of {id, name}
            'allergies'   => Allergy::select('id','name')->orderBy('name')->get(),
            'tags'        => Tag::select('id','name')->orderBy('name')->get(),
            'units'       => Unit::select('id','name')->orderBy('name')->get(),
            'suppliers'   => Supplier::select('id','name')->orderBy('name')->get(),

            // if you have parent/child categories, keep parent_id; otherwise just id/name
            'categories'  => InventoryCategory::select('id','name','parent_id')->orderBy('name')->get(),
        ]);
    }

    public function apiList(Request $request)
    {
        
        $inventories = $this->service->list($request->only('q'));
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
}

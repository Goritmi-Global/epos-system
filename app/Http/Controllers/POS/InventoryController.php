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
use App\Helpers\UploadHelper;

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
        // dd($request->all());
        $inventory = $this->service->create($request->validated());
        return response()->json([
            'message' => 'Inventory created successfully',
            'data' => $inventory
        ], 201);
    }

    public function show(Inventory $inventory)
    { 
        $inventory->load('user');

        $data = $inventory->toArray();
        $data['upload_id'] = $inventory->upload_id;
        
        $data['image'] = UploadHelper::url($inventory->upload_id) ?? asset('assets/img/default.png');

        return response()->json($data);
    }

    public function edit(Inventory $inventory)
    {
        $item = $inventory->toArray();
        $item['upload_id'] = $inventory->upload_id ?? null;
       
        $item['image'] = UploadHelper::url($inventory->upload_id) ?? asset('assets/img/default.png');

     
        return Inertia::render('Inventory/Form', [
            'mode' => 'edit',
            'item' => $item,
        ]);
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

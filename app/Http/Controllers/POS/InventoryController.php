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
                $allergies = Allergy::get();
                $categories = Category::get();
                $units = Unit::get();
                $suppliers =Supplier::get();
                $tags = Tag::get();
                return Inertia::render('Backend/Inventory/Index',[
                    'allergies' => $allergies,
                    'units' => $units,
                    'suppliers' => $suppliers,
                    'tags' => $tags,
                    'categories' => $categories
                ]);

        // $items = $this->service->list($request->only('q'));
        // return Inertia::render('Inventory/Index', ['items' => $items]);
    }

    public function create()
    {
        return Inertia::render('Inventory/Form', ['mode' => 'create']);
    }

    public function store(StoreInventoryRequest $request)
    {
        $this->service->create($request->validated());
        return redirect()->route('inventory.index')->with('success','Item created');
    }

    public function edit(Inventory $inventory)
    {
        return Inertia::render('Inventory/Form', ['mode' => 'edit','item' => $inventory]);
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory)
    {
        $this->service->update($inventory, $request->validated());
        return redirect()->route('inventory.index')->with('success','Item updated');
    }

    public function destroy(Inventory $inventory)
    {
        $this->service->delete($inventory);
        return back()->with('success','Item deleted');
    }
}
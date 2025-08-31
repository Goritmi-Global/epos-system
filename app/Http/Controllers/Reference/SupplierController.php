<?php
namespace App\Http\Controllers\Reference;


use App\Http\Controllers\Controller;
// Requests for validation
use App\Http\Requests\Reference\SupplierStoreRequest;
use App\Http\Requests\Reference\SupplierUpdateRequest;
 
// service for business logic
use App\Services\Reference\SupplierService;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;

use Auth;
class SupplierController extends Controller
{
    public function __construct(private SupplierService $service) {}
    public function index(Request $request)
    {
        // Optional: server-rendered list
        // $suppliers = $this->service->list($request->only('q'));
        return Inertia::render('Reference/Suppliers/Index', [
            // 'suppliers' => $suppliers,
            // 'filters'   => $request->only('q'),
        ]);
    }
     
    
    public function store(SupplierStoreRequest $request): JsonResponse
    {
        
        $supplier = $this->service->create($request->validated());
        return response()->json([
            'message' => 'Supplier created successfully',
            'data'    => $supplier,
        ], 201);
    }
    
    public function edit(Supplier $supplier)
    {
        return Inertia::render('Reference/Suppliers/Form', [
            'mode' => 'edit',
            'item' => $supplier,
        ]);
    }

    public function update(SupplierUpdateRequest $request, Supplier $supplier): JsonResponse
    {
        $updated = $this->service->update($supplier, $request->validated());
        return response()->json([
            'message' => 'Supplier updated successfully',
            'data'    => $updated,
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->service->delete($supplier);
        return response()->json(['message' => 'Supplier deleted successfully']);
    }


}

<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
// Requests for validation
use App\Http\Requests\Reference\SupplierStoreRequest;
use App\Http\Requests\Reference\SupplierUpdateRequest;
// service for business logic
use App\Models\Supplier;
use App\Services\Reference\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $service) {}

    public function index(Request $request)
    {
        $suppliers = $this->service->list($request->only('q'));

        return $suppliers;
    }

    // SupplierService.php
    public function pluck(array $filters = [])
    {
        return Supplier::query()
            ->when($filters['q'] ?? null, function ($q, $v) {
                $q->where('name', 'like', "%$v%");
            })
            ->get(['id', 'name']); // returns collection of {id, name}
    }

    public function store(SupplierStoreRequest $request): JsonResponse
    {

        $supplier = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier,
        ], 201);
    }

    public function edit(Supplier $supplier)
    {
        return Inertia::render('Reference/Suppliers/Form', [
            'mode' => 'edit',
            'item' => $supplier,
        ]);
    }

    public function update(SupplierUpdateRequest $request)
    {
        // dd($request);
        $supplier = Supplier::findOrFail($request->input('id'));
        $updated = $this->service->update($supplier, $request->validated());

        return response()->json([
            'message' => 'Supplier updated successfully',
            'data' => $updated,
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->service->delete($supplier);

        return response()->json(['message' => 'Supplier deleted successfully']);
    }

    // SupplierController.php
    public function import(Request $request): JsonResponse
    {
    
        $suppliers = $request->input('suppliers', []);
        
        foreach ($suppliers as $data) {
            $validated = validator($data, [
                'name' => 'required|string|max:100',
                'email' => 'required|email|max:100|unique:suppliers,email',
                'contact' => 'nullable|unique:suppliers,contact',
                'address' => 'required|string|max:255',
                'preferred_items' => 'nullable|string',
            ])->validate();

            $this->service->create($validated);
        }

        return response()->json(['message' => 'Suppliers imported successfully']);
    }
}

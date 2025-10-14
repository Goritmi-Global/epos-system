<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\Reference\UnitsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    public function __construct(private UnitsService $service) {}

    public function index(Request $request)
    {
        // pass only_base and q to service; allow per_page override
        $units = $this->service->list($request->only(['q', 'only_base', 'per_page']));

        return response()->json($units);
    }

    /**
     * Store a new unit (single derived or multiple base units)
     */
    public function store(Request $request): JsonResponse
    {

        // Two supported request shapes:
        // 1) Batch base units:
        //    { units: [ { name: 'kg' }, { name: 'g' } ] }
        // 2) Single derived unit:
        //    { name: 'gram', base_unit_id: 1, conversion_factor: 0.001 }

        if ($request->has('units') && is_array($request->input('units'))) {
            $validator = Validator::make($request->all(), [
                'units' => 'required|array|min:1',
                'units.*.name' => 'required|string|max:100|distinct|unique:units,name',
                'units.*.base_unit_id' => 'nullable|exists:units,id',
                'units.*.conversion_factor' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $created = $this->service->create(['units' => $validator->validated()['units']]);

            return response()->json(['message' => 'Units created successfully', 'data' => $created], 201);
        }

        // Single unit path (base or derived)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:units,name',
            'base_unit_id' => 'nullable|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $created = $this->service->create($validator->validated());

        return response()->json(['message' => 'Unit created successfully', 'data' => $created], 201);
    }

    /**
     * Update an existing unit
     */
    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        $unit = $this->service->update($unit, $request->validated());

        return response()->json([
            'message' => 'Unit Updated Successfully',
            'unit' => $unit,
        ], 200);
    }

    /**
     * Delete a unit
     */

    public function destroy(Unit $unit)
    {
        try {
            $this->service->delete($unit);
            return response()->json(['message' => 'Unit deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }


    /**
     * Import: similar to store batch. Kept for compatibility.
     */
    public function import(Request $request): JsonResponse
    {
        $units = $request->input('units', []);

        $validator = Validator::make(['units' => $units], [
            'units' => 'required|array|min:1',
            'units.*.name' => 'required|string|max:100|unique:units,name',
            'units.*.base_unit_id' => 'nullable|exists:units,id',
            'units.*.conversion_factor' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $this->service->create(['units' => $validator->validated()['units']]);

        return response()->json(['message' => 'Units imported successfully']);
    }
}

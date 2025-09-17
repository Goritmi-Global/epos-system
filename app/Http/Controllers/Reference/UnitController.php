<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\UpdateUnitRequest;
use App\Models\Unit;
// use App\Services\Reference\UnitsService;
use App\Services\Reference\UnitsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    public function __construct(private UnitsService $service) {}

    public function index(Request $request)
    {
        $units = $this->service->list($request->only('q'));

        return $units;
    }

    /**
     * Store a new unit
     */
    public function store(Request $request): JsonResponse
    {
        $unitsData = $request->input('units');

        // If it's a single request, wrap into array
        if (! is_array($unitsData)) {
            $unitsData = [['name' => $request->input('name')]];
        }

        // Insert all at once
        $created = Unit::insert($unitsData);

        return response()->json([
            'message' => 'Units created successfully',
            'data' => $unitsData,
        ], 201);
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
        ], 201);
    }

    /**
     * Delete a unit
     */
    public function destroy(Unit $unit)
    {
        $this->service->delete($unit);

        return response()->json(null, 204);
    }

    public function import(Request $request): JsonResponse
    {
        $units = $request->input('units', []);
       
        // Validate all tags in one go
        $validator = Validator::make(
            ['units' => $units],
            [
                'units' => 'required|array|min:1',
                'units.*.name' => 'required|string|max:100|unique:units,name',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $wrapped = ['units' => $validator->validated()['units']];

        $this->service->create($wrapped);

        return response()->json(['message' => 'Units imported successfully']);
    }
}

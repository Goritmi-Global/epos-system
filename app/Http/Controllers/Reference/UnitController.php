<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\StoreUnitRequest;
use App\Http\Requests\Reference\UpdateUnitRequest;
use App\Models\Unit;
// use App\Services\Reference\UnitsService;
use App\Services\Reference\UnitsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(private UnitsService $service) {}

    /**
     * List all units with optional search
     */
    public function index(Request $request)
    {
        $units = $this->service->list($request->only('q'));
        return response()->json($units);
    }


    /**
     * Store a new unit
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {

        $unit = $this->service->create($request->validated());
        return response()->json([
            'message' => 'unit created successfully',
            'data'    => $unit,
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
            'unit' => $unit
        ],201);
    }

    /**
     * Delete a unit
     */
    public function destroy(Unit $unit)
    {
        $this->service->delete($unit);
        return response()->json(null, 204);
    }
}

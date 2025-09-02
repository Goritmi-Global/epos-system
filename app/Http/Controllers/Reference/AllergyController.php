<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\StoreAllergyRequest;
use App\Http\Requests\Reference\UpdateAllergyRequest;
use App\Models\Allergy;
use App\Services\Reference\AllergiesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllergyController extends Controller
{
    public function __construct(private AllergiesService $service) {}

    public function index(Request $request)
    {
        $allergies = $this->service->list($request->only('q'));
        return $allergies;
    }

    public function store(StoreAllergyRequest $request): JsonResponse
    {
        $allergy = $this->service->create($request->validated());
        return response()->json([
            'message' => 'Allergy created successfully',
            'data'    => $allergy,
        ], 201);
    }

    public function update(UpdateAllergyRequest $request, Allergy $allergy): JsonResponse
    {
        $allergy = $this->service->update($allergy, $request->validated());
        return response()->json([
            'message' => 'Allergy updated successfully',
            'data'    => $allergy,
        ], 200);
    }

    public function destroy(Allergy $allergy): JsonResponse
    {
        $this->service->delete($allergy);
        return response()->json(['message' => 'Allergy deleted successfully']);
    }
}

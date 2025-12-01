<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\StoreAllergyRequest;
use App\Http\Requests\Reference\UpdateAllergyRequest;
use App\Models\Allergy;
use App\Services\Reference\AllergiesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AllergyController extends Controller
{
    public function __construct(private AllergiesService $service) {}

    public function index(Request $request)
{
    $allergies = $this->service->list($request->only(['q', 'per_page']));

    return response()->json($allergies);
}

    public function store(StoreAllergyRequest $request): JsonResponse
    {
        $allergy = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Allergy created successfully',
            'data' => $allergy,
        ], 201);
    }

    public function update(UpdateAllergyRequest $request, Allergy $allergy): JsonResponse
    {
        $allergy = $this->service->update($allergy, $request->validated());

        return response()->json([
            'message' => 'Allergy updated successfully',
            'data' => $allergy,
        ], 200);
    }

    public function destroy(Allergy $allergy): JsonResponse
    {
        $this->service->delete($allergy);

        return response()->json(['message' => 'Allergy deleted successfully']);
    }

    public function import(Request $request): JsonResponse
    {
        $allergies = $request->input('allergies', []);
    
        // Validate all tags in one go
        $validator = Validator::make(
            ['allergies' => $allergies],
            [
                'allergies' => 'required|array|min:1',
                'allergies.*.name' => 'required|string|max:100|unique:tags,name',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $wrapped = ['allergies' => $validator->validated()['allergies']];

        $this->service->create($wrapped);

        return response()->json(['message' => 'Tags imported successfully']);
    }
}

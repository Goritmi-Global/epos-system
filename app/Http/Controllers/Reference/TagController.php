<?php
namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\TagStoreRequest;
use App\Http\Requests\Reference\TagUpdateRequest;
use App\Models\Tag;
use App\Services\Reference\TagService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TagController extends Controller
{
    public function __construct(private TagService $service) {}
    public function index(Request $request)
    {  
        $tags = $this->service->list($request->only('q'));
        return $tags;
    }
    public function store(TagStoreRequest $request): JsonResponse
    {

        // dd($request);
        $tag = $this->service->create($request->validated());
        return response()->json([
            'message' => 'Tag created successfully',
            'data'    => $tag,
        ], 201);
    }

    public function update(TagUpdateRequest $request, Tag $tag)
    {
        return $this->service->update($tag, $request->validated());
    }

    public function destroy(Tag $tag): JsonResponse
{
    $this->service->delete($tag);

    return response()->json([
        'message' => 'Tag deleted successfully',
    ]);
}

public function import(Request $request): JsonResponse
{
    $tags = $request->input('tags', []);

    // Validate all tags in one go
    $validator = Validator::make(
        ['tags' => $tags],
        [
            'tags' => 'required|array|min:1',
            'tags.*.name' => 'required|string|max:100|unique:tags,name',
        ]
    );

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    $wrapped = ['tags' => $validator->validated()['tags']];

    $this->service->create($wrapped);

    return response()->json(['message' => 'Tags imported successfully']);
}


}

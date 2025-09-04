<?php
namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\TagStoreRequest;
use App\Http\Requests\Reference\TagUpdateRequest;
use App\Models\Tag;
use App\Services\Reference\TagService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

}

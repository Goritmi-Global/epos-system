<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Models\Allergy;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tag;
use App\Services\POS\MenuService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Helpers\UploadHelper;
use App\Http\Requests\Menu\UpdateMenuRequest;

class MenuController extends Controller
{
    public function __construct(private MenuService $service) {}

    public function index(Request $request)
    {
        $categories = MenuCategory::active()
            ->whereNull('parent_id')
            ->with('children')
            ->get(['id', 'name', 'parent_id']);

        $allergies  = Allergy::all(['id', 'name']);
        $tags       = Tag::all(['id', 'name']);


        return Inertia::render('Backend/Menu/Index', [
            'categories' => $categories,
            'allergies'  => $allergies,
            'tags'       => $tags,
        ]);
    }


    public function create()
    {
        return Inertia::render('Menu/Form', ['mode' => 'create']);
    }


    public function store(StoreMenuRequest $request)
    {
        $menu = $this->service->create($request->validated(), $request);

        return response()->json([
            'message' => 'Menu created successfully',
            'data'    => $menu,
        ], 201);
    }

    public function update(UpdateMenuRequest $request, MenuItem $menu)
    {
        $data = $request->all();
        $menu = $this->service->update($menu, $data);

        return response()->json([
            'message' => 'Menu updated successfully',
            'data'    => $menu,
        ]);
    }


    public function apiIndex()
    {
        $menus = MenuItem::with(['category', 'ingredients', 'allergies', 'tags', 'nutrition'])
            ->get()
            ->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'price'       => $item->price,
                    'description' => $item->description,
                    'status' => $item->status,
                    'category'    => $item->category,
                    'ingredients' => $item->ingredients,
                    'nutrition'   => $item->nutrition,
                    'allergies'   => $item->allergies,
                    'tags'        => $item->tags,
                    'image_url'   => UploadHelper::url($item->upload_id),
                ];
            });

        return response()->json([
            'message' => 'Menu items fetched successfully',
            'data'    => $menus,
        ]);
    }

    public function toggleStatus(Request $request, MenuItem $menu)
    {
        
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $menu->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status updated successfully',
            'status'  => $menu->status,
        ]);
    }
}

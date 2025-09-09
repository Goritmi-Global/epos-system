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

    public function apiIndex()
    {
        $menus = MenuItem::with(['category', 'ingredients', 'allergies', 'tags'])->get();

        return response()->json([
            'message' => 'Menu items fetched successfully',
            'data'    => $menus,
        ]);
    }
}

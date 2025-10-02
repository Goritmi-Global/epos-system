<?php

namespace App\Http\Controllers\POS;

use App\Helpers\UploadHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;
use App\Models\Allergy;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Tag;
use App\Services\POS\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $allergies = Allergy::all(['id', 'name']);
        $tags = Tag::all(['id', 'name']);

        return Inertia::render('Backend/Menu/Index', [
            'categories' => $categories,
            'allergies' => $allergies,
            'tags' => $tags,
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
            'data' => $menu,
        ], 201);
    }

    public function update(UpdateMenuRequest $request, MenuItem $menu)
    {
        $data = $request->all();
        $menu = $this->service->update($menu, $data);

        return response()->json([
            'message' => 'Menu updated successfully',
            'data' => $menu,
        ]);
    }

    public function apiIndex()
    {
        $menus = MenuItem::with(['category', 'ingredients', 'allergies', 'tags', 'nutrition'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'description' => $item->description,
                    'label_color' => $item->label_color,
                    'status' => $item->status,
                    'category' => $item->category,
                    'ingredients' => $item->ingredients,
                    'nutrition' => $item->nutrition,
                    'allergies' => $item->allergies,
                    'tags' => $item->tags,
                    'image_url' => UploadHelper::url($item->upload_id),
                ];
            });

        return response()->json([
            'message' => 'Menu items fetched successfully',
            'data' => $menus,
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
            'status' => $menu->status,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $items = $request->input('items', []);

        foreach ($items as $row) {

            // 1. Handle category
            $category = null;
            if (! empty($row['category'])) {
                $category = MenuItem::firstOrCreate(
                    ['name' => $row['category'], 'category_id' => null],
                    ['icon' => '📦', 'active' => 1]
                );
            }

            $item = MenuItem::create([
                'name' => $row['name'],
                'price' => $row['price'],
                'category_id' => $category?->id,
                'active' => $row['active'] ?? 1,

            ]);

            // 5. Insert nutrition if available
            if ($row['calories'] || $row['fat'] || $row['protein'] || $row['carbs']) {
                DB::table('menu_nutrition')->insert([
                    'menu_item_id' => $item->id,
                    'calories' => $row['calories'] ?? 0,
                    'fat' => $row['fat'] ?? 0,
                    'protein' => $row['protein'] ?? 0,
                    'carbs' => $row['carbs'] ?? 0,
                ]);
            }
        }

        return response()->json(['message' => 'Items imported successfully']);
    }
}

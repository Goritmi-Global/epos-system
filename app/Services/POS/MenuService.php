<?php

namespace App\Services\POS;

use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuService
{
    public function list(array $filters = [])
    {
        return MenuItem::query()
            ->when($filters['q'] ?? null, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data, Request $request): MenuItem
{
    // handle image upload
    if ($request->hasFile('image')) {
        $data['image'] = $request->file('image')->store('menus', 'public');
    }

    $menu = MenuItem::create([
        'name'        => $data['name'],
        'price'       => $data['price'],
        'category_id' => $data['category_id'],
        'subcategory_id' => $data['subcategory_id'] ?? null,
        'description' => $data['description'] ?? null,
        'image'       => $data['image'] ?? null,
    ]);

    // Nutrition
    $menu->nutrition()->create($data['nutrition'] ?? []);

    // Allergies + Tags
    if (!empty($data['allergies'])) {
        $menu->allergies()->sync($data['allergies']);
    }
    if (!empty($data['tags'])) {
        $menu->tags()->sync($data['tags']);
    }

    // Ingredients
    if (!empty($data['ingredients'])) {
        foreach ($data['ingredients'] as $ing) {
            $menu->ingredients()->create($ing);
        }
    }

    return $menu;
}


    public function update(MenuItem $menu, array $data): MenuItem
    {
        $menu->update($data);
        return $menu;
    }

    public function delete(MenuItem $menu): void
    {
        $menu->delete();
    }
}

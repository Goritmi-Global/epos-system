<?php

namespace App\Services\POS;

use App\Models\Menu;

class MenuService
{
    public function list(array $filters = [])
    {
        return Menu::query()
            ->when($filters['q'] ?? null, fn($q, $v) =>
                $q->where('name', 'like', "%$v%")
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data): Menu
    {
        return Menu::create($data);
    }

    public function update(Menu $menu, array $data): Menu
    {
        $menu->update($data);
        return $menu;
    }

    public function delete(Menu $menu): void
    {
        $menu->delete();
    }
}

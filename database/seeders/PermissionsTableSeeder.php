<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $routes = collect(Route::getRoutes())
            ->map(function ($route) {
                return $route->getName();
            })
            ->filter()
            ->unique();

        foreach ($routes as $name) {
            $humanName = $this->humanize($name);

            Permission::updateOrCreate(
                ['name' => $name],
                ['description' => $humanName]
            );
        }
    }

    protected function humanize($routeName)
    {
        $parts = explode('.', $routeName);
        $isApi = $parts[0] === 'api';
        if ($isApi) {
            array_shift($parts);
        }
        $resource = '';
        if (count($parts) >= 2) {
            if ($parts[0] === 'menu-categories' || $parts[0] === 'menu') {
                $resource = 'Menu Categories';
                array_shift($parts);
            } elseif ($parts[0] === 'inventory-categories' ||
                    ($parts[0] === 'inventory' && isset($parts[1]) && $parts[1] === 'categories')) {
                $resource = 'Inventory Categories';
                array_shift($parts);
                if (isset($parts[0]) && $parts[0] === 'categories') {
                    array_shift($parts);
                }
            } elseif ($parts[0] === 'categories') {
                $resource = 'Categories';
                array_shift($parts);
            } else {
                $resource = Str::title(str_replace('-', ' ', $parts[0]));
                array_shift($parts);
            }
        }
        $action = count($parts) > 0 ? Str::title(str_replace('-', ' ', array_pop($parts))) : '';
        $middle = count($parts) > 0
            ? implode(' ', array_map(fn ($p) => Str::title(str_replace('-', ' ', $p)), $parts))
            : '';
        $label = trim("$action $middle $resource");
        $label = str_replace('  ', ' ', $label);

        return $label;
    }
}

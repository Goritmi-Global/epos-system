<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

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

        // Remove 'api' prefix but keep processing
        $isApi = $parts[0] === 'api';
        if ($isApi) {
            array_shift($parts);
        }

        // Special handling for menu-categories vs categories
        $resource = '';
        if (count($parts) >= 2) {
            // Check if it's menu-categories
            if ($parts[0] === 'menu-categories' || $parts[0] === 'menu') {
                $resource = 'Menu Categories';
                array_shift($parts);
            } 
            // Check if it's inventory-categories or inventory.categories
            elseif ($parts[0] === 'inventory-categories' || 
                    ($parts[0] === 'inventory' && isset($parts[1]) && $parts[1] === 'categories')) {
                $resource = 'Inventory Categories';
                array_shift($parts);
                if (isset($parts[0]) && $parts[0] === 'categories') {
                    array_shift($parts);
                }
            }
            // Regular categories
            elseif ($parts[0] === 'categories') {
                $resource = 'Categories';
                array_shift($parts);
            }
            // Handle other resources
            else {
                $resource = Str::title(str_replace('-', ' ', $parts[0]));
                array_shift($parts);
            }
        }

        // Get the action (last part)
        $action = count($parts) > 0 ? Str::title(str_replace('-', ' ', array_pop($parts))) : '';

        // Handle any remaining middle parts
        $middle = count($parts) > 0 
            ? implode(' ', array_map(fn($p) => Str::title(str_replace('-', ' ', $p)), $parts))
            : '';

        // Build final label
        $label = trim("$action $middle $resource");
        
        // Clean up common patterns
        $label = str_replace('  ', ' ', $label); // Remove double spaces
        
        return $label;
    }
}
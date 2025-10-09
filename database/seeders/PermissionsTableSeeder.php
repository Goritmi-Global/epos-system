<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Get all route names
        $routes = collect(Route::getRoutes())
            ->map(function ($route) {
                return $route->getName();
            })
            ->filter() // remove null route names
            ->unique();

        $count = 0;

        foreach ($routes as $name) {
            // Skip unnecessary routes (like login, logout, etc.)
            if (
                str_contains($name, 'sanctum') ||
                str_contains($name, 'ignition') ||
                str_contains($name, 'password') ||
                str_contains($name, 'verification')
            ) {
                continue;
            }

            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => ucfirst(str_replace('.', ' → ', $name))]
            );

            $count++;
        }

        $this->command->info("✅ Created or updated {$count} permissions successfully.");
    }
}

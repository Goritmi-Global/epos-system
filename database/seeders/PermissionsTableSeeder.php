<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $routes = collect(Route::getRoutes())
            ->map(fn($route) => $route->getName())
            ->filter()
            ->unique();

        $count = 0;

        foreach ($routes as $name) {
            // Skip system or irrelevant routes
            if (
                str_contains($name, 'sanctum') ||
                str_contains($name, 'ignition') ||
                str_contains($name, 'password') ||
                str_contains($name, 'verification')
            ) {
                continue;
            }

            $description = $this->generateMeaningfulDescription($name);

            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );

            $count++;
        }

        $this->command->info("✅ Created or updated {$count} permissions successfully.");
    }

    private function generateMeaningfulDescription(string $routeName): string
    {
        $parts = explode('.', $routeName);

        if (count($parts) < 2) {
            return ucfirst(str_replace('.', ' → ', $routeName));
        }

        $module = ucfirst($parts[0]);
        $action = strtolower(end($parts));

        // Map common Laravel route actions to readable verbs
        $actionMap = [
            'index' => 'View',
            'show' => 'View Details of',
            'create' => 'Create',
            'store' => 'Add',
            'edit' => 'Edit',
            'update' => 'Update',
            'destroy' => 'Delete',
            'delete' => 'Delete',
            'export' => 'Export',
            'import' => 'Import',
        ];

        $verb = $actionMap[$action] ?? ucfirst($action);

        return "{$verb} {$module}";
    }
}

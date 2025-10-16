<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles if they don't exist
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $cashier = Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']);

        // Get all permissions
        $allPermissions = Permission::all();

        // Assign all permissions to Super Admin
        $superAdmin->syncPermissions($allPermissions);
        $this->command->info('✅ Super Admin assigned all permissions.');

        // Assign Manager permissions: dashboard, inventory, menu
        $managerPermissions = Permission::query()
            ->where(function ($query) {
                $query->where('name', 'like', 'dashboard%')
                      ->orWhere('name', 'like', 'inventory%')
                      ->orWhere('name', 'like', 'menu%');
            })
            ->get();

        $manager->syncPermissions($managerPermissions);
        $this->command->info('✅ Manager assigned Dashboard, Inventory, and Menu permissions.');

        // Assign Cashier permissions: all POS routes
        $cashierPermissions = Permission::query()
            ->where('name', 'like', 'pos%')
            ->get();

        $cashier->syncPermissions($cashierPermissions);
        $this->command->info('✅ Cashier assigned all POS permissions.');

        $this->command->info('🎉 Roles and permissions seeded successfully!');
    }
}

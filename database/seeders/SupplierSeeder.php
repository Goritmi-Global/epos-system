<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
/**
 * Run the database seeds.
 */
public function run(): void
{
    $superAdmin = \App\Models\User::where('is_first_super_admin', true)->first();
    $userId = $superAdmin?->id ?? null;

    $suppliers = [
        ['name' => 'Fresh Fruits Co.', 'email' => 'fruits@example.com', 'address' => '123 Orchard Rd, London', 'preferred_items' => 'Fruits', 'contact' => '+44 7700 900001'],
        ['name' => 'Green Veggies Ltd.', 'email' => 'veggies@example.com', 'address' => '45 Green St, Manchester', 'preferred_items' => 'Vegetables', 'contact' => '+44 7700 900002'],
        ['name' => 'Hot Drinks Inc.', 'email' => 'coffee@example.com', 'address' => '67 Brew Ave, Birmingham', 'preferred_items' => 'Beverages', 'contact' => '+44 7700 900003'],
        ['name' => 'Snack Shack', 'email' => 'snacks@example.com', 'address' => '89 Crunch Ln, Leeds', 'preferred_items' => 'Snacks', 'contact' => '+44 7700 900004'],
        ['name' => 'Bakery Delight', 'email' => 'bakery@example.com', 'address' => '101 Bread Blvd, Liverpool', 'preferred_items' => 'Bakery', 'contact' => '+44 7700 900005'],
        ['name' => 'Dairy Supplies', 'email' => 'dairy@example.com', 'address' => '202 Milk Rd, Glasgow', 'preferred_items' => 'Dairy', 'contact' => '+44 7700 900006'],
        ['name' => 'Spice Traders', 'email' => 'spices@example.com', 'address' => '303 Flavor St, Bristol', 'preferred_items' => 'Spices', 'contact' => '+44 7700 900007'],
        ['name' => 'Seafood Fresh', 'email' => 'seafood@example.com', 'address' => '404 Ocean Dr, Edinburgh', 'preferred_items' => 'Seafood', 'contact' => '+44 7700 900008'],
        ['name' => 'Meat Suppliers', 'email' => 'meat@example.com', 'address' => '505 Protein Rd, Cardiff', 'preferred_items' => 'Meat', 'contact' => '+44 7700 900009'],
        ['name' => 'Beverage Hub', 'email' => 'beverages@example.com', 'address' => '606 Drink Ln, Belfast', 'preferred_items' => 'Beverages', 'contact' => '+44 7700 900010'],
    ];

    foreach ($suppliers as $sup) {
        Supplier::create([
            'name' => $sup['name'],
            'email' => $sup['email'],
            'address' => $sup['address'],
            'preferred_items' => $sup['preferred_items'],
            'contact' => $sup['contact'],
            'user_id' => $userId, // Assign to a default user
        ]);
    }
}
}

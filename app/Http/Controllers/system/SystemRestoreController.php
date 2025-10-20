<?php

namespace App\Http\Controllers\system;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SystemRestoreController extends Controller
{
    public function restore(Request $request)
    {
        // Optional: double-check the user
        $user = $request->user();
        if (! $user->hasRole('Super Admin')) {
            abort(403, 'Unauthorized');
        }

        // Disable foreign key checks to truncate safely
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $dbName = env('DB_DATABASE');
        $key = "Tables_in_$dbName";

        foreach ($tables as $table) {
            DB::table($table->$key)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Artisan::call('db:seed'); // Runs DatabaseSeeder
        // Artisan::call('db:seed', ['--class' => 'CountriesTableSeeder']);
        // Artisan::call('db:seed', ['--class' => 'TimezonesTableSeeder']);
        // Artisan::call('db:seed', ['--class' => 'PermissionsTableSeeder']);
        // Artisan::call('db:seed', ['--class' => 'RolesTableSeeder']);
        // // Artisan::call('db:seed', ['--class' => 'SupplierSeeder']);
        // Artisan::call('db:seed', ['--class' => 'AllergySeeder']);
        // Artisan::call('db:seed', ['--class' => 'UnitSeeder']);
        // Artisan::call('db:seed', ['--class' => 'TagSeeder']);

        return response()->json([
            'success' => true,
            'message' => 'System restored. All tables truncated.',
            'redirect' => route('front-page')
        ]);
    }
}

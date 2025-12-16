<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class MigrateExistingDataToTenant extends Command
{
    protected $signature = 'tenant:migrate-data {tenant_id}';
    protected $description = 'Migrate existing data from main database to tenant database';

    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            $this->error('Tenant not found!');
            return 1;
        }

        $this->info("Starting data migration for tenant: {$tenant->restaurant_name}");

        // Get database names
        $sourceDb = config('database.connections.mysql.database');
        $tenantDb = 'tenant' . str_replace('-', '', $tenant->id);

        $this->info("Source DB: {$sourceDb}");
        $this->info("Tenant DB: {$tenantDb}");

        // Get all tables from source database
        $tables = DB::select("SHOW TABLES");
        $tableKey = "Tables_in_{$sourceDb}";

        // Tables to exclude (central/system tables)
        $excludeTables = [
            'tenants',
            'domains',
            'migrations',
            'password_reset_tokens',
            'password_resets',
            'failed_jobs',
            'personal_access_tokens',
        ];

        $this->info("\nCopying tables...");

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;

            // Skip excluded tables
            if (in_array($tableName, $excludeTables)) {
                $this->warn("  ⊘ Skipped: {$tableName} (system table)");
                continue;
            }

            try {
                // Count records in source table
                $sourceCount = DB::connection('mysql')
                    ->table($tableName)
                    ->count();

                if ($sourceCount === 0) {
                    $this->comment("  - Empty table: {$tableName}");
                    continue;
                }

                // Check if table exists in tenant database
                $tenantTableExists = DB::select("
                    SELECT COUNT(*) as count 
                    FROM information_schema.tables 
                    WHERE table_schema = '{$tenantDb}' 
                    AND table_name = '{$tableName}'
                ")[0]->count;

                if (!$tenantTableExists) {
                    $this->error("  ✗ Table {$tableName} doesn't exist in tenant DB!");
                    continue;
                }

                // Clear existing data in tenant table
                DB::statement("DELETE FROM `{$tenantDb}`.`{$tableName}`");

                // Copy data using INSERT INTO SELECT
                DB::statement("
                    INSERT INTO `{$tenantDb}`.`{$tableName}` 
                    SELECT * FROM `{$sourceDb}`.`{$tableName}`
                ");

                // Verify copy
                $tenantCount = DB::connection('mysql')
                    ->select("SELECT COUNT(*) as count FROM `{$tenantDb}`.`{$tableName}`")[0]->count;
                
                $this->info("  ✓ Copied {$tenantCount}/{$sourceCount} records to: {$tableName}");

            } catch (\Exception $e) {
                $this->error("  ✗ Error with {$tableName}: " . $e->getMessage());
            }
        }

        $this->info("\n✅ Data migration completed!");
        $this->info("🌐 Tenant database: {$tenantDb}");
        
        // Show the domain
        $domain = $tenant->domains()->first();
        if ($domain) {
            $this->info("🌐 Access at: http://{$domain->domain}:8000");
        }

        return 0;
    }
}
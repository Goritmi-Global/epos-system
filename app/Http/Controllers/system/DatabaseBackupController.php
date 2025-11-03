<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DatabaseBackupController extends Controller
{
    public function backup(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Super Admin')) {
            abort(403, 'Unauthorized');
        }

        try {
            $database = env('DB_DATABASE');
            $filename = 'backup_'.$database.'_'.date('Y-m-d_H-i-s').'.sql';
            $backupPath = storage_path('app/backups');

            if (! file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $fullPath = $backupPath.'/'.$filename;

            // Get all table names
            $tables = \DB::select('SHOW TABLES');
            $key = 'Tables_in_'.$database;

            $sql = "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $tableName = $table->$key;

                // Get create table statement
                $create = \DB::select("SHOW CREATE TABLE `$tableName`")[0]->{'Create Table'};
                $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
                $sql .= $create.";\n\n";

                // Get table data
                $rows = \DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $values = array_map(function ($value) {
                        return isset($value) ? "'".addslashes($value)."'" : 'NULL';
                    }, (array) $row);

                    $sql .= "INSERT INTO `$tableName` VALUES(".implode(',', $values).");\n";
                }

                $sql .= "\n\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            file_put_contents($fullPath, $sql);

            return response()->download($fullPath, $filename, [
                'Content-Type' => 'application/sql',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Database backup failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup: '.$e->getMessage(),
            ], 500);
        }
    }
}

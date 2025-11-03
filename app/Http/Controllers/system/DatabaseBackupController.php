<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DatabaseBackupController extends Controller
{
    public function backup(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Super Admin')) {
            abort(403, 'Unauthorized');
        }

        try {
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            $password = env('DB_PASSWORD');
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');

            $filename = 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
            $backupPath = storage_path('app/backups');

            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $fullPath = $backupPath . '/' . $filename;

            // Detect OS-specific mysqldump command
            $mysqldump = 'mysqldump'; // default for Linux/macOS/Windows (if in PATH)

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // On Windows, check if mysqldump exists in PATH
                $check = shell_exec('where mysqldump');
                if (!$check) {
                    throw new \Exception(
                        'mysqldump is not found in system PATH. Please ensure MySQL is installed and added to environment variables.'
                    );
                }
            } else {
                // For Linux/Mac check command existence
                $check = shell_exec('which mysqldump');
                if (!$check) {
                    throw new \Exception(
                        'mysqldump command not found. Please install mysql-client or add it to PATH.'
                    );
                }
            }

            // Build the command
            // (Avoid exposing password if empty)
            $command = sprintf(
                '%s --user=%s %s --host=%s --port=%s %s > "%s"',
                $mysqldump,
                escapeshellarg($username),
                $password ? '--password=' . escapeshellarg($password) : '',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                $fullPath
            );

            // Execute process
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            if (!file_exists($fullPath)) {
                throw new \Exception('Backup file was not created');
            }

            return response()->download($fullPath, $filename, [
                'Content-Type' => 'application/sql',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Database backup failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create database backup: ' . $e->getMessage(),
            ], 500);
        }
    }
}

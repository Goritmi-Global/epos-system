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

            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s',
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($database),
                escapeshellarg($fullPath)
            );

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
                'message' => 'Failed to create database backup: ' . $e->getMessage()
            ], 500);
        }
    }
}
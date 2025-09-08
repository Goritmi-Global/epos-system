<?php

namespace App\Helpers;

use App\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadHelper
{
    /**
     * Store a file and create an Upload row.
     * Returns the Upload model.
     */
    public static function store(UploadedFile $file, string $dir = 'uploads', string $disk = 'public'): Upload
    {
        $storedPath = $file->store($dir, $disk);

        return Upload::create([
            'file_original_name' => $file->getClientOriginalName(),
            'file_name'          => $storedPath,                   // relative path on disk
            'file_size'          => $file->getSize(),
            'extension'          => $file->getClientOriginalExtension(),
            'type'               => $file->getMimeType(),
        ]);
    }

    /**
     * Replace/Update an existing upload with a new file.
     * Deletes the old file (if present) and returns the NEW Upload model.
     */
    public static function replace(?int $oldUploadId, UploadedFile $file, string $dir = 'uploads', string $disk = 'public'): Upload
    {
        // Create new upload first
        $newUpload = self::store($file, $dir, $disk);

        // Remove old file + soft delete old upload row
        if ($oldUploadId) {
            self::delete($oldUploadId, $disk);
        }

        return $newUpload;
    }

    /**
     * Delete the physical file and soft-delete the Upload row.
     */
    public static function delete(?int $uploadId, string $disk = 'public'): void
    {
        if (!$uploadId) return;

        $upload = Upload::find($uploadId);
        if (!$upload) return;

        if ($upload->file_name && Storage::disk($disk)->exists($upload->file_name)) {
            Storage::disk($disk)->delete($upload->file_name);
        }

        $upload->delete(); // soft delete
    }

    /**
     * Get a public URL for the file (or null if missing).
     */
    public static function url(?int $uploadId, string $disk = 'public'): ?string
    {
        if (!$uploadId) return null;
        // dd("in the box");

        $upload = Upload::find($uploadId);
        if (!$upload?->file_name) return null;

        return Storage::disk($disk)->url($upload->file_name);
    }
}

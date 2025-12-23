<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalkInCounter extends Model
{
    protected $fillable = ['current_number'];

    /**
     * Get next walk-in number and increment counter
     */
    public static function getNextNumber(): int
    {
        return DB::transaction(function () {
            $counter = self::lockForUpdate()->first();
            
            if (!$counter) {
                // Start from 1, not 0
                $counter = self::create(['current_number' => 1]);
                return 1;
            }
            
            $counter->increment('current_number');
            
            return $counter->current_number;
        });
    }

    /**
     * Get current number without incrementing
     */
    public static function getCurrentNumber(): int
    {
        $counter = self::first();
        return $counter ? $counter->current_number : 1; // Changed from 0 to 1
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TerminalState extends Model
{
    protected $fillable = [
        'terminal_id',
        'user_id',
        'cart_data',
        'ui_data',
        'version',
        'last_updated'
    ];

    protected $casts = [
        'cart_data' => 'array',
        'ui_data' => 'array',
        'version' => 'integer',
        'last_updated' => 'datetime'
    ];

    public $timestamps = false;

    /**
     * ✅ FIXED: Atomic version increment with data update
     */
    public function updateCartData(array $cartData): void
    {
        $now = now();
        
        // ✅ Single atomic query - no race condition
        DB::table('terminal_states')
            ->where('id', $this->id)
            ->update([
                'cart_data' => json_encode($cartData),
                'version' => DB::raw('version + 1'),
                'last_updated' => $now
            ]);

        // Refresh model to get new version
        $this->refresh();

        // Update cache with new data
        Cache::put(
            "terminal_cart:{$this->terminal_id}",
            [
                'data' => $cartData,
                'version' => $this->version,
                'timestamp' => $now->toIso8601String()
            ],
            now()->addMinutes(5)
        );
        
        // ✅ Also update version cache immediately
        Cache::put(
            "terminal_version:{$this->terminal_id}",
            $this->version,
            now()->addSeconds(30)
        );
    }

    public function updateUIData(array $uiData): void
    {
        $now = now();
        
        // ✅ Single atomic query
        DB::table('terminal_states')
            ->where('id', $this->id)
            ->update([
                'ui_data' => json_encode($uiData),
                'version' => DB::raw('version + 1'),
                'last_updated' => $now
            ]);

        $this->refresh();

        Cache::put(
            "terminal_ui:{$this->terminal_id}",
            [
                'data' => $uiData,
                'version' => $this->version,
                'timestamp' => $now->toIso8601String()
            ],
            now()->addMinutes(5)
        );
        
        Cache::put(
            "terminal_version:{$this->terminal_id}",
            $this->version,
            now()->addSeconds(30)
        );
    }

    /**
     * ✅ Get cart data from cache or DB
     */
    public static function getCartData(string $terminalId): ?array
    {
        $cached = Cache::get("terminal_cart:{$terminalId}");
        
        if ($cached && isset($cached['data'])) {
            return $cached['data'];
        }

        $terminal = self::where('terminal_id', $terminalId)->first(['cart_data']);
        return $terminal?->cart_data;
    }

    public static function getUIData(string $terminalId): ?array
    {
        $cached = Cache::get("terminal_ui:{$terminalId}");
        
        if ($cached && isset($cached['data'])) {
            return $cached['data'];
        }

        $terminal = self::where('terminal_id', $terminalId)->first(['ui_data']);
        return $terminal?->ui_data;
    }

    /**
     * ✅ OPTIMIZED: Version check with better caching strategy
     */
    public static function getVersion(string $terminalId): int
    {
        $cacheKey = "terminal_version:{$terminalId}";
        
        // Try cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (int) $cached;
        }

        // Fetch from DB - use selectRaw for better performance
        $result = DB::table('terminal_states')
            ->where('terminal_id', $terminalId)
            ->value('version');
        
        $version = $result ?? 0;
        
        // Cache for 30 seconds
        Cache::put($cacheKey, $version, now()->addSeconds(30));
        
        return $version;
    }

    /**
     * ✅ IMPROVED: Get full state with better error handling
     */
    public static function getFullState(string $terminalId): array
    {
        // Try to get from cache first
        $cartCache = Cache::get("terminal_cart:{$terminalId}");
        $uiCache = Cache::get("terminal_ui:{$terminalId}");
        
        // If both are cached, return immediately
        if ($cartCache && $uiCache && $cartCache['version'] === $uiCache['version']) {
            return [
                'cart' => $cartCache['data'],
                'ui' => $uiCache['data'],
                'version' => $cartCache['version'],
                'timestamp' => $cartCache['timestamp']
            ];
        }

        // Otherwise fetch from DB
        $terminal = self::where('terminal_id', $terminalId)
            ->select(['cart_data', 'ui_data', 'version', 'last_updated'])
            ->first();

        if (!$terminal) {
            return [
                'cart' => null,
                'ui' => null,
                'version' => 0,
                'timestamp' => now()->toIso8601String()
            ];
        }

        $timestamp = $terminal->last_updated->toIso8601String();

        // Update both caches
        Cache::put("terminal_cart:{$terminalId}", [
            'data' => $terminal->cart_data,
            'version' => $terminal->version,
            'timestamp' => $timestamp
        ], now()->addMinutes(5));

        Cache::put("terminal_ui:{$terminalId}", [
            'data' => $terminal->ui_data,
            'version' => $terminal->version,
            'timestamp' => $timestamp
        ], now()->addMinutes(5));
        
        Cache::put("terminal_version:{$terminalId}", $terminal->version, now()->addSeconds(30));

        return [
            'cart' => $terminal->cart_data,
            'ui' => $terminal->ui_data,
            'version' => $terminal->version,
            'timestamp' => $timestamp
        ];
    }
    
    /**
     * ✅ NEW: Batch update both cart and UI (more efficient for simultaneous updates)
     */
    public function updateBothData(array $cartData, array $uiData): void
    {
        $now = now();
        
        DB::table('terminal_states')
            ->where('id', $this->id)
            ->update([
                'cart_data' => json_encode($cartData),
                'ui_data' => json_encode($uiData),
                'version' => DB::raw('version + 1'),
                'last_updated' => $now
            ]);

        $this->refresh();
        
        $timestamp = $now->toIso8601String();

        // Update all caches
        Cache::put("terminal_cart:{$this->terminal_id}", [
            'data' => $cartData,
            'version' => $this->version,
            'timestamp' => $timestamp
        ], now()->addMinutes(5));

        Cache::put("terminal_ui:{$this->terminal_id}", [
            'data' => $uiData,
            'version' => $this->version,
            'timestamp' => $timestamp
        ], now()->addMinutes(5));
        
        Cache::put("terminal_version:{$this->terminal_id}", $this->version, now()->addSeconds(30));
    }
    
    /**
     * ✅ NEW: Clear terminal cache (useful for debugging)
     */
    public static function clearCache(string $terminalId): void
    {
        Cache::forget("terminal_cart:{$terminalId}");
        Cache::forget("terminal_ui:{$terminalId}");
        Cache::forget("terminal_version:{$terminalId}");
    }
}
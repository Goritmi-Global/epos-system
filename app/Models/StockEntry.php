<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Models\Notification;

class StockEntry extends Model
{
    use HasFactory;

    protected $table = 'stock_entries';

    protected $fillable = [
        'category_id',
        'supplier_id',
        'product_id',
        'user_id',
        'quantity',
        'price',
        'value',
        'operation_type',
        'stock_type',
        'expiry_date',
        'description',
        'purchase_date',
    ];

    protected $casts = [
        'quantity'      => 'decimal:2',
        'price'         => 'decimal:2',
        'value'         => 'decimal:2',
        'expiry_date'   => 'date',
        'purchase_date' => 'date',
    ];

    // ----------------- Relationships ----------------- //
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(InventoryItem::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function allocationsUsedFromMe()
    {
        return $this->hasMany(StockOutAllocation::class, 'stock_in_entry_id');
    }

    public function allocationsForThisOut()
    {
        return $this->hasMany(StockOutAllocation::class, 'stock_out_entry_id');
    }

    // ----------------- Scopes ----------------- //
    public function scopeForItem($q, int $itemId)
    {
        return $q->where('product_id', $itemId);
    }

    public function scopeStockIn($q)
    {
        return $q->where('stock_type', 'stockin');
    }

    public function scopeStockOut($q)
    {
        return $q->where('stock_type', 'stockout');
    }

    // ----------------- Boot Method ----------------- //
    protected static function boot()
    {
        parent::boot();

        static::created(function ($entry) {
            // Trigger notifications for any stock change (in or out)
            $entry->checkStockStatus();
        });
    }

    // ----------------- Stock Checking Logic ----------------- //
    public function checkStockStatus()
    {
        $product = $this->product;

        if (!$product) {
            return;
        }

        // Calculate current total available quantity
        $totalStock = self::where('product_id', $product->id)
            ->where('stock_type', 'stockin')
            ->sum('quantity')
            - self::where('product_id', $product->id)
            ->where('stock_type', 'stockout')
            ->sum('quantity');

        // Use product’s minAlert value as threshold
        $lowStockThreshold = $product->minAlert ?? 0;

        // Expiry logic
        $expiryDate = $this->expiry_date ? Carbon::parse($this->expiry_date) : null;
        $now = Carbon::now();

        // ---- Conditions ---- //
        if ($totalStock <= 0) {
            $this->createNotification('out_of_stock', "{$product->name} is out of stock.");
        } elseif ($totalStock <= $lowStockThreshold) {
            $this->createNotification('low_stock', "{$product->name} stock is low ({$totalStock} left).");
        }

        if ($expiryDate) {
            if ($expiryDate->isPast()) {
                $this->createNotification('expired', "{$product->name} has expired.");
            } elseif ($expiryDate->diffInDays($now) <= 7) {
                $this->createNotification('near_expiry', "{$product->name} is near expiry ({$expiryDate->format('Y-m-d')}).");
            }
        }
    }

    private function createNotification(string $status, string $message)
    {
        // Prevent duplicate notifications for same product + status
        $exists = Notification::where('product_id', $this->product_id)
            ->where('status', $status)
            ->where('is_read', false)
            ->exists();

        if (!$exists) {
            Notification::create([
                'product_id' => $this->product_id,
                'message' => $message,
                'status' => $status,
                'is_read' => false,
            ]);
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingIngredientDeduction extends Model
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'inventory_item_id',
        'inventory_item_name',
        'required_quantity',
        'available_quantity',
        'pending_quantity',
        'status',
        'fulfilled_at',
        'fulfilled_by',
        'notes'
    ];

    protected $casts = [
        'required_quantity' => 'decimal:2',
        'available_quantity' => 'decimal:2',
        'pending_quantity' => 'decimal:2',
        'fulfilled_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(PosOrder::class, 'order_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(PosOrderItem::class, 'order_item_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function fulfilledBy()
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    /**
     * Scope to get pending deductions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get deductions for specific inventory item
     */
    public function scopeForInventoryItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }
}
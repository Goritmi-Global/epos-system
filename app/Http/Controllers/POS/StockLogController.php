<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockEntry;
use App\Models\StockOutAllocation;
use Inertia\Inertia;


class StockLogController extends Controller
{
    public function index()
    { 
        return Inertia::render('Backend/Inventory/StockMomentLogs/Index');
    }

    public function allocations(\App\Models\StockEntry $log)
{
    if (strtolower($log->stock_type) !== 'stockout') {
        return response()->json([]);
    }

    $allocations = \App\Models\StockOutAllocation::query()
        ->leftJoin('inventory_items as items', 'items.id', '=', 'stock_out_allocations.product_id')
        ->where('stock_out_entry_id', $log->id)
        ->orderByRaw('stock_out_allocations.expiry_date IS NULL, stock_out_allocations.expiry_date ASC, stock_out_allocations.id ASC')
        ->get([
            'stock_out_allocations.id',
            'stock_out_allocations.stock_out_entry_id',
            'stock_out_allocations.stock_in_entry_id',
            'stock_out_allocations.product_id',
            'items.name as product_name',          // 👈 here
            'stock_out_allocations.quantity',
            'stock_out_allocations.unit_price',
            'stock_out_allocations.expiry_date',
            'stock_out_allocations.created_at',
        ]);

    return response()->json($allocations);
}
    
}


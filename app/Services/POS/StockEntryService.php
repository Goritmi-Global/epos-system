<?php

namespace App\Services\POS;

use App\Models\Inventory;
use App\Models\StockEntry;

class StockEntryService
{
    public function create(array $data): StockEntry
    {
        // Remove frontend-only field
        unset($data['available_quantity']);

        // Create the stock entry
        return StockEntry::create($data);
    }

    public function list(array $filters)
    {
        $query = StockEntry::with(['product', 'category', 'supplier', 'user']);

        if (!empty($filters['q'])) {
            $query->where('name', 'like', '%' . $filters['q'] . '%');
        }

        return $query->paginate(20);
    }

    // New method to get total stock for a product
    public function totalStock(int $product): \Illuminate\Http\JsonResponse
    {
        $totalIn = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->sum('quantity');

        $totalOut = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockout')
            ->sum('quantity');

        $available = $totalIn - $totalOut;

        // total stock-in value
        $totalInValue = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->sum(\DB::raw('quantity * price'));

        // total stock-out value
        $totalOutValue = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockout')
            ->sum(\DB::raw('quantity * price'));

        // net stock value
        $stockValue = $totalInValue - $totalOutValue;

        $inventory = Inventory::find($product);
        $minAlert = $inventory?->minAlert ?? 0;

        return response()->json([
            'available' => $available,
            'stockValue' => $stockValue,
            'minAlert' => $minAlert,
        ]);
    }
}

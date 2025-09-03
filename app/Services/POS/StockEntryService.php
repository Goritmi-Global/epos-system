<?php

namespace App\Services\POS;

use App\Models\Inventory;
use App\Models\StockEntry;
use Illuminate\Http\Request;

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

    // Get stock logs
    public function getStockLogs(): array
    {
        return StockEntry::with('product')
            ->latest()
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'itemName' => $entry->product->name ?? 'N/A',
                    'totalPrice' => $entry->quantity * $entry->price,
                    'category' => $entry->product->getAttribute('category') ?? 'N/A',
                    'unitPrice' => $entry->price,
                    'dateTime' => $entry->created_at->toIso8601String(),
                    'expiryDate' => $entry->expiry_date,
                    'quantity' => $entry->quantity,
                    'operationType' => $entry->operation_type,
                    'type' => $entry->stock_type,
                ];
            })
            ->values()
            ->toArray();
    }

    public function updateLog(Request $request, $id): bool
    {
        $entry = StockEntry::findOrFail($id);

        $entry->quantity = $request->quantity;
        $entry->price = $request->unitPrice;
        $entry->expiry_date = $request->expiryDate;
        $entry->operation_type = $request->operationType;

        return $entry->save();
    }
    
    public function deleteLog($id): bool
    {
        $entry = StockEntry::findOrFail($id);
        return $entry->delete();
    }
}

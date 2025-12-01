<?php

namespace App\Services\POS;

use App\Models\InventoryItem;
use App\Models\StockEntry;
use Illuminate\Http\Request;

use App\Models\StockOutAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockEntryService
{
    public function create(array $data): StockEntry
    {
        // frontend-only
        unset($data['available_quantity']);

        // ---- If NOT stockout => normal insert (no FIFO logic) ----
        if (strtolower($data['stock_type'] ?? '') !== 'stockout') {
            return StockEntry::create($data);
        }

        // ---- STOCKOUT path (FIFO by expiry) ----
        return DB::transaction(function () use ($data) {
            // do not send NULL price; let DB default apply
            unset($data['price']);

            $productId  = (int) $data['product_id'];
            $needQty    = (float) $data['quantity'];  // ✅ Changed to float

            // 1) create the stock-out entry
            $stockOut = StockEntry::create($data);

            // 2) get IN batches ordered by earliest expiry first (NULLs last)
            $batches = StockEntry::query()
                ->where('product_id', $productId)
                ->where('stock_type', 'stockin')
                ->select(['id', 'quantity', 'price', 'expiry_date', 'created_at'])
                ->orderByRaw('expiry_date IS NULL, expiry_date ASC, created_at ASC, id ASC')
                ->lockForUpdate()
                ->get();

            $left      = $needQty;
            $totalCost = 0.0;

            foreach ($batches as $batch) {
                // remaining = in_qty - allocations already recorded
                $allocated = (float) StockOutAllocation::where('stock_in_entry_id', $batch->id)->sum('quantity');  // ✅ Changed to float
                $remaining = max(0, (float)$batch->quantity - $allocated);  // ✅ Changed to float
                if ($remaining <= 0) continue;

                $use = min($left, $remaining);
                if ($use <= 0) break;

                StockOutAllocation::create([
                    'stock_out_entry_id' => $stockOut->id,
                    'stock_in_entry_id'  => $batch->id,
                    'product_id'         => $productId,
                    'quantity'           => $use,  // ✅ This will now be 2.22 instead of 2
                    'unit_price'         => $batch->price,
                    'expiry_date'        => $batch->expiry_date,
                ]);

                $totalCost += ((float)$batch->price) * $use;
                $left -= $use;

                if ($left <= 0.001) break;  // ✅ Use small epsilon for float comparison
            }

            if ($left > 0.001) {  // ✅ Use small epsilon for float comparison
                throw ValidationException::withMessages([
                    'quantity' => ["Insufficient stock to fulfill request (short by " . round($left, 2) . ")."],
                ]);
            }

            // Optional: capture weighted cost on the OUT row
            $stockOut->update(['value' => $totalCost]);

            $inventoryItem = InventoryItem::find($productId);
            $inventoryItem->refresh();

            $stockOut->updated_stock = $inventoryItem->stock;

            return $stockOut;
        });
    }

    public function list(array $filters)
    {
        $query = StockEntry::with(['product', 'category', 'supplier', 'user']);

        if (!empty($filters['q'])) {
            $query->where('name', 'like', '%' . $filters['q'] . '%');
        }

        // Get paginated results
        $paginated = $query->paginate(20);

        // ✅ Transform the data to include allocated quantities
        $paginated->getCollection()->transform(function ($entry) {
            $allocatedQty = 0;

            // If it's a stock-in entry, calculate how much has been allocated
            if ($entry->stock_type === 'stockin') {
                $allocatedQty = (float) StockOutAllocation::where('stock_in_entry_id', $entry->id)
                    ->sum('quantity');
            }

            // Add the new fields to the entry object
            $entry->allocated_quantity = $allocatedQty;
            $entry->available_quantity = max(0, $entry->quantity - $allocatedQty);

            return $entry;
        });

        return $paginated;
    }

    /**
     * Compute current available quantity for a product without mutating stock-in rows.
     * Sum(IN) - Sum(allocations).
     */

    public function availableQuantity(int $productId): float  // ✅ Changed return type to float
    {
        $in = (float) StockEntry::where('product_id', $productId)
            ->where('stock_type', 'stockin')->sum('quantity');  // ✅ Changed to float

        $allocated = (float) StockOutAllocation::where('product_id', $productId)
            ->sum('quantity');  // ✅ Changed to float

        return max(0, $in - $allocated);
    }

    // New method to get total stock for a product
    public function totalStock(int $product): array
    {
        \Log::info("=== TOTAL STOCK CALCULATION FOR PRODUCT {$product} ===");

        // ✅ Use float instead of int to preserve decimals
        $totalIn = (float) StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->sum('quantity');

        \Log::info("Total Stock In: {$totalIn}");

        $totalAllocated = (float) StockOutAllocation::where('product_id', $product)
            ->sum('quantity');

        \Log::info("Total Allocated: {$totalAllocated}");

        $available = max(0, $totalIn - $totalAllocated);

        \Log::info("Available: {$available}");

        // Stock value calculation
        $totalInValue = (float) StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->sum(DB::raw('quantity * price'));

        \Log::info("Total In Value: {$totalInValue}");

        $totalOutValue = (float) StockOutAllocation::where('product_id', $product)
            ->sum(DB::raw('quantity * unit_price'));

        \Log::info("Total Out Value: {$totalOutValue}");

        $stockValue = max(0, $totalInValue - $totalOutValue);

        \Log::info("Stock Value: {$stockValue}");

        $inventory = InventoryItem::find($product);
        $minAlert = $inventory?->minAlert ?? 0;

        // ✅ Get earliest expiry from AVAILABLE (not allocated) batches
        $earliestStockIn = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->whereNotNull('expiry_date')
            ->whereRaw('quantity > (
            SELECT COALESCE(SUM(quantity), 0) 
            FROM stock_out_allocations 
            WHERE stock_in_entry_id = stock_entries.id
        )')
            ->orderBy('expiry_date', 'asc')
            ->first();

        $expiryDate = $earliestStockIn?->expiry_date;
        $status = null;

        // ✅ Check only AVAILABLE batches for expiry status
        $hasExpired = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now())
            ->whereRaw('quantity > (
            SELECT COALESCE(SUM(quantity), 0) 
            FROM stock_out_allocations 
            WHERE stock_in_entry_id = stock_entries.id
        )')
            ->exists();

        $nearExpiry = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(15)])
            ->whereRaw('quantity > (
            SELECT COALESCE(SUM(quantity), 0) 
            FROM stock_out_allocations 
            WHERE stock_in_entry_id = stock_entries.id
        )')
            ->exists();

        if ($hasExpired) {
            $status = 'expired';
        } elseif ($nearExpiry) {
            $status = 'near_expiry';
        }

        $result = [
            'available'   => $available,
            'stockValue'  => $stockValue,
            'minAlert'    => $minAlert,
            'expiry_date' => $expiryDate,
            'status'      => $status,
        ];

        \Log::info("Final Result:", $result);

        return $result;
    }

    /**
     * Calculate stock for multiple products at once (OPTIMIZED)
     */
    public function bulkTotalStock(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        \Log::info("=== BULK STOCK CALCULATION FOR " . count($productIds) . " PRODUCTS ===");

        // ✅ Get all stock-ins for these products in ONE query
        $stockIns = StockEntry::whereIn('product_id', $productIds)
            ->where('stock_type', 'stockin')
            ->select('product_id', 'id', 'quantity', 'price', 'expiry_date')
            ->get()
            ->groupBy('product_id');

        // ✅ Get all allocations in ONE query
        $allocations = StockOutAllocation::whereIn('product_id', $productIds)
            ->select('product_id', 'stock_in_entry_id', 'quantity', 'unit_price')
            ->get()
            ->groupBy('product_id');

        // ✅ Get all inventory items with minAlert in ONE query
        $inventories = InventoryItem::whereIn('id', $productIds)
            ->select('id', 'minAlert')
            ->get()
            ->keyBy('id');

        $results = [];
        $today = now()->startOfDay();

        foreach ($productIds as $productId) {
            $productStockIns = $stockIns->get($productId, collect());
            $productAllocations = $allocations->get($productId, collect());

            // Calculate totals
            $totalIn = $productStockIns->sum('quantity');
            $totalAllocated = $productAllocations->sum('quantity');
            $available = max(0, $totalIn - $totalAllocated);

            // Calculate values
            $totalInValue = $productStockIns->sum(fn($s) => $s->quantity * $s->price);
            $totalOutValue = $productAllocations->sum(fn($a) => $a->quantity * $a->unit_price);
            $stockValue = max(0, $totalInValue - $totalOutValue);

            // Get minAlert
            $minAlert = $inventories->get($productId)?->minAlert ?? 0;

            // Calculate allocated quantity per stock-in entry
            $allocatedByEntry = $productAllocations->groupBy('stock_in_entry_id')
                ->map(fn($group) => $group->sum('quantity'));

            // Find earliest expiry from AVAILABLE batches
            $availableBatches = $productStockIns->filter(function ($entry) use ($allocatedByEntry) {
                $allocated = $allocatedByEntry->get($entry->id, 0);
                return ($entry->quantity - $allocated) > 0;
            });

            $expiryDate = null;
            $status = null;
            $hasExpired = false;
            $nearExpiry = false;

            foreach ($availableBatches as $batch) {
                if ($batch->expiry_date) {
                    $expiry = \Carbon\Carbon::parse($batch->expiry_date)->startOfDay();
                    $diffDays = $today->diffInDays($expiry, false);

                    if (!$expiryDate || $expiry->lt(\Carbon\Carbon::parse($expiryDate))) {
                        $expiryDate = $batch->expiry_date;
                    }

                    if ($diffDays < 0) {
                        $hasExpired = true;
                    } elseif ($diffDays >= 0 && $diffDays <= 15) {
                        $nearExpiry = true;
                    }
                }
            }

            if ($hasExpired) {
                $status = 'expired';
            } elseif ($nearExpiry) {
                $status = 'near_expiry';
            }

            $results[$productId] = [
                'available'   => $available,
                'stockValue'  => number_format($stockValue, 2, '.', ''),
                'minAlert'    => $minAlert,
                'expiry_date' => $expiryDate,
                'status'      => $status,
            ];
        }

        \Log::info("Bulk calculation completed for " . count($results) . " products");

        return $results;
    }

    // Get stock logs
    public function getStockLogs(): array
    {
        return StockEntry::with('product', 'supplier')
            ->latest()
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'itemName' => $entry->product->name ?? 'N/A',
                    'totalPrice' => $entry->quantity * $entry->price,
                    'category' => $entry->product->getAttribute('category') ?? '',
                    'unitPrice' => $entry->price,
                    'dateTime' => $entry->created_at->toIso8601String(),
                    'expiryDate' => $entry->expiry_date,
                    'quantity' => $entry->quantity,
                    'operationType' => $entry->operation_type,
                    'type' => $entry->stock_type,
                    // 👇 Add supplier name instead of ID
                    'supplier' => $entry->supplier->name ?? '',
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

    // Stock Out Allocations 
    public function getAllocations(int $id)
    {
        $stockOut = StockEntry::with(['allocationsForThisOut.stockIn.product'])->findOrFail($id);

        if ($stockOut->stock_type !== 'stockout') {
            throw ValidationException::withMessages([
                'stock_type' => ['Allocations only exist for stockout entries.'],
            ]);
        }

        return $stockOut->allocationsForThisOut->map(function ($a) {
            return [
                'id'           => $a->id,
                'stock_in_id'  => $a->stock_in_entry_id,
                'expiry_date'  => optional($a->stockIn)->expiry_date,
                'unit_price'   => $a->unit_price ?? optional($a->stockIn)->price,
                'quantity'     => $a->quantity,
                'product_name' => optional($a->stockIn->product)->name ?? null,
            ];
        });
    }
}

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
            $needQty    = (int) $data['quantity'];

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
                $allocated = (int) StockOutAllocation::where('stock_in_entry_id', $batch->id)->sum('quantity');
                $remaining = max(0, (int)$batch->quantity - $allocated);
                if ($remaining <= 0) continue;

                $use = min($left, $remaining);
                if ($use <= 0) break;

                StockOutAllocation::create([
                    'stock_out_entry_id' => $stockOut->id,
                    'stock_in_entry_id'  => $batch->id,
                    'product_id'         => $productId,
                    'quantity'           => $use,
                    'unit_price'         => $batch->price,
                    'expiry_date'        => $batch->expiry_date,
                ]);

                $totalCost += ((float)$batch->price) * $use;
                $left -= $use;

                if ($left === 0) break;
            }

            if ($left > 0) {
                throw ValidationException::withMessages([
                    'quantity' => ["Insufficient stock to fulfill request (short by {$left})."],
                ]);
            }

            // Optional: capture weighted cost on the OUT row; remove if you want 0.
            $stockOut->update(['value' => $totalCost]);

            $inventoryItem = InventoryItem::find($productId);
            $inventoryItem->refresh(); // ensures latest relations & computed attributes

            // Optionally, attach updated stock to the returned object
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

        return $query->paginate(20);
    }

    /**
     * Compute current available quantity for a product without mutating stock-in rows.
     * Sum(IN) - Sum(allocations).
     */

    public function availableQuantity(int $productId): int
    {
        $in = (int) StockEntry::where('product_id', $productId)
            ->where('stock_type', 'stockin')->sum('quantity');

        $allocated = (int) StockOutAllocation::where('product_id', $productId)
            ->sum('quantity');

        return max(0, $in - $allocated);
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

        $totalOutValue = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockout')
            ->sum('value');

        $stockValue = $totalInValue - $totalOutValue;

        $inventory = InventoryItem::find($product);
        $minAlert = $inventory?->minAlert ?? 0;

        // 🧠 Fetch the earliest expiry date (first batch to expire)
        $earliestStockIn = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date', 'asc')
            ->first();

        $expiryDate = $earliestStockIn?->expiry_date;
        $status = null; // ✅ initialize

        // Check for expired and near-expiry batches
        $hasExpired = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now())
            ->exists();

        $nearExpiry = StockEntry::where('product_id', $product)
            ->where('stock_type', 'stockin')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(15)])
            ->exists();

        if ($hasExpired) {
            $status = 'expired';
        } elseif ($nearExpiry) {
            $status = 'near_expiry';
        }

        return response()->json([
            'available'   => $available,
            'stockValue'  => $stockValue,
            'minAlert'    => $minAlert,
            'expiry_date' => $expiryDate,
            'status'      => $status,
        ]);
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

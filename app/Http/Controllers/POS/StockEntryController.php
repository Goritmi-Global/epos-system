<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\POS\StockEntryService;
use App\Http\Requests\Inventory\StoreStockEntryRequest;
use App\Http\Requests\Inventory\UpdateStockEntryRequest;
use App\Models\StockEntry;
use App\Models\InventoryItem;
use App\Models\StockOutAllocation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StockEntryController extends Controller
{
    public function __construct(private StockEntryService $service) {}

    // List all stock entries
    public function index(Request $request)
    {
        $filters = $request->only('q');
        $stockEntries = $this->service->list($filters);

        return response()->json($stockEntries);
    }

    // Store a new stock entry
    public function store(StoreStockEntryRequest $request)
    {
        $entry = $this->service->create($request->validated());
        return response()->json([
            'message' => 'Stock Entry created successfully',
            'data' => $entry
        ], 201);
    }

    // Show a single stock entry
    public function show($id)
    {
        $entry = $this->service->list([])->find($id);
        return response()->json($entry);
    }

    public function totalStock($productId)
    {
        \Log::info("Controller: totalStock called for product {$productId}");

        $total = $this->service->totalStock($productId);

        \Log::info("Controller: Service returned:", $total);

        return response()->json($total); // ✅ No 'total' wrapper
    }

    public function stockLogs()
    {
        $logs = $this->service->getStockLogs();
        return response()->json($logs);
    }

    public function updateLog(UpdateStockEntryRequest $request, $id)
    {
        $updated = $this->service->updateLog($request, $id);

        if ($updated) {
            return response()->json(['message' => 'Stock log updated successfully']);
        }

        return response()->json(['message' => 'Failed to update log'], 500);
    }

    public function deleteLog($id)
    {
        $deleted = $this->service->deleteLog($id);
        if ($deleted) {
            return response()->json(['message' => 'Stock log deleted successfully']);
        }
        return response()->json(['message' => 'Failed to delete log'], 500);
    }

    public function byItem(InventoryItem $inventory)
    {
        $today   = Carbon::today();
        $cutoff  = Carbon::today()->addDays(15);

        $rows = StockEntry::query()
            ->forItem($inventory->id)
            ->stockIn()
            ->orderBy('expiry_date', 'asc')
            ->get(['id', 'quantity', 'price', 'value', 'description', 'purchase_date', 'expiry_date', 'created_at']);

        $mapped = $rows->map(function ($r) use ($today, $cutoff) {
            // ✅ Calculate how much has been allocated from this batch
            $allocated = (float) StockOutAllocation::where('stock_in_entry_id', $r->id)
                ->sum('quantity');

            // ✅ Calculate remaining available quantity
            $available = max(0, (float) $r->quantity - $allocated);

            $status = 'active';
            if ($r->expiry_date) {
                if ($r->expiry_date->lt($today)) {
                    $status = 'expired';
                } elseif ($r->expiry_date->lte($cutoff)) {
                    $status = 'near';
                }
            }

            return [
                'id'                 => $r->id,
                'quantity'           => (float) $r->quantity,
                'allocated_quantity' => $allocated,              // ✅ NEW
                'available_quantity' => $available,              // ✅ NEW
                'price'              => (float) $r->price,
                'value'              => (float) $r->value,
                'description'        => $r->description,
                'purchase_date'      => optional($r->purchase_date)->format('Y-m-d'),
                'expiry_date'        => optional($r->expiry_date)->format('Y-m-d'),
                'created_at'         => optional($r->created_at)->format('Y-m-d H:i'),
                'status'             => $status, // active | near | expired
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'records'        => $mapped,
                'near_count'     => $mapped->where('status', 'near')->count(),
                'near_qty'       => $mapped->where('status', 'near')->sum('available_quantity'), 
                'expired_count'  => $mapped->where('status', 'expired')->count(),
                'expired_qty'    => $mapped->where('status', 'expired')->sum('available_quantity'), 
            ],
        ]);
    }

    // Stockout Allocations
    public function allocations($id)
    {
        $allocations = $this->service->getAllocations((int) $id);

        return response()->json($allocations);
    }


    public function debugStock($productId)
    {
        \Log::info("=== DEBUG STOCK FOR PRODUCT {$productId} ===");

        // 1. Check Stock In entries
        $stockIns = StockEntry::where('product_id', $productId)
            ->where('stock_type', 'stockin')
            ->get(['id', 'quantity', 'price', 'expiry_date', 'created_at']);

        \Log::info("Stock Ins:", $stockIns->toArray());

        // 2. Check Allocations
        $allocations = StockOutAllocation::where('product_id', $productId)
            ->get(['id', 'stock_in_entry_id', 'stock_out_entry_id', 'quantity', 'unit_price']);

        \Log::info("Allocations:", $allocations->toArray());

        // 3. Check Stock Out entries
        $stockOuts = StockEntry::where('product_id', $productId)
            ->where('stock_type', 'stockout')
            ->get(['id', 'quantity', 'value', 'created_at']);

        \Log::info("Stock Outs:", $stockOuts->toArray());

        // 4. Calculate totals
        $totalIn = StockEntry::where('product_id', $productId)
            ->where('stock_type', 'stockin')
            ->sum('quantity');

        $totalAllocated = StockOutAllocation::where('product_id', $productId)
            ->sum('quantity');

        $totalOut = StockEntry::where('product_id', $productId)
            ->where('stock_type', 'stockout')
            ->sum('quantity');

        \Log::info("Calculations:", [
            'total_in' => $totalIn,
            'total_allocated' => $totalAllocated,
            'total_out' => $totalOut,
            'available_method_1' => $totalIn - $totalAllocated,
            'available_method_2' => $totalIn - $totalOut,
        ]);

        // 5. Per-batch availability
        $batchDetails = [];
        foreach ($stockIns as $batch) {
            $allocated = StockOutAllocation::where('stock_in_entry_id', $batch->id)->sum('quantity');
            $batchDetails[] = [
                'batch_id' => $batch->id,
                'quantity' => $batch->quantity,
                'allocated' => $allocated,
                'remaining' => $batch->quantity - $allocated,
            ];
        }
        \Log::info("Batch Details:", $batchDetails);

        return response()->json([
            'product_id' => $productId,
            'stock_ins' => $stockIns,
            'allocations' => $allocations,
            'stock_outs' => $stockOuts,
            'totals' => [
                'total_in' => $totalIn,
                'total_allocated' => $totalAllocated,
                'total_out' => $totalOut,
                'available' => $totalIn - $totalAllocated,
            ],
            'batch_details' => $batchDetails,
        ]);
    }
}

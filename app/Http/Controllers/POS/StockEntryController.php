<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockEntryRequest;
use App\Http\Requests\Inventory\UpdateStockEntryRequest;
use App\Models\InventoryItem;
use App\Models\StockEntry;
use App\Models\StockOutAllocation;
use App\Services\POS\StockEntryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            'data' => $entry,
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
        $total = $this->service->totalStock($productId);

        return response()->json($total);
    }

    public function bulkTotalStock(Request $request)
    {
        $productIds = $request->input('product_ids', []);

        if (empty($productIds)) {
            return response()->json([]);
        }

        $results = $this->service->bulkTotalStock($productIds);

        return response()->json($results);
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
        $today = Carbon::today();
        $cutoff = Carbon::today()->addDays(15);

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
                'id' => $r->id,
                'quantity' => (float) $r->quantity,
                'allocated_quantity' => $allocated,              // ✅ NEW
                'available_quantity' => $available,              // ✅ NEW
                'price' => (float) $r->price,
                'value' => (float) $r->value,
                'description' => $r->description,
                'purchase_date' => optional($r->purchase_date)->format('Y-m-d'),
                'expiry_date' => optional($r->expiry_date)->format('Y-m-d'),
                'created_at' => optional($r->created_at)->format('Y-m-d H:i'),
                'status' => $status, // active | near | expired
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'records' => $mapped,
                'near_count' => $mapped->where('status', 'near')->count(),
                'near_qty' => $mapped->where('status', 'near')->sum('available_quantity'),
                'expired_count' => $mapped->where('status', 'expired')->count(),
                'expired_qty' => $mapped->where('status', 'expired')->sum('available_quantity'),
            ],
        ]);
    }

    // Stockout Allocations
    public function allocations($id)
    {
        $allocations = $this->service->getAllocations((int) $id);

        return response()->json($allocations);
    }

    // Add this method in StockEntryController
    public function apiStockInLogs(Request $request)
    {
        $logs = StockEntry::query()
            ->where('stock_type', 'stockin')
            ->with(['product', 'category', 'supplier'])
            ->when($request->q, function ($q, $search) {
                $q->where(function ($qq) use ($search) {
                    $qq->whereHas('product', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    })
                        ->orWhere('quantity', 'like', "%{$search}%")
                        ->orWhere('operation_type', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        // Transform the data to match your component structure
        $logs->getCollection()->transform(function ($entry) {
            return [
                'id' => $entry->id,
                'itemName' => $entry->product->name ?? 'N/A',
                'quantity' => $entry->quantity,
                'unitPrice' => $entry->price,
                'totalPrice' => $entry->quantity * $entry->price,
                'category' => $entry->category ?? $entry->product->category ?? '',
                'dateTime' => $entry->created_at->toIso8601String(),
                'expiryDate' => $entry->expiry_date,
                'operationType' => $entry->operation_type,
                'type' => $entry->stock_type,
                'supplier' => $entry->supplier->name ?? '',
            ];
        });

        return response()->json($logs);
    }

    // Similarly, add this for Stock Out logs
    public function apiStockOutLogs(Request $request)
    {
        $logs = StockEntry::query()
            ->where('stock_type', 'stockout')
            ->with(['product', 'category'])
            ->when($request->q, function ($q, $search) {
                $q->where(function ($qq) use ($search) {
                    $qq->whereHas('product', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    })
                        ->orWhere('quantity', 'like', "%{$search}%")
                        ->orWhere('operation_type', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        $logs->getCollection()->transform(function ($entry) {
            return [
                'id' => $entry->id,
                'itemName' => $entry->product->name ?? 'N/A',
                'quantity' => $entry->quantity,
                'unitPrice' => $entry->price ?? 0,
                'totalPrice' => $entry->value ?? 0,
                'category' => $entry->category ?? $entry->product->category ?? '',
                'dateTime' => $entry->created_at->toIso8601String(),
                'operationType' => $entry->operation_type,
                'type' => $entry->stock_type,
                'description' => $entry->description ?? '',
            ];
        });

        return response()->json($logs);
    }
}

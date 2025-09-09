<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\POS\StockEntryService;
use App\Http\Requests\Inventory\StoreStockEntryRequest;
use App\Models\StockEntry;
use App\Models\InventoryItem;
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
        $total = $this->service->totalStock($productId);
        return response()->json(['total' => $total]);
    }

    public function stockLogs()
    {
        $logs = $this->service->getStockLogs();
        return response()->json($logs);
    }

    public function updateLog(Request $request, $id)
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
            ->get(['id','quantity','price','value','description','purchase_date','expiry_date','created_at']);

        $mapped = $rows->map(function ($r) use ($today, $cutoff) {
            $status = 'active';
            if ($r->expiry_date) {
                if ($r->expiry_date->lt($today)) {
                    $status = 'expired';
                } elseif ($r->expiry_date->lte($cutoff)) {
                    $status = 'near';
                }
            }

            return [
                'id'            => $r->id,
                'quantity'      => (int) $r->quantity,
                'price'         => (float) $r->price,
                'value'         => (float) $r->value,
                'description'   => $r->description,
                'purchase_date' => optional($r->purchase_date)->format('Y-m-d'),
                'expiry_date'   => optional($r->expiry_date)->format('Y-m-d'),
                'created_at'    => optional($r->created_at)->format('Y-m-d H:i'),
                'status'        => $status, // active | near | expired
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'records'        => $mapped,
                'near_count'     => $mapped->where('status', 'near')->count(),
                'near_qty'       => $mapped->where('status', 'near')->sum('quantity'),
                'expired_count'  => $mapped->where('status', 'expired')->count(),
                'expired_qty'    => $mapped->where('status', 'expired')->sum('quantity'),
            ],
        ]);
    }

}

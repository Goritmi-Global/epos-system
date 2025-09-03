<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\POS\StockEntryService;
use App\Http\Requests\Inventory\StoreStockEntryRequest;
use App\Models\StockEntry;
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
}

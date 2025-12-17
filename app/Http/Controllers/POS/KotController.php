<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\KitchenOrderItem;
use App\Services\POS\KotOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KotController extends Controller
{
    public function __construct(private KotOrderService $service) {}

    public function index()
    {
        return Inertia::render('Backend/KOT/Index');
    }

    public function getAllKotOrders(Request $request)
    {
        $filters = [
            'q' => $request->query('q', ''),
            'sort_by' => $request->query('sort_by', ''),
            'order_type' => $request->query('order_type', ''),
            'status' => $request->query('status', ''),
            'date_from' => $request->query('date_from', ''),
            'date_to' => $request->query('date_to', ''),
            'per_page' => $request->query('per_page', 10),
            'export' => $request->query('export', ''),
        ];

        $orders = $this->service->getAllOrders($filters);

        return response()->json($orders);
    }

    public function updateItemStatus(Request $request, $itemId)
    {
        $request->validate([
            'status' => 'required|in:Waiting,In Progress,Done,Cancelled',
        ]);

        $item = KitchenOrderItem::findOrFail($itemId);
        $item->status = $request->status;
        $item->save();
        $kitchenOrder = $item->kitchenOrder;
        $kitchenOrder->updateStatus();

        return response()->json([
            'message' => 'Item status updated successfully',
            'status' => $item->status,
            'order_status' => $kitchenOrder->status,
        ]);
    }
}

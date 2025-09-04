<?php

namespace App\Http\Controllers\POS;

use App\Http\Requests\Inventory\StorePurchaseOrderRequest;
use App\Http\Requests\Inventory\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\POS\PurchaseOrderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    protected $service;

    public function __construct(PurchaseOrderService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $orders = $this->service->list($request->only(['q', 'status']));
        // Instead of JSON response, use Inertia:
        return Inertia::render('Backend/Inventory/PurchaseOrder/Index', [
            'orders' => $orders
        ]);
    }


    public function store(StorePurchaseOrderRequest $request)
    {
        $order = $this->service->store($request->validated());
        return response()->json(['message' => 'Purchase order created successfully', 'data' => $order], 201);
    }

    public function show(PurchaseOrder $purchaseOrder)
{
    return response()->json($purchaseOrder->load([
        'supplier',
        'items.product' // Load product details for each item
    ]));
}

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $order = $this->service->update($purchaseOrder, $request->validated());
        return response()->json(['message' => 'Purchase order updated successfully', 'data' => $order]);
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->delete();
        return response()->json(['message' => 'Purchase order deleted successfully']);
    }
}

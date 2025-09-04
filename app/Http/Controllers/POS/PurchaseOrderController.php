<?php

namespace App\Http\Controllers\POS;

use App\Http\Requests\Inventory\StorePurchaseOrderRequest;
use App\Http\Requests\Inventory\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\POS\PurchaseOrderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        return response()->json($orders);
    }


    public function store(StorePurchaseOrderRequest $request)
    {
        $order = $this->service->store($request->validated());
        return response()->json(['message' => 'Purchase order created successfully', 'data' => $order], 201);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return $purchaseOrder->load('items');
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

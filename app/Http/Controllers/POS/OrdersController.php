<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\POS\OrdersService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrdersController extends Controller
{
    public function __construct(private OrdersService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Backend/POS/Order');

        // $orders = $this->service->list($request->only('q','status'));
        // return Inertia::render('Orders/Index', ['orders'=>$orders]);
    }

    public function show(Order $order)
    {
        return Inertia::render('Orders/Show', ['order' => $order->load([])]);
    }
    public function fetchAllOrders()
    {
        $orders = $this->service->getAllOrders();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }
}

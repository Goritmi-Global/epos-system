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

    public function fetchAllOrders(Request $request)
    {
        $filters = [
            'q' => $request->query('q', ''),
            'sort_by' => $request->query('sort_by', ''),
            'order_type' => $request->query('order_type', ''),
            'payment_type' => $request->query('payment_type', ''),
            'status' => $request->query('status', ''),
            'price_min' => $request->query('price_min', ''),
            'price_max' => $request->query('price_max', ''),
            'date_from' => $request->query('date_from', ''),
            'date_to' => $request->query('date_to', ''),
            'per_page' => $request->query('per_page', 10),
        ];
        $orders = $this->service->getAllOrders($filters);

        return response()->json($orders);
    }
}

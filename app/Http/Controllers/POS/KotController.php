<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
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
        $filters = $request->only(['status']);
        $orders = $this->service->getAllOrders($filters);

        return response()->json([
            'success' => true,
            'data'    => $orders,
            'filters' => $filters,
        ]);
    }
}

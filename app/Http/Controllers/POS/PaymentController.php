<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Services\POS\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Backend/Payment/Index');
    }

    public function getAllPayments(Request $request)
    {
        $filters = [
            'q' => $request->query('q', ''),
            'sort_by' => $request->query('sort_by', ''),
            'payment_type' => $request->query('payment_type', ''),
            'date_from' => $request->query('date_from', ''),
            'date_to' => $request->query('date_to', ''),
            'price_min' => $request->query('price_min', ''),
            'price_max' => $request->query('price_max', ''),
            'per_page' => $request->query('per_page', 10),
            'export' => $request->query('export', ''),
        ];

        $payments = $this->service->list($filters);

        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'method' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'reference' => 'nullable|string|max:255',
        ]);
        $this->service->create($validated);

        return back()->with('success', 'Payment recorded');
    }
}

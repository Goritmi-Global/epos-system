<?php

namespace App\Http\Controllers;

use App\Models\PendingOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PendingOrderController extends Controller
{
    /**
     * Store order as pending (hold order)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'delivery_location' => 'nullable|string|max:500',
            'order_type' => 'required|string',
            'table_number' => 'nullable|string',
            'sub_total' => 'required|numeric',
            'tax' => 'required|numeric',
            'service_charges' => 'required|numeric',
            'delivery_charges' => 'required|numeric',
            'sale_discount' => 'required|numeric',
            'promo_discount' => 'required|numeric',
            'approved_discounts' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'note' => 'nullable|string',
            'kitchen_note' => 'nullable|string',
            'order_items' => 'required|array',
            'applied_promos' => 'nullable|array',
            'approved_discount_details' => 'nullable|array',
            'selected_discounts' => 'nullable|array',
            'terminal_id' => 'nullable|string',
        ]);

        try {
            $pendingOrder = PendingOrder::create([
                'user_id' => Auth::id(),
                'customer_name' => $validated['customer_name'],
                'phone_number' => $validated['phone_number'],
                'delivery_location' => $validated['delivery_location'],
                'order_type' => $validated['order_type'],
                'table_number' => $validated['table_number'],
                'sub_total' => $validated['sub_total'],
                'tax' => $validated['tax'],
                'service_charges' => $validated['service_charges'],
                'delivery_charges' => $validated['delivery_charges'],
                'sale_discount' => $validated['sale_discount'],
                'promo_discount' => $validated['promo_discount'],
                'approved_discounts' => $validated['approved_discounts'],
                'total_amount' => $validated['total_amount'],
                'note' => $validated['note'],
                'kitchen_note' => $validated['kitchen_note'],
                'order_items' => $validated['order_items'],
                'applied_promos' => $validated['applied_promos'] ?? [],
                'approved_discount_details' => $validated['approved_discount_details'] ?? [],
                'selected_discounts' => $validated['selected_discounts'] ?? [],
                'terminal_id' => $validated['terminal_id'],
                'held_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order held successfully',
                'data' => $pendingOrder
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to hold order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pending orders for current user
     */
    public function index()
    {
        try {
            $pendingOrders = PendingOrder::where('user_id', Auth::id())
                ->orderBy('held_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $pendingOrders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single pending order
     */
    public function show($id)
    {
        try {
            $pendingOrder = PendingOrder::where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $pendingOrder
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pending order not found'
            ], 404);
        }
    }

    /**
     * Delete pending order (reject)
     */
    public function destroy($id)
    {
        try {
            $pendingOrder = PendingOrder::where('user_id', Auth::id())
                ->findOrFail($id);

            $pendingOrder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pending order deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pending order: ' . $e->getMessage()
            ], 500);
        }
    }
}
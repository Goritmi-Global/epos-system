<?php

namespace App\Http\Controllers;

use App\Models\PendingOrder;
use App\Models\PosOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
                'data' => $pendingOrder,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to hold order', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to hold order: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all pending orders (both held orders and unpaid POS orders)
     */
    public function index()
    {
        try {
            // Get held orders (from pending_orders table)
            $heldOrders = PendingOrder::where('user_id', Auth::id())
                ->orderBy('held_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'type' => 'held',
                        'customer_name' => $order->customer_name,
                        'phone_number' => $order->phone_number,
                        'delivery_location' => $order->delivery_location,
                        'order_type' => $order->order_type,
                        'table_number' => $order->table_number,
                        'sub_total' => $order->sub_total,
                        'tax' => $order->tax,
                        'service_charges' => $order->service_charges,
                        'delivery_charges' => $order->delivery_charges,
                        'sale_discount' => $order->sale_discount ?? 0,
                        'promo_discount' => $order->promo_discount ?? 0,
                        'approved_discounts' => $order->approved_discounts ?? 0,
                        'total_amount' => $order->total_amount,
                        'note' => $order->note,
                        'kitchen_note' => $order->kitchen_note,
                        'order_items' => $order->order_items,
                        'applied_promos' => $order->applied_promos ?? [],
                        'selected_discounts' => $order->selected_discounts ?? [],
                        'held_at' => $order->held_at,
                        'created_at' => $order->created_at,
                    ];
                });

            // Get unpaid POS orders (from pos_orders table)
            $unpaidOrders = PosOrder::with(['items', 'type', 'deliveryDetail', 'promo'])
                ->where('payment_status', 'pending')
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'type' => 'unpaid',
                        'customer_name' => $order->customer_name,
                        'phone_number' => $order->deliveryDetail->phone_number ?? null,
                        'delivery_location' => $order->deliveryDetail->delivery_location ?? null,
                        'order_type' => $order->type->order_type ?? 'Eat In', // ✅ Changed to type and Eat In
                        'table_number' => $order->type->table_number ?? null, // ✅ Changed to type
                        'sub_total' => $order->sub_total,
                        'tax' => $order->tax,
                        'service_charges' => $order->service_charges,
                        'delivery_charges' => $order->delivery_charges,
                        'sale_discount' => $order->sales_discount ?? 0,
                        'promo_discount' => $order->promo->sum('discount_amount') ?? 0,
                        'approved_discounts' => $order->approved_discounts,
                        'total_amount' => $order->total_amount,
                        'note' => $order->note,
                        'kitchen_note' => $order->kitchen_note,
                        'order_items' => $order->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product_id' => $item->menu_item_id,
                                'title' => $item->title,
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                                'unit_price' => $item->price / $item->quantity,
                                'variant_name' => $item->variant_name,
                                'variant_id' => $item->variant_id ?? null,
                                'addons' => $item->addons ?? [],
                                'note' => $item->note,
                                'item_kitchen_note' => $item->item_kitchen_note,
                                'is_deal' => $item->is_deal ?? false,
                                'deal_id' => $item->deal_id ?? null,
                            ];
                        }),
                        'held_at' => $order->created_at,
                        'created_at' => $order->created_at,
                        'pos_order_id' => $order->id,
                    ];
                });

            // Merge both collections
            $allPendingOrders = $heldOrders->concat($unpaidOrders)
                ->sortByDesc('created_at')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $allPendingOrders,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch pending orders', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending orders: '.$e->getMessage(),
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
                'data' => $pendingOrder,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pending order not found',
            ], 404);
        }
    }

    /**
     * Delete pending order (reject)
     * Handles both held orders and unpaid POS orders
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Check if this is an unpaid POS order
            $orderType = $request->input('order_type', 'held');

            if ($orderType === 'unpaid') {
                // Delete unpaid POS order
                $posOrder = PosOrder::where('user_id', Auth::id())
                    ->where('payment_status', 'pending')
                    ->findOrFail($id);

                // Optional: Restore inventory if needed
                // You can add inventory restoration logic here

                $posOrder->delete();

                Log::info('Unpaid POS order deleted', [
                    'order_id' => $id,
                    'user_id' => Auth::id(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Unpaid order deleted successfully',
                ]);
            } else {
                // Delete held order from pending_orders table
                $pendingOrder = PendingOrder::where('user_id', Auth::id())
                    ->findOrFail($id);

                $pendingOrder->delete();

                Log::info('Held order deleted', [
                    'order_id' => $id,
                    'user_id' => Auth::id(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pending order deleted successfully',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to delete pending order', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pending order: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume held order (move back to cart)
     * Only for held orders, not unpaid POS orders
     */
    public function resume($id)
    {
        try {
            $pendingOrder = PendingOrder::where('user_id', Auth::id())
                ->findOrFail($id);

            // Return the order data to frontend
            // Frontend will populate the cart with this data
            return response()->json([
                'success' => true,
                'message' => 'Order resumed successfully',
                'data' => $pendingOrder,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to resume pending order', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resume order: '.$e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\POS;

use App\Events\CartUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosOrders\StorePosOrderRequest;
use App\Models\KitchenOrderItem;
use App\Services\POS\PosOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosOrderController extends Controller
{
    public function __construct(private PosOrderService $service) {}

    public function index()
    {
        return Inertia::render('Backend/POS/Index');
    }

    public function store(StorePosOrderRequest $request)
    {
        $result = $this->service->create($request->validated());

        // ✅ Check if this is an array with logout flag (cashier auto-logout)
        if (is_array($result) && isset($result['logout']) && $result['logout'] === true) {
            $order = $result['order'];

            return response()->json([
                'message' => 'Order created successfully. You have been logged out.',
                'order' => $order,
                'kot' => $order->kot ? $order->kot->load('items') : null,
                'redirect' => route('login'),
                'logout' => true,
            ]);
        }

        // ✅ Normal response (non-cashier or auto-logout disabled)
        return response()->json([
            'message' => 'Order created successfully',
            'order' => $result,
            'kot' => $result->kot ? $result->kot->load('items') : null,
        ]);
    }

    public function fetchMenuCategories()
    {
        $menuCategories = $this->service->getMenuCategories();

        return $menuCategories;
    }

    public function fetchMenuItems()
    {
        $menus = $this->service->getAllMenus();

        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    public function fetchProfileTables()
    {
        $profileTables = $this->service->getProfileTable();

        return $profileTables;
    }

    public function updateKotItemStatus(Request $request, KitchenOrderItem $item)
    {
        $request->validate([
            'status' => 'required|in:Waiting,Done,Cancelled',
        ]);

        $item->update(['status' => $request->status]);

        return response()->json([
            'status' => $item->status,
            'message' => 'Item status updated successfully.',
        ]);
    }

    // Fetch Today KOT order
    public function getTodaysOrders()
    {
        $todayKotOrders = $this->service->getTodaysOrders();

        return response()->json(['orders' => $todayKotOrders]);
    }

    // Paymet Using Stript
    public function createIntent(Request $request)
    {
        $amount = (float) $request->input('amount', 0);
        $currency = strtolower($request->input('currency', 'usd'));
        $orderCode = $request->input('order_code') ?: (now()->format('Ymd-His') . rand(10, 99));

        //  Convert to cents (integers)
        $amountInCents = (int) round($amount * 100);
        if ($amountInCents < 50) {
            return response()->json(['error' => 'Amount must be at least 0.50'], 422);
        }

        $stripe = new \Stripe\StripeClient(config('app.stripe_secret_key'));

        //  Create PI with the real amount
        $pi = $stripe->paymentIntents->create([
            'amount' => $amountInCents,
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'order_code' => $orderCode,
                'source' => 'pos-web',
            ],
            'description' => "POS Order {$orderCode} ({$currency} {$amount})",
        ]);

        return response()->json([
            'payment_intent' => $pi->id,
            'client_secret' => $pi->client_secret,
            'order_code' => $orderCode,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    public function placeStripeOrder(Request $request)
    {
        $paymentIntentId = $request->query('payment_intent');
        $redirectStatus = $request->query('redirect_status'); // succeeded | failed | requires_action

        if ($redirectStatus !== 'succeeded' || empty($paymentIntentId)) {
            return redirect()->route('pos.order')->with('error', 'Stripe payment not successful.');
        }

        try {
            $stripe = new \Stripe\StripeClient(config('app.stripe_secret_key'));
            $pi = $stripe->paymentIntents->retrieve($paymentIntentId, [
                'expand' => ['payment_method', 'latest_charge.payment_method_details'],
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('pos.order')->with('error', 'Unable to verify payment with Stripe.');
        }

        if (($pi->status ?? null) !== 'succeeded') {
            return redirect()->route('pos.order')->with('error', 'Payment not captured yet.');
        }

        // Items JSON from query
        $items = [];
        if ($request->filled('items')) {
            try {
                $items = json_decode($request->query('items'), true) ?? [];
                \Log::info('Parsed items:', $items);
            } catch (\Throwable $e) {
                \Log::error('Failed to parse items', ['error' => $e->getMessage()]);
            }
        }

        // ✅ NEW: Parse approved_discount_details
        $approvedDiscountDetails = [];
        if ($request->filled('approved_discount_details')) {
            try {
                $approvedDiscountDetails = json_decode($request->query('approved_discount_details'), true) ?? [];
            } catch (\Throwable $e) {
                \Log::error('Failed to parse approved_discount_details', ['error' => $e->getMessage()]);
            }
        }

        // ✅ Parse applied_promos JSON
        $appliedPromos = [];
        if ($request->filled('applied_promos')) {
            try {
                $appliedPromos = json_decode($request->query('applied_promos'), true) ?? [];
            } catch (\Throwable $e) {
                \Log::error('Failed to parse applied_promos', ['error' => $e->getMessage()]);
            }
        }

        // ✅ Calculate total promo discount from array
        $totalPromoDiscount = 0;
        if (!empty($appliedPromos)) {
            foreach ($appliedPromos as $promo) {
                $totalPromoDiscount += (float) ($promo['discount_amount'] ?? 0);
            }
        }

        $currency = strtoupper($pi->currency ?? $request->query('currency_code', 'GBP'));

        // Card details (prefer latest_charge)
        $pm = $pi->payment_method;
        $chargePmd = $pi->latest_charge->payment_method_details->card ?? null;

        $brand = $chargePmd->brand ?? ($pm->card->brand ?? null);
        $last4 = $chargePmd->last4 ?? ($pm->card->last4 ?? null);
        $expMonth = $pm->card->exp_month ?? null;
        $expYear = $pm->card->exp_year ?? null;

        $code = $request->query('order_code') ?: (date('Ymd-His') . rand(10, 99));

        $data = [
            'customer_name' => $request->query('customer_name'),
            'phone_number' => $request->query('phone_number'),
            'delivery_location' => $request->query('delivery_location'),
            'sub_total' => (float) $request->query('sub_total', 0),
            'total_amount' => (float) $request->query('total_amount', 0),
            'tax' => (float) $request->query('tax', 0),
            'service_charges' => (float) $request->query('service_charges', 0),
            'delivery_charges' => (float) $request->query('delivery_charges', 0),
            'sale_discount' => (float) $request->query('sale_discount', 0),
            'approved_discounts' => (float) $request->query('approved_discounts', 0),
            'approved_discount_details' => $approvedDiscountDetails,
            'status' => 'paid',
            'note' => $request->query('note'),
            'kitchen_note' => $request->query('kitchen_note'),
            'order_date' => $request->query('order_date', now()->toDateString()),
            'order_time' => $request->query('order_time', now()->toTimeString()),
            'order_type' => $request->query('order_type'),
            'table_number' => $request->query('table_number'),
            'payment_type' => $request->query('payment_type'),
            'items' => $items,

            // Payment block
            // Payment block
            'payment_method' => 'Stripe',
            'payment_status' => $pi->status ?? 'succeeded',
            'cash_received' => (float) $request->query('cash_received', 0),
            'card_payment' => (float) $request->query('card_payment', 0),

            // Split payment amounts
            'cash_amount' => (float) $request->query('cash_received', 0),
            'card_amount' => (float) $request->query('card_payment', 0),

            // Tracking
            'order_code' => $code,
            'stripe_payment_intent_id' => $paymentIntentId,

            // Card details
            'last_digits' => $last4,
            'brand' => $brand,
            'currency_code' => $currency,
            'exp_month' => $expMonth,
            'exp_year' => $expYear,

            // ✅ NEW: Pass the full applied_promos array
            'applied_promos' => $appliedPromos,

            // ✅ Keep these for backward compatibility (optional)
            'promo_discount' => $totalPromoDiscount,
        ];

        $order = $this->service->create($data);

        // ✅ Build promo names string for receipt
        $promoNames = !empty($appliedPromos)
            ? implode(', ', array_column($appliedPromos, 'promo_name'))
            : null;

        // for printing receipt
        $printPayload = [
            'id' => $order->id,
            'customer_name' => $data['customer_name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'delivery_location' => $data['delivery_location'] ?? null,
            'order_type' => $data['order_type'] ?? null,
            'payment_method' => 'Card', // display text for receipt
            'card_brand' => $data['brand'] ?? null,
            'last4' => $data['last_digits'] ?? null,
            'sub_total' => $data['sub_total'] ?? 0,
            'total_amount' => $data['total_amount'] ?? 0,
            'sale_discount' => $data['sale_discount'] ?? 0,
            'items' => $data['items'] ?? [],
            'payment_type' => $data['payment_type'] ?? 'Card',

            // ✅ Updated promo fields for receipt
            'promo_discount' => $totalPromoDiscount,
            'promo_names' => $promoNames,
            'applied_promos' => $appliedPromos,

            // ✅ Split payment amounts for receipt
            'cash_amount' => $data['cash_amount'] ?? null,
            'card_amount' => $data['card_amount'] ?? null,
            'cash_received' => $data['cash_received'] ?? 0,

            'note' => $data['note'] ?? null,
            'kitchen_note' => $data['kitchen_note'] ?? null,
            'item_kitchen_note' => $data['item_kitchen_note'] ?? null,
        ];

        // 🔔 Flash for the frontend toast
        $promoMsg = $totalPromoDiscount > 0 ? " with £{$totalPromoDiscount} promo discount" : "";
        $msg = "Payment successful! Order #{$order->id} placed{$promoMsg}. Card {$brand} •••• {$last4}.";

        return redirect()
            ->route('pos.order')
            ->with([
                'success' => $msg,
                'print_payload' => $printPayload,
            ]);
    }

    public function cancel(Request $request, $orderId)
    {
        try {
            $order = \App\Models\PosOrder::with(['items.menuItem.ingredients.inventoryItem.category'])
                ->findOrFail($orderId);

            if ($order->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already cancelled'
                ], 422);
            }

            \DB::beginTransaction();

            // Restore inventory stock WITH expiry dates
            foreach ($order->items as $item) {
                $menuItem = $item->menuItem;
                if (!$menuItem) continue;

                $ingredients = [];
                if ($item->variant_id && $menuItem->variants) {
                    $variant = $menuItem->variants->find($item->variant_id);
                    if ($variant && $variant->ingredients) {
                        $ingredients = $variant->ingredients;
                    }
                } else {
                    $ingredients = $menuItem->ingredients ?? [];
                }

                foreach ($ingredients as $ingredient) {
                    $inventoryItem = $ingredient->inventoryItem;
                    if (!$inventoryItem) continue;

                    $requiredQty = ($ingredient->quantity ?? 1) * $item->quantity;

                    // ✅ Find the original stockout entry for this order
                    $stockOutEntry = \App\Models\StockEntry::where('product_id', $inventoryItem->id)
                        ->where('stock_type', 'stockout')
                        ->where('operation_type', 'pos_stockout')
                        ->where('description', 'like', "%Order #{$order->id}%")
                        ->first();

                    if ($stockOutEntry) {
                        // ✅ Get allocations to find which batches were used (with expiry dates)
                        $allocations = \App\Models\StockOutAllocation::where('stock_out_entry_id', $stockOutEntry->id)
                            ->get();

                        // ✅ Restore stock for each allocation (preserving expiry dates)
                        foreach ($allocations as $allocation) {
                            \App\Models\StockEntry::create([
                                'product_id' => $inventoryItem->id,
                                'name' => $ingredient->product_name ?? $inventoryItem->name,
                                'category_id' => $inventoryItem->category_id,
                                'supplier_id' => $inventoryItem->supplier_id ?? null,
                                'quantity' => $allocation->quantity,
                                'price' => $allocation->unit_price ?? $ingredient->cost ?? 0,
                                'value' => $allocation->quantity * ($allocation->unit_price ?? $ingredient->cost ?? 0),
                                'operation_type' => 'pos_cancel_return',
                                'stock_type' => 'stockin',
                                'expiry_date' => $allocation->expiry_date, // ✅ RESTORE ORIGINAL EXPIRY DATE
                                'description' => "Stock restored from cancelled order #{$order->id} (Original batch expiry: {$allocation->expiry_date})",
                                'user_id' => auth()->id(),
                            ]);

                            \Log::info("✅ Restored {$allocation->quantity} units of {$inventoryItem->name} with expiry: {$allocation->expiry_date}");
                        }
                    } else {
                        // ✅ Fallback: If no stockout entry found, restore without expiry date
                        \Log::warning("⚠️ No stockout entry found for Order #{$order->id}, Product ID: {$inventoryItem->id}. Restoring without expiry date.");

                        \App\Models\StockEntry::create([
                            'product_id' => $inventoryItem->id,
                            'name' => $ingredient->product_name ?? $inventoryItem->name,
                            'category_id' => $inventoryItem->category_id,
                            'supplier_id' => $inventoryItem->supplier_id ?? null,
                            'quantity' => $requiredQty,
                            'price' => $ingredient->cost ?? 0,
                            'value' => ($ingredient->cost ?? 0) * $requiredQty,
                            'operation_type' => 'pos_cancel_return',
                            'stock_type' => 'stockin',
                            'expiry_date' => null, // No expiry date available
                            'description' => "Stock restored from cancelled order #{$order->id} (Expiry date unknown)",
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $request->input('reason', 'Cancelled by admin')
            ]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled and inventory restored with original expiry dates',
                'order' => $order->fresh(['payment', 'items'])
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Order cancellation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function refund(Request $request, $orderId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
            'payment_type' => 'required|in:card,split'
        ]);

        try {
            $order = \App\Models\PosOrder::with(['payment'])->findOrFail($orderId);

            if (!$order->payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment record found for this order'
                ], 422);
            }

            $paymentType = strtolower($order->payment->payment_type);

            if (!in_array($paymentType, ['card', 'split'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only card and split payments can be refunded'
                ], 422);
            }

            if ($order->payment->refund_status === 'refunded') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment has already been refunded'
                ], 422);
            }

            $maxRefundAmount = $paymentType === 'split'
                ? $order->payment->card_amount
                : $order->payment->amount_received;

            if ($request->amount > $maxRefundAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund amount exceeds maximum refundable amount'
                ], 422);
            }

            \DB::beginTransaction();

            // Process Stripe refund if available
            if ($order->payment->stripe_payment_intent_id) {
                try {
                    $stripe = new \Stripe\StripeClient(config('app.stripe_secret_key'));

                    $refund = $stripe->refunds->create([
                        'payment_intent' => $order->payment->stripe_payment_intent_id,
                        'amount' => (int)round($request->amount * 100),
                        'reason' => 'requested_by_customer',
                        'metadata' => [
                            'order_id' => $order->id,
                            'refund_reason' => $request->reason ?? 'Customer requested refund',
                            'refunded_by' => auth()->id()
                        ]
                    ]);

                    $order->payment->update([
                        'refund_status' => 'refunded',
                        'refund_amount' => $request->amount,
                        'refund_date' => now(),
                        'refund_id' => $refund->id,
                        'refund_reason' => $request->reason,
                        'refunded_by' => auth()->id()
                    ]);

                    $order->update([
                        'status' => 'refunded',
                        'refund_amount' => $request->amount
                    ]);

                    \DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Refund processed successfully',
                        'order' => $order->fresh(['payment']),
                        'refund' => [
                            'id' => $refund->id,
                            'amount' => $request->amount,
                            'status' => $refund->status
                        ]
                    ]);
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    \DB::rollBack();
                    \Log::error('Stripe refund failed: ' . $e->getMessage());

                    return response()->json([
                        'success' => false,
                        'message' => 'Stripe refund failed: ' . $e->getMessage()
                    ], 500);
                }
            } else {
                // Manual refund recording
                $order->payment->update([
                    'refund_status' => 'refunded',
                    'refund_amount' => $request->amount,
                    'refund_date' => now(),
                    'refund_reason' => $request->reason,
                    'refunded_by' => auth()->id()
                ]);

                $order->update([
                    'status' => 'refunded',
                    'refund_amount' => $request->amount
                ]);

                \DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Refund recorded successfully',
                    'order' => $order->fresh(['payment'])
                ]);
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Refund processing failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund: ' . $e->getMessage()
            ], 500);
        }
    }

    public function broadcastCart(Request $request)
    {
        $validated = $request->validate([
            'terminal_id' => 'required|string',
            'cart' => 'required|array',
        ]);

        event(new CartUpdated(
            $validated['terminal_id'],
            $validated['cart']
        ));

        return response()->json(['success' => true]);
    }

    public function broadcastUI(Request $request)
    {
        $validated = $request->validate([
            'terminal_id' => 'required|string',
            'ui' => 'required|array',
        ]);

        event(new \App\Events\UIUpdated(
            $validated['terminal_id'],
            $validated['ui']
        ));

        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Http\Controllers\POS;

use App\Exceptions\MissingIngredientsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosOrders\StorePosOrderRequest;
use App\Models\KitchenOrderItem;
use App\Models\PosOrder;
use App\Models\TerminalState;
use App\Services\POS\PosOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PosOrderController extends Controller
{
    public function __construct(private PosOrderService $service) {}

    public function index()
    {
        return Inertia::render('Backend/POS/Index');
    }

    // PosOrderController.php

    /**
     * Create order without payment
     */
    public function store(StorePosOrderRequest $request)
    {
        try {
            $validated = $request->validated();
            $order = $this->service->createOrderWithoutPayment($validated);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully. Ready for payment.',
                'order' => $order->load(['items', 'type', 'deliveryDetail', 'kot.items']),
            ]);
        } catch (MissingIngredientsException $e) {
            return response()->json([
                'success' => false,
                'type' => 'missing_ingredients',
                'message' => 'Some ingredients are not available in sufficient quantity',
                'data' => $e->getMissingIngredients(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process payment for an order
     */
    public function processPayment(Request $request, $orderId)
    {
        $request->validate([
            'full_payment' => 'boolean',
            'paid_item_ids' => 'array',
            'paid_item_ids.*' => 'exists:pos_order_items,id',
            'payment_method' => 'required|string|in:Cash,Card,Stripe',
            'payment_type' => 'nullable|string|in:cash,card,split',
            'cash_received' => 'nullable|numeric|min:0',
            'cash_amount' => 'nullable|numeric|min:0',
            'card_amount' => 'nullable|numeric|min:0',
            'order_code' => 'nullable|string',
            'stripe_payment_intent_id' => 'nullable|string',
            'last_digits' => 'nullable|string',
            'brand' => 'nullable|string',
            'currency_code' => 'nullable|string',
            'exp_month' => 'nullable|integer',
            'exp_year' => 'nullable|integer',
        ]);

        try {
            $result = $this->service->processPayment($orderId, $request->all());

            return response()->json([
                'success' => true,
                'message' => $result['is_final_payment']
                    ? 'Payment completed. Order is now fully paid!'
                    : 'Partial payment received successfully.',
                'payment' => $result['payment'],
                'order' => $result['order'],
                'is_final_payment' => $result['is_final_payment'],
                'paid_items' => $result['paid_items'],
                'remaining_balance' => $result['remaining_balance'],
                'receipt_type' => $result['receipt_type'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment processing failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order payment status
     */
    public function getPaymentStatus($orderId)
    {
        try {
            $order = PosOrder::with(['items', 'payments'])->findOrFail($orderId);

            $unpaidItems = $order->items->where('payment_status', 'unpaid');
            $paidItems = $order->items->where('payment_status', 'paid');

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_status' => $order->status,
                'total_amount' => $order->total_amount,
                'total_paid' => $order->getTotalPaidAmount(),
                'remaining_balance' => $order->getRemainingBalance(),
                'unpaid_items' => $unpaidItems->values(),
                'paid_items' => $paidItems->values(),
                'payment_history' => $order->payments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }
    }

    public function show($orderId)
    {
        try {
            $order = PosOrder::with([
                'items' => function ($query) {
                    $query->select([
                        'id',
                        'pos_order_id',
                        'menu_item_id',
                        'deal_id',
                        'is_deal',
                        'title',
                        'quantity',
                        'price',
                        'amount_paid',
                        'payment_status',
                        'variant_name',
                        'note',
                        'kitchen_note',
                        'item_kitchen_note',
                    ]);
                },
                'type',
                'deliveryDetail',
                'payments',
            ])->findOrFail($orderId);

            return response()->json([
                'success' => true,
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }
    }

    /**
     * Store order without payment (Pay Later)
     */
    public function storeWithoutPayment(StorePosOrderRequest $request)
    {
        try {
            $validated = $request->validated();
            $order = $this->service->createWithoutPayment($validated);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully. Payment pending.',
                'order' => $order->load(['items', 'type', 'deliveryDetail']),
                'kot' => $order->kot ? $order->kot->load('items') : null,
            ]);
        } catch (MissingIngredientsException $e) {
            return response()->json([
                'success' => false,
                'type' => 'missing_ingredients',
                'message' => 'Some ingredients are not available in sufficient quantity',
                'data' => $e->getMissingIngredients(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete payment for pending order
     */
    public function completePayment(Request $request, $orderId)
    {
        try {
            $validated = $request->validate([
                'payment_method' => 'required|string',
                'cash_received' => 'nullable|numeric',
                'payment_type' => 'nullable|string',
                'cash_amount' => 'nullable|numeric',
                'card_amount' => 'nullable|numeric',
                'order_code' => 'nullable|string',
                'stripe_payment_intent_id' => 'nullable|string',
                'payment_intent' => 'nullable|string',
                'last_digits' => 'nullable|string',
                'brand' => 'nullable|string',
                'currency_code' => 'nullable|string',
                'exp_month' => 'nullable|integer',
                'exp_year' => 'nullable|integer',
                'payment_status' => 'nullable|string',
            ]);

            $order = $this->service->completePayment($orderId, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully',
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check ingredient availability for items (used for increment validation)
     */
    public function checkIngredients(Request $request)
    {
        $items = $request->input('items', []);

        // Reuse your existing checkStockAvailability method
        $missingIngredients = $this->service->checkStockAvailability($items);

        return response()->json([
            'success' => true,
            'missing_ingredients' => $missingIngredients,
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
            'status' => 'required|in:Waiting,Done,Cancelled,In Progress',
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
        $orderCode = $request->input('order_code') ?: (now()->format('Ymd-His').rand(10, 99));

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
        $paymentIntentId = $request->input('payment_intent');
        $redirectStatus = $request->input('redirect_status'); // succeeded | failed | requires_action

        if ($redirectStatus !== 'succeeded' || empty($paymentIntentId)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Stripe payment not successful.']);
            }

            return redirect()->route('pos.order')->with('error', 'Stripe payment not successful.');
        }

        try {
            $stripe = new \Stripe\StripeClient(config('app.stripe_secret_key'));
            $pi = $stripe->paymentIntents->retrieve($paymentIntentId, [
                'expand' => ['payment_method', 'latest_charge.payment_method_details'],
            ]);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unable to verify payment with Stripe.']);
            }

            return redirect()->route('pos.order')->with('error', 'Unable to verify payment with Stripe.');
        }

        if (($pi->status ?? null) !== 'succeeded') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Payment not captured yet.']);
            }

            return redirect()->route('pos.order')->with('error', 'Payment not captured yet.');
        }

        // Items JSON from request
        $items = [];
        if ($request->filled('items')) {
            try {
                $rawItems = $request->input('items');
                $items = is_array($rawItems) ? $rawItems : json_decode($rawItems, true) ?? [];
                \Log::info('Parsed items:', $items);
            } catch (\Throwable $e) {
                \Log::error('Failed to parse items', ['error' => $e->getMessage()]);
            }
        }

        // ✅ NEW: Parse approved_discount_details
        $approvedDiscountDetails = [];
        if ($request->filled('approved_discount_details')) {
            try {
                $rawDetails = $request->input('approved_discount_details');
                $approvedDiscountDetails = is_array($rawDetails) ? $rawDetails : json_decode($rawDetails, true) ?? [];
            } catch (\Throwable $e) {
                \Log::error('Failed to parse approved_discount_details', ['error' => $e->getMessage()]);
            }
        }

        // ✅ Parse applied_promos JSON
        $appliedPromos = [];
        if ($request->filled('applied_promos')) {
            try {
                $rawPromos = $request->input('applied_promos');
                $appliedPromos = is_array($rawPromos) ? $rawPromos : json_decode($rawPromos, true) ?? [];
            } catch (\Throwable $e) {
                \Log::error('Failed to parse applied_promos', ['error' => $e->getMessage()]);
            }
        }

        // ✅ Calculate total promo discount from array
        $totalPromoDiscount = 0;
        if (! empty($appliedPromos)) {
            foreach ($appliedPromos as $promo) {
                $totalPromoDiscount += (float) ($promo['discount_amount'] ?? 0);
            }
        }

        $currency = strtoupper($pi->currency ?? $request->input('currency_code', 'GBP'));

        // Card details (prefer latest_charge)
        $pm = $pi->payment_method;
        $chargePmd = $pi->latest_charge->payment_method_details->card ?? null;

        $brand = $chargePmd->brand ?? ($pm->card->brand ?? null);
        $last4 = $chargePmd->last4 ?? ($pm->card->last4 ?? null);
        $expMonth = $pm->card->exp_month ?? null;
        $expYear = $pm->card->exp_year ?? null;

        // ✅ NEW: Handle existing order context from callback query
        $existingOrderId = $request->input('order_id');
        $isPartialPayment = $request->input('is_partial_payment') === '1';
        $selectedItemIds = [];
        $paidItemIds = [];
        $paidProductIds = [];
        $paidDealIds = [];

        if ($request->filled('selected_item_ids')) {
            try {
                $rawSelectedIds = $request->input('selected_item_ids');
                $selectedItemIds = is_array($rawSelectedIds) ? $rawSelectedIds : json_decode($rawSelectedIds, true) ?? [];
            } catch (\Throwable $e) {
                \Log::error('Failed to parse selected_item_ids', ['error' => $e->getMessage()]);
            }
        }

        if ($request->filled('paid_item_ids')) {
            try {
                $rawIds = $request->input('paid_item_ids');
                $paidItemIds = is_array($rawIds) ? $rawIds : json_decode($rawIds, true) ?? [];
            } catch (\Throwable $e) {
                \Log::error('Failed to parse paid_item_ids', ['error' => $e->getMessage()]);
            }
        }

        if ($request->filled('paid_product_ids')) {
            try {
                $rawIds = $request->input('paid_product_ids');
                $paidProductIds = is_array($rawIds) ? $rawIds : json_decode($rawIds, true) ?? [];
            } catch (\Throwable $e) {
                \Log::error('Failed to parse paid_product_ids', ['error' => $e->getMessage()]);
            }
        }

        if ($request->filled('paid_deal_ids')) {
            try {
                $rawIds = $request->input('paid_deal_ids');
                $paidDealIds = is_array($rawIds) ? $rawIds : json_decode($rawIds, true) ?? [];
            } catch (\Throwable $e) {
                \Log::error('Failed to parse paid_deal_ids', ['error' => $e->getMessage()]);
            }
        }

        // ✅ CONTEXT-AWARE FALLBACK: If explicit arrays are empty, infer from selected_item_ids
        if (empty($paidItemIds) && empty($paidProductIds) && ! empty($selectedItemIds)) {
            \Log::info('⚠️ Using context-aware fallback for ID arrays', [
                'has_order_id' => ! empty($existingOrderId),
                'selected_count' => count($selectedItemIds),
            ]);

            if ($existingOrderId) {
                // If it's an existing order, selected_item_ids are database PKs
                $paidItemIds = $selectedItemIds;
            } else {
                // If it's a new order, selected_item_ids are Menu IDs
                $paidProductIds = $selectedItemIds;
            }
        }

        $code = $request->input('order_code') ?: (date('Ymd-His').rand(10, 99));

        $data = [
            'customer_name' => $request->input('customer_name'),
            'phone_number' => $request->input('phone_number'),
            'delivery_location' => $request->input('delivery_location'),
            'sub_total' => (float) $request->input('sub_total', 0),
            'total_amount' => (float) $request->input('total_amount', 0),
            'tax' => (float) $request->input('tax', 0),
            'service_charges' => (float) $request->input('service_charges', 0),
            'delivery_charges' => (float) $request->input('delivery_charges', 0),
            'sale_discount' => (float) $request->input('sale_discount', 0),
            'approved_discounts' => (float) $request->input('approved_discounts', 0),
            'approved_discount_details' => $approvedDiscountDetails,
            'status' => 'paid',
            'note' => $request->input('note'),
            'kitchen_note' => $request->input('kitchen_note'),
            'order_date' => $request->input('order_date', now()->toDateString()),
            'order_time' => $request->input('order_time', now()->toTimeString()),
            'order_type' => $request->input('order_type'),
            'table_number' => $request->input('table_number'),
            'payment_type' => $request->input('payment_type'),
            'items' => $items,

            // Payment data
            'payment_method' => $request->input('payment_method', 'Stripe'),
            'payment_status' => 'completed',
            'cash_amount' => (float) $request->input('cash_received', 0),
            'card_amount' => (float) $request->input('card_payment', 0),

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

        // ✅ NEW: If we have an existing order, we only need to process payment
        \Log::info('🔵 Stripe Payment Attempt', [
            'existing_order_id' => $existingOrderId,
            'is_partial_payment' => $isPartialPayment,
            'selected_item_count' => count($selectedItemIds),
        ]);

        try {
            if ($existingOrderId && is_numeric($existingOrderId)) {
                $paymentData = $data;
                $paymentData['full_payment'] = ! $isPartialPayment;
                $paymentData['paid_item_ids'] = $paidItemIds;
                $paymentData['paid_product_ids'] = $paidProductIds;
                $paymentData['paid_deal_ids'] = $paidDealIds;
                $paymentData['payment_method'] = 'Stripe';

                $result = $this->service->processPayment((int) $existingOrderId, $paymentData);
                $order = $result['order'];
                $isFinalPayment = $result['is_final_payment'];
            } else {
                // New order creation flow
                $data['full_payment'] = ! $isPartialPayment;
                $data['paid_item_ids'] = $paidItemIds;
                $data['paid_product_ids'] = $paidProductIds ?: $selectedItemIds;
                $order = $this->service->create($data);
                $isFinalPayment = $order->refresh()->isPaid();
            }
        } catch (\Throwable $e) {
            \Log::error('Stripe Order Processing Error:', ['error' => $e->getMessage()]);
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()]);
            }

            return redirect()->route('pos.order')->with('error', $e->getMessage());
        }

        \Log::info('🟢 Stripe Payment Result', [
            'order_id' => $order->id,
            'order_status' => $order->status,
            'payment_status' => $order->payment_status,
            'is_final_payment' => $isFinalPayment,
        ]);

        // ✅ Build promo names string for receipt
        $promoNames = ! empty($appliedPromos) ? implode(', ', array_column($appliedPromos, 'promo_name')) : 'None';

        // Print Payload
        $printPayload = [
            'id' => $order->id,
            'customer_name' => $data['customer_name'] ?? null,
            'order_type' => $data['order_type'] ?? null,
            'payment_method' => 'Card',
            'card_brand' => $brand,
            'last4' => $last4,
            'total_amount' => $order->total_amount,
            'items' => $order->items,
            'promo_discount' => $totalPromoDiscount,
            'promo_names' => $promoNames,
        ];

        $msg = $isFinalPayment
            ? 'Order placed & paid successfully via Stripe!'
            : 'Partial payment processed via Stripe! Some items remain unpaid.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'order' => $order->load(['items', 'payments', 'type', 'deliveryDetail', 'promo']),
                'is_final_payment' => $isFinalPayment,
                'print_payload' => $printPayload,
            ]);
        }

        return redirect()->route('pos.order')->with([
            'success' => $msg,
            'print_payload' => $printPayload,
        ]);
    }

    public function cancel(Request $request, $orderId)
    {
        try {
            $order = \App\Models\PosOrder::with([
                'items.menuItem.ingredients.inventoryItem.category',
                'type',
            ])
                ->findOrFail($orderId);

            if ($order->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already cancelled',
                ], 422);
            }

            \DB::beginTransaction();

            // ✅ Find kitchen orders - with aggressive logging
            if ($order->type) {

                // Get kitchen orders
                $kitchenOrders = \App\Models\KitchenOrder::where('pos_order_type_id', $order->type->id)->get();

                foreach ($kitchenOrders as $kitchenOrder) {

                    // Get kitchen order items BEFORE update
                    $itemsBefore = \App\Models\KitchenOrderItem::where('kitchen_order_id', $kitchenOrder->id)->get();

                    foreach ($itemsBefore as $item) {
                    }

                    // Update items
                    $affected = \App\Models\KitchenOrderItem::where('kitchen_order_id', $kitchenOrder->id)
                        ->update(['status' => 'Cancelled']);

                    // Get items AFTER update to verify
                    $itemsAfter = \App\Models\KitchenOrderItem::where('kitchen_order_id', $kitchenOrder->id)->get();
                    foreach ($itemsAfter as $item) {
                    }
                }
            }
            $posOrderTypes = \App\Models\PosOrderType::where('pos_order_id', $order->id)->get();

            foreach ($posOrderTypes as $pot) {

                $koIds = \App\Models\KitchenOrder::where('pos_order_type_id', $pot->id)->pluck('id');

                if ($koIds->isNotEmpty()) {
                    $updated = \App\Models\KitchenOrderItem::whereIn('kitchen_order_id', $koIds)
                        ->update(['status' => 'Cancelled']);
                }
            }

            // Restore inventory stock WITH expiry dates
            foreach ($order->items as $item) {
                $menuItem = $item->menuItem;
                if (! $menuItem) {
                    continue;
                }

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
                    if (! $inventoryItem) {
                        continue;
                    }

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
                                'expiry_date' => $allocation->expiry_date,
                                'description' => "Stock restored from cancelled order #{$order->id} (Original batch expiry: {$allocation->expiry_date})",
                                'user_id' => auth()->id(),
                            ]);
                        }
                    } else {

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
                            'expiry_date' => null,
                            'description' => "Stock restored from cancelled order #{$order->id} (Expiry date unknown)",
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            // Update POS order status
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $request->input('reason', 'Cancelled by admin'),
            ]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled, kitchen order items updated, and inventory restored',
                'order' => $order->fresh(['payment', 'items', 'type']),
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order: '.$e->getMessage(),
            ], 500);
        }
    }

    public function refund(Request $request, $orderId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
            'payment_type' => 'required|in:card,split',
        ]);

        try {
            $order = \App\Models\PosOrder::with(['payment'])->findOrFail($orderId);

            if (! $order->payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment record found for this order',
                ], 422);
            }

            $paymentType = strtolower($order->payment->payment_type);

            if (! in_array($paymentType, ['card', 'split'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only card and split payments can be refunded',
                ], 422);
            }

            if ($order->payment->refund_status === 'refunded') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment has already been refunded',
                ], 422);
            }

            $maxRefundAmount = $paymentType === 'split'
                ? $order->payment->card_amount
                : $order->payment->amount_received;

            if ($request->amount > $maxRefundAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund amount exceeds maximum refundable amount',
                ], 422);
            }

            \DB::beginTransaction();

            // Process Stripe refund if available
            if ($order->payment->stripe_payment_intent_id) {
                try {
                    $stripe = new \Stripe\StripeClient(config('app.stripe_secret_key'));

                    $refund = $stripe->refunds->create([
                        'payment_intent' => $order->payment->stripe_payment_intent_id,
                        'amount' => (int) round($request->amount * 100),
                        'reason' => 'requested_by_customer',
                        'metadata' => [
                            'order_id' => $order->id,
                            'refund_reason' => $request->reason ?? 'Customer requested refund',
                            'refunded_by' => auth()->id(),
                        ],
                    ]);

                    $order->payment->update([
                        'refund_status' => 'refunded',
                        'refund_amount' => $request->amount,
                        'refund_date' => now(),
                        'refund_id' => $refund->id,
                        'refund_reason' => $request->reason,
                        'refunded_by' => auth()->id(),
                    ]);

                    $order->update([
                        'status' => 'refunded',
                        'refund_amount' => $request->amount,
                    ]);

                    \DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Refund processed successfully',
                        'order' => $order->fresh(['payment']),
                        'refund' => [
                            'id' => $refund->id,
                            'amount' => $request->amount,
                            'status' => $refund->status,
                        ],
                    ]);
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    \DB::rollBack();
                    \Log::error('Stripe refund failed: '.$e->getMessage());

                    return response()->json([
                        'success' => false,
                        'message' => 'Stripe refund failed: '.$e->getMessage(),
                    ], 500);
                }
            } else {
                // Manual refund recording
                $order->payment->update([
                    'refund_status' => 'refunded',
                    'refund_amount' => $request->amount,
                    'refund_date' => now(),
                    'refund_reason' => $request->reason,
                    'refunded_by' => auth()->id(),
                ]);

                $order->update([
                    'status' => 'refunded',
                    'refund_amount' => $request->amount,
                ]);

                \DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Refund recorded successfully',
                    'order' => $order->fresh(['payment']),
                ]);
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Refund processing failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * OPTIMIZED: Lightweight version check endpoint
     */
    public function getTerminalVersion(string $terminalId)
    {
        try {
            $version = TerminalState::getVersion($terminalId);

            return response()->json([
                'version' => $version,
                'timestamp' => now()->toIso8601String(),
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('Failed to get terminal version', [
                'terminal_id' => $terminalId,
                'error' => $e->getMessage(),
            ]);

            // Return 0 version instead of error to keep display functional
            return response()->json([
                'version' => 0,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        }
    }

    /**
     * IMPROVED: Update terminal cart state
     */
    public function updateTerminalCart(Request $request)
    {
        // ✅ Use manual validation for better performance
        $validator = Validator::make($request->all(), [
            'terminal_id' => 'required|string|max:255',
            'cart' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            // ✅ Use firstOrCreate with proper defaults
            $terminal = TerminalState::firstOrCreate(
                ['terminal_id' => $validated['terminal_id']],
                [
                    'user_id' => auth()->id(),
                    'version' => 0,
                    'cart_data' => [],
                    'ui_data' => [],
                    'last_updated' => now(),
                ]
            );

            $terminal->updateCartData($validated['cart']);

            return response()->json([
                'success' => true,
                'message' => 'Cart updated',
                'version' => $terminal->version,
                'timestamp' => now()->toIso8601String(),
            ])->header('Cache-Control', 'no-cache');
        } catch (\Exception $e) {
            Log::error('Failed to update terminal cart', [
                'terminal_id' => $validated['terminal_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart',
            ], 500);
        }
    }

    /**
     * IMPROVED: Update terminal UI state
     */
    public function updateTerminalUI(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'terminal_id' => 'required|string|max:255',
            'ui' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $terminal = TerminalState::firstOrCreate(
                ['terminal_id' => $validated['terminal_id']],
                [
                    'user_id' => auth()->id(),
                    'version' => 0,
                    'cart_data' => [],
                    'ui_data' => [],
                    'last_updated' => now(),
                ]
            );

            $terminal->updateUIData($validated['ui']);

            return response()->json([
                'success' => true,
                'message' => 'UI updated',
                'version' => $terminal->version,
                'timestamp' => now()->toIso8601String(),
            ])->header('Cache-Control', 'no-cache');
        } catch (\Exception $e) {
            Log::error('Failed to update terminal UI', [
                'terminal_id' => $validated['terminal_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update UI',
            ], 500);
        }
    }

    /**
     * Batch update both cart and UI (more efficient)
     */
    public function updateTerminalBoth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'terminal_id' => 'required|string|max:255',
            'cart' => 'required|array',
            'ui' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $terminal = TerminalState::firstOrCreate(
                ['terminal_id' => $validated['terminal_id']],
                [
                    'user_id' => auth()->id(),
                    'version' => 0,
                    'cart_data' => [],
                    'ui_data' => [],
                    'last_updated' => now(),
                ]
            );

            // ✅ Update both in single transaction
            $terminal->updateBothData($validated['cart'], $validated['ui']);

            return response()->json([
                'success' => true,
                'message' => 'State updated',
                'version' => $terminal->version,
                'timestamp' => now()->toIso8601String(),
            ])->header('Cache-Control', 'no-cache');
        } catch (\Exception $e) {
            Log::error('Failed to update terminal state', [
                'terminal_id' => $validated['terminal_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update state',
            ], 500);
        }
    }

    /**
     * Get terminal state (for customer display polling)
     */
    public function getTerminalState(string $terminalId)
    {
        try {
            $state = TerminalState::getFullState($terminalId);

            return response()->json([
                'success' => true,
                'cart' => $state['cart'] ?? $this->getDefaultCartData(),
                'ui' => $state['ui'] ?? $this->getDefaultUIData(),
                'version' => $state['version'],
                'timestamp' => $state['timestamp'],
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('Failed to get terminal state', [
                'terminal_id' => $terminalId,
                'error' => $e->getMessage(),
            ]);

            // Return default state instead of error
            return response()->json([
                'success' => false,
                'cart' => $this->getDefaultCartData(),
                'ui' => $this->getDefaultUIData(),
                'version' => 0,
                'timestamp' => now()->toIso8601String(),
            ], 200);
        }
    }

    /**
     * Clear terminal cache (useful for debugging)
     */
    public function clearTerminalCache(string $terminalId)
    {
        try {
            TerminalState::clearCache($terminalId);

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear terminal cache', [
                'terminal_id' => $terminalId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
            ], 500);
        }
    }

    /**
     * Default cart data structure
     */
    private function getDefaultCartData(): array
    {
        return [
            'items' => [],
            'customer' => 'Walk In',
            'orderType' => 'Dine In',
            'table' => null,
            'subtotal' => 0,
            'tax' => 0,
            'serviceCharges' => 0,
            'deliveryCharges' => 0,
            'saleDiscount' => 0,
            'promoDiscount' => 0,
            'total' => 0,
            'note' => '',
            'appliedPromos' => [],
        ];
    }

    /**
     * Default UI data structure
     */
    private function getDefaultUIData(): array
    {
        return [
            'showCategories' => true,
            'activeCat' => null,
            'menuCategories' => [],
            'menuItems' => [],
            'searchQuery' => '',
            'selectedCardVariant' => [],
            'selectedCardAddons' => [],
        ];
    }

    /**
     * Get next walk-in counter number
     */
    public function getNextWalkInNumber()
    {
        try {
            $number = \App\Models\WalkInCounter::getNextNumber();

            return response()->json([
                'success' => true,
                'number' => $number,
                'formatted' => 'Walk In-'.str_pad($number, 3, '0', STR_PAD_LEFT),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get walk-in number', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get walk-in number',
            ], 500);
        }
    }

    /**
     * Get current walk-in counter (without incrementing)
     */
    public function getCurrentWalkInNumber()
    {
        try {
            $number = \App\Models\WalkInCounter::getCurrentNumber();

            return response()->json([
                'success' => true,
                'number' => $number,
                'formatted' => 'Walk In-'.str_pad($number, 3, '0', STR_PAD_LEFT),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get current number',
            ], 500);
        }
    }

    public function payForItems(Request $request, $orderId)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:pos_order_items,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'cash_received' => 'nullable|numeric',
            'payment_type' => 'nullable|string',
            'cash_amount' => 'nullable|numeric',
            'card_amount' => 'nullable|numeric',
        ]);

        try {
            $result = $this->service->payForItems($orderId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Payment successful',
                'order' => $result['order'],
                'receipt' => $result['receipt'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Pay for items failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

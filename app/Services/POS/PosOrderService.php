<?php

namespace App\Services\POS;

use App\Exceptions\MissingIngredientsException;
use App\Helpers\UploadHelper;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\PendingIngredientDeduction;
use App\Models\PosOrder;
use App\Models\PosOrderType;
use App\Models\RestaurantProfile;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PosOrderService
{
    public function __construct(private StockEntryService $stockEntryService) {}

    public function list(array $filters = [])
    {
        return PosOrder::query()
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    // PosOrderService.php - Modified create method
    public function create(array $data): PosOrder
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the order without payment first
            $order = $this->createOrderWithoutPayment($data);

            // 2. Prepare payment data
            $paymentData = [
                'payment_method' => $data['payment_method'] ?? 'Stripe',
                'full_payment' => $data['full_payment'] ?? true,
                'paid_item_ids' => $data['paid_item_ids'] ?? [],
                'paid_product_ids' => $data['paid_product_ids'] ?? [],
                'payment_status' => $data['payment_status'] ?? 'completed',
                'order_code' => $data['order_code'] ?? null,
                'stripe_payment_intent_id' => $data['stripe_payment_intent_id'] ?? null,
                'last_digits' => $data['last_digits'] ?? null,
                'brand' => $data['brand'] ?? null,
                'currency_code' => $data['currency_code'] ?? 'GBP',
                'exp_month' => $data['exp_month'] ?? null,
                'exp_year' => $data['exp_year'] ?? null,
                'payment_type' => $data['payment_type'] ?? null,
                'cash_amount' => $data['cash_amount'] ?? 0,
                'card_amount' => $data['card_amount'] ?? 0,
                'cash_received' => $data['cash_received'] ?? 0,
            ];

            // 3. CRITICAL: Map frontend product/deal IDs to backend item IDs
            if (! $paymentData['full_payment']) {
                $dbMapped = $this->mapDatabaseIds($order, (array) ($paymentData['paid_item_ids'] ?? []));
                $productMapped = $this->mapProductIds($order, (array) ($paymentData['paid_product_ids'] ?? []));
                $dealMapped = $this->mapDealIds($order, (array) ($paymentData['paid_deal_ids'] ?? []));
                $paymentData['paid_item_ids'] = array_unique(array_merge($dbMapped, $productMapped, $dealMapped));
            }

            // 4. Process the payment
            $this->processPayment($order->id, $paymentData);

            return $order->fresh(['items', 'payments', 'type', 'deliveryDetail', 'kot.items', 'promo']);
        });
    }

    public function createOrderWithoutPayment(array $data): PosOrder
    {
        return DB::transaction(function () use ($data) {

            // ✅ CHECK INVENTORY (your existing logic)
            $superAdmin = \App\Models\User::where('is_first_super_admin', true)->first();
            $inventoryTrackingEnabled = false;

            if ($superAdmin) {
                $settings = \App\Models\ProfileStep9::where('user_id', $superAdmin->id)->first();
                $inventoryTrackingEnabled = $settings && $settings->enable_inventory_tracking == 1;
            }

            $confirmMissing = $data['confirm_missing_ingredients'] ?? false;

            if ($inventoryTrackingEnabled && $confirmMissing !== true) {
                $missingIngredients = $this->checkStockAvailability($data['items'] ?? []);
                if (! empty($missingIngredients)) {
                    throw new MissingIngredientsException($missingIngredients);
                }
            }

            // ✅ CHECK ACTIVE SHIFT
            $activeShift = Shift::where('status', 'open')->latest()->first();
            if (! $activeShift) {
                throw new \Exception('No active shift found. Please start a shift before creating an order.');
            }

            // ✅ WALK-IN CUSTOMER NUMBER
            if (empty($data['customer_name']) || str_starts_with($data['customer_name'], 'Walk In')) {
                $counter = DB::table('walk_in_counters')->lockForUpdate()->first();
                if (! $counter) {
                    throw new \Exception('Walk-in counter not initialized.');
                }

                $nextNumber = $counter->current_number + 1;
                DB::table('walk_in_counters')
                    ->where('id', $counter->id)
                    ->update([
                        'current_number' => $nextNumber,
                        'updated_at' => now(),
                    ]);

                $data['customer_name'] = 'Walk In-'.str_pad($nextNumber, 3, '0');
            }

            // ✅ CREATE ORDER (NO PAYMENT YET)
            $order = PosOrder::create([
                'user_id' => Auth::id(),
                'shift_id' => $activeShift->id,
                'customer_name' => $data['customer_name'] ?? null,
                'sub_total' => $data['sub_total'],
                'total_amount' => $data['total_amount'],
                'tax' => $data['tax'] ?? null,
                'service_charges' => $data['service_charges'] ?? null,
                'delivery_charges' => $data['delivery_charges'] ?? null,
                'sales_discount' => $data['sale_discount'] ?? 0,
                'approved_discounts' => $data['approved_discounts'] ?? 0,
                'status' => 'unpaid', // ⭐ START AS UNPAID
                'note' => $data['note'] ?? null,
                'kitchen_note' => $data['kitchen_note'] ?? null,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'order_time' => $data['order_time'] ?? now()->toTimeString(),
                'source' => $data['source'] ?? 'Pos System',
            ]);

            // ✅ DELIVERY DETAILS
            if (($data['order_type'] ?? '') === 'Delivery') {
                $order->deliveryDetail()->create([
                    'phone_number' => $data['phone_number'] ?? null,
                    'delivery_location' => $data['delivery_location'] ?? null,
                ]);
            }

            // ✅ ORDER TYPE
            $orderType = PosOrderType::create([
                'pos_order_id' => $order->id,
                'order_type' => $data['order_type'],
                'table_number' => $data['table_number'] ?? null,
            ]);

            // ✅ PROCESS ORDER ITEMS (your existing logic)
            foreach ($data['items'] as $item) {
                $isDeal = $item['is_deal'] ?? false;

                if ($isDeal) {
                    $orderItem = $order->items()->create([
                        'menu_item_id' => null,
                        'deal_id' => $item['deal_id'],
                        'is_deal' => true,
                        'title' => $item['title'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'amount_paid' => 0, // ⭐ INITIALIZE AS UNPAID
                        'payment_status' => 'unpaid', // ⭐ UNPAID STATUS
                        'sale_discount_per_item' => 0,
                        'note' => $item['note'] ?? null,
                        'variant_name' => null,
                        'kitchen_note' => $item['kitchen_note'] ?? null,
                        'item_kitchen_note' => $item['item_kitchen_note'] ?? null,
                    ]);

                    // Process ingredients for deals (your existing logic)
                    if ($inventoryTrackingEnabled) {
                        $removedIngredientsRaw = $item['removed_ingredients'] ?? [];
                        $removedIngredients = collect($removedIngredientsRaw)->pluck('id')->filter()->toArray();

                        if (! empty($item['menu_items']) && is_array($item['menu_items'])) {
                            foreach ($item['menu_items'] as $dealMenuItem) {
                                $ingredients = $dealMenuItem['ingredients'] ?? [];
                                if (! empty($ingredients)) {
                                    foreach ($ingredients as $ingredient) {
                                        $inventoryItemId = $ingredient['inventory_item_id'] ?? $ingredient['id'] ?? null;
                                        if (! $inventoryItemId) {
                                            continue;
                                        }

                                        if (in_array($inventoryItemId, $removedIngredients) ||
                                            in_array($ingredient['id'] ?? null, $removedIngredients)) {
                                            continue;
                                        }

                                        $inventoryItem = InventoryItem::find($inventoryItemId);
                                        if ($inventoryItem) {
                                            $requiredQty = ($ingredient['quantity'] ?? 1) * $item['quantity'];
                                            $availableStock = (float) $inventoryItem->stock;

                                            if ($availableStock >= $requiredQty) {
                                                $this->stockEntryService->create([
                                                    'product_id' => $inventoryItem->id,
                                                    'name' => $inventoryItem->name,
                                                    'category_id' => $inventoryItem->category_id,
                                                    'supplier_id' => $inventoryItem->supplier_id,
                                                    'quantity' => $requiredQty,
                                                    'value' => 0,
                                                    'operation_type' => 'pos_stockout',
                                                    'stock_type' => 'stockout',
                                                    'description' => "Auto stockout from POS Order #{$order->id} - Deal: {$item['title']} - Item: {$dealMenuItem['name']}",
                                                    'user_id' => Auth::id(),
                                                ]);
                                            } else {
                                                $deductedQty = min($availableStock, $requiredQty);
                                                $pendingQty = $requiredQty - $deductedQty;

                                                if ($deductedQty > 0) {
                                                    $this->stockEntryService->create([
                                                        'product_id' => $inventoryItem->id,
                                                        'name' => $inventoryItem->name,
                                                        'category_id' => $inventoryItem->category_id,
                                                        'supplier_id' => $inventoryItem->supplier_id,
                                                        'quantity' => $deductedQty,
                                                        'value' => 0,
                                                        'operation_type' => 'pos_stockout',
                                                        'stock_type' => 'stockout',
                                                        'description' => "Partial stockout from POS Order #{$order->id} - Deal: {$item['title']} ({$deductedQty} of {$requiredQty})",
                                                        'user_id' => Auth::id(),
                                                    ]);
                                                }

                                                $unit = $ingredient['unit'] ?? 'units';
                                                PendingIngredientDeduction::create([
                                                    'order_id' => $order->id,
                                                    'order_item_id' => $orderItem->id,
                                                    'inventory_item_id' => $inventoryItem->id,
                                                    'inventory_item_name' => $ingredient['product_name'] ?? $inventoryItem->name,
                                                    'required_quantity' => $requiredQty,
                                                    'available_quantity' => $availableStock,
                                                    'pending_quantity' => $pendingQty,
                                                    'status' => 'pending',
                                                    'notes' => "Order #{$order->id} - Deal: {$item['title']} - Item: {$dealMenuItem['name']} - Missing {$pendingQty} {$unit}",
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // Regular menu item
                    $orderItem = $order->items()->create([
                        'menu_item_id' => $item['product_id'],
                        'deal_id' => null,
                        'is_deal' => false,
                        'title' => $item['title'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'amount_paid' => 0, // ⭐ INITIALIZE AS UNPAID
                        'payment_status' => 'unpaid', // ⭐ UNPAID STATUS
                        'sale_discount_per_item' => $item['sale_discount_per_item'] ?? 0,
                        'note' => $item['note'] ?? null,
                        'variant_name' => $item['variant_name'] ?? null,
                        'kitchen_note' => $item['kitchen_note'] ?? null,
                        'item_kitchen_note' => $item['item_kitchen_note'] ?? null,
                    ]);

                    // Store addons
                    if (! empty($item['addons']) && is_array($item['addons'])) {
                        foreach ($item['addons'] as $addon) {
                            $orderItem->addons()->create([
                                'addon_id' => $addon['id'],
                                'addon_name' => $addon['name'],
                                'price' => $addon['price'],
                                'quantity' => $addon['quantity'] ?? 1,
                            ]);
                        }
                    }

                    // Process ingredients (your existing logic)
                    if ($inventoryTrackingEnabled) {
                        $removedIngredientsRaw = $item['removed_ingredients'] ?? [];
                        $removedIngredients = collect($removedIngredientsRaw)->pluck('id')->filter()->toArray();
                        $ingredients = $this->getIngredientsForItem($item);

                        if (! empty($ingredients)) {
                            foreach ($ingredients as $ingredient) {
                                if (in_array($ingredient->id, $removedIngredients) ||
                                    in_array($ingredient->inventory_item_id, $removedIngredients)) {
                                    continue;
                                }

                                $inventoryItem = InventoryItem::find($ingredient->inventory_item_id);
                                if ($inventoryItem) {
                                    $requiredQty = $ingredient->quantity * $item['quantity'];
                                    $availableStock = (float) $inventoryItem->stock;

                                    if ($availableStock >= $requiredQty) {
                                        $this->stockEntryService->create([
                                            'product_id' => $inventoryItem->id,
                                            'name' => $inventoryItem->name,
                                            'category_id' => $inventoryItem->category_id,
                                            'supplier_id' => $inventoryItem->supplier_id,
                                            'quantity' => $requiredQty,
                                            'value' => 0,
                                            'operation_type' => 'pos_stockout',
                                            'stock_type' => 'stockout',
                                            'description' => "Auto stockout from POS Order #{$order->id}".
                                                (isset($item['variant_name']) && $item['variant_name'] ? " - Variant: {$item['variant_name']}" : ''),
                                            'user_id' => Auth::id(),
                                        ]);
                                    } else {
                                        $deductedQty = min($availableStock, $requiredQty);
                                        $pendingQty = $requiredQty - $deductedQty;

                                        if ($deductedQty > 0) {
                                            $this->stockEntryService->create([
                                                'product_id' => $inventoryItem->id,
                                                'name' => $inventoryItem->name,
                                                'category_id' => $inventoryItem->category_id,
                                                'supplier_id' => $inventoryItem->supplier_id,
                                                'quantity' => $deductedQty,
                                                'value' => 0,
                                                'operation_type' => 'pos_stockout',
                                                'stock_type' => 'stockout',
                                                'description' => "Partial stockout from POS Order #{$order->id} ({$deductedQty} of {$requiredQty})".
                                                    (isset($item['variant_name']) && $item['variant_name'] ? " - Variant: {$item['variant_name']}" : ''),
                                                'user_id' => Auth::id(),
                                            ]);
                                        }

                                        $unit = $ingredient->unit ?? 'units';
                                        PendingIngredientDeduction::create([
                                            'order_id' => $order->id,
                                            'order_item_id' => $orderItem->id,
                                            'inventory_item_id' => $inventoryItem->id,
                                            'inventory_item_name' => $ingredient->product_name ?? $inventoryItem->name,
                                            'required_quantity' => $requiredQty,
                                            'available_quantity' => $availableStock,
                                            'pending_quantity' => $pendingQty,
                                            'status' => 'pending',
                                            'notes' => "Order #{$order->id} - {$item['title']} ({$item['quantity']} qty) - Missing {$pendingQty} {$unit}",
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $order->load('items');

            // ✅ CREATE KOT
            $kot = KitchenOrder::create([
                'pos_order_type_id' => $orderType->id,
                'order_time' => now()->toTimeString(),
                'order_date' => now()->toDateString(),
                'note' => $data['note'] ?? null,
                'kitchen_note' => $data['kitchen_note'] ?? null,
            ]);

            // ✅ CREATE KOT ITEMS
            foreach ($order->items as $orderItem) {
                $itemData = collect($data['items'])->firstWhere('product_id', $orderItem->menu_item_id);

                if (! $itemData) {
                    $kot->items()->create([
                        'item_name' => $orderItem->title,
                        'quantity' => $orderItem->quantity,
                        'variant_name' => $orderItem->variant_name ?? null,
                        'ingredients' => [],
                        'item_kitchen_note' => $orderItem->item_kitchen_note ?? null,
                        'status' => KitchenOrderItem::STATUS_WAITING,
                    ]);

                    continue;
                }

                $ingredients = $this->getIngredientsForItem($itemData);
                $removedIngredientsRaw = $itemData['removed_ingredients'] ?? [];
                $removedIngredients = collect($removedIngredientsRaw)->pluck('id')->filter()->toArray();

                $ingredientsArray = [];
                if (! empty($ingredients)) {
                    foreach ($ingredients as $ingredient) {
                        if (! in_array($ingredient->id, $removedIngredients) &&
                            ! in_array($ingredient->inventory_item_id, $removedIngredients)) {
                            $ingredientsArray[] = $ingredient->product_name;
                        }
                    }
                }

                $kot->items()->create([
                    'item_name' => $orderItem->title,
                    'quantity' => $orderItem->quantity,
                    'variant_name' => $orderItem->variant_name ?? null,
                    'ingredients' => $ingredientsArray,
                    'item_kitchen_note' => $itemData['item_kitchen_note'] ?? null,
                    'status' => KitchenOrderItem::STATUS_WAITING,
                ]);
            }

            $kot->load('items');

            // ✅ STORE PROMO DETAILS
            if (! empty($data['applied_promos']) && is_array($data['applied_promos'])) {
                foreach ($data['applied_promos'] as $promoData) {
                    \App\Models\OrderPromo::create([
                        'order_id' => $order->id,
                        'promo_id' => $promoData['promo_id'],
                        'promo_name' => $promoData['promo_name'] ?? null,
                        'promo_type' => $promoData['promo_type'] ?? 'flat',
                        'discount_amount' => $promoData['discount_amount'] ?? 0,
                    ]);
                }
            }

            $order->load(['items', 'kot.items', 'promo']);

            return $order;
        });
    }

    public function processPayment(int $orderId, array $paymentData): array
    {
        return DB::transaction(function () use ($orderId, $paymentData) {

            $order = PosOrder::with('items')->lockForUpdate()->findOrFail($orderId);

            \Log::info('🔍 Processing payment', [
                'order_id' => $orderId,
                'order_status' => $order->status,
                'total_items' => $order->items->count(),
                'payment_data' => $paymentData,
            ]);

            // ✅ VALIDATE ORDER STATUS
            if ($order->status === 'paid') {
                throw new \Exception('This order has already been fully paid.');
            }

            // ✅ DETERMINE PAYMENT TYPE
            $isFullPayment = $paymentData['full_payment'] ?? false;
            $paidItemIds = $paymentData['paid_item_ids'] ?? [];

            // ✅ MAP FRONTEND IDS IF NECESSARY
            if (! $isFullPayment) {
                $paidItemIdsRaw = (array) ($paymentData['paid_item_ids'] ?? []);
                $paidProductIdsRaw = (array) ($paymentData['paid_product_ids'] ?? []);
                $paidDealIdsRaw = (array) ($paymentData['paid_deal_ids'] ?? []);

                $dbMapped = $this->mapDatabaseIds($order, $paidItemIdsRaw);
                $productMapped = $this->mapProductIds($order, $paidProductIdsRaw);
                $dealMapped = $this->mapDealIds($order, $paidDealIdsRaw);

                $paidItemIds = array_unique(array_merge($dbMapped, $productMapped, $dealMapped));
            }

            \Log::info('💳 Payment details', [
                'is_full_payment' => $isFullPayment,
                'paid_item_ids' => $paidItemIds,
                'paid_item_ids_count' => count($paidItemIds),
                'request_is_partial' => $paymentData['is_partial_payment'] ?? 'N/A',
            ]);

            if (! $isFullPayment && empty($paidItemIds)) {
                throw new \Exception('Please select at least one item to pay for.');
            }

            // ✅ GET ITEMS TO PAY
            $itemsToPay = collect();
            $totalPaymentAmount = 0;

            if ($isFullPayment) {
                // Pay all unpaid items
                $itemsToPay = $order->items->where('payment_status', 'unpaid');
                \Log::info('📦 Full payment - unpaid items', [
                    'unpaid_count' => $itemsToPay->count(),
                ]);
            } else {
                // Pay selected items only
                foreach ($paidItemIds as $itemId) {
                    \Log::info('🔍 Looking for item', [
                        'looking_for_id' => $itemId,
                        'all_item_ids' => $order->items->pluck('id', 'payment_status')->toArray(),
                    ]);

                    $item = $order->items->where('id', $itemId)
                        ->where('payment_status', 'unpaid')
                        ->first();

                    if ($item) {
                        $itemsToPay->push($item);
                        \Log::info('✅ Found unpaid item', [
                            'item_id' => $item->id,
                            'title' => $item->title,
                            'payment_status' => $item->payment_status,
                        ]);
                    } else {
                        \Log::warning('⚠️ Item not found or already paid', [
                            'item_id' => $itemId,
                        ]);
                    }
                }
            }

            \Log::info('📊 Items to pay summary', [
                'items_to_pay_count' => $itemsToPay->count(),
                'items_to_pay_ids' => $itemsToPay->pluck('id')->toArray(),
            ]);

            if ($itemsToPay->isEmpty()) {
                \Log::error('❌ No unpaid items found', [
                    'requested_ids' => $paidItemIds,
                    'all_items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'title' => $item->title,
                            'payment_status' => $item->payment_status,
                            'amount_paid' => $item->amount_paid,
                        ];
                    })->toArray(),
                ]);
                throw new \Exception('No unpaid items found to process payment.');
            }

            // ✅ CALCULATE PAYMENT AMOUNT
            foreach ($itemsToPay as $item) {
                $itemTotal = $item->price * $item->quantity;
                $totalPaymentAmount += $itemTotal;
            }
            $totalPaymentAmount = round($totalPaymentAmount, 2);

            \Log::info('💰 Payment amount calculated', [
                'total_payment_amount' => $totalPaymentAmount,
            ]);

            // Rest of your payment processing code...
            // (Keep the existing code for payment method, updating items, creating payment record, etc.)

            // ✅ PARSE PAYMENT METHOD
            $paymentMethod = $paymentData['payment_method'] ?? 'Cash';
            $cashAmount = 0;
            $cardAmount = 0;
            $paymentType = $paymentMethod;

            if (($paymentData['payment_type'] ?? '') === 'split') {
                \Log::info('🔄 Processing Split Payment', [
                    'cash_amount' => $paymentData['cash_amount'] ?? 0,
                    'card_amount' => $paymentData['card_amount'] ?? 0,
                    'payment_data' => $paymentData,
                ]);
                $cashAmount = $paymentData['cash_amount'] ?? 0;
                $cardAmount = $paymentData['card_amount'] ?? 0;
                $paymentType = 'Split';

                if (round($cashAmount + $cardAmount, 2) < round($totalPaymentAmount, 2)) {
                    throw new \Exception('Split payment amounts are insufficient.');
                }
            } elseif ($paymentMethod === 'Cash') {
                $cashAmount = $paymentData['cash_received'] ?? $totalPaymentAmount;
                $cardAmount = 0;

                if (round($cashAmount, 2) < round($totalPaymentAmount, 2)) {
                    throw new \Exception('Cash received is less than payment amount.');
                }
            } else {
                $cashAmount = 0;
                $cardAmount = $totalPaymentAmount;
                $paymentType = 'Card';
            }

            // ✅ UPDATE ITEMS AS PAID
            $paidItemsPayload = [];
            foreach ($itemsToPay as $item) {
                $itemTotal = $item->price * $item->quantity;

                $item->update([
                    'amount_paid' => $itemTotal,
                    'payment_status' => 'paid',
                ]);

                $paidItemsPayload[] = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'amount' => $itemTotal,
                ];
            }

            // ✅ CREATE PAYMENT RECORD
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'amount_received' => $totalPaymentAmount,
                'payment_type' => $paymentType,
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'paid_items' => json_encode($paidItemsPayload),
                'payment_date' => now(),
                'payment_status' => 'completed',
                'code' => $paymentData['order_code'] ?? null,
                'stripe_payment_intent_id' => $paymentData['stripe_payment_intent_id'] ?? null,
                'last_digits' => $paymentData['last_digits'] ?? null,
                'brand' => $paymentData['brand'] ?? null,
                'currency_code' => $paymentData['currency_code'] ?? 'GBP',
                'exp_month' => $paymentData['exp_month'] ?? null,
                'exp_year' => $paymentData['exp_year'] ?? null,
            ]);

            // ✅ UPDATE ORDER STATUS
            $order->updatePaymentStatusFromItems();
            $order->refresh();

            $isFinalPayment = $order->payment_status === 'paid';

            \Log::info('🔄 Order status updated', [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
                'status' => $order->status,
                'is_final_payment' => $isFinalPayment,
                'total_items' => $order->items->count(),
                'unpaid_items' => $order->items()->where('payment_status', 'unpaid')->count(),
            ]);

            $payment->update(['is_final_receipt' => $isFinalPayment]);

            \Log::info('✅ Payment completed', [
                'is_final_payment' => $isFinalPayment,
                'remaining_unpaid' => $order->items->where('payment_status', 'unpaid')->count(),
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'order' => $order->fresh(['items', 'payments']),
                'is_final_payment' => $isFinalPayment,
                'paid_items' => $paidItemsPayload,
                'remaining_balance' => $order->total_amount - $order->getTotalPaidAmount(),
                'receipt_type' => $isFinalPayment ? 'final_invoice' : 'payment_slip',
            ];
        });
    }

    /**
     * Map frontend product/deal IDs to actual database PosOrderItem IDs
     */
    private function mapDatabaseIds(PosOrder $order, array $dbIds): array
    {
        $mappedItemIds = [];
        $unpaidItems = $order->items->where('payment_status', 'unpaid');

        foreach ($dbIds as $id) {
            $item = $unpaidItems->where('id', (int) $id)->first();
            if ($item) {
                $mappedItemIds[] = $item->id;
                // Temporarily mark so we don't pick it again if IDs duplicated
                $item->payment_status = 'pending_map';
            }
        }

        // Reset temporary status
        foreach ($order->items as $it) {
            if ($it->payment_status === 'pending_map') {
                $it->payment_status = 'unpaid';
            }
        }

        return $mappedItemIds;
    }

    private function mapDealIds(PosOrder $order, array $dealIds): array
    {
        $mappedItemIds = [];
        $unpaidItems = $order->items->where('payment_status', 'unpaid');

        foreach ($dealIds as $fid) {
            $item = $unpaidItems->filter(function ($it) use ($fid) {
                return $it->is_deal && (int) $it->deal_id === (int) $fid;
            })->first();

            if ($item) {
                $mappedItemIds[] = $item->id;
                $item->payment_status = 'pending_map';
            }
        }

        // Reset temporary status
        foreach ($order->items as $it) {
            if ($it->payment_status === 'pending_map') {
                $it->payment_status = 'unpaid';
            }
        }

        return $mappedItemIds;
    }

    private function mapProductIds(PosOrder $order, array $productIds): array
    {
        $mappedItemIds = [];
        $unpaidItems = $order->items->where('payment_status', 'unpaid');

        foreach ($productIds as $fid) {
            $item = $unpaidItems->filter(function ($it) use ($fid) {
                return ! $it->is_deal && (int) $it->menu_item_id === (int) $fid;
            })->first();

            if ($item) {
                $mappedItemIds[] = $item->id;
                $item->payment_status = 'pending_map';
            }
        }

        // Reset temporary status
        foreach ($order->items as $it) {
            if ($it->payment_status === 'pending_map') {
                $it->payment_status = 'unpaid';
            }
        }

        return $mappedItemIds;
    }

    /**
     * Map frontend product/deal IDs to actual database PosOrderItem IDs
     * DEPRECATED: Use mapDatabaseIds or mapProductIds
     */
    private function mapFrontendIdsToDatabaseIds(PosOrder $order, array $frontendIds): array
    {
        // For backward compatibility, prioritize product mapping but log a warning
        \Log::warning('⚠️ Using deprecated mapFrontendIdsToDatabaseIds. Ambiguity possible.', ['ids' => $frontendIds]);

        return $this->mapProductIds($order, $frontendIds);
    }

    /**
     * Get ingredients for a menu item (variant-aware) with null handling
     *
     * @param  array|null  $item  The item data from the request
     * @return \Illuminate\Support\Collection
     */
    private function getIngredientsForItem(?array $item)
    {
        if (! $item) {
            return collect();
        }

        // If variant_id is provided, get variant ingredients
        if (! empty($item['variant_id'])) {
            $variant = \App\Models\MenuVariant::with('ingredients.inventoryItem')
                ->find($item['variant_id']);

            if ($variant && $variant->ingredients->count() > 0) {
                return $variant->ingredients;
            }
        }

        // Fallback to base menu item ingredients
        $menuItem = MenuItem::with('ingredients.inventoryItem')
            ->find($item['product_id']);

        return $menuItem ? $menuItem->ingredients : collect();
    }

    public function startOrder(array $payload = []): PosOrder
    {
        return PosOrder::create([
            'order_no' => $payload['order_no'] ?? Str::upper(Str::random(8)),
            'customer_name' => $payload['customer_name'] ?? null,
            'service_type' => $payload['service_type'] ?? 'dine_in',
            'table_no' => $payload['table_no'] ?? null,
            'status' => 'draft',
            'total' => 0,
            'paid' => 0,
            'change' => 0,
        ]);
    }

    public function updateTotals(PosOrder $order, float $total, float $paid = 0): PosOrder
    {
        $order->fill([
            'total' => $total,
            'paid' => $paid,
            'change' => max(0, $paid - $total),
        ])->save();

        return $order;
    }

    public function markAsCompleted(PosOrder $order): PosOrder
    {
        $order->status = 'completed';
        $order->save();

        return $order;
    }

    public function cancel(PosOrder $order): void
    {
        $order->status = 'canceled';
        $order->save();
    }

    public function getMenuCategories(bool $onlyActive = true)
    {
        $query = MenuCategory::with('children')
            ->withCount([
                'menuItems as menu_items_count' => function ($q) {
                    $q->where('status', 1);
                },
                // Add deals count
                'deals as deals_count' => function ($q) {
                    $q->where('status', 1);
                },
            ])
            ->whereNull('parent_id');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get()->map(function ($cat) {
            $cat->image_url = UploadHelper::url($cat->upload_id);

            // Calculate total items (menu items + deals)
            $totalItems = ($cat->menu_items_count ?? 0) + ($cat->deals_count ?? 0);

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'image_url' => $cat->image_url,
                'box_bg_color' => $cat->box_bg_color ?? '#1b1670',
                'menu_items_count' => $cat->menu_items_count,
                'deals_count' => $cat->deals_count ?? 0,
                'total_items_count' => $totalItems, // Add this
                'children' => $cat->children,
            ];
        });
    }

    public function getAllMenus()
    {
        $menus = MenuItem::with([
            'category',
            'ingredients.inventoryItem',
            'variants.ingredients.inventoryItem',
            'nutrition',
            'allergies',
            'tags',
            'upload',
            'addonGroupRelations.addonGroup.addons',
        ])->where('status', 1)->get();

        return $menus->map(function ($item) {
            $item->image_url = $item->upload_id ? UploadHelper::url($item->upload_id) : null;

            $item->ingredients = $item->ingredients->map(function ($ingredient) {
                return [
                    'id' => $ingredient->id,
                    'product_name' => $ingredient->product_name,
                    'quantity' => $ingredient->quantity,
                    'cost' => $ingredient->cost,
                    'inventory_stock' => $ingredient->inventoryItem?->stock ?? 0,
                    'inventory_item_id' => $ingredient->inventory_item_id,
                    'category_id' => $ingredient->inventoryItem?->category_id,
                    'supplier_id' => $ingredient->inventoryItem?->supplier_id,
                    'user_id' => $ingredient->inventoryItem?->user_id,
                ];
            })->values()->toArray();

            $item->variants = $item->variants->map(function ($variant) {
                if ($variant->ingredients->isEmpty()) {
                    \Log::warning("⚠️ No ingredients found for Variant ID: {$variant->id}");
                } else {
                    foreach ($variant->ingredients as $ing) {
                        \Log::info("✅ Ingredient: {$ing->product_name} (Inventory ID: {$ing->inventory_item_id}) Quantity: {$ing->quantity}");
                    }
                }

                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'price' => (float) $variant->price,
                    'ingredients' => $variant->ingredients->map(function ($ingredient) {
                        return [
                            'id' => $ingredient->id,
                            'product_name' => $ingredient->product_name,
                            'quantity' => $ingredient->quantity,
                            'cost' => $ingredient->cost,
                            'inventory_stock' => $ingredient->inventoryItem?->stock ?? 0,
                            'inventory_item_id' => $ingredient->inventory_item_id,
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();

            $addonsGrouped = [];
            foreach ($item->addonGroupRelations ?? [] as $relation) {
                $group = $relation->addonGroup;
                if (! $group || $group->status !== 'active') {
                    continue;
                }

                $groupId = $group->id;
                if (! isset($addonsGrouped[$groupId])) {
                    $addonsGrouped[$groupId] = [
                        'group_id' => $group->id,
                        'group_name' => $group->name,
                        'min_select' => $group->min_select,
                        'max_select' => $group->max_select,
                        'addons' => [],
                    ];
                }

                foreach ($group->addons as $addon) {
                    if ($addon->status !== 'active') {
                        continue;
                    }

                    $addonsGrouped[$groupId]['addons'][] = [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'price' => (float) $addon->price,
                        'description' => $addon->description,
                    ];
                }
            }

            $item->addon_groups = array_values($addonsGrouped);
            $item->is_taxable = $item->is_taxable ?? 0;
            $item->tax_percentage = $item->tax_percentage ?? 0;

            unset($item->addonGroupRelations);

            return $item->toArray();
        });
    }

    public function getProfileTable()
    {
        return RestaurantProfile::select('order_types', 'table_details')->first();
    }

    public function getTodaysOrders()
    {
        $today = now()->toDateString();

        return KitchenOrder::with([
            'items',
            'posOrderType.order.payment',
            'posOrderType.order.items',
        ])->whereDate('order_date', $today)->get();
    }

    /**
     * Check stock availability for all items in order
     */
    public function checkStockAvailability(array $items): array
    {
        $missingIngredients = [];

        foreach ($items as $item) {
            $menuItem = \App\Models\MenuItem::with(['ingredients.inventoryItem', 'variants.ingredients.inventoryItem'])
                ->find($item['product_id']);

            if (! $menuItem) {
                continue;
            }

            $ingredients = [];
            if (! empty($item['variant_id']) && $menuItem->variants) {
                $variant = $menuItem->variants->find($item['variant_id']);
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

                $requiredQty = ($ingredient->quantity ?? 1) * $item['quantity'];
                $availableStock = (float) $inventoryItem->stock;

                $removedIngredients = $item['removed_ingredients'] ?? [];
                $ingredientId = $ingredient->id ?? $ingredient->inventory_item_id;

                if (in_array($ingredientId, $removedIngredients)) {
                    continue;
                }

                if ($availableStock < $requiredQty) {
                    $missingIngredients[] = [
                        'item_id' => $item['product_id'],
                        'item_title' => $item['title'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'variant_name' => $item['variant_name'] ?? null,
                        'inventory_item_id' => $inventoryItem->id,
                        'inventory_item_name' => $ingredient->product_name ?? $inventoryItem->name,
                        'required_quantity' => $requiredQty,
                        'available_quantity' => $availableStock,
                        'shortage_quantity' => $requiredQty - $availableStock,
                        'unit' => $ingredient->unit ?? 'unit',
                        'order_quantity' => $item['quantity'],
                    ];
                }
            }
        }

        return $missingIngredients;
    }

    /**
     * Deduct stock with proper tracking
     */
    private function deductStock($inventoryItem, $quantity, $order, $ingredient)
    {
        \App\Models\StockEntry::create([
            'product_id' => $inventoryItem->id,
            'name' => $ingredient->product_name ?? $inventoryItem->name,
            'category_id' => $inventoryItem->category_id,
            'quantity' => $quantity,
            'value' => 0,
            'operation_type' => 'pos_stockout',
            'stock_type' => 'stockout',
            'description' => "Deducted for Order #{$order->id}",
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Create order without payment (Pay Later)
     */
    public function createWithoutPayment(array $data): PosOrder
    {
        return DB::transaction(function () use ($data) {
            // Check inventory tracking
            $superAdmin = \App\Models\User::where('is_first_super_admin', true)->first();
            $inventoryTrackingEnabled = false;

            if ($superAdmin) {
                $settings = \App\Models\ProfileStep9::where('user_id', $superAdmin->id)->first();
                $inventoryTrackingEnabled = $settings && $settings->enable_inventory_tracking == 1;
            }

            $confirmMissing = $data['confirm_missing_ingredients'] ?? false;

            // Check stock if tracking enabled and not confirmed
            if ($inventoryTrackingEnabled && $confirmMissing !== true) {
                $missingIngredients = $this->checkStockAvailability($data['items'] ?? []);
                if (! empty($missingIngredients)) {
                    throw new MissingIngredientsException($missingIngredients);
                }
            }

            // Check active shift
            $activeShift = Shift::where('status', 'open')->latest()->first();
            if (! $activeShift) {
                throw new \Exception('No active shift found. Please start a shift before creating an order.');
            }

            // Create order with pending payment
            $order = PosOrder::create([
                'user_id' => Auth::id(),
                'shift_id' => $activeShift->id,
                'customer_name' => $data['customer_name'] ?? null,
                'sub_total' => $data['sub_total'],
                'total_amount' => $data['total_amount'],
                'tax' => $data['tax'] ?? null,
                'service_charges' => $data['service_charges'] ?? null,
                'delivery_charges' => $data['delivery_charges'] ?? null,
                'sales_discount' => $data['sale_discount'] ?? $data['sales_discount'] ?? 0,
                'approved_discounts' => $data['approved_discounts'] ?? 0,
                'status' => 'pending', // Order pending until payment
                'payment_status' => 'pending', // Payment pending
                'note' => $data['note'] ?? null,
                'kitchen_note' => $data['kitchen_note'] ?? null,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'order_time' => $data['order_time'] ?? now()->toTimeString(),
                'source' => $data['source'] ?? 'Pos System',
            ]);

            // Handle delivery details
            if (($data['order_type'] ?? '') === 'Delivery') {
                $order->deliveryDetail()->create([
                    'phone_number' => $data['phone_number'] ?? null,
                    'delivery_location' => $data['delivery_location'] ?? null,
                ]);
            }

            // Create order type
            $orderType = PosOrderType::create([
                'pos_order_id' => $order->id,
                'order_type' => $data['order_type'],
                'table_number' => $data['table_number'] ?? null,
            ]);

            // Process order items (same as create method)
            $this->processOrderItems($order, $data['items'], $inventoryTrackingEnabled);

            $order->load('items');

            foreach ($order->items as $item) {
                $item->update(['amount_paid' => 0, 'payment_status' => 'unpaid']);
            }

            // Create KOT
            $kot = $this->createKitchenOrder($order, $orderType, $data);

            // Store promo details
            $this->storePromoDetails($order, $data);

            $order->load(['items', 'kot.items', 'promo']);

            return $order;
        });
    }

    /**
     * Complete payment for pending order
     */
    public function completePayment(int $orderId, array $paymentData): PosOrder
    {
        // Reuse the robust processPayment logic
        $result = $this->processPayment($orderId, $paymentData);

        return $result['order'];
    }

    /**
     * Process order items and inventory
     */
    protected function processOrderItems(PosOrder $order, array $items, bool $inventoryTrackingEnabled): void
    {
        foreach ($items as $item) {
            $isDeal = $item['is_deal'] ?? false;

            if ($isDeal) {
                $this->processDealItem($order, $item, $inventoryTrackingEnabled);
            } else {
                $this->processRegularItem($order, $item, $inventoryTrackingEnabled);
            }
        }
    }

    /**
     * Process deal item
     */
    protected function processDealItem(PosOrder $order, array $item, bool $inventoryTrackingEnabled): void
    {
        $orderItem = $order->items()->create([
            'menu_item_id' => null,
            'deal_id' => $item['deal_id'],
            'is_deal' => true,
            'title' => $item['title'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'sale_discount_per_item' => 0,
            'note' => $item['note'] ?? null,
            'variant_name' => null,
            'kitchen_note' => $item['kitchen_note'] ?? null,
            'item_kitchen_note' => $item['item_kitchen_note'] ?? null,
        ]);

        if ($inventoryTrackingEnabled) {
            $removedIngredientsRaw = $item['removed_ingredients'] ?? [];
            $removedIngredients = collect($removedIngredientsRaw)->pluck('id')->filter()->toArray();

            if (! empty($item['menu_items']) && is_array($item['menu_items'])) {
                foreach ($item['menu_items'] as $dealMenuItem) {
                    $ingredients = $dealMenuItem['ingredients'] ?? [];

                    if (! empty($ingredients)) {
                        $this->processIngredients(
                            $order,
                            $orderItem,
                            $ingredients,
                            $item['quantity'],
                            $removedIngredients,
                            "Deal: {$item['title']} - Item: {$dealMenuItem['name']}"
                        );
                    }
                }
            }
        }
    }

    /**
     * Process regular menu item
     */
    protected function processRegularItem(PosOrder $order, array $item, bool $inventoryTrackingEnabled): void
    {
        $orderItem = $order->items()->create([
            'menu_item_id' => $item['product_id'],
            'deal_id' => null,
            'is_deal' => false,
            'title' => $item['title'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'sale_discount_per_item' => $item['sale_discount_per_item'] ?? 0,
            'note' => $item['note'] ?? null,
            'variant_name' => $item['variant_name'] ?? null,
            'kitchen_note' => $item['kitchen_note'] ?? null,
            'item_kitchen_note' => $item['item_kitchen_note'] ?? null,
        ]);

        // Store addons
        if (! empty($item['addons']) && is_array($item['addons'])) {
            foreach ($item['addons'] as $addon) {
                $orderItem->addons()->create([
                    'addon_id' => $addon['id'],
                    'addon_name' => $addon['name'],
                    'price' => $addon['price'],
                    'quantity' => $addon['quantity'] ?? 1,
                ]);
            }
        }

        if ($inventoryTrackingEnabled) {
            $removedIngredientsRaw = $item['removed_ingredients'] ?? [];
            $removedIngredients = collect($removedIngredientsRaw)->pluck('id')->filter()->toArray();

            $ingredients = $this->getIngredientsForItem($item);

            if ($ingredients->isNotEmpty()) {  // ✅ Fixed: Use isNotEmpty() for Collection
                $this->processIngredients(
                    $order,
                    $orderItem,
                    $ingredients->toArray(),  // ✅ Fixed: Convert to array
                    $item['quantity'],
                    $removedIngredients,
                    $item['title'].(isset($item['variant_name']) ? " - Variant: {$item['variant_name']}" : '')
                );
            }
        }
    }

    /**
     * Process ingredients for stockout
     */
    protected function processIngredients(
        PosOrder $order,
        $orderItem,
        array $ingredients,
        int $quantity,
        array $removedIngredients,
        string $description
    ): void {
        foreach ($ingredients as $ingredient) {
            $inventoryItemId = is_object($ingredient)
                ? $ingredient->inventory_item_id
                : ($ingredient['inventory_item_id'] ?? $ingredient['id'] ?? null);

            if (! $inventoryItemId) {
                Log::warning('Missing inventory_item_id for ingredient', ['ingredient' => $ingredient]);

                continue;
            }

            // Skip if ingredient was removed
            if (in_array($inventoryItemId, $removedIngredients)) {
                Log::info("Skipped removed ingredient: {$inventoryItemId} in Order #{$order->id}");

                continue;
            }

            $inventoryItem = InventoryItem::find($inventoryItemId);

            if ($inventoryItem) {
                $ingredientQty = is_object($ingredient) ? $ingredient->quantity : ($ingredient['quantity'] ?? 1);
                $requiredQty = $ingredientQty * $quantity;
                $availableStock = (float) $inventoryItem->stock;

                if ($availableStock >= $requiredQty) {
                    // Full stock available
                    $this->stockEntryService->create([
                        'product_id' => $inventoryItem->id,
                        'name' => $inventoryItem->name,
                        'category_id' => $inventoryItem->category_id,
                        'supplier_id' => $inventoryItem->supplier_id,
                        'quantity' => $requiredQty,
                        'value' => 0,
                        'operation_type' => 'pos_stockout',
                        'stock_type' => 'stockout',
                        'description' => "Auto stockout from POS Order #{$order->id} - {$description}",
                        'user_id' => Auth::id(),
                    ]);
                } else {
                    // Partial/No stock - Handle missing ingredients
                    $deductedQty = min($availableStock, $requiredQty);
                    $pendingQty = $requiredQty - $deductedQty;

                    // Deduct available stock
                    if ($deductedQty > 0) {
                        $this->stockEntryService->create([
                            'product_id' => $inventoryItem->id,
                            'name' => $inventoryItem->name,
                            'category_id' => $inventoryItem->category_id,
                            'supplier_id' => $inventoryItem->supplier_id,
                            'quantity' => $deductedQty,
                            'value' => 0,
                            'operation_type' => 'pos_stockout',
                            'stock_type' => 'stockout',
                            'description' => "Partial stockout from POS Order #{$order->id} - {$description} ({$deductedQty} of {$requiredQty})",
                            'user_id' => Auth::id(),
                        ]);
                    }

                    // Record pending deduction
                    $unit = is_object($ingredient) ? ($ingredient->unit ?? 'units') : ($ingredient['unit'] ?? 'units');
                    $ingredientName = is_object($ingredient)
                        ? ($ingredient->product_name ?? $inventoryItem->name)
                        : ($ingredient['product_name'] ?? $inventoryItem->name);

                    PendingIngredientDeduction::create([
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'inventory_item_id' => $inventoryItem->id,
                        'inventory_item_name' => $ingredientName,
                        'required_quantity' => $requiredQty,
                        'available_quantity' => $availableStock,
                        'pending_quantity' => $pendingQty,
                        'status' => 'pending',
                        'notes' => "Order #{$order->id} - {$description} - Missing {$pendingQty} {$unit}",
                    ]);
                }
            }
        }
    }

    /**
     * Create kitchen order (KOT)
     */
    protected function createKitchenOrder(PosOrder $order, PosOrderType $orderType, array $data): KitchenOrder
    {
        $kot = KitchenOrder::create([
            'pos_order_type_id' => $orderType->id,
            'order_time' => now()->toTimeString(),
            'order_date' => now()->toDateString(),
            'note' => $data['note'] ?? null,
            'kitchen_note' => $data['kitchen_note'] ?? null,
        ]);

        // Create KOT items
        foreach ($order->items as $orderItem) {
            $itemData = collect($data['items'])->firstWhere('product_id', $orderItem->menu_item_id);

            if (! $itemData) {
                $kot->items()->create([
                    'item_name' => $orderItem->title,
                    'quantity' => $orderItem->quantity,
                    'variant_name' => $orderItem->variant_name ?? null,
                    'ingredients' => [],
                    'item_kitchen_note' => $orderItem->item_kitchen_note ?? null,
                    'status' => \App\Models\KitchenOrderItem::STATUS_WAITING,
                ]);

                continue;
            }

            $ingredients = $this->getIngredientsForItem($itemData);
            $removedIngredientsRaw = $itemData['removed_ingredients'] ?? [];
            $removedIngredients = collect($removedIngredientsRaw)->pluck('id')->filter()->toArray();

            $ingredientsArray = [];
            if (! empty($ingredients)) {
                foreach ($ingredients as $ingredient) {
                    $ingredientId = is_object($ingredient) ? $ingredient->id : ($ingredient['id'] ?? null);
                    $inventoryItemId = is_object($ingredient) ? $ingredient->inventory_item_id : ($ingredient['inventory_item_id'] ?? null);

                    if (! in_array($ingredientId, $removedIngredients) && ! in_array($inventoryItemId, $removedIngredients)) {
                        $ingredientsArray[] = is_object($ingredient) ? $ingredient->product_name : ($ingredient['product_name'] ?? '');
                    }
                }
            }

            $kot->items()->create([
                'item_name' => $orderItem->title,
                'quantity' => $orderItem->quantity,
                'variant_name' => $orderItem->variant_name ?? null,
                'ingredients' => $ingredientsArray,
                'item_kitchen_note' => $itemData['item_kitchen_note'] ?? null,
                'status' => \App\Models\KitchenOrderItem::STATUS_WAITING,
            ]);
        }

        $kot->load('items');

        return $kot;
    }

    /**
     * Store promo details
     */
    protected function storePromoDetails(PosOrder $order, array $data): void
    {
        if (! empty($data['applied_promos']) && is_array($data['applied_promos'])) {
            foreach ($data['applied_promos'] as $promoData) {
                \App\Models\OrderPromo::create([
                    'order_id' => $order->id,
                    'promo_id' => $promoData['promo_id'],
                    'promo_name' => $promoData['promo_name'] ?? null,
                    'promo_type' => $promoData['promo_type'] ?? 'flat',
                    'discount_amount' => $promoData['discount_amount'] ?? 0,
                ]);
            }
        } elseif (! empty($data['promo_id']) && ! empty($data['promo_discount'])) {
            \App\Models\OrderPromo::create([
                'order_id' => $order->id,
                'promo_id' => $data['promo_id'],
                'promo_name' => $data['promo_name'] ?? null,
                'promo_type' => $data['promo_type'] ?? 'flat',
                'discount_amount' => $data['promo_discount'],
            ]);
        }
    }

    public function payForItems(int $orderId, array $data): PosOrder
    {
        return DB::transaction(function () use ($orderId, $data) {
            $order = PosOrder::with('items')->findOrFail($orderId);

            // Calculate payment
            $totalAmount = collect($data['items'])->sum('amount');
            $paymentType = $data['payment_method'];
            $cashAmount = 0;
            $cardAmount = 0;

            if ($data['payment_type'] === 'split') {
                $cashAmount = $data['cash_amount'] ?? 0;
                $cardAmount = $data['card_amount'] ?? 0;
                $paymentType = 'Split';
            } elseif ($paymentType === 'Cash') {
                $cashAmount = $data['cash_received'] ?? $totalAmount;
            } else {
                $cardAmount = $data['cash_received'] ?? $totalAmount;
                $paymentType = 'Card';
            }

            // Create payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'amount_received' => $totalAmount,
                'payment_type' => $paymentType,
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'payment_date' => now(),
                'payment_status' => 'completed',
                'paid_items' => $data['items'],
            ]);

            // Update items
            foreach ($data['items'] as $itemData) {
                $item = $order->items()->find($itemData['id']);
                if ($item) {
                    $item->amount_paid += $itemData['amount'];
                    $item->updatePaymentStatus();
                }
            }

            // Update order status
            $order->updatePaymentStatusFromItems();

            return $order->fresh(['items', 'payments']);
        });
    }
}

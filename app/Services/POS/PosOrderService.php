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

    public function create(array $data): PosOrder|array
    {
        return DB::transaction(function () use ($data) {

            // ✅ CHECK IF INVENTORY TRACKING IS ENABLED
            $superAdmin = \App\Models\User::where('is_first_super_admin', true)->first();
            $inventoryTrackingEnabled = false;

            if ($superAdmin) {
                $settings = \App\Models\ProfileStep9::where('user_id', $superAdmin->id)->first();
                $inventoryTrackingEnabled = $settings && $settings->enable_inventory_tracking == 1;
            }

            // Use strict comparison instead of empty()
            $confirmMissing = $data['confirm_missing_ingredients'] ?? false;

            // 1. Check for missing ingredients (if not already confirmed AND if tracking is enabled)
            if ($inventoryTrackingEnabled && $confirmMissing !== true) {
                $missingIngredients = $this->checkStockAvailability($data['items'] ?? []);

                if (! empty($missingIngredients)) {
                    throw new MissingIngredientsException($missingIngredients);
                }
            } else {
                if (! $inventoryTrackingEnabled) {
                    Log::info('⏭️ Skipping ingredient check - Inventory tracking disabled');
                } else {
                    Log::info('⏭️ Skipping ingredient check', [
                        'reason' => 'User confirmed to proceed with missing ingredients',
                        'confirm_flag_value' => $confirmMissing,
                    ]);
                }
            }

            // 2. Check for active shift
            $activeShift = Shift::where('status', 'open')->latest()->first();

            if (! $activeShift) {
                throw new \Exception('No active shift found. Please start a shift before creating an order.');
            }

            // 3. Create the main order
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
                'status' => $data['status'] ?? 'paid',
                'note' => $data['note'] ?? null,
                'kitchen_note' => $data['kitchen_note'] ?? null,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'order_time' => $data['order_time'] ?? now()->toTimeString(),
                'source' => $data['source'] ?? 'Pos System',
            ]);

            // 4. Handle delivery details
            if (($data['order_type'] ?? '') === 'Delivery') {
                $order->deliveryDetail()->create([
                    'phone_number' => $data['phone_number'] ?? null,
                    'delivery_location' => $data['delivery_location'] ?? null,
                ]);
            }

            // 5. Create order type
            $orderType = PosOrderType::create([
                'pos_order_id' => $order->id,
                'order_type' => $data['order_type'],
                'table_number' => $data['table_number'] ?? null,
            ]);

            // 6. Process order items and inventory
            foreach ($data['items'] as $item) {

                // ✅ Check if this is a deal
                $isDeal = $item['is_deal'] ?? false;

                if ($isDeal) {
                    // ✅ Handle Deal Order Item
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

                    // ✅ ONLY PROCESS INVENTORY IF TRACKING IS ENABLED
                    if ($inventoryTrackingEnabled) {
                        // ✅ GET REMOVED INGREDIENTS FOR THIS DEAL and extract IDs
                        $removedIngredientsRaw = $item['removed_ingredients'] ?? [];

                        // Convert array of objects to array of IDs
                        $removedIngredients = collect($removedIngredientsRaw)
                            ->pluck('id')
                            ->filter()
                            ->toArray();

                        // ✅ Process ingredients for each menu item in the deal
                        if (! empty($item['menu_items']) && is_array($item['menu_items'])) {
                            foreach ($item['menu_items'] as $dealMenuItem) {
                                $ingredients = $dealMenuItem['ingredients'] ?? [];

                                if (! empty($ingredients)) {
                                    foreach ($ingredients as $ingredient) {
                                        // FIX: Handle both 'id' and 'inventory_item_id' keys
                                        $inventoryItemId = $ingredient['inventory_item_id'] ?? $ingredient['id'] ?? null;

                                        if (! $inventoryItemId) {
                                            Log::warning('Missing inventory_item_id for ingredient in deal', [
                                                'ingredient' => $ingredient,
                                                'deal_menu_item' => $dealMenuItem['name'],
                                            ]);

                                            continue;
                                        }

                                        // ✅ SKIP IF INGREDIENT WAS REMOVED BY CUSTOMER
                                        if (in_array($inventoryItemId, $removedIngredients) ||
                                            in_array($ingredient['id'] ?? null, $removedIngredients)) {
                                            Log::info("Skipping stockout for removed ingredient in deal: {$ingredient['product_name']} in Order #{$order->id}");

                                            continue;
                                        }

                                        $inventoryItem = InventoryItem::find($inventoryItemId);

                                        if ($inventoryItem) {
                                            $requiredQty = ($ingredient['quantity'] ?? 1) * $item['quantity'];
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
                                                    'description' => "Auto stockout from POS Order #{$order->id} - Deal: {$item['title']} - Item: {$dealMenuItem['name']}",
                                                    'user_id' => Auth::id(),
                                                ]);
                                            } else {
                                                // Partial/No stock - Handle missing ingredients
                                                $deductedQty = min($availableStock, $requiredQty);
                                                $pendingQty = $requiredQty - $deductedQty;

                                                // Deduct whatever is available
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

                                                // Record pending deduction
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
                                        } else {
                                            Log::warning('Inventory item not found', [
                                                'inventory_item_id' => $inventoryItemId,
                                                'ingredient' => $ingredient,
                                                'deal_menu_item' => $dealMenuItem['name'],
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // ✅ Handle Regular Menu Item
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

                    // ✅ ONLY PROCESS INVENTORY IF TRACKING IS ENABLED
                    if ($inventoryTrackingEnabled) {
                        // Get removed ingredients from request and extract IDs
                        $removedIngredientsRaw = $item['removed_ingredients'] ?? [];

                        // Convert array of objects to array of IDs
                        $removedIngredients = collect($removedIngredientsRaw)
                            ->pluck('id')
                            ->filter()
                            ->toArray();

                        // 🐛 DEBUG: Log removed ingredients
                        Log::info('Processing regular item stockout', [
                            'item_title' => $item['title'],
                            'removed_ingredients_raw' => $removedIngredientsRaw,
                            'removed_ingredient_ids' => $removedIngredients,
                            'removed_count' => count($removedIngredients),
                        ]);

                        // Get ingredients for this item
                        $ingredients = $this->getIngredientsForItem($item);

                        // Process ingredient stockout with missing ingredient handling
                        if (! empty($ingredients)) {
                            foreach ($ingredients as $ingredient) {
                                // 🐛 DEBUG: Log each ingredient check
                                Log::info('Checking ingredient', [
                                    'ingredient_id' => $ingredient->id,
                                    'inventory_item_id' => $ingredient->inventory_item_id,
                                    'product_name' => $ingredient->product_name,
                                    'is_in_removed_list' => in_array($ingredient->id, $removedIngredients) ||
                                                            in_array($ingredient->inventory_item_id, $removedIngredients),
                                ]);

                                // Skip if ingredient was removed by customer
                                if (
                                    in_array($ingredient->id, $removedIngredients) ||
                                    in_array($ingredient->inventory_item_id, $removedIngredients)
                                ) {
                                    Log::info("✅ SKIPPED: Removed ingredient will not be deducted: {$ingredient->product_name} in Order #{$order->id}");

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
                                        // Partial/No stock - Create pending deduction
                                        $deductedQty = min($availableStock, $requiredQty);
                                        $pendingQty = $requiredQty - $deductedQty;

                                        // Deduct whatever is available
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

                                        // Record pending deduction
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

            // 7. Create KOT
            $kot = KitchenOrder::create([
                'pos_order_type_id' => $orderType->id,
                'order_time' => now()->toTimeString(),
                'order_date' => now()->toDateString(),
                'note' => $data['note'] ?? null,
                'kitchen_note' => $data['kitchen_note'] ?? null,
            ]);

            // 8. Create KOT items with removed ingredients filtered
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

                // Get ingredients for this item
                $ingredients = $this->getIngredientsForItem($itemData);

                // Filter out removed ingredients from KOT display - Extract IDs from objects
                $removedIngredientsRaw = $itemData['removed_ingredients'] ?? [];
                $removedIngredients = collect($removedIngredientsRaw)
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                $ingredientsArray = [];
                if (! empty($ingredients)) {
                    foreach ($ingredients as $ingredient) {
                        // Only show ingredients that were NOT removed
                        if (
                            ! in_array($ingredient->id, $removedIngredients) &&
                            ! in_array($ingredient->inventory_item_id, $removedIngredients)
                        ) {
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

            // 9. Payment handling
            $cashAmount = null;
            $cardAmount = null;

            if (($data['payment_type'] ?? '') === 'split') {
                $cashAmount = $data['cash_amount'] ?? 0;
                $cardAmount = $data['card_amount'] ?? 0;
                $payedUsing = 'Split';
            } elseif (($data['payment_method'] ?? 'Cash') === 'Cash') {
                $cashAmount = $data['cash_received'] ?? $data['total_amount'];
                $payedUsing = 'Cash';
                $cardAmount = 0;
            } elseif (in_array($data['payment_method'] ?? '', ['Card', 'Stripe'])) {
                $cashAmount = 0;
                $cardAmount = $data['cash_received'] ?? $data['total_amount'];
                $payedUsing = 'Card';
            }

            Payment::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'amount_received' => $data['total_amount'],
                'payment_type' => $payedUsing,
                'payment_date' => now(),
                'cash_amount' => $cashAmount,
                'card_amount' => $cardAmount,
                'payment_status' => $data['payment_status'] ?? null,
                'code' => $data['order_code'] ?? ($data['code'] ?? null),
                'stripe_payment_intent_id' => $data['stripe_payment_intent_id'] ?? ($data['payment_intent'] ?? null),
                'last_digits' => $data['last_digits'] ?? null,
                'brand' => $data['brand'] ?? null,
                'currency_code' => $data['currency_code'] ?? null,
                'exp_month' => $data['exp_month'] ?? null,
                'exp_year' => $data['exp_year'] ?? null,
            ]);

            // 10. Store promo details
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

            $order->load(['items', 'kot.items', 'promo']);

            // 11. Handle Auto Logout for Cashier Role
            $currentUser = Auth::user();

            if ($currentUser && $currentUser->hasRole('Cashier')) {
                $superAdmin = \App\Models\User::where('is_first_super_admin', true)->first();

                if ($superAdmin) {
                    $settings = \App\Models\ProfileStep7::where('user_id', $superAdmin->id)->first();

                    if ($settings && $settings->logout_after_order) {
                        Auth::logout();
                        request()->session()->invalidate();
                        request()->session()->regenerateToken();

                        return [
                            'order' => $order,
                            'kot' => $kot,
                            'logout' => true,
                        ];
                    }
                }
            }

            return $order;
        });
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
            ])
            ->whereNull('parent_id');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get()->map(function ($cat) {
            $cat->image_url = UploadHelper::url($cat->upload_id);

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'image_url' => $cat->image_url,
                'box_bg_color' => $cat->box_bg_color ?? '#1b1670',
                'menu_items_count' => $cat->menu_items_count,
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
}

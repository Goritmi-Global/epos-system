<?php

namespace App\Services\POS;

use App\Helpers\UploadHelper;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\PosOrder;
use App\Models\PosOrderType;
use App\Models\RestaurantProfile;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosOrderService
{
    public function __construct(private StockEntryService $stockEntryService) {}

    public function list(array $filters = [])
    {
        return PosOrder::query()
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data): PosOrder|array
    {
        return DB::transaction(function () use ($data) {
            $activeShift = Shift::where('status', 'open')->latest()->first();

            if (! $activeShift) {
                throw new \Exception('No active shift found. Please start a shift before creating an order.');
            }

            // Create the main order
            $order = PosOrder::create([
                'user_id' => Auth::id(),
                'shift_id' => $activeShift?->id,
                'customer_name' => $data['customer_name'] ?? null,
                'sub_total' => $data['sub_total'],
                'total_amount' => $data['total_amount'],
                'tax' => $data['tax'] ?? null,
                'service_charges' => $data['service_charges'] ?? null,
                'delivery_charges' => $data['delivery_charges'] ?? null,
                'sales_discount' => $data['sale_discount'] ?? 0,
                'status' => $data['status'] ?? 'paid',
                'note' => $data['note'] ?? null,
                'kitchen_note' => $data['kitchen_note'] ?? null,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'order_time' => $data['order_time'] ?? now()->toTimeString(),
            ]);

            // Create order type
            $orderType = PosOrderType::create([
                'pos_order_id' => $order->id,
                'order_type' => $data['order_type'],
                'table_number' => $data['table_number'] ?? null,
            ]);

            // Create Order details
            foreach ($data['items'] as $item) {
                // Save order item
                $orderItem = $order->items()->create([
                    'menu_item_id' => $item['product_id'],
                    'title' => $item['title'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'sale_discount_per_item' => $item['sale_discount_per_item'] ?? 0,
                    'note' => $item['note'] ?? null,
                    'variant_name' => $item['variant_name'] ?? null,
                    'kitchen_note' => $item['kitchen_note'] ?? null,
                    'item_kitchen_note' => $item['item_kitchen_note'] ?? null,
                ]);

                // Store addons in order_item_addons table
                if (!empty($item['addons']) && is_array($item['addons'])) {
                    foreach ($item['addons'] as $addon) {
                        $orderItem->addons()->create([
                            'addon_id' => $addon['id'],
                            'addon_name' => $addon['name'],
                            'price' => $addon['price'],
                            'quantity' => $addon['quantity'] ?? 1,
                        ]);
                    }
                }

                // Get ingredients based on variant or base menu item
                $ingredients = $this->getIngredientsForItem($item);

                // Process stockout for the correct ingredients
                if (! empty($ingredients)) {
                    foreach ($ingredients as $ingredient) {
                        $inventoryItem = InventoryItem::find($ingredient->inventory_item_id);

                        if ($inventoryItem) {
                            $requiredQty = $ingredient->quantity * $item['quantity'];

                            $this->stockEntryService->create([
                                'product_id' => $inventoryItem->id,
                                'name' => $inventoryItem->name,
                                'category_id' => $inventoryItem->category_id,
                                'supplier_id' => $inventoryItem->supplier_id,
                                'quantity' => $requiredQty,
                                'value' => 0,
                                'operation_type' => 'pos_stockout',
                                'stock_type' => 'stockout',
                                'description' => "Auto stockout from POS Order #{$order->id}" .
                                    (isset($item['variant_name']) && $item['variant_name'] ? " - Variant: {$item['variant_name']}" : ''),
                                'user_id' => Auth::id(),
                            ]);
                        }
                    }
                }
            }

            $order->load('items');

            // Create KOT
            $kot = KitchenOrder::create([
                'pos_order_type_id' => $orderType->id,
                'order_time' => now()->toTimeString(),
                'order_date' => now()->toDateString(),
                'note' => $data['note'] ?? null,
                'kitchen_note' => $data['kitchen_note'] ?? null,
            ]);

            foreach ($order->items as $orderItem) {
                $itemData = collect($data['items'])->firstWhere('product_id', $orderItem->menu_item_id);
                $ingredients = $this->getIngredientsForItem($itemData);

                $ingredientsArray = [];
                if (! empty($ingredients)) {
                    foreach ($ingredients as $ingredient) {
                        $ingredientsArray[] = $ingredient->product_name;
                    }
                }

                $kot->items()->create([
                    'item_name' => $orderItem->title,
                    'quantity' => $orderItem->quantity,
                    'variant_name' => $orderItem->variant_name ?? null,
                    'ingredients' => $ingredientsArray,
                    'item_kitchen_note' => $item['item_kitchen_note'] ?? null,
                    'status' => KitchenOrderItem::STATUS_WAITING,
                ]);
            }
            $kot->load('items');

            // Payment handling
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

            // Store multiple promo details
            if (!empty($data['applied_promos']) && is_array($data['applied_promos'])) {
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
            // Fallback: Handle old single promo format
            elseif (!empty($data['promo_id']) && !empty($data['promo_discount'])) {
                \App\Models\OrderPromo::create([
                    'order_id' => $order->id,
                    'promo_id' => $data['promo_id'],
                    'promo_name' => $data['promo_name'] ?? null,
                    'promo_type' => $data['promo_type'] ?? 'flat',
                    'discount_amount' => $data['promo_discount'],
                ]);
            }

            $order->load(['items', 'kot.items', 'promo']);

            // ✅ Handle Auto Logout for Cashier Role (Only "logout after order")
            $currentUser = Auth::user();

            if ($currentUser && $currentUser->hasRole('Cashier')) {
                // Find the Super Admin
                $superAdmin = \App\Models\User::where('is_first_super_admin', true)->first();

                if ($superAdmin) {
                    // Get Super Admin's settings
                    $settings = \App\Models\ProfileStep7::where('user_id', $superAdmin->id)->first();

                    // ✅ Only logout immediately after order if enabled
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
     * ✅ NEW METHOD: Get ingredients for a menu item (variant-aware)
     *
     * @param  array  $item  The item data from the request
     * @return \Illuminate\Support\Collection
     */
    private function getIngredientsForItem(array $item)
    {
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
            'service_type' => $payload['service_type'] ?? 'dine_in', // dine_in | takeaway | delivery
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
        // TODO: rollback stock/movements if you manage inventory reservations here
    }

    public function getMenuCategories(bool $onlyActive = true)
    {
        $query = MenuCategory::with('children')
            ->withCount('menuItems') // now works because relation exists
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
        \Log::info('--- Fetching all menus ---');

        $menus = MenuItem::with([
            'category',
            'ingredients.inventoryItem',
            'variants.ingredients.inventoryItem',
            'nutrition',
            'allergies',
            'tags',
            'upload',
            'addonGroupRelations.addonGroup.addons',
        ])->get();

        \Log::info('Total MenuItems loaded: ' . $menus->count());

        return $menus->map(function ($item) {
            \Log::info("🔸 Processing MenuItem: {$item->name} (ID: {$item->id})");

            // Check variant and ingredient relations before mapping
            \Log::info(' - Variants count: ' . $item->variants->count());
            \Log::info(' - Ingredients count: ' . $item->ingredients->count());

            $item->image_url = $item->upload_id ? UploadHelper::url($item->upload_id) : null;

            // --- Map simple ingredients ---
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

            // --- Map and log variants ---
            $item->variants = $item->variants->map(function ($variant) use ($item) {
                \Log::info("  🧩 Variant found: {$variant->name} (ID: {$variant->id}) for MenuItem: {$item->id}");
                \Log::info('    - Ingredients count: ' . $variant->ingredients->count());

                if ($variant->ingredients->isEmpty()) {
                    \Log::warning("    ⚠️ No ingredients found for Variant ID: {$variant->id}");
                } else {
                    foreach ($variant->ingredients as $ing) {
                        \Log::info("    ✅ Ingredient: {$ing->product_name} (Inventory ID: {$ing->inventory_item_id}) Quantity: {$ing->quantity}");
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

            // --- Log after variants mapped ---
            \Log::info("✅ Finished MenuItem: {$item->name} | Variants processed: " . count($item->variants));

            // --- Addons mapping ---
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

            \Log::info("--- Finished processing MenuItem ID: {$item->id} ---");

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
            'posOrderType.order.items', // PosOrderItems with prices
        ])->whereDate('order_date', $today)->get();
    }
}

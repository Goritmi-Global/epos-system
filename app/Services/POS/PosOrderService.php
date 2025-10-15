<?php

namespace App\Services\POS;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\PosOrder;
use App\Models\RestaurantProfile;
use Illuminate\Support\Str;
use App\Helpers\UploadHelper;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Payment;
use App\Models\PosOrderType;
use App\Models\StockEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

    public function create(array $data): PosOrder
    {
        return DB::transaction(function () use ($data) {
            //  Create the main order
            $order = PosOrder::create([
                'user_id' => Auth::id(),
                'customer_name' => $data['customer_name'] ?? null,
                'sub_total' => $data['sub_total'],
                'total_amount' => $data['total_amount'],
                'tax' => $data['tax'] ?? null,
                'service_charges' => $data['service_charges'] ?? null,
                'delivery_charges' => $data['delivery_charges'] ?? null,
                'status' => $data['status'] ?? 'paid',
                'note' => $data['note'] ?? null,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'order_time' => $data['order_time'] ?? now()->toTimeString(),
            ]);

            //  Create order type
            $orderType = PosOrderType::create([
                'pos_order_id' => $order->id,
                'order_type'   => $data['order_type'],
                'table_number' => $data['table_number'] ?? null,
            ]);

            // Create Order details , where for each order a detailed recods stored of items
            foreach ($data['items'] as $item) {
                // Save order item
                $order->items()->create([
                    'menu_item_id' => $item['product_id'],
                    'title'        => $item['title'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'note'         => $item['note'] ?? null,
                ]);


                // Fetch the MenuItem with its ingredients
                $menuItem = MenuItem::with('ingredients')->find($item['product_id']);

                if ($menuItem && $menuItem->ingredients->count()) {
                    foreach ($menuItem->ingredients as $ingredient) {
                        // Each MenuIngredient row points to an InventoryItem
                        $inventoryItem = InventoryItem::find($ingredient->inventory_item_id);

                        if ($inventoryItem) {
                            $requiredQty = $ingredient->quantity * $item['quantity'];

                            $this->stockEntryService->create([
                                'product_id'     => $inventoryItem->id,
                                'name'           => $inventoryItem->name,
                                'category_id'    => $inventoryItem->category_id,
                                'supplier_id'    => $inventoryItem->supplier_id,
                                'quantity'       => $requiredQty,
                                'value'          => 0,
                                'operation_type' => 'pos_stockout',
                                'stock_type'     => 'stockout',
                                'description'    => "Auto stockout from POS Order #{$order->id}",
                                'user_id'        => Auth::id(),
                            ]);
                        }
                    }
                }
            }

            $order->load('items');
            $kot = null;

            // 2. Handle KOT (after items exist)


            $kot = KitchenOrder::create([
                'pos_order_type_id' => $orderType->id,
                'order_time'        => now()->toTimeString(),
                'order_date'        => now()->toDateString(),
                'note'              => $data['note'] ?? null,
            ]);

            foreach ($order->items as $orderItem) {
                $menuItem = MenuItem::with('ingredients')->find($orderItem->menu_item_id);

                $ingredientsArray = [];
                if ($menuItem && $menuItem->ingredients->count()) {
                    foreach ($menuItem->ingredients as $ingredient) {
                        $ingredientsArray[] = $ingredient->product_name;
                    }
                }

                $kot->items()->create([
                    'item_name'    => $orderItem->title,
                    'quantity'     => $orderItem->quantity,
                    'variant_name' => $orderItem->variant_name ?? null,
                    'ingredients'  => $ingredientsArray,
                    'status'       => KitchenOrderItem::STATUS_WAITING,
                ]);
            }
            $kot->load('items');


            $cashAmount = null;
            $cardAmount = null;

            // Payment field adjustment logic
            if (($data['payment_type'] ?? '') === 'Split') {
                $cashAmount = $data['cash_received'] ?? 0;
                $cardAmount = $data['card_payment'] ?? 0;
                $payedUsing = $data['payment_type'];
            } elseif (($data['payment_method'] ?? 'Cash') === 'Cash') {
                $cashAmount = $data['amount_received'] ?? $data['total_amount'];
                $payedUsing = $data['payment_method'];
                $cardAmount = 0;
            } elseif (($data['payment_method'] ?? '') === 'Card' || ($data['payment_method'] ?? '') === 'Stripe') {
                $cashAmount = 0;
                $cardAmount = $data['amount_received'] ?? $data['total_amount'];
                $payedUsing = 'Card';
            }

            // Payment model data saving
            Payment::create([
                'order_id'                 => $order->id,
                'user_id'                  => Auth::id(),
                'amount_received'          =>  $data['total_amount'],
                'payment_type'             =>  $payedUsing,
                'payment_date'             => now(),

                // Split fields (clean values)
                'cash_amount'              => $cashAmount,
                'card_amount'              => $cardAmount,


                'payment_status'           => $data['payment_status'] ?? null,
                'code'                     => $data['order_code'] ?? ($data['code'] ?? null),
                'stripe_payment_intent_id' => $data['stripe_payment_intent_id'] ?? ($data['payment_intent'] ?? null),
                'last_digits'              => $data['last_digits'] ?? null,
                'brand'                    => $data['brand'] ?? null,
                'currency_code'            => $data['currency_code'] ?? null,
                'exp_month'                => $data['exp_month'] ?? null,
                'exp_year'                 => $data['exp_year'] ?? null,
            ]);

            // Store promo details if promo was applied
            if (!empty($data['promo_id']) && !empty($data['promo_discount'])) {
                \App\Models\OrderPromo::create([
                    'order_id'        => $order->id,
                    'promo_id'        => $data['promo_id'],
                    'promo_name'      => $data['promo_name'] ?? null,
                    'promo_type'      => $data['promo_type'] ?? 'flat',
                    'discount_amount' => $data['promo_discount'],
                ]);
            }


            $order->load(['items', 'kot.items']);
            // dd("Service class Done ",$data);
            return $order;
        });
    }

    public function startOrder(array $payload = []): PosOrder
    {
        return PosOrder::create([
            'order_no'     => $payload['order_no'] ?? Str::upper(Str::random(8)),
            'customer_name' => $payload['customer_name'] ?? null,
            'service_type' => $payload['service_type'] ?? 'dine_in', // dine_in | takeaway | delivery
            'table_no'     => $payload['table_no'] ?? null,
            'status'       => 'draft',
            'total'        => 0,
            'paid'         => 0,
            'change'       => 0,
        ]);
    }

    public function updateTotals(PosOrder $order, float $total, float $paid = 0): PosOrder
    {
        $order->fill([
            'total'  => $total,
            'paid'   => $paid,
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
            return [
                'id'    => $cat->id,
                'name'  => $cat->name,
                'icon'  => $cat->icon,
                'box_bg_color' => $cat->box_bg_color ?? '#1b1670',
                'menu_items_count' => $cat->menu_items_count,
                'children' => $cat->children,
            ];
        });
    }


    public function getAllMenus()
    {
        return MenuItem::with([
            'category',
            'ingredients.inventoryItem', // load inventory items + their stock
            'nutrition',
            'allergies',
            'tags',
            'upload',
        ])
            ->get()
            ->map(function ($item) {
                $item->image_url = $item->upload_id ? UploadHelper::url($item->upload_id) : null;

                // map ingredients to include stock
                $item->ingredients->transform(function ($ingredient) {
                    return [
                        'id' => $ingredient->id,
                        'product_name' => $ingredient->product_name,
                        'quantity' => $ingredient->quantity,
                        'cost' => $ingredient->cost,
                        'inventory_stock' => $ingredient->inventoryItem?->stock ?? 0, // 👈 actual inventory stock
                        'inventory_item_id' => $ingredient->inventory_item_id,
                        'category_id'     => $ingredient->inventoryItem?->category_id,
                        'supplier_id'     => $ingredient->inventoryItem?->supplier_id,
                        'user_id'     => $ingredient->inventoryItem?->user_id,
                    ];
                });

                return $item;
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
            'posOrderType.order.items' // PosOrderItems with prices
        ])->whereDate('order_date', $today)->get();
    }
}

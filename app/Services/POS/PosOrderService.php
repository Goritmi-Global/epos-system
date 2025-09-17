<?php

namespace App\Services\POS;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\PosOrder;
use App\Models\RestaurantProfile;
use Illuminate\Support\Str;
use App\Helpers\UploadHelper;
use App\Models\PosOrderType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PosOrderService
{
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

            // 1️⃣ Create the main order
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

            // 2️⃣ Create order type
            PosOrderType::create([
                'pos_order_id' => $order->id,
                'order_type' => $data['order_type'],
                'table_number' => $data['table_number'] ?? null,
            ]);

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
            ->whereNull('parent_id');
        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
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
}

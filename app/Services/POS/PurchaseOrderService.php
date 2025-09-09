<?php

namespace App\Services\POS;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseItem;
use App\Models\StockEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderService
{
    public function list(array $filters = [])
    {
        $query = PurchaseOrder::with('supplier')
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->whereHas('supplier', function ($sq) use ($q) {
                    $sq->where('name', 'like', "%$q%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest();

        // paginate first, then map
        return $query->paginate(20)->through(function ($order) {
            return [
                'id'          => $order->id,
                'supplier'    => $order->supplier->name ?? 'N/A',
                'purchasedAt' => $order->purchase_date,
                'status'      => $order->status,
                'total'       => $order->total_amount,
            ];
        });
    }

    public function store(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'supplier_id'   => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'] ?? now(),
                'status'        => $data['status'] ?? 'pending', // <-- pending for orders
                'total_amount'  => 0,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $sub_total = ($item['quantity'] ?? $item['qty']) * $item['unit_price'];
                $total += $sub_total;

                PurchaseItem::create([
                    'purchase_id' => $order->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'] ?? $item['qty'],
                    'unit_price'  => $item['unit_price'],
                    'sub_total'   => $sub_total,
                ]);

                // For initial orders, no stock entry is created if status is pending
                if (($data['status'] ?? 'pending') === 'completed') {
                    $inventory = InventoryItem::find($item['product_id']);
                    $categoryId = InventoryCategory::where('name', $inventory->category)->first()->id;
                    StockEntry::create([
                        'product_id'     => $item['product_id'],
                        'category_id'    => $categoryId,
                        'supplier_id'    => $data['supplier_id'],
                        'user_id'        => Auth::id(),
                        'quantity'       => $item['quantity'] ?? $item['qty'],
                        'price'          => $item['unit_price'],
                        'value'          => $sub_total,
                        'stock_type'     => 'stockin',
                        'operation_type' => 'purchase',
                        'expiry_date'    => $item['expiry'] ?? null,
                        'purchase_date'  => $data['purchase_date'] ?? now(),
                    ]);
                }
            }

            $order->update(['total_amount' => $total]);

            return $order->load('items');
        });
    }

    public function update(PurchaseOrder $order, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $oldStatus = $order->status;

            if (isset($data['supplier_id'])) {
                $order->supplier_id = $data['supplier_id'];
            }
            if (isset($data['order_date'])) {
                $order->order_date = $data['order_date'];
            }
            if (isset($data['status'])) {
                $order->status = $data['status'];
            }
            $order->save();

            if (isset($data['items'])) {
                $order->items()->delete();
                $total = 0;
                foreach ($data['items'] as $item) {
                    $cost = ($item['qty'] ?? $item['quantity']) * $item['unit_price'];
                    $total += $cost;
                    PurchaseItem::create([
                        'purchase_id' => $order->id,
                        'product_id'  => $item['product_id'],
                        'quantity'    => $item['qty'] ?? $item['quantity'],
                        'unit_price'  => $item['unit_price'],
                        'expiry'      => $item['expiry'] ?? null,
                        'sub_total'  => $cost,
                    ]);
                }
                $order->update(['total_amount' => $total]);
            }

            // Only create stock entries if status changed from pending -> completed
            if ($oldStatus !== 'completed' && $order->status === 'completed') {
                foreach ($order->items as $item) {
                    $inventory = InventoryItem::find($item->product_id);
                    $categoryId = InventoryCategory::where('name', $inventory->category)->first()->id;

                    // Ensure total_cost is set
                    $totalValue = $item->quantity * $item->unit_price;

                    StockEntry::create([
                        'product_id'     => $item->product_id,
                        'category_id'    => $categoryId,
                        'supplier_id'    => $order->supplier_id,
                        'user_id'        => Auth::id(),
                        'quantity'       => $item->quantity,
                        'price'          => $item->unit_price,
                        'value'          => $totalValue,  
                        'stock_type'     => 'stockin',
                        'operation_type' => 'purchase',
                        'expiry_date'    => $item->expiry,
                        'purchase_date'  => $order->purchase_date ?? now(),
                    ]);
                }
            }

            return $order;
        });
    }
}

<?php

namespace App\Services\POS;

use App\Models\PosOrder;
use App\Models\KitchenOrder;


class KotOrderService
{
    public function list(array $filters = [])
    {
        return PosOrder::query()
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function getTodaysOrders()
    {
        $today = now()->toDateString();

        return KitchenOrder::with([
            'items',
            'posOrderType.order.payment',
            'posOrderType.order.items' 
        ])->whereDate('order_date', $today)->get();
    }

    public function getAllOrders(array $filters = [])
    {
        return KitchenOrder::with([
            'items',
            'posOrderType.order.payment',
            'posOrderType.order.items'
        ])
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->get(); 
    }
}

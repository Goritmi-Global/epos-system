<?php

namespace App\Services\POS;

use App\Models\Order;

class OrdersService
{
    public function list(array $filters = [])
    {
        return Order::query()
            ->when($filters['q'] ?? null, fn($q, $v) =>
                $q->where('order_no', 'like', "%$v%")
            )
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function find(int|string $id): Order
    {
        /** @var Order $order */
        $order = Order::query()->findOrFail($id);
        return $order;
    }
}

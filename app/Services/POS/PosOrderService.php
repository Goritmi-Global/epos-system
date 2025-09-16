<?php

namespace App\Services\POS;

use App\Models\PosOrder;
use App\Models\RestaurantProfile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PosOrderService
{
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

    public function list(array $filters = [])
    {
        return PosOrder::query()
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }


    public function getProfileTable()
    {
        return RestaurantProfile::select('order_types', 'table_details')->first();
      
    }
}

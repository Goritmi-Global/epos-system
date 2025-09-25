<?php

namespace App\Services\POS;

use App\Models\PosOrder;
use App\Models\Payment;
use App\Models\PosOrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getDateRange(string $range): array
    {
        $now = now();
        return match ($range) {
            'today'     => [Carbon::today(), Carbon::tomorrow()],
            'last7'     => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'thisMonth' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'last30'    => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            default     => [Carbon::parse('2000-01-01')->startOfDay(), $now->copy()->endOfDay()], // all
        };
    }

    public function fetch(array $filters): array
    {
        [$from, $to] = $this->getDateRange($filters['range'] ?? 'last30');
        $orderType   = $filters['orderType'] ?? 'All';   // 'All'|'dine'|'delivery'
        $payType     = $filters['payType'] ?? 'All';     // 'All'|'cash'|'card'|'qr'|'bank'

        // Base orders query with joins to order type + payment
        $ordersQ = DB::table('pos_orders as o')
            ->leftJoin('pos_order_types as t', 't.pos_order_id', '=', 'o.id')
            ->leftJoin('payments as p', 'p.order_id', '=', 'o.id')
            ->whereBetween(DB::raw('o.created_at'), [$from, $to])
            ->whereIn('o.status', ['paid', 'completed']); // adjust to your statuses

        if ($orderType !== 'All') {
            $ordersQ->where('t.order_type', $orderType); // assume 'dine' | 'delivery'
        }
        if ($payType !== 'All') {
            // assume Payment.payment_type stores 'cash','card','qr','bank'
            $ordersQ->where('p.payment_type', $payType);
        }

        // --- KPI: revenue, ordersCount, AOV
        $kpiRow = (clone $ordersQ)
            ->selectRaw('COALESCE(SUM(o.total_amount),0) as revenue, COUNT(DISTINCT o.id) as orders_count')
            ->first();

        $revenue = (float) ($kpiRow->revenue ?? 0);
        $ordersCount = (int)   ($kpiRow->orders_count ?? 0);
        $aov = $ordersCount ? $revenue / $ordersCount : 0;

        // --- Items Sold (sum of quantities in order items)
        $itemsSold = (int) DB::table('pos_order_items as oi')
            ->joinSub((clone $ordersQ)->select('o.id'), 'ord', 'ord.id', '=', 'oi.pos_order_id')
            ->sum('oi.quantity');

        // --- Sales series per day (fill gaps on frontend)
        $salesRows = (clone $ordersQ)
            ->selectRaw("DATE(o.created_at) as day, COALESCE(SUM(o.total_amount),0) as total")
            ->groupByRaw('DATE(o.created_at)')
            ->orderBy('day')
            ->get()
            ->map(fn($r) => ['day' => (string)$r->day, 'total' => (float)$r->total])
            ->all();

        // --- Orders by type
        $ordersTypeRows = (clone $ordersQ)
            ->selectRaw("t.order_type, COUNT(DISTINCT o.id) as cnt")
            ->groupBy('t.order_type')
            ->pluck('cnt', 'order_type');

        $dine     = (int) ($ordersTypeRows['dine'] ?? 0);
        $delivery = (int) ($ordersTypeRows['delivery'] ?? 0);
        $typeTotal = max(1, $dine + $delivery);
        $ordersByType = [
            'dine'       => $dine,
            'delivery'   => $delivery,
            'dinePct'    => (int) round($dine * 100 / $typeTotal),
            'deliveryPct'=> (int) round($delivery * 100 / $typeTotal),
        ];

        // --- Payments mix
        $mixRows = (clone $ordersQ)
            ->selectRaw("LOWER(COALESCE(p.payment_type,'unknown')) as method, COUNT(DISTINCT o.id) as cnt")
            ->groupBy('method')
            ->pluck('cnt', 'method')
            ->toArray();

        $methods = ['cash','card','qr','bank'];
        $mixTotal = array_sum($mixRows) ?: 1;
        $paymentsMix = [];
        foreach ($methods as $m) {
            $c = (int) ($mixRows[$m] ?? 0);
            $paymentsMix[] = ['method' => $m, 'count' => $c, 'pct' => (int) round(($c/$mixTotal)*100)];
        }

        // --- Top Items (by revenue)
        $topItems = DB::table('pos_order_items as oi')
            ->joinSub((clone $ordersQ)->select('o.id'), 'ord', 'ord.id', '=', 'oi.pos_order_id')
            ->selectRaw('oi.title as name, SUM(oi.quantity) as qty, SUM(oi.quantity * oi.price) as revenue')
            ->groupBy('oi.title')
            ->orderByDesc('revenue')
            ->limit(100)
            ->get()
            ->map(fn($r) => [
                'name'    => $r->name,
                'qty'     => (int)$r->qty,
                'revenue' => (float)$r->revenue,
            ])
            ->all();

        return [
            'range'        => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'revenue'      => $revenue,
            'ordersCount'  => $ordersCount,
            'aov'          => $aov,
            'itemsSold'    => $itemsSold,
            'salesSeries'  => $salesRows,   // [{day:'2025-08-17', total: 123.45}, ...]
            'ordersByType' => $ordersByType,
            'paymentsMix'  => $paymentsMix,
            'topItems'     => $topItems,
        ];
    }
}

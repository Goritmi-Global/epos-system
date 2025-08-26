<?php

namespace App\Services\POS;

use App\Models\Order;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Return arrays for charts/widgets based on $range (e.g., 7d, 30d, 12m)
     */
    public function dashboard(string $range = '7d'): array
    {
        [$from, $to, $groupByFormat] = $this->resolveRange($range);

        // Example: daily sales totals
        $series = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '{$groupByFormat}') as bucket, SUM(total) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'salesSeries' => $series,
            'kpis' => [
                'totalSales'   => Order::whereBetween('created_at', [$from, $to])->sum('total'),
                'ordersCount'  => Order::whereBetween('created_at', [$from, $to])->count(),
                'avgOrder'     => round((float) Order::whereBetween('created_at', [$from, $to])->avg('total'), 2),
            ],
            'range' => compact('from', 'to'),
        ];
    }

    private function resolveRange(string $range): array
    {
        $to = now();
        return match ($range) {
            '30d' => [now()->subDays(30), $to, '%Y-%m-%d'],
            '12m' => [now()->subMonths(12), $to, '%Y-%m'],
            default => [now()->subDays(7), $to, '%Y-%m-%d'],
        };
    }
}

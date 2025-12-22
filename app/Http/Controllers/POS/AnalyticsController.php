<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\OrderPromo;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function page(Request $req)
    {
        return Inertia::render('Backend/Analytics/Index');
    }

    public function index(Request $req)
    {
        $validated = $req->validate([
            'type' => 'required|in:sales,purchase,comparison,stock,userSales,category',
            'timeRange' => 'required|in:daily,monthly,yearly,custom',
            'selectedMonth' => 'nullable|string',
            'selectedYear' => 'nullable|integer',
            'selectedDate' => 'nullable|date',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
            'orderType' => 'nullable|in:Eat In,Delivery,Collection,Takeaway',
            'paymentType' => 'nullable|in:cash,card,qr,bank',
        ]);

        // Get date range
        [$from, $to] = $this->getDateRange(
            $validated['timeRange'],
            $validated['selectedMonth'] ?? null,
            $validated['selectedYear'] ?? null,
            $validated['selectedDate'] ?? null,
            $validated['dateFrom'] ?? null,
            $validated['dateTo'] ?? null
        );

        $analyticsType = $validated['type'];

        // Route to appropriate analytics method
        return match ($analyticsType) {
            'sales' => $this->getSalesAnalytics($from, $to, $validated),
            'purchase' => $this->getPurchaseAnalytics($from, $to, $validated),
            'comparison' => $this->getComparisonAnalytics($from, $to, $validated),
            'stock' => $this->getStockAnalytics($from, $to, $validated),
            'userSales' => $this->getUserSalesAnalytics($from, $to, $validated),
            'category' => $this->getCategoryAnalytics($from, $to, $validated),
            default => response()->json(['error' => 'Invalid analytics type'], 400),
        };
    }

    /**
     * Get date range based on timeRange parameter
     */
    protected function getDateRange($timeRange, $selectedMonth = null, $selectedYear = null, $selectedDate = null, $dateFrom = null, $dateTo = null)
    {

        if ($timeRange === 'daily' && $selectedDate) {
            return [
                Carbon::parse($selectedDate)->startOfDay(),
                Carbon::parse($selectedDate)->endOfDay(),
            ];
        }
        if ($timeRange === 'custom' && $dateFrom && $dateTo) {
            return [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ];
        }

        if ($timeRange === 'monthly' && $selectedMonth && $selectedYear) {
            $start = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return [$start, $end];
        }

        if ($timeRange === 'yearly' && $selectedYear) {
            $start = Carbon::createFromDate($selectedYear, 1, 1)->startOfYear();
            $end = Carbon::createFromDate($selectedYear, 12, 31)->endOfYear();

            return [$start, $end];
        }

        // Fallback to current month
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return [$start, $end];
    }

    protected function getSalesAnalytics($from, $to, $validated)
    {
        $orderType = $validated['orderType'] ?? null;
        $paymentType = $validated['paymentType'] ?? null;
        $timeRange = $validated['timeRange'];

        // Base query
        $ordersQ = PosOrder::whereBetween('order_date', [$from, $to])
            ->where('status', 'paid');

        // Filter by order type using relationship
        if ($orderType) {
            $ordersQ->whereHas('orderType', function ($q) use ($orderType) {
                $q->where('order_type', $orderType);
            });
        }

        // KPIs
        $totalRevenue = (clone $ordersQ)->sum('total_amount');
        $ordersCount = (clone $ordersQ)->count();
        $aov = $ordersCount > 0 ? $totalRevenue / $ordersCount : 0;

        $itemsSold = PosOrderItem::whereHas('order', function ($q) use ($from, $to, $orderType) {
            $q->whereBetween('order_date', [$from, $to])
                ->where('status', 'paid');
            if ($orderType) {
                $q->whereHas('orderType', function ($subQ) use ($orderType) {
                    $subQ->where('order_type', $orderType);
                });
            }
        })->sum('quantity');

        // Promo KPIs
        $totalPromoDiscount = OrderPromo::whereHas('order', function ($q) use ($from, $to, $orderType) {
            $q->whereBetween('order_date', [$from, $to])
                ->where('status', 'paid');
            if ($orderType) {
                $q->whereHas('orderType', function ($subQ) use ($orderType) {
                    $subQ->where('order_type', $orderType);
                });
            }
        })->sum('discount_amount');

        $ordersWithPromo = OrderPromo::whereHas('order', function ($q) use ($from, $to, $orderType) {
            $q->whereBetween('order_date', [$from, $to])
                ->where('status', 'paid');
            if ($orderType) {
                $q->whereHas('orderType', function ($subQ) use ($orderType) {
                    $subQ->where('order_type', $orderType);
                });
            }
        })->distinct('order_id')->count('order_id');

        // Chart data based on time range
        if ($timeRange === 'daily') {
            // Hourly data for daily view
            $chartData = (clone $ordersQ)
                ->selectRaw('HOUR(order_date) as hour, SUM(total_amount) as total')
                ->groupByRaw('HOUR(order_date)')
                ->orderBy('hour')
                ->get()
                ->map(fn ($row) => [
                    'date' => sprintf('%02d:00', $row->hour),
                    'total' => (float) $row->total,
                ])
                ->toArray();
        } elseif ($timeRange === 'monthly') {
            // Weekly data for monthly view
            $chartData = (clone $ordersQ)
                ->selectRaw('WEEK(order_date, 1) - WEEK(DATE_SUB(order_date, INTERVAL DAYOFMONTH(order_date) - 1 DAY), 1) + 1 as week_num, SUM(total_amount) as total')
                ->groupByRaw('week_num')
                ->orderBy('week_num')
                ->get()
                ->map(fn ($row) => [
                    'date' => 'Week '.$row->week_num,
                    'total' => (float) $row->total,
                ])
                ->toArray();
        } elseif ($timeRange === 'yearly') {
            // Monthly data for yearly view
            $chartData = (clone $ordersQ)
                ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as date, SUM(total_amount) as total')
                ->groupByRaw('DATE_FORMAT(order_date, "%Y-%m")')
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => [
                    'date' => $row->date,
                    'total' => (float) $row->total,
                ])
                ->toArray();
        } else {
            // Daily data for custom range
            $chartData = (clone $ordersQ)
                ->selectRaw('DATE(order_date) as date, SUM(total_amount) as total')
                ->groupByRaw('DATE(order_date)')
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => [
                    'date' => $row->date,
                    'total' => (float) $row->total,
                ])
                ->toArray();
        }

        // Distribution data - by order type using JOIN
        $orderTypeDistribution = DB::table('pos_orders')
            ->join('pos_order_types', 'pos_orders.id', '=', 'pos_order_types.pos_order_id')
            ->whereBetween('pos_orders.order_date', [$from, $to])
            ->where('pos_orders.status', 'paid')
            ->selectRaw('pos_order_types.order_type, COUNT(DISTINCT pos_orders.id) as count')
            ->groupBy('pos_order_types.order_type')
            ->get();

        $totalOrders = max(1, $orderTypeDistribution->sum('count'));

        $distributionData = $orderTypeDistribution->map(function ($row) use ($totalOrders) {
            // Match the enum values from migration
            $label = match ($row->order_type) {
                'Eat In' => 'Eat In',
                'Takeaway' => 'Takeaway',
                'Collection' => 'Collection',
                'Delivery' => 'Delivery',
                default => $row->order_type
            };

            $color = match ($row->order_type) {
                'Eat In' => '#10b981',
                'Takeaway' => '#f59e0b',
                'Collection' => '#8b5cf6',
                'Delivery' => '#3b82f6',
                default => '#6b7280'
            };

            return [
                'label' => $label,
                'value' => (int) $row->count,
                'percentage' => round($row->count * 100 / $totalOrders),
                'color' => $color,
            ];
        })->toArray();

        // If no orders with types, show default
        if (empty($distributionData)) {
            $distributionData = [
                ['label' => 'No Orders', 'value' => 0, 'percentage' => 0, 'color' => '#6b7280'],
            ];
        }

        // Table query logic
        if ($timeRange === 'monthly') {
            $orderAggregates = (clone $ordersQ)
                ->selectRaw('
                DATE(pos_orders.order_date) as order_date,
                COUNT(DISTINCT pos_orders.id) as order_count,
                SUM(pos_orders.sub_total) as sub_total,
                SUM(pos_orders.total_amount) as total_amount,
                SUM(pos_orders.tax) as tax,
                SUM(pos_orders.service_charges) as service_charges,
                SUM(pos_orders.delivery_charges) as delivery_charges,
                SUM(pos_orders.sales_discount) as sales_discount,
                SUM(pos_orders.approved_discounts) as approved_discounts
            ')
                ->groupByRaw('DATE(pos_orders.order_date)')
                ->get();

            $itemCountsByDate = PosOrderItem::whereHas('order', function ($q) use ($from, $to, $orderType) {
                $q->whereBetween('order_date', [$from, $to])
                    ->where('status', 'paid');
                if ($orderType) {
                    $q->whereHas('orderType', function ($subQ) use ($orderType) {
                        $subQ->where('order_type', $orderType);
                    });
                }
            })
                ->selectRaw('DATE(pos_orders.order_date) as order_date, COUNT(DISTINCT pos_order_items.id) as item_count')
                ->join('pos_orders', 'pos_order_items.pos_order_id', '=', 'pos_orders.id')
                ->groupByRaw('DATE(pos_orders.order_date)')
                ->pluck('item_count', 'order_date')
                ->toArray();

            $qtyByDate = PosOrderItem::whereHas('order', function ($q) use ($from, $to, $orderType) {
                $q->whereBetween('order_date', [$from, $to])
                    ->where('status', 'paid');
                if ($orderType) {
                    $q->whereHas('orderType', function ($subQ) use ($orderType) {
                        $subQ->where('order_type', $orderType);
                    });
                }
            })
                ->selectRaw('DATE(pos_orders.order_date) as order_date, COALESCE(SUM(pos_order_items.quantity), 0) as total_qty')
                ->join('pos_orders', 'pos_order_items.pos_order_id', '=', 'pos_orders.id')
                ->groupByRaw('DATE(pos_orders.order_date)')
                ->pluck('total_qty', 'order_date')
                ->toArray();

            $promoByDate = OrderPromo::whereHas('order', function ($q) use ($from, $to, $orderType) {
                $q->whereBetween('order_date', [$from, $to])
                    ->where('status', 'paid');
                if ($orderType) {
                    $q->whereHas('orderType', function ($subQ) use ($orderType) {
                        $subQ->where('order_type', $orderType);
                    });
                }
            })
                ->selectRaw('DATE(pos_orders.order_date) as order_date, COALESCE(SUM(order_promos.discount_amount), 0) as promo_discount')
                ->join('pos_orders', 'order_promos.order_id', '=', 'pos_orders.id')
                ->groupByRaw('DATE(pos_orders.order_date)')
                ->pluck('promo_discount', 'order_date')
                ->toArray();

            $tableData = $orderAggregates->map(fn ($row) => [
                'order_date' => $row->order_date,
                'order_count' => (int) $row->order_count,
                'sub_total' => (float) $row->sub_total,
                'total_amount' => (float) $row->total_amount,
                'tax' => (float) $row->tax,
                'service_charges' => (float) $row->service_charges,
                'delivery_charges' => (float) $row->delivery_charges,
                'sales_discount' => (float) $row->sales_discount,
                'approved_discounts' => (float) $row->approved_discounts,
                'item_count' => (int) ($itemCountsByDate[$row->order_date] ?? 0),
                'total_qty' => (int) ($qtyByDate[$row->order_date] ?? 0),
                'promo_discount' => (float) ($promoByDate[$row->order_date] ?? 0),
                'promo_names' => '-',
                'promo_types' => '-',
            ])
                ->sortByDesc('order_date')
                ->values()
                ->toArray();

        } elseif ($timeRange === 'yearly') {
            $orderAggregates = (clone $ordersQ)
                ->selectRaw('
                DATE_FORMAT(pos_orders.order_date, "%Y-%m") as order_month,
                COUNT(DISTINCT pos_orders.id) as order_count,
                SUM(pos_orders.sub_total) as sub_total,
                SUM(pos_orders.total_amount) as total_amount,
                SUM(pos_orders.tax) as tax,
                SUM(pos_orders.service_charges) as service_charges,
                SUM(pos_orders.delivery_charges) as delivery_charges,
                SUM(pos_orders.sales_discount) as sales_discount,
                SUM(pos_orders.approved_discounts) as approved_discounts
            ')
                ->groupByRaw('DATE_FORMAT(pos_orders.order_date, "%Y-%m")')
                ->get();

            $itemCountsByMonth = PosOrderItem::whereHas('order', function ($q) use ($from, $to, $orderType) {
                $q->whereBetween('order_date', [$from, $to])
                    ->where('status', 'paid');
                if ($orderType) {
                    $q->whereHas('orderType', function ($subQ) use ($orderType) {
                        $subQ->where('order_type', $orderType);
                    });
                }
            })
                ->selectRaw('DATE_FORMAT(pos_orders.order_date, "%Y-%m") as order_month, COUNT(DISTINCT pos_order_items.id) as item_count')
                ->join('pos_orders', 'pos_order_items.pos_order_id', '=', 'pos_orders.id')
                ->groupByRaw('DATE_FORMAT(pos_orders.order_date, "%Y-%m")')
                ->pluck('item_count', 'order_month')
                ->toArray();

            $qtyByMonth = PosOrderItem::whereHas('order', function ($q) use ($from, $to, $orderType) {
                $q->whereBetween('order_date', [$from, $to])
                    ->where('status', 'paid');
                if ($orderType) {
                    $q->whereHas('orderType', function ($subQ) use ($orderType) {
                        $subQ->where('order_type', $orderType);
                    });
                }
            })
                ->selectRaw('DATE_FORMAT(pos_orders.order_date, "%Y-%m") as order_month, COALESCE(SUM(pos_order_items.quantity), 0) as total_qty')
                ->join('pos_orders', 'pos_order_items.pos_order_id', '=', 'pos_orders.id')
                ->groupByRaw('DATE_FORMAT(pos_orders.order_date, "%Y-%m")')
                ->pluck('total_qty', 'order_month')
                ->toArray();

            $promoByMonth = OrderPromo::whereHas('order', function ($q) use ($from, $to, $orderType) {
                $q->whereBetween('order_date', [$from, $to])
                    ->where('status', 'paid');
                if ($orderType) {
                    $q->whereHas('orderType', function ($subQ) use ($orderType) {
                        $subQ->where('order_type', $orderType);
                    });
                }
            })
                ->selectRaw('DATE_FORMAT(pos_orders.order_date, "%Y-%m") as order_month, COALESCE(SUM(order_promos.discount_amount), 0) as promo_discount')
                ->join('pos_orders', 'order_promos.order_id', '=', 'pos_orders.id')
                ->groupByRaw('DATE_FORMAT(pos_orders.order_date, "%Y-%m")')
                ->pluck('promo_discount', 'order_month')
                ->toArray();

            $tableData = $orderAggregates->map(fn ($row) => [
                'order_month' => $row->order_month,
                'order_count' => (int) $row->order_count,
                'sub_total' => (float) $row->sub_total,
                'total_amount' => (float) $row->total_amount,
                'tax' => (float) $row->tax,
                'service_charges' => (float) $row->service_charges,
                'delivery_charges' => (float) $row->delivery_charges,
                'sales_discount' => (float) $row->sales_discount,
                'approved_discounts' => (float) $row->approved_discounts,
                'item_count' => (int) ($itemCountsByMonth[$row->order_month] ?? 0),
                'total_qty' => (int) ($qtyByMonth[$row->order_month] ?? 0),
                'promo_discount' => (float) ($promoByMonth[$row->order_month] ?? 0),
                'promo_names' => '-',
                'promo_types' => '-',
            ])
                ->sortByDesc('order_month')
                ->values()
                ->toArray();

        } else {
            $tableData = (clone $ordersQ)
                ->selectRaw('
                pos_orders.id,
                pos_orders.customer_name,
                pos_orders.sub_total,
                pos_orders.total_amount,
                pos_orders.tax,
                pos_orders.service_charges,
                pos_orders.delivery_charges,
                pos_orders.sales_discount,
                pos_orders.approved_discounts,
                pos_orders.order_date
            ')
                ->orderByDesc('pos_orders.order_date')
                ->limit(50)
                ->get()
                ->map(function ($order) {
                    $itemData = PosOrderItem::where('pos_order_id', $order->id)
                        ->selectRaw('COUNT(DISTINCT id) as item_count, SUM(quantity) as total_qty')
                        ->first();

                    $promoData = OrderPromo::where('order_id', $order->id)
                        ->selectRaw('COALESCE(SUM(discount_amount), 0) as promo_discount, GROUP_CONCAT(DISTINCT promo_name SEPARATOR ", ") as promo_names, GROUP_CONCAT(DISTINCT promo_type SEPARATOR ", ") as promo_types')
                        ->first();

                    return [
                        'id' => $order->id,
                        'customer_name' => $order->customer_name,
                        'sub_total' => (float) $order->sub_total,
                        'total_amount' => (float) $order->total_amount,
                        'tax' => (float) $order->tax,
                        'service_charges' => (float) $order->service_charges,
                        'delivery_charges' => (float) $order->delivery_charges,
                        'sales_discount' => (float) $order->sales_discount,
                        'approved_discounts' => (float) $order->approved_discounts,
                        'item_count' => (int) ($itemData?->item_count ?? 0),
                        'total_qty' => (int) ($itemData?->total_qty ?? 0),
                        'order_date' => $order->order_date,
                        'promo_discount' => (float) ($promoData?->promo_discount ?? 0),
                        'promo_names' => $promoData?->promo_names ?? '-',
                        'promo_types' => $promoData?->promo_types ?? '-',
                    ];
                })
                ->toArray();
        }

        $totalsSummary = [
            'id' => 'TOTAL',
            'customer_name' => 'TOTAL',
            'sub_total' => array_sum(array_column($tableData, 'sub_total')),
            'total_amount' => array_sum(array_column($tableData, 'total_amount')),
            'tax' => array_sum(array_column($tableData, 'tax')),
            'service_charges' => array_sum(array_column($tableData, 'service_charges')),
            'delivery_charges' => array_sum(array_column($tableData, 'delivery_charges')),
            'sales_discount' => array_sum(array_column($tableData, 'sales_discount')),
            'approved_discounts' => array_sum(array_column($tableData, 'approved_discounts')),
            'item_count' => array_sum(array_column($tableData, 'item_count')),
            'total_qty' => array_sum(array_column($tableData, 'total_qty')),
            'promo_discount' => array_sum(array_column($tableData, 'promo_discount')),
            'order_date' => null,
        ];

        $promoDistribution = OrderPromo::whereHas('order', function ($q) use ($from, $to, $orderType) {
            $q->whereBetween('order_date', [$from, $to])
                ->where('status', 'paid');
            if ($orderType) {
                $q->whereHas('orderType', function ($subQ) use ($orderType) {
                    $subQ->where('order_type', $orderType);
                });
            }
        })
            ->selectRaw('promo_type, COUNT(*) as count, SUM(discount_amount) as total_discount')
            ->groupBy('promo_type')
            ->get()
            ->toArray();

        return response()->json([
            'kpi' => [
                'revenue' => (float) $totalRevenue,
                'ordersCount' => $ordersCount,
                'aov' => (float) $aov,
                'itemsSold' => (int) $itemsSold,
                'totalPromoDiscount' => (float) $totalPromoDiscount,
                'ordersWithPromo' => (int) $ordersWithPromo,
                'promoPercentage' => $ordersCount > 0 ? round(($ordersWithPromo / $ordersCount) * 100, 2) : 0,
            ],
            'chartData' => $chartData,
            'distributionData' => $distributionData,
            'tableData' => $tableData,
            'totalsSummary' => $totalsSummary,
            'promoDistribution' => $promoDistribution,
        ]);
    }

    // Also update getComparisonAnalytics method
    protected function getComparisonAnalytics($from, $to, $validated)
    {
        $orderType = $validated['orderType'] ?? null;

        $salesQ = PosOrder::whereBetween('order_date', [$from, $to])
            ->where('status', 'paid');
        if ($orderType) {
            $salesQ->whereHas('orderType', function ($q) use ($orderType) {
                $q->where('order_type', $orderType);
            });
        }

        $totalSales = (clone $salesQ)->sum('total_amount');
        $salesCount = (clone $salesQ)->count();

        $purchaseQ = PurchaseOrder::whereBetween('purchase_date', [$from, $to])
            ->where('status', 'completed');

        $totalPurchase = (clone $purchaseQ)->sum('total_amount');
        $purchaseCount = (clone $purchaseQ)->count();

        $grossProfit = $totalSales - $totalPurchase;
        $profitMargin = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 2) : 0;

        $salesChart = (clone $salesQ)
            ->selectRaw('DATE(order_date) as date, SUM(total_amount) as total')
            ->groupByRaw('DATE(order_date)')
            ->pluck('total', 'date');

        $purchaseChart = (clone $purchaseQ)
            ->selectRaw('DATE(purchase_date) as date, SUM(total_amount) as total')
            ->groupByRaw('DATE(purchase_date)')
            ->pluck('total', 'date');

        $allDates = collect($salesChart->keys())->merge($purchaseChart->keys())->unique()->sort();

        $chartData = $allDates->map(fn ($date) => [
            'date' => $date,
            'sales' => (float) ($salesChart->get($date) ?? 0),
            'purchase' => (float) ($purchaseChart->get($date) ?? 0),
        ])->toArray();

        $totalAmount = $totalSales + $totalPurchase;
        $distributionData = [];

        if ($totalAmount > 0) {
            if ($totalSales > 0) {
                $distributionData[] = [
                    'label' => 'Sales',
                    'value' => (int) $salesCount,
                    'percentage' => round(($totalSales / $totalAmount) * 100),
                    'color' => '#10b981',
                ];
            }

            if ($totalPurchase > 0) {
                $distributionData[] = [
                    'label' => 'Purchases',
                    'value' => (int) $purchaseCount,
                    'percentage' => round(($totalPurchase / $totalAmount) * 100),
                    'color' => '#ef4444',
                ];
            }
        }

        $tableData = [
            ['metric' => 'Total Revenue', 'sales' => $totalSales, 'purchase' => 0, 'difference' => $totalSales],
            ['metric' => 'Total Cost', 'sales' => 0, 'purchase' => $totalPurchase, 'difference' => -$totalPurchase],
            ['metric' => 'Gross Profit', 'sales' => $totalSales, 'purchase' => $totalPurchase, 'difference' => $grossProfit],
            ['metric' => 'Transactions', 'sales' => $salesCount, 'purchase' => $purchaseCount, 'difference' => $salesCount - $purchaseCount],
        ];

        return response()->json([
            'kpi' => [
                'salesRevenue' => (float) $totalSales,
                'purchaseCost' => (float) $totalPurchase,
                'grossProfit' => (float) $grossProfit,
                'profitMargin' => (float) $profitMargin,
            ],
            'chartData' => $chartData,
            'distributionData' => $distributionData,
            'tableData' => $tableData,
        ]);
    }

    /**
     * Purchase Analytics
     */
    protected function getPurchaseAnalytics($from, $to, $validated)
    {
        $timeRange = $validated['timeRange'];

        $ordersQ = PurchaseOrder::whereBetween('purchase_date', [$from, $to])
            ->where('status', 'completed');

        // KPIs
        $totalCost = (clone $ordersQ)->sum('total_amount');
        $purchaseCount = (clone $ordersQ)->count();
        $avgPurchaseValue = $purchaseCount > 0 ? $totalCost / $purchaseCount : 0;

        $itemsPurchased = PurchaseItem::whereHas('purchase', function ($q) use ($from, $to) {
            $q->whereBetween('purchase_date', [$from, $to])
                ->where('status', 'completed');
        })->sum('quantity');
        if ($timeRange === 'yearly') {
            $chartData = (clone $ordersQ)
                ->selectRaw('DATE_FORMAT(purchase_date, "%Y-%m") as date, SUM(total_amount) as total')
                ->groupByRaw('DATE_FORMAT(purchase_date, "%Y-%m")')
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => [
                    'date' => $row->date,
                    'total' => (float) $row->total,
                ])
                ->toArray();
        } else {
            $chartData = (clone $ordersQ)
                ->selectRaw('DATE(purchase_date) as date, SUM(total_amount) as total')
                ->groupByRaw('DATE(purchase_date)')
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => [
                    'date' => $row->date,
                    'total' => (float) $row->total,
                ])
                ->toArray();
        }

        // Distribution data - by supplier
        $supplierData = (clone $ordersQ)
            ->selectRaw('supplier_id, COUNT(*) as cnt, SUM(total_amount) as amount')
            ->groupBy('supplier_id')
            ->with('supplier:id,name')
            ->get();

        $totalAmount = $supplierData->sum('amount') ?: 1;
        $distributionData = $supplierData->map(function ($row) use ($totalAmount) {
            return [
                'label' => $row->supplier->name ?? 'Unknown',
                'value' => (int) $row->cnt,
                'percentage' => round($row->amount * 100 / $totalAmount),
                'color' => '#f59e0b',
            ];
        })->toArray();
        if ($timeRange === 'monthly') {
            $tableData = PurchaseItem::whereHas('purchase', function ($q) use ($from, $to) {
                $q->whereBetween('purchase_date', [$from, $to])
                    ->where('status', 'completed');
            })
                ->selectRaw('
        DATE(purchase_orders.purchase_date) as purchase_date,
        COUNT(DISTINCT purchase_orders.id) as purchase_count,
        COALESCE(SUM(purchase_items.quantity), 0) as quantity,
        COALESCE(SUM(purchase_items.unit_price), 0) as total_unit_price,
        COALESCE(SUM(purchase_items.sub_total), 0) as sub_total,

        
        COALESCE(SUM(DISTINCT purchase_orders.total_amount), 0) as total_amount,

        GROUP_CONCAT(DISTINCT suppliers.name SEPARATOR ", ") as supplier_names
    ')
                ->leftJoin('purchase_orders', 'purchase_items.purchase_id', '=', 'purchase_orders.id')
                ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->groupByRaw('DATE(purchase_orders.purchase_date)')
                ->orderBy('purchase_date', 'desc')
                ->get()
                ->map(fn ($row) => [
                    'purchase_date' => $row->purchase_date,
                    'purchase_count' => (int) $row->purchase_count,
                    'quantity' => (int) $row->quantity,
                    'total_unit_price' => (float) $row->total_unit_price,
                    'sub_total' => (float) $row->sub_total,
                    'total_amount' => (float) $row->total_amount,
                    'supplier_names' => $row->supplier_names ?? '-',
                ])
                ->toArray();

        } elseif ($timeRange === 'yearly') {
            $tableData = PurchaseItem::whereHas('purchase', function ($q) use ($from, $to) {
                $q->whereBetween('purchase_date', [$from, $to])
                    ->where('status', 'completed');
            })
                ->selectRaw('
                DATE_FORMAT(purchase_orders.purchase_date, "%Y-%m") as purchase_month,
                COUNT(DISTINCT purchase_orders.id) as purchase_count,
                COALESCE(SUM(purchase_items.quantity), 0) as quantity,
                COALESCE(SUM(purchase_items.unit_price), 0) as total_unit_price,
                COALESCE(SUM(purchase_items.sub_total), 0) as sub_total,   
                COALESCE(SUM(DISTINCT purchase_orders.total_amount), 0) as total_amount,
                GROUP_CONCAT(DISTINCT suppliers.name SEPARATOR ", ") as supplier_names
            ')
                ->leftJoin('purchase_orders', 'purchase_items.purchase_id', '=', 'purchase_orders.id')
                ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->groupByRaw('DATE_FORMAT(purchase_orders.purchase_date, "%Y-%m")')
                ->orderBy('purchase_month', 'desc')
                ->get()
                ->map(fn ($row) => [
                    'purchase_month' => $row->purchase_month,
                    'purchase_count' => (int) $row->purchase_count,
                    'quantity' => (int) $row->quantity,
                    'total_unit_price' => (float) $row->total_unit_price,
                    'sub_total' => (float) $row->sub_total,
                    'total_amount' => (float) $row->total_amount,
                    'supplier_names' => $row->supplier_names ?? '-',
                ])
                ->toArray();
        } else {
            $tableData = PurchaseItem::whereHas('purchase', function ($q) use ($from, $to) {
                $q->whereBetween('purchase_date', [$from, $to])
                    ->where('status', 'completed');
            })
                ->selectRaw('
                purchase_items.id,
                purchase_items.purchase_id,
                purchase_items.product_id,
                purchase_items.quantity,
                purchase_items.unit_price,
                purchase_items.sub_total,
                purchase_items.expiry_date,
                purchase_orders.purchase_date,
                purchase_orders.status,
                purchase_orders.total_amount,
                suppliers.name as supplier_name,
                inventory_items.name as product_name
            ')
                ->leftJoin('purchase_orders', 'purchase_items.purchase_id', '=', 'purchase_orders.id')
                ->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->leftJoin('inventory_items', 'purchase_items.product_id', '=', 'inventory_items.id')
                ->orderByDesc('purchase_items.created_at')
                ->limit(100)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'purchase_id' => $row->purchase_id,
                    'product_id' => $row->product_id,
                    'product_name' => $row->product_name ?? 'Unknown Item',
                    'supplier_name' => $row->supplier_name ?? 'Unknown Supplier',
                    'quantity' => (int) $row->quantity,
                    'unit_price' => (float) $row->unit_price,
                    'sub_total' => (float) $row->sub_total,
                    'expiry_date' => $row->expiry_date,
                    'purchase_date' => $row->purchase_date,
                    'status' => $row->status,
                    'total_amount' => (float) $row->total_amount,
                ])
                ->toArray();
        }
        $totalsSummary = [
            'product_name' => 'TOTAL',
            'quantity' => array_sum(array_column($tableData, 'quantity')),
            'total_unit_price' => array_sum(array_column($tableData, 'total_unit_price')),
            'sub_total' => array_sum(array_column($tableData, 'sub_total')),
            'total_amount' => array_sum(array_column($tableData, 'total_amount')),
        ];

        return response()->json([
            'kpi' => [
                'purchaseCost' => (float) $totalCost,
                'purchaseCount' => $purchaseCount,
                'avgPurchaseValue' => (float) $avgPurchaseValue,
                'itemsPurchased' => (int) $itemsPurchased,
            ],
            'chartData' => $chartData,
            'distributionData' => $distributionData,
            'tableData' => $tableData,
            'totalsSummary' => $totalsSummary,
        ]);
    }
    /**
     * Stock Analytics
     */
    protected function getStockAnalytics($from, $to, $validated)
    {
        $items = InventoryItem::all();

        $totalStock = (int) $items->sum('stock');
        $lowStockItems = (int) $items->filter(function ($item) {
            return $item->stock < ($item->min_alert ?? 0);
        })->count();
        $outOfStockItems = (int) $items->where('stock', '<=', 0)->count();
        $stockValue = (float) $items->sum(fn ($item) => ($item->stock ?? 0) * ($item->unit_cost ?? 0));
        $chartData = $items->sortByDesc('stock')->take(10)->map(fn ($item) => [
            'date' => $item->name,
            'total' => (int) $item->stock,
        ])->values()->toArray();

        $inStockCount = $items->filter(function ($item) {
            return $item->stock > ($item->min_alert ?? 0);
        })->count();
        $total = max(1, $items->count());

        $distributionData = [
            ['label' => 'In Stock', 'value' => $inStockCount, 'percentage' => round($inStockCount * 100 / $total), 'color' => '#10b981'],
            ['label' => 'Low Stock', 'value' => $lowStockItems, 'percentage' => round($lowStockItems * 100 / $total), 'color' => '#f59e0b'],
            ['label' => 'Out of Stock', 'value' => $outOfStockItems, 'percentage' => round($outOfStockItems * 100 / $total), 'color' => '#ef4444'],
        ];

        $tableData = $items->map(fn ($item) => [
            'name' => $item->name,
            'currentStock' => (int) $item->stock,
            'minLevel' => (int) ($item->min_alert ?? 5),
            'status' => $item->stock <= 0 ? 'out_of_stock' : ($item->stock < ($item->min_alert ?? 5) ? 'low_stock' : 'in_stock'),
        ])->toArray();

        return response()->json([
            'kpi' => [
                'totalStock' => $totalStock,
                'lowStockItems' => $lowStockItems,
                'outOfStockItems' => $outOfStockItems,
                'stockValue' => $stockValue,
            ],
            'chartData' => $chartData,
            'distributionData' => $distributionData,
            'tableData' => $tableData,
        ]);
    }

    /**
     * User Sales Analytics (Cashier Performance)
     */
    protected function getUserSalesAnalytics($from, $to, $validated)
    {
        $orderType = $validated['orderType'] ?? null;

        $ordersQ = PosOrder::whereBetween('order_date', [$from, $to])
            ->where('status', 'paid');

        if ($orderType) {
            $ordersQ->where('order_type', $orderType);
        }

        $totalUserSales = (clone $ordersQ)->sum('total_amount');
        $activeCashiers = (clone $ordersQ)->distinct('user_id')->count('user_id');
        $avgCashierSales = $activeCashiers > 0 ? $totalUserSales / $activeCashiers : 0;

        $cashierSales = (clone $ordersQ)
            ->selectRaw('user_id, COUNT(*) as order_count, SUM(total_amount) as total_sales')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total_sales')
            ->get();

        $topCashierSales = $cashierSales->first()?->total_sales ?? 0;

        // Fixed chart data - use cashier names
        $chartData = $cashierSales->take(10)->map(fn ($row) => [
            'date' => $row->user->name ?? 'Unknown',
            'total' => (float) $row->total_sales,
        ])->toArray();

        $totalSales = $cashierSales->sum('total_sales') ?: 1;
        $distributionData = $cashierSales->take(5)->map(function ($row) use ($totalSales) {
            return [
                'label' => $row->user->name ?? 'Unknown',
                'value' => (int) $row->order_count,
                'percentage' => round($row->total_sales * 100 / $totalSales),
                'color' => '#'.substr(md5($row->user_id), 0, 6),
            ];
        })->toArray();

        $tableData = $cashierSales->map(fn ($row) => [
            'cashierName' => $row->user->name ?? 'Unknown',
            'orderCount' => (int) $row->order_count,
            'totalSales' => (float) $row->total_sales,
            'avgSale' => (float) ($row->total_sales / $row->order_count),
        ])->toArray();

        return response()->json([
            'kpi' => [
                'totalUserSales' => (float) $totalUserSales,
                'avgCashierSales' => (float) $avgCashierSales,
                'activeCashiers' => $activeCashiers,
                'topCashierSales' => (float) $topCashierSales,
            ],
            'chartData' => $chartData,
            'distributionData' => $distributionData,
            'tableData' => $tableData,
        ]);
    }

    /**
     * Category Wise Sales Analytics
     */
    protected function getCategoryAnalytics($from, $to, $validated)
    {
        $orderType = $validated['orderType'] ?? null;

        $ordersQ = PosOrder::whereBetween('order_date', [$from, $to])
            ->where('status', 'paid');

        if ($orderType) {
            $ordersQ->where('order_type', $orderType);
        }

        $categorySales = PosOrderItem::whereHas('order', function ($q) use ($from, $to, $orderType) {
            $q->whereBetween('order_date', [$from, $to])
                ->where('status', 'paid');

            if ($orderType) {
                $q->where('order_type', $orderType);
            }
        })
            ->join('menu_items', 'menu_items.id', '=', 'pos_order_items.menu_item_id')
            ->join('menu_categories', 'menu_categories.id', '=', 'menu_items.category_id')
            ->selectRaw('
            menu_categories.name as categoryName,
            SUM(pos_order_items.quantity) as qty,
            SUM(pos_order_items.quantity * pos_order_items.price) as revenue
        ')
            ->groupBy('menu_categories.name')
            ->orderByDesc('revenue')
            ->get();

        $totalCategories = $categorySales->count();
        $categoryRevenue = (float) $categorySales->sum('revenue');
        $topCategory = $categorySales->first()?->categoryName ?? 'N/A';
        $totalCategorySales = (int) $categorySales->sum('qty');

        // Fixed chart data - use category names
        $totalRev = $categorySales->sum('revenue') ?: 1;
        $chartData = $categorySales->take(10)->map(fn ($row) => [
            'date' => $row->categoryName,
            'total' => (float) $row->revenue,
        ])->toArray();

        $distributionData = $categorySales->take(5)->map(function ($row) use ($totalRev) {
            return [
                'label' => $row->categoryName,
                'value' => (int) $row->qty,
                'percentage' => round($row->revenue * 100 / $totalRev),
                'color' => '#'.substr(md5($row->categoryName), 0, 6),
            ];
        })->toArray();

        $tableData = $categorySales->map(function ($row) use ($totalRev) {
            return [
                'categoryName' => $row->categoryName,
                'qty' => (int) $row->qty,
                'revenue' => (float) $row->revenue,
                'percentage' => round($row->revenue * 100 / $totalRev),
            ];
        })->toArray();

        return response()->json([
            'kpi' => [
                'totalCategories' => $totalCategories,
                'categoryRevenue' => $categoryRevenue,
                'topCategory' => $topCategory,
                'totalCategorySales' => $totalCategorySales,
            ],
            'chartData' => $chartData,
            'distributionData' => $distributionData,
            'tableData' => $tableData,
        ]);
    }

    public function exportCsv(Request $req)
    {
        $validated = $req->validate([
            'type' => 'required|in:sales,purchase,comparison,stock,userSales,category',
            'timeRange' => 'required|in:daily,monthly,yearly,custom',
            'selectedMonth' => 'nullable|string',
            'selectedYear' => 'nullable|integer',
            'selectedDate' => 'nullable|date',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
            'orderType' => 'nullable|in:dine,delivery',
            'paymentType' => 'nullable|in:cash,card,qr,bank',
        ]);

        [$from, $to] = $this->getDateRange(
            $validated['timeRange'],
            $validated['selectedMonth'] ?? null,
            $validated['selectedYear'] ?? null,
            $validated['selectedDate'] ?? null,
            $validated['dateFrom'] ?? null,
            $validated['dateTo'] ?? null
        );

        $analyticsType = $validated['type'];

        // Get data
        $response = match ($analyticsType) {
            'sales' => $this->getSalesAnalytics($from, $to, $validated),
            'purchase' => $this->getPurchaseAnalytics($from, $to, $validated),
            'comparison' => $this->getComparisonAnalytics($from, $to, $validated),
            'stock' => $this->getStockAnalytics($from, $to, $validated),
            'userSales' => $this->getUserSalesAnalytics($from, $to, $validated),
            'category' => $this->getCategoryAnalytics($from, $to, $validated),
        };

        $data = $response->getData(true);
        $tableData = $data['tableData'];

        // Generate CSV
        $filename = $analyticsType.'_analytics_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($tableData, $analyticsType, $validated) {
            $file = fopen('php://output', 'w');

            // Add headers based on type
            if ($analyticsType === 'sales') {
                if ($validated['timeRange'] === 'monthly') {
                    fputcsv($file, ['S.#', 'Day', 'Orders', 'Items', 'Qty', 'Sub Total', 'Tax', 'Service', 'Delivery', 'Sales Disc.', 'Approved Disc.', 'Promo Disc.', 'Total']);
                } elseif ($validated['timeRange'] === 'yearly') {
                    fputcsv($file, ['S.#', 'Month', 'Orders', 'Items', 'Qty', 'Sub Total', 'Tax', 'Service', 'Delivery', 'Sales Disc.', 'Approved Disc.', 'Promo Disc.', 'Total']);
                } else {
                    fputcsv($file, ['S.#', 'Order ID', 'Customer', 'Items', 'Qty', 'Sub Total', 'Tax', 'Service', 'Delivery', 'Sales Disc.', 'Approved Disc.', 'Promo Disc.', 'Promos', 'Promo Type', 'Total', 'Date']);
                }
            } elseif ($analyticsType === 'purchase') {
                if ($validated['timeRange'] === 'monthly' || $validated['timeRange'] === 'yearly') {
                    fputcsv($file, ['S.#', $validated['timeRange'] === 'monthly' ? 'Day' : 'Month', 'Purchases', 'Quantity', 'Unit Price', 'Sub Total', 'Suppliers', 'Total Amount']);
                } else {
                    fputcsv($file, ['S.#', 'Purchase ID', 'Supplier', 'Product', 'Quantity', 'Unit Price', 'Sub Total', 'Expiry', 'Purchase Date', 'Status']);
                }
            } elseif ($analyticsType === 'comparison') {
                fputcsv($file, ['S.#', 'Metric', 'Sales', 'Purchase', 'Difference']);
            } elseif ($analyticsType === 'stock') {
                fputcsv($file, ['S.#', 'Item', 'Current Stock', 'Min Level', 'Status']);
            } elseif ($analyticsType === 'userSales') {
                fputcsv($file, ['S.#', 'Cashier Name', 'Orders', 'Total Sales', 'Avg Sale']);
            } elseif ($analyticsType === 'category') {
                fputcsv($file, ['S.#', 'Category', 'Qty Sold', 'Revenue', '% of Total']);
            }

            // Add data rows
            $index = 1;
            foreach ($tableData as $row) {
                if ($analyticsType === 'sales') {
                    if ($validated['timeRange'] === 'monthly') {
                        fputcsv($file, [
                            $index++,
                            date('M d', strtotime($row['order_date'])),
                            $row['order_count'],
                            $row['item_count'],
                            $row['total_qty'],
                            $row['sub_total'],
                            $row['tax'],
                            $row['service_charges'],
                            $row['delivery_charges'],
                            $row['sales_discount'],
                            $row['approved_discounts'],
                            $row['promo_discount'],
                            $row['total_amount'],
                        ]);
                    } elseif ($validated['timeRange'] === 'yearly') {
                        fputcsv($file, [
                            $index++,
                            date('F Y', strtotime($row['order_month'].'-01')),
                            $row['order_count'],
                            $row['item_count'],
                            $row['total_qty'],
                            $row['sub_total'],
                            $row['tax'],
                            $row['service_charges'],
                            $row['delivery_charges'],
                            $row['sales_discount'],
                            $row['approved_discounts'],
                            $row['promo_discount'],
                            $row['total_amount'],
                        ]);
                    } else {
                        fputcsv($file, [
                            $index++,
                            $row['id'],
                            $row['customer_name'],
                            $row['item_count'],
                            $row['total_qty'],
                            $row['sub_total'],
                            $row['tax'],
                            $row['service_charges'],
                            $row['delivery_charges'],
                            $row['sales_discount'],
                            $row['approved_discounts'],
                            $row['promo_discount'],
                            $row['promo_names'],
                            $row['promo_types'],
                            $row['total_amount'],
                            date('Y-m-d H:i', strtotime($row['order_date'])),
                        ]);
                    }
                } elseif ($analyticsType === 'purchase') {
                    if ($validated['timeRange'] === 'monthly') {
                        fputcsv($file, [
                            $index++,
                            date('M d', strtotime($row['purchase_date'])),
                            $row['purchase_count'],
                            $row['quantity'],
                            $row['total_unit_price'],
                            $row['sub_total'],
                            $row['supplier_names'],
                            $row['total_amount'],
                        ]);
                    } elseif ($validated['timeRange'] === 'yearly') {
                        fputcsv($file, [
                            $index++,
                            date('F Y', strtotime($row['purchase_month'].'-01')),
                            $row['purchase_count'],
                            $row['quantity'],
                            $row['total_unit_price'],
                            $row['sub_total'],
                            $row['supplier_names'],
                            $row['total_amount'],
                        ]);
                    } else {
                        fputcsv($file, [
                            $index++,
                            $row['purchase_id'],
                            $row['supplier_name'],
                            $row['product_name'],
                            $row['quantity'],
                            $row['unit_price'],
                            $row['sub_total'],
                            $row['expiry_date'] ?? 'N/A',
                            date('Y-m-d', strtotime($row['purchase_date'])),
                            $row['status'],
                        ]);
                    }
                } elseif ($analyticsType === 'comparison') {
                    fputcsv($file, [$index++, $row['metric'], $row['sales'], $row['purchase'], $row['difference']]);
                } elseif ($analyticsType === 'stock') {
                    $status = $row['currentStock'] > $row['minLevel'] ? 'In Stock' : ($row['currentStock'] > 0 ? 'Low Stock' : 'Out of Stock');
                    fputcsv($file, [$index++, $row['name'], $row['currentStock'], $row['minLevel'], $status]);
                } elseif ($analyticsType === 'userSales') {
                    fputcsv($file, [$index++, $row['cashierName'], $row['orderCount'], $row['totalSales'], $row['avgSale']]);
                } elseif ($analyticsType === 'category') {
                    fputcsv($file, [$index++, $row['categoryName'], $row['qty'], $row['revenue'], $row['percentage'].'%']);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

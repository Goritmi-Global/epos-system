<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosOrderType;
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
            'orderType' => 'nullable|in:dine,delivery',
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

        if ($orderType) {
            $ordersQ->where('order_type', $orderType);
        }

        // KPIs
        $totalRevenue = (clone $ordersQ)->sum('total_amount');
        $ordersCount = (clone $ordersQ)->count();
        $aov = $ordersCount > 0 ? $totalRevenue / $ordersCount : 0;

        $itemsSold = PosOrderItem::whereHas('order', function ($q) use ($from, $to, $orderType) {
            $q->whereBetween('order_date', [$from, $to])
                ->where('status', 'paid');
            if ($orderType) {
                $q->where('order_type', $orderType);
            }
        })->sum('quantity');

        // Chart data - daily or monthly based on timeRange
        if ($timeRange === 'yearly') {
            $chartData = (clone $ordersQ)
                ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as date, SUM(total_amount) as total')
                ->groupByRaw('DATE_FORMAT(order_date, "%Y-%m")')
                ->orderBy('date')
                ->get()
                ->map(fn($row) => [
                    'date' => $row->date,
                    'total' => (float) $row->total,
                ])
                ->toArray();
        } else {
            $chartData = (clone $ordersQ)
                ->selectRaw('DATE(order_date) as date, SUM(total_amount) as total')
                ->groupByRaw('DATE(order_date)')
                ->orderBy('date')
                ->get()
                ->map(fn($row) => [
                    'date' => $row->date,
                    'total' => (float) $row->total,
                ])
                ->toArray();
        }



        // Distribution data - by order type
        $dineCount = PosOrderType::where('order_type', 'dine')->count();
        $deliveryCount = PosOrderType::where('order_type', 'delivery')->count();
        $total = max(1, $dineCount + $deliveryCount);

        $distributionData = [
            ['label' => 'Dine In', 'value' => $dineCount, 'percentage' => round($dineCount * 100 / $total), 'color' => '#10b981'],
            ['label' => 'Delivery', 'value' => $deliveryCount, 'percentage' => round($deliveryCount * 100 / $total), 'color' => '#3b82f6'],
        ];

        // Table data - grouped by month if yearly, otherwise by item
        if ($timeRange === 'yearly') {
            $tableData = (clone $ordersQ)
                ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month_name, COUNT(*) as qty, SUM(total_amount) as revenue, MAX(order_date) as date')
                ->groupByRaw('DATE_FORMAT(order_date, "%Y-%m")')
                ->orderBy('month_name')
                ->get()
                ->map(fn($row) => [
                    'name' => Carbon::parse($row->month_name . '-01')->format('F Y'),
                    'qty' => (int) $row->qty,
                    'revenue' => (float) $row->revenue,
                    'date' => $row->date,
                ])
                ->toArray();
        } else {
            $tableData = PosOrderItem::whereHas('order', function ($q) use ($from, $to, $orderType) {
                $q->whereBetween('order_date', [$from, $to])
                    ->where('status', 'paid');
                if ($orderType) {
                    $q->where('order_type', $orderType);
                }
            })
                ->selectRaw('title as name, SUM(quantity) as qty, SUM(quantity * price) as revenue, MAX(created_at) as date')
                ->groupBy('title')
                ->orderByDesc('revenue')
                ->limit(50)
                ->get()
                ->map(fn($row) => [
                    'name' => $row->name,
                    'qty' => (int) $row->qty,
                    'revenue' => (float) $row->revenue,
                    'date' => $row->date,
                ])
                ->toArray();
        }

        return response()->json([
            'kpi' => [
                'revenue' => (float) $totalRevenue,
                'ordersCount' => $ordersCount,
                'aov' => (float) $aov,
                'itemsSold' => (int) $itemsSold,
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

        // Chart data - monthly if yearly
        if ($timeRange === 'yearly') {
            $chartData = (clone $ordersQ)
                ->selectRaw('DATE_FORMAT(purchase_date, "%Y-%m") as date, SUM(total_amount) as total')
                ->groupByRaw('DATE_FORMAT(purchase_date, "%Y-%m")')
                ->orderBy('date')
                ->get()
                ->map(fn($row) => [
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
                ->map(fn($row) => [
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

        // Table data - monthly if yearly
        if ($timeRange === 'yearly') {
            $tableData = (clone $ordersQ)
                ->selectRaw('DATE_FORMAT(purchase_date, "%Y-%m") as month_name, COUNT(*) as qty, SUM(total_amount) as cost, MAX(purchase_date) as date')
                ->groupByRaw('DATE_FORMAT(purchase_date, "%Y-%m")')
                ->orderBy('month_name')
                ->get()
                ->map(fn($row) => [
                    'name' => Carbon::parse($row->month_name . '-01')->format('F Y'),
                    'qty' => (int) $row->qty,
                    'cost' => (float) $row->cost,
                    'date' => $row->date,
                ])
                ->toArray();
        } else {
            $tableData = PurchaseItem::whereHas('purchase', function ($q) use ($from, $to) {
                $q->whereBetween('purchase_date', [$from, $to])
                    ->where('status', 'completed');
            })
                ->selectRaw('COALESCE(product_id, 0) as product_id, SUM(quantity) as qty, SUM(quantity * unit_price) as cost, MAX(purchase_items.created_at) as date')
                ->groupBy('product_id')
                ->orderByDesc('cost')
                ->limit(50)
                ->with('product:id,name')
                ->get()
                ->map(fn($row) => [
                    'name' => $row->product->name ?? 'Unknown Item',
                    'qty' => (int) $row->qty,
                    'cost' => (float) $row->cost,
                    'date' => $row->date,
                ])
                ->toArray();
        }

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
        ]);
    }

    /**
     * Sales vs Purchase Comparison
     */
    protected function getComparisonAnalytics($from, $to, $validated)
    {
        $orderType = $validated['orderType'] ?? null;

        // Sales data
        $salesQ = PosOrder::whereBetween('order_date', [$from, $to])
            ->where('status', 'paid');
        if ($orderType) {
            $salesQ->where('order_type', $orderType);
        }

        $totalSales = (clone $salesQ)->sum('total_amount');
        $salesCount = (clone $salesQ)->count();

        // Purchase data
        $purchaseQ = PurchaseOrder::whereBetween('purchase_date', [$from, $to])
            ->where('status', 'completed');

        $totalPurchase = (clone $purchaseQ)->sum('total_amount');
        $purchaseCount = (clone $purchaseQ)->count();

        // Calculate profit
        $grossProfit = $totalSales - $totalPurchase;
        $profitMargin = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 2) : 0;

        // Chart data - daily comparison
        $salesChart = (clone $salesQ)
            ->selectRaw('DATE(order_date) as date, SUM(total_amount) as total')
            ->groupByRaw('DATE(order_date)')
            ->pluck('total', 'date');

        $purchaseChart = (clone $purchaseQ)
            ->selectRaw('DATE(purchase_date) as date, SUM(total_amount) as total')
            ->groupByRaw('DATE(purchase_date)')
            ->pluck('total', 'date');

        $allDates = collect($salesChart->keys())->merge($purchaseChart->keys())->unique()->sort();

        $chartData = $allDates->map(fn($date) => [
            'date' => $date,
            'sales' => (float) ($salesChart->get($date) ?? 0),
            'purchase' => (float) ($purchaseChart->get($date) ?? 0),
        ])->toArray();

        // Distribution - comparison breakdown
        $distributionData = [
            ['label' => 'Sales', 'value' => (int) $salesCount, 'percentage' => 50, 'color' => '#10b981'],
            ['label' => 'Purchases', 'value' => (int) $purchaseCount, 'percentage' => 50, 'color' => '#ef4444'],
        ];

        // Table data - metrics comparison
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
     * Stock Analytics
     */
    protected function getStockAnalytics($from, $to, $validated)
    {
        $items = InventoryItem::all();

        $totalStock = (int) $items->sum('stock');
        $lowStockItems = (int) $items->where('stock', '<', DB::raw('min_alert'))->count();
        $outOfStockItems = (int) $items->where('stock', '<=', 0)->count();
        $stockValue = (float) $items->sum(fn($item) => ($item->stock ?? 0) * ($item->unit_cost ?? 0));

        // Chart data - stock by category or item
        $chartData = $items->take(10)->map(fn($item) => [
            'date' => $item->name,
            'total' => (int) $item->stock,
        ])->toArray();

        // Distribution data
        $inStockCount = $items->where('stock', '>', DB::raw('min_alert'))->count();
        $total = max(1, $items->count());

        $distributionData = [
            ['label' => 'In Stock', 'value' => $inStockCount, 'percentage' => round($inStockCount * 100 / $total), 'color' => '#10b981'],
            ['label' => 'Low Stock', 'value' => $lowStockItems, 'percentage' => round($lowStockItems * 100 / $total), 'color' => '#f59e0b'],
            ['label' => 'Out of Stock', 'value' => $outOfStockItems, 'percentage' => round($outOfStockItems * 100 / $total), 'color' => '#ef4444'],
        ];

        // Table data
        $tableData = $items->map(fn($item) => [
            'name' => $item->name,
            'currentStock' => (int) $item->stock,
            'minLevel' => (int) ($item->min_alert ?? 5),
            'status' => $item->stock <= 0 ? 'out_of_stock' : ($item->stock < $item->min_alert ? 'low_stock' : 'in_stock'),
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

        // KPIs
        $totalUserSales = (clone $ordersQ)->sum('total_amount');
        $activeCashiers = (clone $ordersQ)->distinct('user_id')->count('user_id');
        $avgCashierSales = $activeCashiers > 0 ? $totalUserSales / $activeCashiers : 0;

        // Get cashier sales
        $cashierSales = (clone $ordersQ)
            ->selectRaw('user_id, COUNT(*) as order_count, SUM(total_amount) as total_sales')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total_sales')
            ->get();

        $topCashierSales = $cashierSales->first()?->total_sales ?? 0;

        // Chart data - top cashiers
        $chartData = $cashierSales->take(10)->map(fn($row) => [
            'date' => $row->user->name ?? 'Unknown',
            'total' => (float) $row->total_sales,
        ])->toArray();

        // Distribution data
        $totalSales = $cashierSales->sum('total_sales') ?: 1;
        $distributionData = $cashierSales->take(5)->map(function ($row) use ($totalSales) {
            return [
                'label' => $row->user->name ?? 'Unknown',
                'value' => (int) $row->order_count,
                'percentage' => round($row->total_sales * 100 / $totalSales),
                'color' => '#' . substr(md5($row->user_id), 0, 6),
            ];
        })->toArray();

        // Table data
        $tableData = $cashierSales->map(fn($row) => [
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

        // Get items with category info
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


        // KPIs
        $totalCategories = $categorySales->count();
        $categoryRevenue = (float) $categorySales->sum('revenue');
        $topCategory = $categorySales->first()?->categoryName ?? 'N/A';
        $totalCategorySales = (int) $categorySales->sum('qty');

        // Chart data
        $totalRev = $categorySales->sum('revenue') ?: 1;
        $chartData = $categorySales->take(10)->map(fn($row) => [
            'date' => $row->categoryName,
            'total' => (float) $row->revenue,
        ])->toArray();

        // Distribution data
        $distributionData = $categorySales->take(5)->map(function ($row) use ($totalRev) {
            return [
                'label' => $row->categoryName,
                'value' => (int) $row->qty,
                'percentage' => round($row->revenue * 100 / $totalRev),
                'color' => '#' . substr(md5($row->categoryName), 0, 6),
            ];
        })->toArray();

        // Table data
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
}

<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\ProfileStep1;
use App\Models\ProfileStep2;
use App\Models\ProfileStep3;
use App\Models\ProfileStep4;
use App\Models\ProfileStep5;
use App\Models\ProfileStep6;
use App\Models\ProfileStep7;
use App\Models\ProfileStep8;
use App\Models\ProfileStep9;
use App\Models\PurchaseOrder;
use App\Models\StockEntry;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected function getAvailableYears()
    {
        $posYears = PosOrder::selectRaw('YEAR(order_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $purchaseYears = PurchaseOrder::selectRaw('YEAR(purchase_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return $posYears->merge($purchaseYears)->unique()->sort()->values();
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('login'); // or abort(403)
        }
        $role = $user->getRoleNames()->first();
        // ✅ Redirect cashier directly to POS and block dashboard access
        if ($role === 'Cashier') {
            return redirect()->route('pos.order');
        }

        // // ✅ Step check for onboarding
        // if ($role === 'Super Admin') {
        //     $stepsCompleted = ProfileStep1::where('user_id', $user->id)->exists()
        //         && ProfileStep2::where('user_id', $user->id)->exists()
        //         && ProfileStep3::where('user_id', $user->id)->exists()
        //         && ProfileStep4::where('user_id', $user->id)->exists()
        //         && ProfileStep5::where('user_id', $user->id)->exists()
        //         && ProfileStep6::where('user_id', $user->id)->exists()
        //         && ProfileStep7::where('user_id', $user->id)->exists()
        //         && ProfileStep8::where('user_id', $user->id)->exists()
        //         && ProfileStep9::where('user_id', $user->id)->exists();

        //     if (! $stepsCompleted && $request->routeIs('dashboard')) {
        //         session()->forget('url.intended');
        //         return redirect()->route('onboarding.index');
        //     }
        // }

        // ✅ Step check for onboarding only for first super admin
        if ($user->is_first_super_admin) {
            $stepsCompleted = ProfileStep1::where('user_id', $user->id)->exists()
                && ProfileStep2::where('user_id', $user->id)->exists()
                && ProfileStep3::where('user_id', $user->id)->exists()
                && ProfileStep4::where('user_id', $user->id)->exists()
                && ProfileStep5::where('user_id', $user->id)->exists()
                && ProfileStep6::where('user_id', $user->id)->exists()
                && ProfileStep7::where('user_id', $user->id)->exists()
                && ProfileStep8::where('user_id', $user->id)->exists()
                && ProfileStep9::where('user_id', $user->id)->exists();

            if (! $stepsCompleted && $request->routeIs('dashboard')) {
                session()->forget('url.intended');

                return redirect()->route('onboarding.index');
            }
        }

        // ✅ Inventory alert calculations with item details
        $inventories = InventoryItem::all();
        $stockEntries = StockEntry::all();

        $outOfStockItems = [];
        $lowStockItems = [];
        $expiredItems = [];
        $nearExpiryItems = [];

        foreach ($inventories as $item) {
            $stock = $item->stock ?? 0;
            $minAlert = $item->minAlert ?? 5;

            // Format: "Product Name (Current Stock: X)"
            if ($stock <= 0) {
                $outOfStockItems[] = $item->name.' (Stock: 0)';
            } elseif ($stock < $minAlert) {
                $lowStockItems[] = $item->name.' (Stock: '.$stock.', Min: '.$minAlert.')';
            }
        }

        foreach ($stockEntries as $entry) {
            if (! $entry->expiry_date) {
                continue;
            }

            $expiry = Carbon::parse($entry->expiry_date);
            $today = Carbon::today();

            // Get item name - adjust based on your actual relationship
            $itemName = $entry->product->name ?? $entry->item_name ?? 'Unknown Item';
            $batchNumber = $entry->batch_number ?? '';

            if ($expiry->lt($today)) {
                $expiredItems[] = $itemName.' (Expired: '.$expiry->format('M d, Y').')'.($batchNumber ? ' - Batch: '.$batchNumber : '');
            } elseif ($expiry->diffInDays($today) <= 15 && $expiry->gt($today)) {
                $daysLeft = $expiry->diffInDays($today);
                $nearExpiryItems[] = $itemName.' (Expires in '.$daysLeft.' day'.($daysLeft > 1 ? 's' : '').')'.($batchNumber ? ' - Batch: '.$batchNumber : '');
            }
        }

        $inventoryAlerts = [
            'outOfStock' => count($outOfStockItems),
            'lowStock' => count($lowStockItems),
            'expired' => count($expiredItems),
            'nearExpiry' => count($nearExpiryItems),
            'outOfStockItems' => $outOfStockItems,
            'lowStockItems' => $lowStockItems,
            'expiredItems' => $expiredItems,
            'nearExpiryItems' => $nearExpiryItems,
        ];

        // ✅ 1. Payment Counts Summaries
        $paymentSums = $this->calculatePayments();
        // ✅ 2. Order Counts Summaries
        $orderCounts = $this->calculateOrderCounts();
        // ✅ 3. Average Order Value Summaries
        $averageOrders = $this->calculateAverageOrders();
        // ✅ 4. Total Purchase Amount Summaries
        $purchaseTotals = $this->calculateCompletedPurchaseTotal();
        // ✅ 5. Total Suppliers Count Summaries
        $supplierCounts = $this->calculateSuppliers();
        // ✅ Calculate Total Purchase Amount
        $totalPendingPurchases = PurchaseOrder::where('status', '!=', 'completed')->sum('total_amount');
        // ✅ Recent Top 10 Selling Items
        $recentItems = $this->getTopSellingItems();

        // Cash payments total
        $totalCash = Payment::where('payment_type', 'cash')->sum('cash_amount');
        // Card payments total
        $totalCard = Payment::where('payment_type', 'card')->sum('card_amount');

        // Get current month and year dynamically
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        $selectedYear = $request->input('year', now()->year);

        // Get available years
        $availableYears = $this->getAvailableYears();

        // Monthly data for selected year
        // In the index method, update the monthly data section:

        $monthlySales = PosOrder::where('status', 'paid')
            ->whereYear('order_date', $selectedYear)
            ->select(
                DB::raw('MONTH(order_date) as month'),
                DB::raw('CAST(SUM(total_amount) AS DECIMAL(10,2)) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthlyPurchases = PurchaseOrder::where('status', 'completed')
            ->whereYear('purchase_date', $selectedYear)
            ->select(
                DB::raw('MONTH(purchase_date) as month'),
                DB::raw('CAST(SUM(total_amount) AS DECIMAL(10,2)) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // Fill all 12 months with numeric 0
        $monthlySalesArray = [];
        $monthlyPurchasesArray = [];

        for ($i = 1; $i <= 12; $i++) {
            $monthlySalesArray[] = (float) ($monthlySales->get($i, 0));
            $monthlyPurchasesArray[] = (float) ($monthlyPurchases->get($i, 0));
        }

        $dailyPurchases = PurchaseOrder::where('status', 'completed')
            ->whereMonth('purchase_date', $currentMonth)
            ->whereYear('purchase_date', $currentYear)
            ->select(
                DB::raw('DAY(purchase_date) as day'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day'); // returns [day => total_amount]

        // Fill missing days with 0 so array has length = number of days in month
        $daysInMonth = $now->daysInMonth;
        $dailyPurchasesArray = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dailyPurchasesArray[] = $dailyPurchases->get($i, 0);
        }

        $dailySales = PosOrder::where('status', 'paid')
            ->whereMonth('order_date', $currentMonth)
            ->whereYear('order_date', $currentYear)
            ->select(
                DB::raw('DAY(order_date) as day'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day'); // [day => total]

        $daysInMonth = $now->daysInMonth;
        $dailySalesArray = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dailySalesArray[] = $dailySales->get($i, 0); // fill 0 if no sales
        }

        // Render the dashboard view with all calculated data

        return Inertia::render('Backend/Dashboard/Index', [
            'inventoryAlerts' => $inventoryAlerts,
            'showPopup' => session('show_inventory_popup', false),

            'totalPendingPurchases' => $totalPendingPurchases,
            'totalCash' => $totalCash,
            'totalCard' => $totalCard,

            // Payments over different time frames
            'totalPayments' => $paymentSums['totalPayments'],
            'todayPayments' => $paymentSums['todayPayments'],
            'threeDaysPayments' => $paymentSums['threeDaysPayments'],
            'sevenDaysPayments' => $paymentSums['sevenDaysPayments'],
            'monthPayments' => $paymentSums['monthPayments'],
            'yearPayments' => $paymentSums['yearPayments'],

            // Average order values over different time frames
            'totalOrderAverage' => $averageOrders['totalAverage'],
            'todayOrderAverage' => $averageOrders['todayAverage'],
            'threeDaysOrderAverage' => $averageOrders['threeDaysAverage'],
            'sevenDaysOrderAverage' => $averageOrders['sevenDaysAverage'],
            'monthOrderAverage' => $averageOrders['monthAverage'],
            'yearOrderAverage' => $averageOrders['yearAverage'],

            'availableYears' => $availableYears,
            'selectedYear' => $selectedYear,
            'monthlySales' => $monthlySalesArray,
            'monthlyPurchases' => $monthlyPurchasesArray,

            // Orders over different time frames
            'totalOrders' => $orderCounts['totalOrders'],
            'todayOrders' => $orderCounts['todayOrders'],
            'threeDaysOrders' => $orderCounts['threeDaysOrders'],
            'sevenDaysOrders' => $orderCounts['sevenDaysOrders'],
            'yearOrders' => $orderCounts['yearOrders'],

            // Completed purchase totals over different time frames
            'totalPurchaseCompleted' => $purchaseTotals['totalAmount'],
            'todayPurchaseCompleted' => $purchaseTotals['todayAmount'],
            'threeDaysPurchaseCompleted' => $purchaseTotals['threeDaysAmount'],
            'sevenDaysPurchaseCompleted' => $purchaseTotals['sevenDaysAmount'],
            'monthPurchaseCompleted' => $purchaseTotals['monthAmount'],
            'yearPurchaseCompleted' => $purchaseTotals['yearAmount'],
            // Suppliers over different time frames
            'totalSuppliers' => $supplierCounts['totalSuppliers'],
            'todaySuppliers' => $supplierCounts['todaySuppliers'],
            'threeDaysSuppliers' => $supplierCounts['threeDaysSuppliers'],
            'sevenDaysSuppliers' => $supplierCounts['sevenDaysSuppliers'],
            'monthSuppliers' => $supplierCounts['monthSuppliers'],
            'yearSuppliers' => $supplierCounts['yearSuppliers'],

            'recentItems' => $recentItems,

            // Daily Purchases & Sales for current month (chart)
            'dailyPurchases' => $dailyPurchasesArray,
            'dailySales' => $dailySalesArray,
        ]);
    }

    protected function calculateOrderCounts()
    {
        return [
            'totalOrders' => PosOrder::count(),
            'todayOrders' => PosOrder::whereDate('order_date', today())->count(),
            'threeDaysOrders' => PosOrder::whereBetween('order_date', [
                now()->subDays(2)->startOfDay(), // include today
                now()->endOfDay(),
            ])->count(),
            'sevenDaysOrders' => PosOrder::whereBetween('order_date', [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
            ])->count(),
            'yearOrders' => PosOrder::whereBetween('order_date', [
                now()->subYear()->startOfDay(),
                now()->endOfDay(),
            ])->count(),
        ];
    }

    protected function calculatePayments()
    {
        return [
            'totalPayments' => Payment::sum('amount_received'),
            'todayPayments' => Payment::whereDate('payment_date', today())->sum('amount_received'),
            'threeDaysPayments' => Payment::whereBetween('payment_date', [
                now()->subDays(2)->startOfDay(), // include today
                now()->endOfDay(),
            ])->sum('amount_received'),
            'sevenDaysPayments' => Payment::whereBetween('payment_date', [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
            ])->sum('amount_received'),
            'monthPayments' => Payment::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount_received'),
            'yearPayments' => Payment::whereBetween('payment_date', [
                now()->subYear()->startOfDay(),
                now()->endOfDay(),
            ])->sum('amount_received'),
        ];
    }

    protected function calculateAverageOrders()
    {
        return [
            'totalAverage' => PosOrder::where('status', 'paid')->avg('total_amount'),

            'todayAverage' => PosOrder::where('status', 'paid')
                ->whereDate('created_at', today())
                ->avg('total_amount'),

            'threeDaysAverage' => PosOrder::where('status', 'paid')
                ->whereBetween('created_at', [
                    now()->subDays(2)->startOfDay(), // includes today
                    now()->endOfDay(),
                ])
                ->avg('total_amount'),

            'sevenDaysAverage' => PosOrder::where('status', 'paid')
                ->whereBetween('created_at', [
                    now()->subDays(6)->startOfDay(),
                    now()->endOfDay(),
                ])
                ->avg('total_amount'),

            'monthAverage' => PosOrder::where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->avg('total_amount'),

            'yearAverage' => PosOrder::where('status', 'paid')
                ->whereBetween('created_at', [
                    now()->subYear()->startOfDay(),
                    now()->endOfDay(),
                ])
                ->avg('total_amount'),
        ];
    }

    // Total Purchase Amount Calculation
    protected function calculateCompletedPurchaseTotal()
    {
        return [
            'totalAmount' => PurchaseOrder::where('status', 'completed')->sum('total_amount'),

            'todayAmount' => PurchaseOrder::where('status', 'completed')
                ->whereDate('purchase_date', today())
                ->sum('total_amount'),

            'threeDaysAmount' => PurchaseOrder::where('status', 'completed')
                ->whereBetween('purchase_date', [
                    now()->subDays(2)->startOfDay(), // includes today
                    now()->endOfDay(),
                ])
                ->sum('total_amount'),

            'sevenDaysAmount' => PurchaseOrder::where('status', 'completed')
                ->whereBetween('purchase_date', [
                    now()->subDays(6)->startOfDay(),
                    now()->endOfDay(),
                ])
                ->sum('total_amount'),

            'monthAmount' => PurchaseOrder::where('status', 'completed')
                ->whereMonth('purchase_date', now()->month)
                ->whereYear('purchase_date', now()->year)
                ->sum('total_amount'),

            'yearAmount' => PurchaseOrder::where('status', 'completed')
                ->whereBetween('purchase_date', [
                    now()->subYear()->startOfDay(),
                    now()->endOfDay(),
                ])
                ->sum('total_amount'),
        ];
    }

    // Total Suppliers Count
    protected function calculateSuppliers()
    {
        return [
            'totalSuppliers' => Supplier::count(),

            'todaySuppliers' => Supplier::whereDate('created_at', today())->count(),

            'threeDaysSuppliers' => Supplier::whereBetween('created_at', [
                now()->subDays(2)->startOfDay(),
                now()->endOfDay(),
            ])->count(),

            'sevenDaysSuppliers' => Supplier::whereBetween('created_at', [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
            ])->count(),

            'monthSuppliers' => Supplier::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),

            'yearSuppliers' => Supplier::whereBetween('created_at', [
                now()->subYear()->startOfDay(),
                now()->endOfDay(),
            ])->count(),
        ];
    }

    // Top Products Calculation (Optional)
    protected function getTopSellingItems()
    {
        return PosOrderItem::select(
            'title',
            DB::raw('SUM(quantity) as quantity'),
            DB::raw('AVG(price) as price'),
            DB::raw('SUM(quantity * price) as total')
        )
            ->whereHas('order', function ($query) {
                $query->where('status', 'paid'); // match your DB status
            })
            ->groupBy('title')
            ->orderByDesc('quantity')
            ->take(10)
            ->get();
    }
}

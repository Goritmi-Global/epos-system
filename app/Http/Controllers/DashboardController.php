<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\ProfileStep1;
use App\Models\ProfileStep2;
use App\Models\ProfileStep3;
use App\Models\ProfileStep4;
use App\Models\ProfileStep5;
use App\Models\ProfileStep6;
use App\Models\ProfileStep7;
use App\Models\ProfileStep8;
use App\Models\ProfileStep9;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PurchaseOrder;
use App\Models\StockEntry;
use App\Models\Supplier;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login'); // or abort(403)
        }
        $role = $user->getRoleNames()->first();

        // ✅ Step check for onboarding
        if ($role === 'Super Admin') {
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
                $outOfStockItems[] = $item->name . ' (Stock: 0)';
            } elseif ($stock < $minAlert) {
                $lowStockItems[] = $item->name . ' (Stock: ' . $stock . ', Min: ' . $minAlert . ')';
            }
        }

        foreach ($stockEntries as $entry) {
            if (!$entry->expiry_date) continue;

            $expiry = Carbon::parse($entry->expiry_date);
            $today = Carbon::today();

            // Get item name - adjust based on your actual relationship
            $itemName = $entry->product->name ?? $entry->item_name ?? 'Unknown Item';
            $batchNumber = $entry->batch_number ?? '';

            if ($expiry->lt($today)) {
                $expiredItems[] = $itemName . ' (Expired: ' . $expiry->format('M d, Y') . ')' . ($batchNumber ? ' - Batch: ' . $batchNumber : '');
            } elseif ($expiry->diffInDays($today) <= 15 && $expiry->gt($today)) {
                $daysLeft = $expiry->diffInDays($today);
                $nearExpiryItems[] = $itemName . ' (Expires in ' . $daysLeft . ' day' . ($daysLeft > 1 ? 's' : '') . ')' . ($batchNumber ? ' - Batch: ' . $batchNumber : '');
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

        // Count all suppliers
        $totalSuppliers = Supplier::count();

        // ✅ Calculate Total Purchase Amount
        $totalPurchaseAmount = PurchaseOrder::sum('total_amount');
        $totalCompletedPurchases = PurchaseOrder::where('status', 'completed')->sum('total_amount');
        $totalPendingPurchases = PurchaseOrder::where('status', '!=', 'completed')->sum('total_amount');


        // Cash payments total
        $totalCash = Payment::where('payment_type', 'cash')->sum('cash_amount');

        // Card payments total
        $totalCard = Payment::where('payment_type', 'card')->sum('card_amount');

        // Completed payments
        $completedPayments = Payment::where('payment_status', 'completed')->sum('amount_received');

        // Pending payments
        $pendingPayments = Payment::where('payment_status', 'pending')->sum('amount_received');




        // ✅ 1. Total sales (sum of all completed order amounts)
        $totalSales = PosOrder::where('status', 'completed')->sum('total_amount');

        // ✅ 2. Pending orders total (sum of pending order amounts)
        $pendingSales = PosOrder::where('status', 'pending')->sum('total_amount');
        // ✅ 3. Payment Counts Summaries
        $paymentSums = $this->calculatePayments();
        // ✅ 4. Order Counts Summaries
        $orderCounts = $this->calculateOrderCounts();
        // ✅ 4. Completed orders count
        $completedOrders = PosOrder::where('status', 'completed')->count();

        // ✅ 5. Pending orders count
        $pendingOrders = PosOrder::where('status', 'pending')->count();

        // ✅ 6. Today’s sales (filter by today's date)
        $todaySales = PosOrder::whereDate('order_date', today())
            ->where('status', 'completed')
            ->sum('total_amount');

        // ✅ 7. This month’s sales
        $monthSales = PosOrder::whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->where('status', 'completed')
            ->sum('total_amount');

        // ✅ 8. Average order value
        $averageOrder = PosOrder::where('status', 'completed')->avg('total_amount');

        // ✅ 9. Total taxes collected
        $totalTax = PosOrder::sum('tax');

        // ✅ 10. Total service charges
        $totalServiceCharges = PosOrder::sum('service_charges');

        // Recent orders - last 10 orders
        $recentItems = PosOrderItem::with('order')
            ->latest('id')
            ->take(10)
            ->get()
            ->map(function ($item) {
                return [
                    'title' => $item->title,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->quantity * $item->price,
                    'order_date' => optional($item->order)->order_date,
                ];
            });

        return Inertia::render('Backend/Dashboard/Index', [
            'inventoryAlerts' => $inventoryAlerts,
            'showPopup' => session('show_inventory_popup', false),
            'totalSuppliers' => $totalSuppliers,
            'totalPurchaseAmount' => $totalPurchaseAmount,
            'totalCompletedPurchases' => $totalCompletedPurchases,
            'totalPendingPurchases' => $totalPendingPurchases,

            'totalCash' => $totalCash,
            'totalCard' => $totalCard,
            'completedPayments' => $completedPayments,
            'pendingPayments' => $pendingPayments,
            // Payments over different time frames
            'totalPayments'      => $paymentSums['totalPayments'],
            'todayPayments'      => $paymentSums['todayPayments'],
            'threeDaysPayments'  => $paymentSums['threeDaysPayments'],
            'sevenDaysPayments'  => $paymentSums['sevenDaysPayments'],
            'monthPayments'      => $paymentSums['monthPayments'],
            'yearPayments'       => $paymentSums['yearPayments'],
            'totalSales' => $totalSales,
            'pendingSales' => $pendingSales,
            // Orders over different time frames
            'totalOrders'        => $orderCounts['totalOrders'],
            'todayOrders'        => $orderCounts['todayOrders'],
            'threeDaysOrders'    => $orderCounts['threeDaysOrders'],
            'sevenDaysOrders'    => $orderCounts['sevenDaysOrders'],
            'yearOrders'         => $orderCounts['yearOrders'],

            'completedOrders' => $completedOrders,
            'pendingOrders' => $pendingOrders,
            'todaySales' => $todaySales,
            'monthSales' => $monthSales,
            'averageOrder' => $averageOrder,
            'totalTax' => $totalTax,
            'totalServiceCharges' => $totalServiceCharges,
            'recentItems' => $recentItems,
        ]);
    }

    protected function calculateOrderCounts()
    {
        return [
            'totalOrders'     => PosOrder::count(),
            'todayOrders'     => PosOrder::whereDate('order_date', today())->count(),
            'threeDaysOrders' => PosOrder::whereBetween('order_date', [
                now()->subDays(2)->startOfDay(), // include today
                now()->endOfDay()
            ])->count(),
            'sevenDaysOrders' => PosOrder::whereBetween('order_date', [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay()
            ])->count(),
            'yearOrders'      => PosOrder::whereBetween('order_date', [
                now()->subYear()->startOfDay(),
                now()->endOfDay()
            ])->count(),
        ];
    }

    protected function calculatePayments()
    {
        return [
            'totalPayments'      => Payment::sum('amount_received'),
            'todayPayments'      => Payment::whereDate('payment_date', today())->sum('amount_received'),
            'threeDaysPayments'  => Payment::whereBetween('payment_date', [
                now()->subDays(2)->startOfDay(), // include today
                now()->endOfDay()
            ])->sum('amount_received'),
            'sevenDaysPayments'  => Payment::whereBetween('payment_date', [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay()
            ])->sum('amount_received'),
            'monthPayments'      => Payment::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount_received'),
            'yearPayments'       => Payment::whereBetween('payment_date', [
                now()->subYear()->startOfDay(),
                now()->endOfDay()
            ])->sum('amount_received'),
        ];
    }
}

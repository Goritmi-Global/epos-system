<?php

namespace App\Services\Shifts;

use App\Models\Shift;
use App\Models\PosOrder;
use App\Models\ShiftDetail;
use App\Models\ShiftInventorySnapshot;
use App\Models\ShiftInventorySnapshotDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ShiftReportService
{
    /**
     * Generate X Report (Mid-Shift Report for OPEN shifts)
     * 
     * @param Shift $shift
     * @return array
     */
    public function generateXReport(Shift $shift): array
    {
        if ($shift->status !== 'open') {
            throw new \Exception('X Report can only be generated for open shifts');
        }

        // Get all orders for this shift
        $orders = PosOrder::where('shift_id', $shift->id)
            ->where('status', 'paid')
            ->get();

        // Sales Summary
        $salesSummary = $this->calculateSalesSummary($orders);

        // Cash Summary
        $cashSummary = $this->calculateCashSummary($shift, $salesSummary);

        // Payment Methods
        $paymentMethods = $this->getPaymentMethods($orders);

        // Sales by User
        $salesByUser = $this->getSalesByUser($shift);

        // Top Selling Items
        $topItems = $this->getTopSellingItems($orders, 10);

        return [
            'shift_id' => $shift->id,
            'started_by' => $shift->starter?->name ?? 'N/A',
            'start_time' => $shift->start_time,
            'opening_cash' => $shift->opening_cash,
            'status' => $shift->status,
            'generated_at' => now(),
            'sales_summary' => $salesSummary,
            'cash_summary' => $cashSummary,
            'payment_methods' => $paymentMethods,
            'sales_by_user' => $salesByUser,
            'top_items' => $topItems,
        ];
    }

    /**
     * Generate Z Report (End-of-Shift Report for CLOSED shifts)
     * 
     * @param Shift $shift
     * @return array
     */
  public function generateZReport(Shift $shift): array
    {
        if ($shift->status !== 'closed') {
            throw new \Exception('Z Report can only be generated for closed shifts');
        }

        // Get all orders for this shift
        $orders = PosOrder::where('shift_id', $shift->id)
            ->where('status', 'paid')
            ->with('items')
            ->get();

        // Calculate shift duration
        $duration = $this->calculateDuration($shift->start_time, $shift->end_time);

        // Get all report sections
        $salesSummary = $this->calculateSalesSummary($orders);
        $cashReconciliation = $this->calculateCashReconciliation($shift, $salesSummary);
        $paymentMethods = $this->getPaymentMethods($orders);
        $salesByUser = $this->getSalesByUser($shift);
        $topItems = $this->getTopSellingItems($orders, 10);
        $stockMovement = $this->getStockMovement($shift);
        
        // New sections matching client template
        $venueSales = $this->getVenueSales($orders);
        $dispatchSales = $this->getDispatchSales($orders);
        $menuCategorySummary = $this->getMenuCategorySummary($orders);
        $coversSummary = $this->getCoversSummary($orders);
        $discountsSummary = $this->getDiscountsSummary($orders);
        $chargesSummary = $this->getChargesSummary($orders);
        $cancelledItems = $this->getCancelledItems($shift);

        return [
            'shift_id' => $shift->id,
            'started_by' => $shift->starter?->name ?? 'N/A',
            'start_time' => $shift->start_time,
            'ended_by' => $shift->ender?->name ?? 'N/A',
            'end_time' => $shift->end_time,
            'duration' => $duration,
            'status' => $shift->status,
            'opening_cash' => $shift->opening_cash,
            'sales_summary' => $salesSummary,
            'cash_reconciliation' => $cashReconciliation,
            'payment_methods' => $paymentMethods,
            'venue_sales' => $venueSales,
            'dispatch_sales' => $dispatchSales,
            'menu_category_summary' => $menuCategorySummary,
            'covers_summary' => $coversSummary,
            'discounts_summary' => $discountsSummary,
            'charges_summary' => $chargesSummary,
            'cancelled_items' => $cancelledItems,
            'sales_by_user' => $salesByUser,
            'top_items' => $topItems,
            'stock_movement' => $stockMovement,
        ];
    }

    /**
     * Calculate sales summary from orders
     */
    private function calculateSalesSummary(Collection $orders): array
    {
        $subtotal = $orders->sum('sub_total');
        $totalTax = $orders->sum('tax');
        $totalDiscount = $orders->sum('sales_discount');
        $totalSales = $orders->sum('total_amount');
        $totalOrders = $orders->count();
        
        // Calculate charges (delivery + service)
        $totalCharges = $orders->sum(function ($order) {
            return ($order->delivery_charge ?? 0) + ($order->service_charge ?? 0);
        });

        return [
            'total_orders' => $totalOrders,
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'total_discount' => $totalDiscount,
            'total_charges' => $totalCharges,
            'total_sales' => $totalSales,
            'avg_order_value' => $totalOrders > 0 ? $totalSales / $totalOrders : 0,
        ];
    }

    /**
     * Calculate cash reconciliation for Z Report
     */
    private function calculateCashReconciliation(Shift $shift, array $salesSummary): array
    {
        $openingCash = $shift->opening_cash;
        
        // Only count cash sales
        $cashSales = PosOrder::where('shift_id', $shift->id)
            ->where('status', 'paid')
            ->sum('total_amount');
        
        $cashExpenses = $shift->cash_expenses ?? 0;
        $cashTransfers = $shift->cash_transfers ?? 0;
        $cashChanged = $shift->cash_changed ?? 0;
        $cashRefunds = $shift->cash_refunds ?? 0;
        
        $expectedCash = $openingCash + $cashSales - $cashExpenses - $cashTransfers - $cashChanged + $cashRefunds;
        $actualCash = $shift->closing_cash ?? $expectedCash;
        $variance = $actualCash - $expectedCash;
        $variancePercentage = $expectedCash > 0 ? round(($variance / $expectedCash) * 100, 2) : 0;

        return [
            'opening_cash' => $openingCash,
            'cash_expenses' => $cashExpenses,
            'cash_transfers' => $cashTransfers,
            'cash_changed' => $cashChanged,
            'cash_sales' => $cashSales,
            'cash_refunds' => $cashRefunds,
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'variance' => $variance,
            'variance_percentage' => $variancePercentage,
        ];
    }

    /**
     * Get payment methods breakdown
     */
    private function getPaymentMethods(Collection $orders): array
    {
        return $orders->groupBy('payment_method')
            ->map(function ($group, $method) {
                $receipts = $group->sum('total_amount');
                $refunds = $group->sum('refund_amount') ?? 0;
                
                return [
                    'method' => ucfirst($method),
                    'receipts' => $receipts,
                    'refunds' => $refunds,
                    'net' => $receipts - $refunds,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get venue sales (sales channels like Property, UberEats, Flipdish)
     */
    private function getVenueSales(Collection $orders): array
    {
        // Group by venue/channel (adjust based on your schema)
        return $orders->groupBy(function ($order) {
            // Assuming you have a 'source' or 'venue' field
            return $order->source ?? $order->venue ?? 'Property';
        })
        ->map(function ($group, $venue) {
            return [
                'venue' => $venue,
                'count' => $group->count(),
                'amount' => $group->sum('total_amount'),
            ];
        })
        ->values()
        ->toArray();
    }

    /**
     * Get dispatch sales (by order type: Collection, Delivery, DineIn, TakeAway)
     */
    private function getDispatchSales(Collection $orders): array
    {
        return $orders->groupBy('order_type')
            ->map(function ($group, $type) {
                return [
                    'type' => ucfirst($type),
                    'count' => $group->count(),
                    'amount' => $group->sum('total_amount'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get menu categories summary
     */
    private function getMenuCategorySummary(Collection $orders): array
    {
        $categorySales = [];
        
        foreach ($orders as $order) {
            $items = $order->items ?? [];
            
            foreach ($items as $item) {
                // Assuming item has category relationship or category field
                $category = $item->menuItem->category->name ?? $item->category ?? 'Uncategorized';
                $quantity = $item->quantity ?? 1;
                $amount = ($item->price ?? 0) * $quantity;
                
                if (!isset($categorySales[$category])) {
                    $categorySales[$category] = [
                        'category' => $category,
                        'count' => 0,
                        'amount' => 0,
                    ];
                }
                
                $categorySales[$category]['count'] += $quantity;
                $categorySales[$category]['amount'] += $amount;
            }
        }
        
        return array_values($categorySales);
    }

    /**
     * Get covers summary (number of customers/covers served)
     */
    private function getCoversSummary(Collection $orders): array
    {
        $totalCovers = $orders->sum('covers') ?? $orders->sum('guest_count') ?? 0;
        $ordersWithCovers = $orders->where('covers', '>', 0)->count();
        
        return [
            'total_covers' => $totalCovers,
            'avg_revenue_per_cover' => $totalCovers > 0 
                ? $orders->sum('total_amount') / $totalCovers 
                : 0,
        ];
    }

    /**
     * Get discounts summary
     */
    private function getDiscountsSummary(Collection $orders): array
    {
        return $orders->filter(function ($order) {
            return ($order->discount_amount ?? 0) > 0;
        })
        ->groupBy('discount_type')
        ->map(function ($group, $type) {
            return [
                'type' => $type ?: 'General Discount',
                'count' => $group->count(),
                'amount' => $group->sum('discount_amount'),
            ];
        })
        ->values()
        ->toArray();
    }

    /**
     * Get charges summary (delivery & service charges)
     */
    private function getChargesSummary(Collection $orders): array
    {
        $charges = [];
        
        // Delivery charges
        $deliveryOrders = $orders->filter(fn($o) => ($o->delivery_charge ?? 0) > 0);
        if ($deliveryOrders->count() > 0) {
            $charges[] = [
                'scheme' => 'Delivery.Charge',
                'count' => $deliveryOrders->count(),
                'amount' => $deliveryOrders->sum('delivery_charge'),
                'tax' => $deliveryOrders->sum('delivery_tax') ?? 0,
            ];
        }
        
        // Service charges
        $serviceOrders = $orders->filter(fn($o) => ($o->service_charge ?? 0) > 0);
        if ($serviceOrders->count() > 0) {
            $charges[] = [
                'scheme' => 'Service.Charge',
                'count' => $serviceOrders->count(),
                'amount' => $serviceOrders->sum('service_charge'),
                'tax' => $serviceOrders->sum('service_tax') ?? 0,
            ];
        }
        
        return $charges;
    }

    /**
     * Get cancelled items during the shift
     */
    private function getCancelledItems(Shift $shift): array
    {
        // Get cancelled orders or items
        $cancelledOrders = PosOrder::where('shift_id', $shift->id)
            ->where('status', 'cancelled')
            ->with('items')
            ->get();
        
        if ($cancelledOrders->isEmpty()) {
            return [];
        }
        
        $cancelledData = [];
        
        foreach ($cancelledOrders as $order) {
            foreach ($order->items as $item) {
                $cancelledData[] = [
                    'item_name' => $item->menuItem->name ?? $item->title ?? 'Unknown',
                    'quantity' => $item->quantity ?? 1,
                    'amount' => $item->price * ($item->quantity ?? 1),
                    'cancelled_at' => $order->cancelled_at ?? $order->updated_at,
                ];
            }
        }
        
        return $cancelledData;
    }

    /**
     * Get sales breakdown by user
     */
    private function getSalesByUser(Shift $shift): array
    {
        $shiftDetails = ShiftDetail::where('shift_id', $shift->id)
            ->with('user')
            ->get();

        return $shiftDetails->map(function ($detail) {
            $ordersCount = PosOrder::where('shift_id', $detail->shift_id)
                ->where('user_id', $detail->user_id)
                ->where('status', 'paid')
                ->count();

            $totalSales = PosOrder::where('shift_id', $detail->shift_id)
                ->where('user_id', $detail->user_id)
                ->where('status', 'paid')
                ->sum('total_amount');

            return [
                'user_name' => $detail->user?->name ?? 'Unknown',
                'role' => $detail->role,
                'orders_count' => $ordersCount,
                'total_sales' => $totalSales,
            ];
        })->toArray();
    }

    /**
     * Get top selling items
     */
    private function getTopSellingItems(Collection $orders, int $limit = 10): array
    {
        $itemSales = [];

        foreach ($orders as $order) {
            $items = $order->items ?? [];

            foreach ($items as $item) {
                $itemId = $item->product_id ?? $item->menu_item_id ?? $item->id ?? null;
                $itemName = $item->menuItem->name ?? $item->title ?? 'Unknown';
                $quantity = $item->quantity ?? 0;
                $price = $item->price ?? 0;
                $revenue = $quantity * $price;

                if (!isset($itemSales[$itemId])) {
                    $itemSales[$itemId] = [
                        'id' => $itemId,
                        'name' => $itemName,
                        'total_qty' => 0,
                        'total_revenue' => 0,
                    ];
                }

                $itemSales[$itemId]['total_qty'] += $quantity;
                $itemSales[$itemId]['total_revenue'] += $revenue;
            }
        }

        usort($itemSales, function ($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });

        return array_slice($itemSales, 0, $limit);
    }

    /**
     * Get stock movement for Z Report
     */
    private function getStockMovement(Shift $shift): array
    {
        $startSnapshot = ShiftInventorySnapshot::where('shift_id', $shift->id)
            ->where('type', 'started')
            ->first();

        $endSnapshot = ShiftInventorySnapshot::where('shift_id', $shift->id)
            ->where('type', 'ended')
            ->first();

        if (!$startSnapshot || !$endSnapshot) {
            return [];
        }

        $startDetails = ShiftInventorySnapshotDetail::where('snap_id', $startSnapshot->id)
            ->with('item')
            ->get();

        $endDetails = ShiftInventorySnapshotDetail::where('snap_id', $endSnapshot->id)
            ->with('item')
            ->get();

        $stockMovement = [];

        foreach ($startDetails as $startDetail) {
            $endDetail = $endDetails->firstWhere('item_id', $startDetail->item_id);

            $startStock = $startDetail->stock_quantity ?? 0;
            $endStock = $endDetail?->stock_quantity ?? 0;
            $sold = $startStock - $endStock;

            $stockMovement[] = [
                'item_id' => $startDetail->item_id,
                'item_name' => $startDetail->item?->name ?? 'Unknown',
                'start_stock' => $startStock,
                'end_stock' => $endStock,
                'sold' => $sold,
            ];
        }

        return $stockMovement;
    }

    /**
     * Calculate shift duration
     */
    private function calculateDuration($startTime, $endTime): string
    {
        if (!$startTime || !$endTime) {
            return 'N/A';
        }

        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);
        $diff = $end->diff($start);

        return sprintf(
            '%02d:%02d:%02d',
            $diff->h + ($diff->days * 24),
            $diff->i,
            $diff->s
        );
    }
}
<?php

namespace App\Services\Shifts;

use App\Models\Shift;
use App\Models\PosOrder;
use App\Models\ShiftDetail;
use App\Models\ShiftInventorySnapshot;
use App\Models\ShiftInventorySnapshotDetail;
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
            ->get();

        // Sales Summary
        $salesSummary = $this->calculateSalesSummary($orders);

        // Cash Reconciliation
        $cashReconciliation = $this->calculateCashReconciliation($shift, $salesSummary);

        // Payment Methods
        $paymentMethods = $this->getPaymentMethods($orders);

        // Sales by User
        $salesByUser = $this->getSalesByUser($shift);

        // Top Selling Items
        $topItems = $this->getTopSellingItems($orders, 10);

        // Stock Movement
        $stockMovement = $this->getStockMovement($shift);

        // Calculate shift duration
        $duration = $this->calculateDuration($shift->start_time, $shift->end_time);

        return [
            'shift_id' => $shift->id,
            'started_by' => $shift->starter?->name ?? 'N/A',
            'start_time' => $shift->start_time,
            'ended_by' => $shift->ender?->name ?? 'N/A',
            'end_time' => $shift->end_time,
            'duration' => $duration,
            'status' => $shift->status,
            'sales_summary' => $salesSummary,
            'cash_reconciliation' => $cashReconciliation,
            'payment_methods' => $paymentMethods,
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
        $totalDiscount = $orders->sum('discount_amount');
        $totalSales = $orders->sum('total_amount');
        $totalOrders = $orders->count();

        return [
            'total_orders' => $totalOrders,
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'total_discount' => $totalDiscount,
            'total_sales' => $totalSales,
            'avg_order_value' => $totalOrders > 0 ? $totalSales / $totalOrders : 0,
        ];
    }

    /**
     * Calculate cash summary for X Report
     */
    private function calculateCashSummary(Shift $shift, array $salesSummary): array
    {
        $openingCash = $shift->opening_cash;
        $cashSales = $salesSummary['total_sales']; // Assuming all sales are cash
        $expectedCash = $openingCash + $cashSales;

        return [
            'opening_cash' => $openingCash,
            'cash_sales' => $cashSales,
            'expected_cash' => $expectedCash,
        ];
    }

    /**
     * Calculate cash reconciliation for Z Report
     */
    private function calculateCashReconciliation(Shift $shift, array $salesSummary): array
    {
        $openingCash = $shift->opening_cash;
        $cashSales = $salesSummary['total_sales'];
        $expectedCash = $openingCash + $cashSales;
        $actualCash = $shift->closing_cash ?? $expectedCash;
        $variance = $actualCash - $expectedCash;
        $variancePercentage = $expectedCash > 0 ? round(($variance / $expectedCash) * 100, 2) : 0;

        return [
            'opening_cash' => $openingCash,
            'cash_sales' => $cashSales,
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
                return [
                    'method' => ucfirst($method),
                    'count' => $group->count(),
                    'total' => $group->sum('total_amount'),
                ];
            })
            ->values()
            ->toArray();
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
        // Group order items by product and calculate totals
        $itemSales = [];

        foreach ($orders as $order) {
            // Assuming order has items relationship or items stored in JSON
            $items = $order->items ?? []; // Adjust based on your model structure

            foreach ($items as $item) {
                $itemId = $item['product_id'] ?? $item['id'] ?? null;
                $itemName = $item['title'] ?? 'Unknown';
                $quantity = $item['quantity'] ?? 0;
                $price = $item['price'] ?? 0;
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

        // Sort by revenue descending and limit
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
        // Get starting inventory snapshot
        $startSnapshot = ShiftInventorySnapshot::where('shift_id', $shift->id)
            ->where('type', 'started')
            ->first();

        // Get ending inventory snapshot
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
<?php

namespace App\Services\POS;

use App\Models\KitchenOrder;

class KotOrderService
{
    public function getTodaysOrders()
    {
        $today = now()->toDateString();

        return KitchenOrder::with([
            'items',
            'posOrderType.order.payment',
            'posOrderType.order.items',
        ])->whereDate('order_date', $today)->get();
    }

    public function getOrderStatistics(array $filters = [])
    {
        $query = KitchenOrder::with(['items', 'posOrderType']);
        $filtersWithoutStatus = $filters;
        unset($filtersWithoutStatus['status']);

        $this->applyFilters($query, $filtersWithoutStatus);
        $allOrders = $query->get();
        $statusCounts = [
            'All' => $allOrders->count(),
            'Waiting' => $allOrders->where('status', 'Waiting')->count(),
            'In Progress' => $allOrders->where('status', 'In Progress')->count(),
            'Done' => $allOrders->where('status', 'Done')->count(),
            'Cancelled' => $allOrders->where('status', 'Cancelled')->count(),
        ];

        // Calculate KPI metrics from ALL records (respecting other filters)
        $tables = $allOrders->pluck('posOrderType.table_number')
            ->filter()
            ->unique()
            ->count();

        $totalItems = $allOrders->sum(function ($order) {
            return $order->items->count();
        });

        $pendingItems = $allOrders->sum(function ($order) {
            return $order->items->where('status', 'Waiting')->count();
        });

        $inProgressItems = $allOrders->sum(function ($order) {
            return $order->items->where('status', 'In Progress')->count();
        });

        $doneItems = $allOrders->sum(function ($order) {
            return $order->items->where('status', 'Done')->count();
        });

        $cancelledItems = $allOrders->sum(function ($order) {
            return $order->items->where('status', 'Cancelled')->count();
        });

        return [
            'status_counts' => $statusCounts,
            'kpis' => [
                'total_tables' => $tables,
                'total_items' => $totalItems,
                'pending_items' => $pendingItems,
                'in_progress_items' => $inProgressItems,
                'done_items' => $doneItems,
                'cancelled_items' => $cancelledItems,
            ],
        ];
    }

    public function getAllOrders(array $filters = [])
    {
        $query = KitchenOrder::with([
            'items',
            'posOrderType.order.payment',
            'posOrderType.order.items',
        ]);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $this->applySorting($query, $filters);

        // ✅ CHECK IF THIS IS AN EXPORT REQUEST
        if (! empty($filters['export']) && $filters['export'] === 'all') {
            $allKots = $query->get();

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $allKots,
                $allKots->count(),
                $allKots->count(),
                1,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
        }

        // ✅ ALWAYS USE PROPER PAGINATION (removed the hasFilters check)
        $perPage = $filters['per_page'] ?? 10;
        $paginator = $query->paginate($perPage);

        return $paginator;
    }


    private function applyFilters($query, array $filters)
    {
        if (! empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('items', function ($itemQuery) use ($filters) {
                    $itemQuery->where('item_name', 'like', "%{$filters['q']}%")
                        ->orWhere('variant_name', 'like', "%{$filters['q']}%");
                })
                    ->orWhereHas('posOrderType.order', function ($orderQuery) use ($filters) {
                        $orderQuery->where('customer_name', 'like', "%{$filters['q']}%")
                            ->orWhere('id', 'like', "%{$filters['q']}%");
                    });
            });
        }
        if (! empty($filters['order_type'])) {
            $query->whereHas('posOrderType', function ($q) use ($filters) {
                $q->where('order_type', $filters['order_type']);
            });
        }
        if (! empty($filters['status'])) {
            $query->where('kitchen_orders.status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('kitchen_orders.order_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('kitchen_orders.order_date', '<=', $filters['date_to']);
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, array $filters)
    {
        if (! empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'date_desc':
                    $query->orderBy('kitchen_orders.order_date', 'desc');
                    break;
                case 'date_asc':
                    $query->orderBy('kitchen_orders.order_date', 'asc');
                    break;

                case 'order_desc':
                    // Sort by Amount Received (High to Low)
                    $query->leftJoin('pos_order_types', 'kitchen_orders.pos_order_type_id', '=', 'pos_order_types.id')
                        ->leftJoin('pos_orders', 'pos_order_types.pos_order_id', '=', 'pos_orders.id')
                        ->leftJoin('payments', 'pos_orders.id', '=', 'payments.order_id')
                        ->orderByRaw('COALESCE(payments.amount_received, 0) DESC')
                        ->select('kitchen_orders.*'); // Important: select only kitchen_orders columns
                    break;

                case 'order_asc':
                    // Sort by Amount Received (Low to High)
                    $query->leftJoin('pos_order_types', 'kitchen_orders.pos_order_type_id', '=', 'pos_order_types.id')
                        ->leftJoin('pos_orders', 'pos_order_types.pos_order_id', '=', 'pos_orders.id')
                        ->leftJoin('payments', 'pos_orders.id', '=', 'payments.order_id')
                        ->orderByRaw('COALESCE(payments.amount_received, 0) ASC')
                        ->select('kitchen_orders.*');
                    break;

                default:
                    $query->orderBy('kitchen_orders.id', 'desc');
                    break;
            }
        } else {
            $query->orderBy('kitchen_orders.id', 'desc');
        }
    }
}

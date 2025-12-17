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

    public function getAllOrders(array $filters = [])
    {
        $query = KitchenOrder::with([
            'items',
            'posOrderType.order.payment',
            'posOrderType.order.items',
        ]);

        // Apply search filter
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

        // Apply order type filter
        if (! empty($filters['order_type'])) {
            $query->whereHas('posOrderType', function ($q) use ($filters) {
                $q->where('order_type', $filters['order_type']);
            });
        }

        // Apply status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply date range filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('order_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('order_date', '<=', $filters['date_to']);
        }

        // Apply sorting
        if (! empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'date_desc':
                    $query->orderBy('order_date', 'desc');
                    break;
                case 'date_asc':
                    $query->orderBy('order_date', 'asc');
                    break;
                case 'item_asc':
                    $query->orderBy('id', 'asc');
                    break;
                case 'item_desc':
                    $query->orderBy('id', 'desc');
                    break;
                case 'order_asc':
                    $query->orderBy('id', 'asc');
                    break;
                case 'order_desc':
                    $query->orderBy('id', 'desc');
                    break;
                default:
                    $query->orderBy('id', 'desc');
                    break;
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        // ✅ CHECK IF THIS IS AN EXPORT REQUEST
        if (! empty($filters['export']) && $filters['export'] === 'all') {
            // Return all records without pagination
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

        // Original pagination logic
        $searchQuery = trim($filters['q'] ?? '');
        $hasSearch = ! empty($searchQuery);
        $hasOrderType = ! empty($filters['order_type']);
        $hasStatus = ! empty($filters['status']);
        $hasDateRange = ! empty($filters['date_from']) || ! empty($filters['date_to']);
        $hasSorting = ! empty($filters['sort_by']);

        $hasAnyFilter = $hasSearch || $hasOrderType || $hasStatus
                     || $hasDateRange || $hasSorting;

        if ($hasAnyFilter) {
            $allKots = $query->get();
            $total = $allKots->count();

            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $allKots,
                $total,
                max(1, $total),
                1,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
        } else {
            $perPage = $filters['per_page'] ?? 10;
            $paginator = $query->paginate($perPage);
        }

        return $paginator;
    }
}

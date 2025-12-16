<?php

namespace App\Services\POS;

use App\Models\Order;
use App\Models\PosOrder;

class OrdersService
{
    public function list(array $filters = [])
    {
        return Order::query()
            ->when(
                $filters['q'] ?? null,
                fn ($q, $v) => $q->where('order_no', 'like', "%$v%")
            )
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function find(int|string $id): Order
    {
        /** @var Order $order */
        $order = Order::query()->findOrFail($id);

        return $order;
    }

    public function getAllOrders(array $filters = [])
    {
        $query = PosOrder::with(['type', 'payment', 'user', 'items', 'promo']);

        // ✅ Search filter
        if (! empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('id', 'like', "%{$filters['q']}%")
                    ->orWhere('customer_name', 'like', "%{$filters['q']}%")
                    ->orWhereHas('type', function ($typeQuery) use ($filters) {
                        $typeQuery->where('table_number', 'like', "%{$filters['q']}%")
                            ->orWhere('order_type', 'like', "%{$filters['q']}%");
                    });
            });
        }

        // ✅ Order Type filter
        if (! empty($filters['order_type'])) {
            $query->whereHas('type', function ($q) use ($filters) {
                $q->where('order_type', $filters['order_type']);
            });
        }

        // ✅ Payment Type filter
        if (! empty($filters['payment_type'])) {
            $query->whereHas('payment', function ($q) use ($filters) {
                $q->where('payment_type', $filters['payment_type']);
            });
        }

        // ✅ Status filter
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // ✅ Price range filter
        if (! empty($filters['price_min'])) {
            $query->where('total_amount', '>=', $filters['price_min']);
        }
        if (! empty($filters['price_max'])) {
            $query->where('total_amount', '<=', $filters['price_max']);
        }

        // ✅ Date range filter
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // ✅ Sorting
        if (! empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'date_desc':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'date_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('total_amount', 'desc');
                    break;
                case 'price_asc':
                    $query->orderBy('total_amount', 'asc');
                    break;
                case 'customer_asc':
                    $query->orderBy('customer_name', 'asc');
                    break;
                case 'customer_desc':
                    $query->orderBy('customer_name', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // ✅ Check if ANY filter is applied
        $searchQuery = trim($filters['q'] ?? '');
        $hasSearch = ! empty($searchQuery);
        $hasOrderType = ! empty($filters['order_type']);
        $hasPaymentType = ! empty($filters['payment_type']);
        $hasStatus = ! empty($filters['status']);
        $hasPriceRange = ! empty($filters['price_min']) || ! empty($filters['price_max']);
        $hasDateRange = ! empty($filters['date_from']) || ! empty($filters['date_to']);
        $hasSorting = ! empty($filters['sort_by']);

        $hasAnyFilter = $hasSearch || $hasOrderType || $hasPaymentType
                     || $hasStatus || $hasPriceRange || $hasDateRange || $hasSorting;

        \Log::info('Order Filter Debug', [
            'hasAnyFilter' => $hasAnyFilter,
            'filters' => $filters,
        ]);

        if ($hasAnyFilter) {
            // ✅ FILTER MODE: Get all matching records
            $allOrders = $query->get();
            $total = $allOrders->count();

            \Log::info('Filter Mode (Orders)', ['total_found' => $total]);

            // ✅ Create single-page paginator
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $allOrders,
                $total,
                $total > 0 ? $total : 1,
                1,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
        } else {
            // ✅ NO FILTERS: Normal pagination
            \Log::info('Pagination Mode (Orders)');

            $perPage = $filters['per_page'] ?? 10;
            $paginator = $query->paginate($perPage);
        }

        return $paginator;
    }
}

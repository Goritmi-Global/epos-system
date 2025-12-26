<?php

namespace App\Services\POS;

use App\Models\Payment;

class PaymentService
{
    public function list(array $filters = [])
    {
        // Base query for unique order IDs that have payments
        $orderQuery = Payment::query()
            ->select('payments.order_id')
            ->selectRaw('MAX(payments.payment_date) as latest_payment_date')
            ->groupBy('payments.order_id');

        // Apply filters to the order query
        if (! empty($filters['q'])) {
            $search = $filters['q'];
            $orderQuery->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('payments.order_id', $search)
                      ->orWhere('payments.order_id', 'like', "{$search}%");
                } else {
                    $q->where('payments.payment_type', 'like', "%{$search}%")
                      ->orWhere('payments.code', 'like', "%{$search}%")
                      ->orWhereHas('order', function ($oQ) use ($search) {
                          $oQ->where('customer_name', 'like', "%{$search}%");
                      });
                }
            });
        }

        if (! empty($filters['payment_type'])) {
            $orderQuery->where('payments.payment_type', $filters['payment_type']);
        }

        if (! empty($filters['date_from'])) {
            $orderQuery->whereDate('payments.payment_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $orderQuery->whereDate('payments.payment_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['price_min']) || ! empty($filters['price_max'])) {
            $orderQuery->whereHas('order', function($q) use ($filters) {
                if (! empty($filters['price_min'])) $q->where('total_amount', '>=', $filters['price_min']);
                if (! empty($filters['price_max'])) $q->where('total_amount', '<=', $filters['price_max']);
            });
        }

        // Sorting
        $sortBy = (isset($filters['sort_by']) && $filters['sort_by'] !== '') ? $filters['sort_by'] : 'date_desc';
        
        switch ($sortBy) {
            case 'date_asc':
                $orderQuery->orderByRaw('MAX(payments.payment_date) ASC');
                break;
            case 'order_desc':
                $orderQuery->orderBy('payments.order_id', 'desc');
                break;
            case 'order_asc':
                $orderQuery->orderBy('payments.order_id', 'asc');
                break;
            case 'amount_desc':
            case 'amount_asc':
                $orderQuery->leftJoin('pos_orders', 'payments.order_id', '=', 'pos_orders.id')
                    ->orderByRaw('MAX(pos_orders.total_amount) ' . ($sortBy === 'amount_desc' ? 'desc' : 'asc'));
                break;
            default:
                $orderQuery->orderByRaw('MAX(payments.payment_date) DESC');
                break;
        }

        // EXPORT ALL
        if (! empty($filters['export']) && $filters['export'] === 'all') {
            $allOrderIds = (clone $orderQuery)->get()->pluck('order_id');
            $total = $allOrderIds->count();
            $perPage = $total > 0 ? $total : 10;
            $currentPage = 1;
        } else {
            $perPage = $filters['per_page'] ?? 10;
            $paginatedOrders = $orderQuery->paginate($perPage);
            $allOrderIds = collect($paginatedOrders->items())->pluck('order_id');
            $total = $paginatedOrders->total();
            $currentPage = $paginatedOrders->currentPage();
        }

        if ($allOrderIds->isEmpty()) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]), 0, $perPage, 1, ['path' => request()->url()]
            );
        }

        // Fetch all unique orders and their payments
        $paginatedItems = \App\Models\PosOrder::with([
            'promo',
            'items',
            'type',
            'payments.user',
        ])
        ->whereIn('id', $allOrderIds)
        ->orderByRaw("FIELD(id, " . $allOrderIds->implode(',') . ")")
        ->get();

        // Create the paginator using orders as items
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function getPaymentStats(array $filters = [])
    {
        $query = Payment::query();

        if (! empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('payments.order_id', $search)
                      ->orWhere('payments.order_id', 'like', "{$search}%");
                } else {
                    $q->where('payments.payment_type', 'like', "%{$search}%")
                      ->orWhere('payments.code', 'like', "%{$search}%")
                      ->orWhereHas('order', function ($oQ) use ($search) {
                            $oQ->where('customer_name', 'like', "%{$search}%");
                      });
                }
            });
        }

        if (! empty($filters['payment_type'])) {
            $query->where('payments.payment_type', $filters['payment_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('payments.payment_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('payments.payment_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['price_min']) || ! empty($filters['price_max'])) {
            $query->whereHas('order', function($q) use ($filters) {
                if (! empty($filters['price_min'])) $q->where('total_amount', '>=', $filters['price_min']);
                if (! empty($filters['price_max'])) $q->where('total_amount', '<=', $filters['price_max']);
            });
        }

        // Unique Order IDs matching the criteria
        $matchedOrderIds = (clone $query)->distinct()->pluck('payments.order_id');
        $totalOrders = $matchedOrderIds->count();

        // Today's Orders among matched orders
        $today = now()->startOfDay();
        $todaysOrders = (clone $query)->whereDate('payments.payment_date', '>=', $today)
            ->distinct()
            ->pluck('payments.order_id')
            ->count();

        // Total Amount (Sum of unique order totals)
        $totalAmount = \App\Models\PosOrder::whereIn('id', $matchedOrderIds)->sum('total_amount');

        return [
            'total_payments' => $totalOrders,
            'todays_payments' => $todaysOrders,
            'total_amount' => $totalAmount,
        ];
    }

    public function create(array $data): Payment
    {
        if (! isset($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        return Payment::create($data);
    }
}

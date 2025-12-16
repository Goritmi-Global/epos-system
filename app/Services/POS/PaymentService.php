<?php

namespace App\Services\POS;

use App\Models\Payment;

class PaymentService
{
    // public function list(array $filters = [])
    // {
    //     return Payment::query()
    //         ->when($filters['method'] ?? null, fn($q, $v) => $q->where('method', $v))
    //         ->orderByDesc('paid_at')
    //         ->paginate(20)
    //         ->withQueryString();
    // }

    public function list(array $filters = [])
    {
        // ✅ Load order with all necessary relationships
        $query = Payment::with([
            'order.promo',           // Load promo relationship
            'order.items',           // Load order items
            'order.type',            // Load order type (for table info)
            'user',                   // Load user who processed payment
        ]);

        // ✅ Search filter
        if (! empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('id', 'like', "%{$filters['q']}%")
                    ->orWhere('payment_type', 'like', "%{$filters['q']}%")
                    ->orWhere('code', 'like', "%{$filters['q']}%")
                    ->orWhereHas('order', function ($orderQuery) use ($filters) {
                        $orderQuery->where('id', 'like', "%{$filters['q']}%")
                            ->orWhere('customer_name', 'like', "%{$filters['q']}%");
                    });
            });
        }

        // ✅ Payment Type filter
        if (! empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        // ✅ Date range filter
        if (! empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        // ✅ Price range filter (amount_received)
        if (! empty($filters['price_min'])) {
            $query->where('amount_received', '>=', $filters['price_min']);
        }
        if (! empty($filters['price_max'])) {
            $query->where('amount_received', '<=', $filters['price_max']);
        }

        // ✅ Sorting
        if (! empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'date_desc':
                    $query->orderBy('payment_date', 'desc');
                    break;
                case 'date_asc':
                    $query->orderBy('payment_date', 'asc');
                    break;
                case 'amount_desc':
                    $query->orderBy('amount_received', 'desc');
                    break;
                case 'amount_asc':
                    $query->orderBy('amount_received', 'asc');
                    break;
                case 'order_desc':
                    $query->orderBy('order_id', 'desc');
                    break;
                case 'order_asc':
                    $query->orderBy('order_id', 'asc');
                    break;
                default:
                    $query->orderBy('payment_date', 'desc');
                    break;
            }
        } else {
            $query->orderBy('payment_date', 'desc');
        }

        // ✅ Check if ANY filter is applied
        $searchQuery = trim($filters['q'] ?? '');
        $hasSearch = ! empty($searchQuery);
        $hasPaymentType = ! empty($filters['payment_type']);
        $hasDateRange = ! empty($filters['date_from']) || ! empty($filters['date_to']);
        $hasPriceRange = ! empty($filters['price_min']) || ! empty($filters['price_max']);
        $hasSorting = ! empty($filters['sort_by']);

        $hasAnyFilter = $hasSearch || $hasPaymentType || $hasDateRange
                     || $hasPriceRange || $hasSorting;

        if ($hasAnyFilter) {
            // ✅ FILTER MODE: Get all matching payments
            $allPayments = $query->get();
            $total = $allPayments->count();

            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $allPayments,
                $total,
                max(1, $total),
                1,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
        } else {
            // ✅ NO FILTERS: Normal pagination
            $perPage = $filters['per_page'] ?? 10;
            $paginator = $query->paginate($perPage);
        }

        return $paginator;
    }

    public function create(array $data): Payment
    {
        if (! isset($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        return Payment::create($data);
    }
}

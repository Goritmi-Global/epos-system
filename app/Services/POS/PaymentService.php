<?php

namespace App\Services\POS;

use App\Models\Payment;

class PaymentService
{
    public function list(array $filters = [])
    {
        return Payment::query()
            ->when($filters['method'] ?? null, fn($q, $v) => $q->where('method', $v))
            ->orderByDesc('paid_at')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data): Payment
    {
        if (!isset($data['paid_at'])) {
            $data['paid_at'] = now();
        }
        return Payment::create($data);
    }
}

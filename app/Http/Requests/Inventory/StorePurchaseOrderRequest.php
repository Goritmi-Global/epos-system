<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'      => ['required', 'exists:suppliers,id'],
            'purchase_date'    => ['required', 'date'], // match DB column
            'status'           => ['nullable', 'string', 'in:pending,completed,cancelled'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity'  => ['required', 'numeric', 'min:1'], // match your service array
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.expiry'    => ['nullable', 'date'],
        ];
    }
}

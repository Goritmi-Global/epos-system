<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['sometimes', 'exists:suppliers,id'],
            'order_date'  => ['sometimes', 'date'],
            'status'      => ['sometimes', 'string', 'in:pending,completed,cancelled'],
            'items'       => ['sometimes', 'array'],
            'items.*.product_id' => ['required_with:items', 'exists:inventory_items,id'],
            'items.*.qty'        => ['required_with:items', 'numeric', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.expiry'     => ['required', 'date'], 
        ];
    }

    
    public function messages(): array
    {
        return [
            'items.*.expiry.required'         => 'Expiry date is required',  
            'items.*.expiry.date'             => 'Expiry must be a valid date',
        ];
    }
}

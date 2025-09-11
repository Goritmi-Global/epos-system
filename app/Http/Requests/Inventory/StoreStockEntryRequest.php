<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust permissions if needed
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:inventory_items,id',
            'name' => 'required|string',
            'category_id' => 'required|exists:inventory_categories,id',
            'supplier_id' => [
            Rule::requiredIf(fn () => request('stock_type') === 'stockin'),
            'nullable',
            'exists:suppliers,id',
        ],
            'available_quantity' => 'required|integer|min:0',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'value' => 'required|numeric|min:0',
            'operation_type' => 'required|string',
            'stock_type' => 'required|string',
            'expiry_date' => 'nullable|date',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'user_id' => 'required|exists:users,id',
        ];
    }
}

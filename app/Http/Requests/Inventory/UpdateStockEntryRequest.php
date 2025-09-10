<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust with policies if needed
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
            'unitPrice' => 'required|numeric|min:0',
            'expiryDate' => 'nullable|date',
            'operationType' => 'required|string|in:add,remove,adjust',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a number.',
            'quantity.min' => 'Quantity must be at least 1.',

            'unitPrice.required' => 'Unit price is required.',
            'unitPrice.numeric' => 'Unit price must be a valid number.',
            'unitPrice.min' => 'Unit price cannot be negative.',

            'operationType.required' => 'Operation type is required.',
            'operationType.in' => 'Operation type must be add, remove, or adjust.',
        ];
    }
}

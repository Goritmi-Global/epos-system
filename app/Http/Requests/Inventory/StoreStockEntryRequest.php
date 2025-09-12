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
    $isStockOut = strtolower((string) $this->input('stock_type')) === 'stockout';

    return [
        'product_id'        => 'required|exists:inventory_items,id',
        'name'              => 'required|string',
        'category_id'       => 'required|exists:inventory_categories,id',

        'supplier_id' => [
            \Illuminate\Validation\Rule::requiredIf(fn () => !$isStockOut),
            'nullable',
            'exists:suppliers,id',
        ],

        'available_quantity' => 'required|integer|min:0',
        'quantity'           => 'required|integer|min:1',

        //  For stockout → nullable only. For stockin → required|numeric|min:1
        'price' => $isStockOut
            ? ['nullable']                         // nothing else enforced
            : ['required','numeric','min:1'],

        'value'          => 'required|numeric|min:0',
        'operation_type' => 'required|string',
        'stock_type'     => 'required|string',
        'expiry_date'    => 'nullable|date',
        'description'    => 'nullable|string',
        'purchase_date'  => 'nullable|date',
        'user_id'        => 'required|exists:users,id',
    ];
}


protected function prepareForValidation(): void
{
    // Trim & normalize the flag
    $stockType = strtolower(trim((string) $this->input('stock_type')));

    // For stockout, force price to null so it won't be validated
    if ($stockType === 'stockout') {
        $this->merge(['stock_type' => 'stockout', 'price' => null]);
    } else {
        $this->merge(['stock_type' => $stockType]);
    }
}


}

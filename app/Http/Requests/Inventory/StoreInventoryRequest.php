<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'sku'   => ['required','string','max:50','unique:inventories,sku'],
            'name'  => ['required','string','max:255'],
            'price' => ['required','numeric','min:0'],
            'cost'  => ['nullable','numeric','min:0'],
            'stock' => ['required','integer','min:0'],
        ];
    }
}
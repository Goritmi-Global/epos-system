<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('inventory');
        return [
            'sku'   => ['required','string','max:50', Rule::unique('inventories','sku')->ignore($id)],
            'name'  => ['required','string','max:255'],
            'price' => ['required','numeric','min:0'],
            'cost'  => ['nullable','numeric','min:0'],
            'stock' => ['required','integer','min:0'],
        ];
    }
}

<?php
namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('supplier')?->id ?? $this->route('supplier'); // works with model binding or numeric id
        return [
            'name'            => 'required|string|max:100',
            'email'           => [
                'required','email','max:100',
                Rule::unique('suppliers','email')->ignore($id),
            ],
            'contact'         => 'nullable|string|max:100',
            'address'         => 'nullable|string',
            'preferred_items' => 'nullable|string',
        ];
    }
}

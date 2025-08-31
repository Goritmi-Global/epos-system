<?php
namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;

class SupplierStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:100',
            'email'           => 'required|email|max:100|unique:suppliers,email',
            'contact'         => 'nullable|string|max:100',
            'address'         => 'nullable|string',
            'preferred_items' => 'nullable|string',
        ];
    }
}

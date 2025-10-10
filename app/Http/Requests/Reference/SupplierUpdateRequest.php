<?php
namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // $id = $this->route('supplier')?->id ?? $this->route('supplier'); // works with model binding or numeric id
        $id = $this->input('id');
        return [
            'id'              => ['required','integer','exists:suppliers,id'],
            'name'            => ['required','string','max:100'],
            'email'           => ['required','email','max:100', Rule::unique('suppliers','email')->ignore($id)],
            'contact'         => ['required','numeric','max:20', Rule::unique('suppliers','contact')->ignore($id)],
            'address'         => ['required','string'],
            'preferred_items' => ['nullable','string'],
        ];
    }
}

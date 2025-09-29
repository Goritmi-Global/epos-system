<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;

class SupplierStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:100',
            'email'           => 'required|email|max:100|unique:suppliers,email',
            'contact' => 'required|string|max:20|unique:suppliers,contact',
            'address'         => 'required|string|max:255',
            'preferred_items' => 'nullable|string',
        ];
    }

     public function messages(): array
    {
        return [
            'name.required'     => 'Supplier name is required.',
            'name.max'          => 'Supplier name may not be greater than 100 characters.',
            
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please provide a valid email address.',
            'email.unique'      => 'This email address is already registered.',
            'email.max'         => 'Email address may not be greater than 100 characters.',
            
            'contact.unique'    => 'This contact number is already registered.',
            
            'address.max'       => 'Address may not be greater than 255 characters.',
        ];
    }
}

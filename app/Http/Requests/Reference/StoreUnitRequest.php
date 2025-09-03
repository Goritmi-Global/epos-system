<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:units,name',
            'units' => 'required|array|min:1', 
            'units.*' => 'string|max:100'     
        ];
    }

    public function messages(): array
    {
        return [
            'units.required' => 'Please select at least one unit.',
            'units.min' => 'Please select at least one unit.',
        ];
    }
}

